<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Product;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ErpTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function client(array $extra = []): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente ERP', 'correo' => Str::uuid().'@example.test', 'estado' => 'activo', 'etapa_crm' => 'Prospecto', ...$extra]);
    }

    private function product(array $extra = []): Product
    {
        return Product::create(['name' => 'Prenda ERP', 'category' => 'Hoodie', 'description' => 'Prenda para integración ERP', 'image' => 'https://example.test/p.jpg',
            'price_cents' => 10090, 'stock' => 10, 'active' => true, 'stock_minimo' => 2, 'stock_objetivo' => 12, 'costo_unitario_cents' => 4000, 'estrategia_logistica' => 'PULL', ...$extra]);
    }

    private function payload(Cliente $c, Product $p, int $quantity = 2): array
    {
        return ['cliente_id' => $c->id, 'request_key' => (string) Str::uuid(),
            'productos' => [['producto_id' => $p->id, 'cantidad' => $quantity, 'precio_unitario_cents' => $p->price_cents]]];
    }

    public function test_draft_uses_catalog_prices_without_reducing_or_reserving_stock(): void
    {
        $this->actingAs($this->staff('ventas'));
        $c = $this->client();
        $p = $this->product();
        $d = [...$this->payload($c, $p), 'total' => .01, 'usuario_id' => 999, 'estado' => 'completada'];
        $r = $this->postJson('/ordenes', $d)->assertCreated()->assertJsonPath('estado', 'pendiente')->assertJsonPath('total', 201.8);
        $r->assertJsonPath('cliente.id', $c->id)->assertJsonCount(1, 'productos')->assertJsonCount(1, 'historial')->assertJsonMissingPath('payload_hash');
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertDatabaseCount('procesos_internos', 0);
    }

    public function test_process_integrates_crm_scm_erp_and_replay_does_not_duplicate_anything(): void
    {
        $user = $this->staff('ventas');
        $this->actingAs($user);
        $c = $this->client();
        $p = $this->product();
        $d = $this->payload($c, $p);
        $id = $this->postJson('/ordenes/procesar', $d)->assertCreated()->assertJsonPath('estado', 'procesada')->assertJsonPath('total', 201.8)->assertJsonCount(1, 'movimientos')->assertJsonCount(1, 'procesos')->json('id');
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertSame('Activo', $c->fresh()->etapa_crm);
        $this->assertDatabaseHas('movimientos_inventario', ['orden_empresarial_id' => $id, 'cantidad' => 2, 'tipo' => 'salida', 'usuario_id' => $user->id]);
        $this->postJson('/ordenes/procesar', $d)->assertCreated()->assertJsonPath('id', $id);
        $this->postJson('/ordenes/procesar', ['orden_id' => $id])->assertOk()->assertJsonPath('id', $id);
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'procesada'])->assertOk();
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('procesos_internos', 1);
        $this->assertDatabaseCount('orden_historial', 2);
        $this->assertDatabaseCount('movimientos_inventario', 2);
    }

    public function test_failed_multi_product_order_rolls_back_all_tables_and_stock(): void
    {
        $this->actingAs($this->staff());
        $c = $this->client();
        $p = $this->product();
        $empty = $this->product(['stock' => 0]);
        $d = $this->payload($c, $p);
        $d['productos'][] = ['producto_id' => $empty->id, 'cantidad' => 1];
        $this->postJson('/ordenes/procesar', $d)->assertUnprocessable();
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('orden_productos', 0);
        $this->assertDatabaseCount('orden_historial', 0);
        $this->assertDatabaseCount('procesos_internos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertSame('Prospecto', $c->fresh()->etapa_crm);
    }

    public function test_pending_order_rechecks_live_stock_and_keeps_price_snapshot(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product();
        $d = $this->payload($this->client(), $p, 10);
        $id = $this->postJson('/ordenes', $d)->assertCreated()->json('id');
        $p->update(['stock' => 9, 'price_cents' => 20000]);
        $this->postJson('/ordenes/procesar', ['orden_id' => $id])->assertUnprocessable();
        $this->assertDatabaseHas('ordenes_empresariales', ['id' => $id, 'estado' => 'pendiente', 'total_cents' => 100900]);
        $p->update(['stock' => 10]);
        $this->postJson('/ordenes/procesar', ['orden_id' => $id])->assertOk()->assertJsonPath('total', 1009);
        $this->assertSame(0, $p->fresh()->stock);
    }

    public function test_logistics_confirms_delivery_without_a_second_stock_reduction(): void
    {
        $this->actingAs($this->staff('ventas'));
        $p = $this->product();
        $id = $this->postJson('/ordenes/procesar', $this->payload($this->client(), $p))->json('id');
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'completada'])->assertForbidden();
        $this->actingAs($this->staff('logistica'));
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'completada'])->assertOk()->assertJsonPath('estado', 'completada');
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'completada'])->assertOk();
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertDatabaseCount('orden_historial', 3);
    }

    public function test_cancellation_restores_stock_once_and_removes_sale_from_both_dashboards(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product();
        $id = $this->postJson('/ordenes/procesar', $this->payload($this->client(), $p))->json('id');
        $this->getJson('/erp/metricas')->assertJsonPath('ventas_totales', 201.8)->assertJsonPath('productos_vendidos', 2);
        $this->getJson('/scm/reportes')->assertJsonPath('productos_mas_vendidos.0.unidades', 2);
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada', 'motivo' => 'Prueba de devolución'])->assertOk()->assertJsonPath('estado', 'cancelada');
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertOk();
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 3);
        $this->getJson('/erp/metricas')->assertJsonPath('ventas_totales', 0)->assertJsonPath('productos_vendidos', 0)->assertJsonPath('ordenes_canceladas', 1);
        $this->getJson('/scm/reportes')->assertJsonCount(0, 'productos_mas_vendidos');
        $this->postJson('/ordenes/procesar', ['orden_id' => $id])->assertConflict();
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'completada'])->assertConflict();
    }

    public function test_sales_can_cancel_pending_but_not_processed_orders(): void
    {
        $this->actingAs($this->staff('ventas'));
        $p = $this->product();
        $c = $this->client();
        $draft = $this->postJson('/ordenes', $this->payload($c, $p))->json('id');
        $this->putJson('/ordenes/'.$draft.'/estado', ['estado' => 'cancelada'])->assertOk();
        $done = $this->postJson('/ordenes/procesar', $this->payload($c, $p))->json('id');
        $this->putJson('/ordenes/'.$done.'/estado', ['estado' => 'cancelada'])->assertForbidden();
        $this->assertSame(8, $p->fresh()->stock);
    }

    public function test_erp_sale_triggers_existing_push_rule_and_pull_does_not(): void
    {
        $this->actingAs($this->staff());
        $supplier = Proveedor::create(['nombre' => 'Proveedor', 'contacto' => 'Contacto', 'correo' => 'scm@example.test', 'telefono' => '4491234567']);
        $push = $this->product(['stock' => 3, 'estrategia_logistica' => 'PUSH', 'proveedor_id' => $supplier->id]);
        $pull = $this->product(['stock' => 3]);
        $d = $this->payload($this->client(), $push);
        $d['productos'][] = ['producto_id' => $pull->id, 'cantidad' => 2];
        $this->postJson('/ordenes/procesar', $d)->assertCreated();
        $this->assertDatabaseHas('pedidos_scm', ['producto_id' => $push->id, 'tipo' => 'reposicion', 'cantidad' => 11, 'automatico' => true]);
        $this->assertDatabaseMissing('pedidos_scm', ['producto_id' => $pull->id]);
        $this->postJson('/ordenes/procesar', $d)->assertCreated();
        $this->assertDatabaseCount('pedidos_scm', 1);
    }

    public function test_validation_rejects_tampered_price_duplicate_products_and_inactive_customer(): void
    {
        $this->actingAs($this->staff());
        $c = $this->client();
        $p = $this->product();
        $d = $this->payload($c, $p);
        $d['productos'][0]['precio_unitario_cents'] = 1;
        $this->postJson('/ordenes/procesar', $d)->assertUnprocessable();
        $d = $this->payload($c, $p);
        $d['productos'][] = $d['productos'][0];
        $this->postJson('/ordenes/procesar', $d)->assertUnprocessable();
        $c->update(['estado' => 'inactivo']);
        $this->postJson('/ordenes/procesar', $this->payload($c, $p))->assertUnprocessable();
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertSame(10, $p->fresh()->stock);
    }

    public function test_reused_key_with_different_order_is_rejected(): void
    {
        $this->actingAs($this->staff());
        $d = $this->payload($this->client(), $this->product());
        $this->postJson('/ordenes/procesar', $d)->assertCreated();
        $d['productos'][0]['cantidad'] = 3;
        $this->postJson('/ordenes/procesar', $d)->assertConflict();
        $this->assertDatabaseCount('ordenes_empresariales', 1);
    }

    public function test_existing_customer_cannot_be_deleted_and_pending_product_cannot_be_archived(): void
    {
        $this->actingAs($this->staff());
        $c = $this->client();
        $p = $this->product(['stock' => 0]);
        $id = $this->postJson('/ordenes', $this->payload($c, $p))->assertCreated()->json('id');
        $this->deleteJson('/clientes/'.$c->id)->assertConflict();
        $this->deleteJson('/productos/'.$p->id)->assertConflict();
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertOk();
        $this->deleteJson('/productos/'.$p->id)->assertNoContent();
        $this->getJson('/ordenes/'.$id)->assertOk()->assertJsonPath('productos.0.nombre', 'Prenda ERP');
    }

    public function test_search_pagination_and_detail_expose_history(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product();
        $c = $this->client(['nombre' => 'Cliente buscable']);
        for ($n = 0; $n < 16; $n++) {
            $this->postJson('/ordenes', $this->payload($c, $p))->assertCreated();
        }
        $this->getJson('/ordenes?q=buscable&page=2&estado=pendiente')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('total', 16);
        $this->getJson('/ordenes?q=inexistente')->assertJsonCount(0, 'data');
        $this->getJson('/ordenes/99999')->assertNotFound();
    }

    public function test_archived_product_can_be_restored_before_cancelling_a_fulfilled_sale(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product();
        $id = $this->postJson('/ordenes/procesar', $this->payload($this->client(), $p, 10))->assertCreated()->json('id');
        $this->deleteJson('/productos/'.$p->id)->assertNoContent();
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertConflict();
        $this->putJson('/productos/'.$p->id.'/restaurar')->assertOk();
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertOk();
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertNull($p->fresh()->deleted_at);
    }

    public function test_maturity_is_persistent_and_requires_real_operational_evidence(): void
    {
        $this->actingAs($this->staff());
        $all = array_fill_keys(['clientes', 'inventario', 'ordenes', 'movimientos', 'reportes', 'procesos'], true);
        $this->putJson('/erp/estado', ['nivel' => 'Optimizado', 'checklist' => $all])->assertUnprocessable();
        $this->postJson('/ordenes/procesar', $this->payload($this->client(), $this->product()))->assertCreated();
        $this->postJson('/erp/reportes')->assertCreated();
        $this->putJson('/erp/estado', ['nivel' => 'Optimizado', 'checklist' => $all])->assertOk()->assertJsonPath('progreso', 100);
        $this->getJson('/erp/estado')->assertJsonPath('nivel', 'Optimizado')->assertJsonPath('checklist.movimientos', true);
        $this->assertDatabaseHas('estado_erp', ['id' => 1, 'nivel' => 'Optimizado']);
    }

    public function test_report_snapshot_keeps_original_figures_after_cancellation_and_csv_is_safe(): void
    {
        $this->actingAs($this->staff());
        $p = $this->product(['name' => '=HYPERLINK("test")']);
        $id = $this->postJson('/ordenes/procesar', $this->payload($this->client(), $p))->json('id');
        $report = $this->postJson('/erp/reportes')->assertCreated()->assertJsonPath('resumen.ventas_totales', 201.8)->json('id');
        $this->putJson('/ordenes/'.$id.'/estado', ['estado' => 'cancelada'])->assertOk();
        $this->getJson('/erp/reportes')->assertJsonPath('data.0.resumen.ventas_totales', 201.8);
        $csv = $this->get('/erp/reportes/'.$report.'/csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('201.8', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->get('/erp/reportes/csv')->assertOk();
    }

    public function test_resources_are_managed_by_logistics_and_readable_by_sales(): void
    {
        $owner = $this->staff('logistica');
        $this->actingAs($owner);
        $d = ['nombre' => 'Estación de empaque', 'tipo' => 'fisico', 'descripcion' => 'Mesa y herramientas', 'disponible' => true, 'responsable_id' => $owner->id];
        $id = $this->postJson('/erp/recursos', $d)->assertCreated()->json('id');
        $this->putJson('/erp/recursos/'.$id, [...$d, 'disponible' => false])->assertOk()->assertJsonPath('disponible', false);
        $this->getJson('/erp/responsables')->assertOk();
        $this->actingAs($this->staff('ventas'));
        $this->getJson('/erp/recursos')->assertOk()->assertJsonPath('0.nombre', $d['nombre']);
        $this->postJson('/erp/recursos', $d)->assertForbidden();
    }

    public function test_role_matrix_protects_pages_endpoints_and_dynamic_navigation(): void
    {
        $this->getJson('/erp')->assertUnauthorized();
        $this->actingAs($this->staff('cliente'))->getJson('/erp/metricas')->assertForbidden();
        $this->actingAs($this->staff('ventas'));
        $this->get('/erp')->assertOk()->assertSee('Nueva orden')->assertDontSee('id="new-resource"', false)->assertDontSee('id="users"', false);
        $this->get('/crm')->assertOk();
        $this->postJson('/inventario/movimiento', [])->assertForbidden();
        $this->postJson('/productos', [])->assertForbidden();
        $this->putJson('/erp/estado', [])->assertForbidden();
        $this->actingAs($this->staff('logistica'));
        $this->get('/erp')->assertOk()->assertDontSee('id="confirm-order"', false)->assertSee('Recursos internos');
        $this->getJson('/crm')->assertForbidden();
        $this->postJson('/ordenes/procesar', [])->assertForbidden();
        $this->postJson('/proveedores', ['nombre' => 'Proveedor logística', 'contacto' => 'Contacto', 'correo' => 'log@example.test', 'telefono' => '4491234567'])->assertCreated();
        $this->putJson('/usuarios/rol', [])->assertForbidden();
    }

    public function test_admin_assigns_roles_and_cannot_remove_own_admin_access(): void
    {
        $admin = $this->staff();
        $other = $this->staff('usuario');
        $this->actingAs($admin);
        $this->putJson('/usuarios/rol', ['usuario_id' => $other->id, 'role' => 'ventas'])->assertOk()->assertJsonPath('role', 'ventas');
        $this->assertSame('ventas', $other->fresh()->role);
        $this->putJson('/usuarios/rol', ['usuario_id' => $admin->id, 'role' => 'ventas'])->assertConflict();
        $this->putJson('/usuarios/rol', ['usuario_id' => $other->id, 'role' => 'root'])->assertUnprocessable();
        $this->postJson('/usuarios', ['name' => 'Logística nueva', 'email' => 'newlog@example.test', 'password' => 'LongPassword123!', 'role' => 'logistica'])->assertCreated();
        $this->actingAs($other->fresh());
        $this->putJson('/usuarios/rol', ['usuario_id' => $admin->id, 'role' => 'cliente'])->assertForbidden();
    }

    public function test_auth_login_alias_returns_home_and_permissions_without_privilege_escalation(): void
    {
        $u = $this->staff('ventas');
        $u->password = 'PasswordTest123!';
        $u->save();
        $this->postJson('/auth/login', ['email' => $u->email, 'password' => 'PasswordTest123!', 'role' => 'admin'])->assertOk()->assertJsonPath('user.role', 'ventas')->assertJsonPath('user.home', '/erp')->assertJsonPath('user.permissions', fn ($p) => in_array('crm', $p) && in_array('erp.write', $p));
        $this->postJson('/logout')->assertOk();
        $this->postJson('/registro', ['name' => 'Cliente público', 'email' => 'public@example.test', 'password' => 'PasswordTest123!', 'role' => 'admin'])->assertCreated()->assertJsonPath('user.role', 'cliente')->assertJsonCount(0, 'user.permissions');
    }

    public function test_demo_command_is_additive_and_idempotent(): void
    {
        $p = $this->product();
        $this->artisan('hf:demo-u3')->expectsQuestion('Contraseña para las nuevas cuentas demo (mínimo 12 caracteres)', 'DemoPassword123!')->assertExitCode(0);
        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('products', 3);
        $this->assertSame(10, $p->fresh()->stock);
        $this->artisan('hf:demo-u3')->assertExitCode(0);
        $this->assertDatabaseCount('ordenes_empresariales', 1);
        $this->assertDatabaseCount('products', 3);
        $this->artisan('hf:diagnostico')->assertExitCode(0);
    }
}
