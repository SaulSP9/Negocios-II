<?php

namespace Tests\Feature;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Product;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScmTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function supplier(): Proveedor
    {
        return Proveedor::create(['nombre' => 'Textiles del Centro', 'contacto' => 'Ana Pérez', 'correo' => (string) Str::uuid().'@example.com', 'telefono' => '+52 449 123 4567']);
    }

    private function product(array $extra = []): Product
    {
        return Product::create(['name' => 'Prenda SCM', 'category' => 'Hoodie', 'description' => 'Prenda para probar logística', 'image' => 'https://example.com/p.jpg', 'price_cents' => 10000, 'stock' => 5, 'active' => true, 'stock_minimo' => 2, 'stock_objetivo' => 10, 'costo_unitario_cents' => 4000, 'estrategia_logistica' => 'PULL', ...$extra]);
    }

    private function payload(array $extra = []): array
    {
        return ['nombre' => 'Prenda nueva', 'descripcion' => 'Prenda del catálogo SCM', 'categoria' => 'Hoodie', 'stock_actual' => 5, 'stock_minimo' => 2, 'stock_objetivo' => 10, 'proveedor_id' => null, 'costo_unitario' => 40.50, 'precio_venta' => 100.90, 'estrategia_logistica' => 'PULL', 'imagen' => 'https://example.com/p.jpg', 'color' => 'Negro', 'activo' => true, 'ubicacion' => 'Estante A', ...$extra];
    }

    private function move(Product $p, array $extra = []): array
    {
        return ['producto_id' => $p->id, 'tipo' => 'salida', 'cantidad' => 2, 'motivo' => 'venta', 'request_key' => (string) Str::uuid(), ...$extra];
    }

    public function test_routes_protect_staff_actions_and_admin_configuration(): void
    {
        $p = $this->product();
        $this->getJson('/productos')->assertUnauthorized();
        $this->actingAs($this->staff('cliente'))->getJson('/scm/reportes')->assertForbidden();
        $this->actingAs($this->staff('usuario'));
        $this->get('/scm')->assertOk()->assertSee('Pedidos SCM');
        $this->getJson('/productos')->assertOk();
        $this->postJson('/productos', $this->payload())->assertForbidden();
        $this->putJson('/productos/'.$p->id.'/estrategia', ['estrategia_logistica' => 'PUSH'])->assertForbidden();
        $this->postJson('/proveedores', [])->assertForbidden();
        $this->putJson('/scm/nivel', [])->assertForbidden();
        $this->postJson('/inventario/movimiento', $this->move($p))->assertCreated();
    }

    public function test_supplier_and_product_crud_preserve_stock_and_catalog(): void
    {
        $this->actingAs($this->staff());
        $s = ['nombre' => 'Proveedor Uno', 'contacto' => 'María López', 'correo' => 'Textiles@Example.com', 'telefono' => '+52 449 123 4567'];
        $id = $this->postJson('/proveedores', $s)->assertCreated()->assertJsonPath('correo', 'textiles@example.com')->json('id');
        $this->postJson('/proveedores', $s)->assertUnprocessable();
        $this->getJson('/proveedores')->assertJsonCount(1);
        $this->putJson('/proveedores/'.$id, [...$s, 'nombre' => 'Proveedor editado'])->assertOk();
        $body = $this->payload(['proveedor_id' => $id]);
        $p = $this->postJson('/productos', $body)->assertCreated()->assertJsonPath('costo_unitario', 40.5)->assertJsonPath('ubicacion', 'Estante A')->json('id');
        $this->getJson('/tienda/productos')->assertJsonPath('0.stock', 5)->assertJsonPath('0.price', 100.9);
        unset($body['stock_actual']);
        $body['nombre'] = 'Prenda editada';
        $this->putJson('/productos/'.$p, $body)->assertOk()->assertJsonPath('nombre', 'Prenda editada')->assertJsonPath('stock_actual', 5);
        $this->assertDatabaseCount('inventarios', 1);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->deleteJson('/proveedores/'.$id)->assertConflict();
        $this->deleteJson('/productos/'.$p)->assertConflict();
    }

    public function test_invalid_product_settings_are_rejected(): void
    {
        $this->actingAs($this->staff());
        $this->postJson('/productos', $this->payload(['stock_objetivo' => 2]))->assertUnprocessable();
        $this->postJson('/productos', $this->payload(['estrategia_logistica' => 'PUSH']))->assertUnprocessable();
        $this->postJson('/productos', $this->payload(['costo_unitario' => 1.234]))->assertUnprocessable();
        $p = $this->product();
        $this->putJson('/productos/'.$p->id, $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('products', 1);
    }

    public function test_movement_is_atomic_audited_and_idempotent(): void
    {
        $p = $this->product();
        $u = $this->staff('usuario');
        $this->actingAs($u);
        $d = $this->move($p);
        $id = $this->postJson('/inventario/movimiento', $d)->assertCreated()->assertJsonPath('stock_anterior', 5)->assertJsonPath('stock_resultante', 3)->assertJsonPath('usuario_id', $u->id)->json('id');
        $this->postJson('/inventario/movimiento', $d)->assertCreated()->assertJsonPath('id', $id);
        $this->postJson('/inventario/movimiento', [...$d, 'cantidad' => 3])->assertConflict();
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 4]))->assertUnprocessable();
        $this->postJson('/inventario/movimiento', $this->move($p, ['tipo' => 'entrada']))->assertUnprocessable();
        $this->assertSame(3, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->getJson('/productos/'.$p->id.'/movimientos')->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.usuario.name', $u->name);
    }

    public function test_push_orders_cover_target_without_duplicates_and_stock_changes_on_receipt(): void
    {
        $p = $this->product(['proveedor_id' => $this->supplier()->id, 'estrategia_logistica' => 'PUSH']);
        $this->actingAs($this->staff());
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 3]))->assertCreated();
        $order = Pedido::sole();
        $this->assertTrue($order->automatico);
        $this->assertSame(8, $order->cantidad);
        $this->assertSame(2, $p->fresh()->stock);
        app(InventoryService::class)->checkPush($p->id);
        $this->assertDatabaseCount('pedidos_scm', 1);
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 1]))->assertCreated();
        $this->assertSame(9, $order->fresh()->cantidad);
        $this->assertDatabaseCount('pedidos_scm', 1);
        $this->putJson('/pedidos/'.$order->id.'/estado', ['estado' => 'surtido'])->assertOk()->assertJsonPath('estado', 'surtido');
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['pedido_id' => $order->id, 'motivo' => 'reposición', 'cantidad' => 9]);
        $count = MovimientoInventario::count();
        $this->putJson('/pedidos/'.$order->id.'/estado', ['estado' => 'surtido'])->assertOk();
        $this->assertSame($count, MovimientoInventario::count());
        $this->assertSame(10, $p->fresh()->stock);
        $this->putJson('/pedidos/'.$order->id.'/estado', ['estado' => 'pendiente'])->assertUnprocessable();
    }

    public function test_pull_uses_manual_orders_and_push_respects_existing_pending_supply(): void
    {
        $p = $this->product(['stock' => 2, 'proveedor_id' => $this->supplier()->id]);
        $this->actingAs($this->staff());
        app(InventoryService::class)->checkPush($p->id);
        $this->assertDatabaseCount('pedidos_scm', 0);
        $d = ['producto_id' => $p->id, 'tipo' => 'reposicion', 'cantidad' => 4, 'request_key' => (string) Str::uuid()];
        $id = $this->postJson('/pedidos', $d)->assertCreated()->assertJsonPath('automatico', false)->json('id');
        $this->postJson('/pedidos', $d)->assertCreated()->assertJsonPath('id', $id);
        $this->assertSame(2, $p->fresh()->stock);
        $this->putJson('/productos/'.$p->id.'/estrategia', ['estrategia_logistica' => 'PUSH'])->assertOk();
        $this->assertSame(4, Pedido::where('automatico', true)->sole()->cantidad);
        $this->getJson('/productos?estrategia=PUSH')->assertJsonPath('total', 1);
        $this->putJson('/productos/'.$p->id.'/estrategia', ['estrategia_logistica' => 'PULL'])->assertOk();
        $this->assertDatabaseCount('pedidos_scm', 2); // Las solicitudes existentes conservan trazabilidad.
    }

    public function test_sale_order_fulfillment_rolls_back_when_stock_is_insufficient(): void
    {
        $p = $this->product(['stock' => 1]);
        $this->actingAs($this->staff('usuario'));
        $id = $this->postJson('/pedidos', ['producto_id' => $p->id, 'tipo' => 'venta', 'cantidad' => 2])->assertCreated()->json('id');
        $this->putJson('/pedidos/'.$id.'/estado', ['estado' => 'surtido'])->assertUnprocessable();
        $this->assertDatabaseHas('pedidos_scm', ['id' => $id, 'estado' => 'pendiente', 'fecha_surtido' => null]);
        $this->assertSame(1, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->postJson('/inventario/movimiento', $this->move($p, ['tipo' => 'entrada', 'motivo' => 'ajuste', 'cantidad' => 1]))->assertCreated();
        $this->putJson('/pedidos/'.$id.'/estado', ['estado' => 'surtido'])->assertOk();
        $this->assertSame(0, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['pedido_id' => $id, 'tipo' => 'salida', 'motivo' => 'venta']);
    }

    public function test_direct_replenishment_creates_manual_completed_order_once_and_blocks_duplicate_pending_supply(): void
    {
        $p = $this->product(['proveedor_id' => $this->supplier()->id]);
        $this->actingAs($this->staff());
        $d = $this->move($p, ['tipo' => 'entrada', 'motivo' => 'reposición', 'cantidad' => 3]);
        $id = $this->postJson('/inventario/movimiento', $d)->assertCreated()->json('id');
        $this->postJson('/inventario/movimiento', $d)->assertCreated()->assertJsonPath('id', $id);
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertDatabaseCount('pedidos_scm', 1);
        $this->assertSame('surtido', Pedido::sole()->estado);
        $this->postJson('/pedidos', ['producto_id' => $p->id, 'tipo' => 'reposicion', 'cantidad' => 2])->assertCreated();
        $this->postJson('/inventario/movimiento', $this->move($p, ['tipo' => 'entrada', 'motivo' => 'reposición']))->assertUnprocessable();
        $this->assertSame(8, $p->fresh()->stock);
    }

    public function test_store_checkout_cancel_and_legacy_stock_edit_have_ledger_entries(): void
    {
        $p = $this->product(['proveedor_id' => $this->supplier()->id, 'estrategia_logistica' => 'PUSH']);
        $this->actingAs($this->staff('cliente'));
        $d = ['items' => [['id' => $p->id, 'quantity' => 3]], 'address' => 'Calle Uno 123', 'city' => 'Aguascalientes', 'zip' => '20000', 'checkout_key' => (string) Str::uuid()];
        $id = $this->postJson('/tienda/pedidos', $d)->assertCreated()->json('database_id');
        $this->assertSame(2, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['order_id' => $id, 'tipo' => 'salida', 'cantidad' => 3]);
        $this->assertDatabaseCount('pedidos_scm', 1);
        $this->actingAs($this->staff());
        $this->putJson('/tienda/pedidos/'.$id.'/estado', ['status' => 'Cancelado'])->assertOk();
        $this->assertSame(5, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['order_id' => $id, 'tipo' => 'entrada', 'motivo' => 'ajuste']);
        $this->putJson('/tienda/productos/'.$p->id, ['name' => 'Prenda editada', 'category' => 'Hoodie', 'price' => 100, 'stock' => 6, 'image' => 'https://example.com/i.jpg', 'description' => 'Nueva descripción', 'active' => true])->assertOk();
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $p->id, 'referencia' => 'Ajuste desde administración de catálogo', 'stock_resultante' => 6]);
    }

    public function test_reports_count_only_completed_sales_and_export_csv(): void
    {
        $p = $this->product(['stock' => 10]);
        $slow = $this->product(['name' => 'Rotación lenta']);
        $critical = $this->product(['name' => 'Crítico', 'stock' => 1]);
        $this->actingAs($this->staff('cliente'));
        $ids = [];
        foreach ([2, 2, 2] as $n) {
            $ids[] = $this->postJson('/tienda/pedidos', ['items' => [['id' => $p->id, 'quantity' => $n]], 'address' => 'Calle Uno 123', 'city' => 'Aguascalientes', 'zip' => '20000', 'checkout_key' => (string) Str::uuid()])->assertCreated()->json('database_id');
        }
        $this->actingAs($this->staff());
        $this->putJson('/tienda/pedidos/'.$ids[1].'/estado', ['status' => 'Enviado'])->assertOk();
        $this->putJson('/tienda/pedidos/'.$ids[2].'/estado', ['status' => 'Cancelado'])->assertOk();
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 1]))->assertCreated();
        $this->getJson('/scm/reportes')->assertOk()->assertJsonPath('productos_mas_vendidos.0.unidades', 3)->assertJsonPath('total_productos', 3)->assertJsonPath('inventario_critico.0.id', $critical->id)->assertJsonCount(2, 'rotacion_lenta')->assertJsonPath('comparacion.1.productos', 3);
        $csv = $this->get('/scm/reportes/csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Más vendidos', $csv);
        $this->assertStringContainsString('Rotación lenta', $csv);
    }

    public function test_archiving_retains_ledger_and_does_not_sell_archived_products(): void
    {
        $p = $this->product();
        $this->actingAs($this->staff());
        $this->postJson('/inventario/movimiento', $this->move($p, ['cantidad' => 5, 'motivo' => 'ajuste']))->assertCreated();
        $this->deleteJson('/productos/'.$p->id)->assertNoContent();
        $this->assertSoftDeleted('products', ['id' => $p->id]);
        $this->getJson('/productos')->assertJsonPath('total', 0);
        $this->getJson('/productos?archivados=1')->assertJsonPath('data.0.id', $p->id);
        $this->getJson('/productos/'.$p->id.'/movimientos')->assertJsonPath('total', 2);
        $this->getJson('/tienda/productos')->assertExactJson([]);
        $this->postJson('/inventario/movimiento', $this->move($p, ['tipo' => 'entrada', 'motivo' => 'ajuste']))->assertNotFound();
    }

    public function test_maturity_requires_evidence_and_persists(): void
    {
        $this->actingAs($this->staff());
        $check = ['catalogo' => true, 'proveedores' => true, 'inventario' => true, 'estrategias' => false, 'pedidos' => false, 'reportes' => false];
        $this->getJson('/scm/estado')->assertJsonPath('nivel_scm', 'Inicial');
        $this->putJson('/scm/nivel', ['nivel_scm' => 'Optimizado', 'checklist' => $check])->assertUnprocessable();
        $this->putJson('/scm/nivel', ['nivel_scm' => 'En desarrollo', 'checklist' => $check])->assertOk()->assertJsonPath('nivel_scm', 'En desarrollo');
        $this->putJson('/scm/nivel', ['nivel_scm' => 'Optimizado', 'checklist' => array_fill_keys(array_keys($check), true)])->assertOk();
        $this->getJson('/scm/estado')->assertJsonPath('nivel_scm', 'Optimizado')->assertJsonPath('checklist.reportes', true);
    }
}
