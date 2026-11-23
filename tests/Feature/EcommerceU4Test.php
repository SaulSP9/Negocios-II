<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Monedero;
use App\Models\Orden;
use App\Models\Product;
use App\Models\Promocion;
use App\Models\Proveedor;
use App\Models\User;
use App\Notifications\OrderPaid;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EcommerceU4Test extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_by_family_and_name_and_excludes_hidden_products(): void
    {
        $hoodie = $this->product();
        $this->product(['name' => 'Tee Blanca', 'category' => 'T-Shirt']);
        $hidden = $this->product(['name' => 'Hoodie Privado', 'active' => false]);
        $this->getJson('/catalogo?categoria=hoodies-outerwear&buscar=carb')->assertOk()
            ->assertJsonCount(1, 'productos')->assertJsonPath('productos.0.id', $hoodie->id);
        $this->getJson('/catalogo/'.$hoodie->id)->assertOk()
            ->assertJsonPath('tallas', ['XS', 'S', 'M', 'L', 'XL'])
            ->assertJsonPath('stock_actual', 5);
        $this->get('/catalogo/'.$hoodie->id)->assertOk()->assertSee('data-initial-product="'.$hoodie->id.'"', false);
        $this->getJson('/catalogo/'.$hidden->id)->assertNotFound();
    }

    public function test_cart_persists_sizes_and_rejects_other_customers_and_combined_overstock(): void
    {
        [$customer, $client] = $this->customer();
        $product = $this->product();
        $this->actingAs($customer);
        $line = $this->add($product, 2)->assertOk()->assertJsonPath('cantidad', 2)->json('items.0.id');
        $second = $this->add($product, 1, 'L')->assertOk()->assertJsonPath('cantidad', 3)->json('items.1.id');
        $this->putJson('/carrito/item/'.$line, ['cantidad' => 5])->assertUnprocessable();
        $this->putJson('/carrito/item/'.$line, ['cantidad' => 3])->assertOk()->assertJsonPath('cantidad', 4);
        $this->getJson('/carrito/'.$client->id)->assertJsonPath('items.1.talla', 'L');
        [$other] = $this->customer();
        $this->actingAs($other)->getJson('/carrito/'.$client->id)->assertNotFound();
        $this->deleteJson('/carrito/vaciar/'.$client->id)->assertNotFound();
        $this->deleteJson('/carrito/item/'.$line)->assertNotFound();
        $this->putJson('/carrito/item/'.$line, ['cantidad' => 1])->assertNotFound();
        $this->actingAs($customer)->deleteJson('/carrito/item/'.$second)->assertOk()->assertJsonPath('cantidad', 3);
        $this->deleteJson('/carrito/vaciar/'.$client->id)->assertOk()->assertJsonPath('total', 0);
        $this->assertDatabaseCount('carrito_detalle', 0);
    }

    public function test_commercial_pricing_uses_active_best_promotion_and_taxes_net_subtotal(): void
    {
        $product = $this->product();
        $this->promotion(['valor' => 10]);
        $this->promotion(['valor' => 20, 'categoria_id' => $product->categoria_id]);
        $this->promotion(['valor' => 99, 'fecha_fin' => now()->subSecond()]);
        $this->promotion(['valor' => 99, 'fecha_inicio' => now()->addHours(2), 'fecha_fin' => now()->addDay()]);
        $other = Categoria::where('slug', 'pantalones')->firstOrFail();
        $this->promotion(['valor' => 99, 'categoria_id' => $other->id]);
        $this->getJson('/promociones')->assertJsonCount(3);
        $this->getJson('/catalogo/'.$product->id)->assertJsonPath('precio', 8000)->assertJsonPath('precio_anterior', 10000);

        [$actor] = $this->customer();
        $this->actingAs($actor);
        $this->add($product, 2)->assertOk()->assertJsonPath('subtotal', 20000)
            ->assertJsonPath('descuento', 4000)->assertJsonPath('impuesto', 2560)->assertJsonPath('total', 18560);
        $this->postJson('/impuestos', ['nombre' => 'IEPS', 'porcentaje' => 5, 'estado' => true])->assertForbidden();
        $this->postJson('/promociones', [])->assertForbidden();

        $this->actingAs($this->staff('admin'));
        $this->putJson('/impuestos/1', ['nombre' => 'IVA', 'porcentaje' => 10, 'estado' => true])->assertOk();
        $this->actingAs($actor);
        $this->add($product, 1)->assertOk()->assertJsonPath('total', 26400);
    }

    public function test_checkout_links_payment_erp_and_push_restock_with_one_inventory_exit(): void
    {
        config(['commerce.simulate_payments' => true]);
        [$actor, $client] = $this->customer();
        $supplier = Proveedor::create(['nombre' => 'Proveedor', 'contacto' => 'Almacén', 'telefono' => '4490000001', 'correo' => 'abasto@example.test']);
        $product = $this->product(['proveedor_id' => $supplier->id, 'estrategia_logistica' => 'PUSH', 'stock_minimo' => 2, 'stock_objetivo' => 10]);
        $this->promotion(['valor' => 25]);
        $this->actingAs($actor);
        $this->add($product, 2, 'M')->assertOk();
        $this->add($product, 1, 'L')->assertOk();

        $payload = $this->payment(['total_esperado' => 26100]);
        $response = $this->postJson('/pagos/procesar', $payload)->assertCreated()
            ->assertJsonPath('pago.estado', 'Pagado')->assertJsonPath('status', 'Pagado')
            ->assertJsonPath('subtotal', 300)->assertJsonPath('discount', 75)->assertJsonPath('impuesto', 36)
            ->assertJsonPath('total', 261)->assertJsonCount(2, 'items');
        $this->postJson('/pagos/procesar', $payload)->assertOk()->assertJsonPath('id', $response->json('id'));
        $this->postJson('/pagos/procesar', [...$payload, 'metodo_pago' => 'transferencia'])->assertConflict();

        $orderId = $response->json('database_id');
        $paymentId = $response->json('pago.id');
        $this->getJson('/pagos/'.$paymentId)->assertOk()->assertJsonPath('folio', $response->json('id'));
        $this->getJson('/carrito/'.$client->id)->assertJsonPath('cantidad', 0);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseHas('ordenes_empresariales', ['cliente_id' => $client->id, 'estado' => 'procesada', 'total_cents' => 26100]);
        $erp = Orden::sole();
        $this->assertDatabaseHas('orden_productos', ['orden_id' => $erp->id, 'cantidad' => 3]);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $product->id, 'tipo' => 'salida', 'motivo' => 'venta',
            'cantidad' => 3, 'orden_empresarial_id' => $erp->id, 'order_id' => $orderId]);
        $this->assertDatabaseHas('pedidos_scm', ['producto_id' => $product->id, 'tipo' => 'reposicion', 'cantidad' => 8, 'automatico' => true]);
        $this->assertSame(2, $product->fresh()->stock);
        [$other] = $this->customer();
        $this->actingAs($other)->getJson('/pagos/'.$paymentId)->assertNotFound();
    }

    public function test_stock_and_quote_changes_abort_without_any_business_side_effects(): void
    {
        config(['commerce.simulate_payments' => true]);
        [$actor] = $this->customer();
        $first = $this->product();
        $second = $this->product(['name' => 'Tee', 'category' => 'T-Shirt']);
        $this->actingAs($actor);
        $this->add($first, 2)->assertOk();
        $this->add($second, 2)->assertOk();

        $this->postJson('/pagos/procesar', $this->payment(['total_esperado' => 1]))->assertConflict();
        $second->update(['stock' => 1]);
        $this->postJson('/pagos/procesar', $this->payment())->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseMissing('movimientos_inventario', ['tipo' => 'salida']);
        $this->assertDatabaseCount('carrito_detalle', 2);
        $this->assertSame(5, $first->fresh()->stock);
    }

    public function test_wallet_debit_and_erp_cancellation_refund_each_occur_once(): void
    {
        [$actor, $client] = $this->customer();
        $wallet = Monedero::create(['cliente_id' => $client->id, 'saldo_cents' => 1000]);
        $product = $this->product();
        $this->actingAs($actor);
        $this->add($product, 2)->assertOk();
        $payload = $this->payment(['metodo_pago' => 'monedero']);
        $this->postJson('/pagos/procesar', $payload)->assertUnprocessable();
        $this->assertSame(1000, $wallet->fresh()->saldo_cents);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);

        $wallet->update(['saldo_cents' => 30000]);
        $id = $this->postJson('/pagos/procesar', $payload)->assertCreated()->json('database_id');
        $this->assertSame(6800, $wallet->fresh()->saldo_cents);
        $this->actingAs($this->staff('admin'));
        $this->putJson('/tienda/pedidos/'.$id.'/estado', ['status' => 'Cancelado'])->assertOk()->assertJsonPath('pago.estado', 'Reembolsado');
        $this->assertDatabaseHas('ordenes_empresariales', ['estado' => 'cancelada']);
        $this->assertDatabaseHas('movimientos_monedero', ['order_id' => $id, 'tipo' => 'compra', 'importe_cents' => -23200, 'saldo_cents' => 6800]);
        $this->assertDatabaseHas('movimientos_monedero', ['order_id' => $id, 'tipo' => 'reembolso', 'importe_cents' => 23200, 'saldo_cents' => 30000]);
        $this->putJson('/tienda/pedidos/'.$id.'/estado', ['status' => 'Cancelado'])->assertOk();
        $this->assertSame(30000, $wallet->fresh()->saldo_cents);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_customer_authentication_registration_and_profile_share_the_crm_record(): void
    {
        $this->getJson('/auth/perfil')->assertUnauthorized();
        $email = 'nuevo@hfstudio.test';
        $password = 'NuevoClienteSeguro2026!';
        $this->postJson('/auth/registro', ['name' => 'Cliente Nuevo', 'email' => $email, 'password' => $password])
            ->assertCreated()->assertJsonPath('user.role', 'cliente')->assertJsonMissingPath('user.password');
        $this->assertDatabaseMissing('users', ['password' => $password]);
        $id = $this->getJson('/auth/perfil')->assertOk()->json('cliente.id');
        $this->putJson('/auth/perfil', ['nombre' => 'Nombre Editado', 'telefono' => '4491112222',
            'direccion' => 'Calle Uno 123', 'ciudad' => 'Aguascalientes', 'codigo_postal' => '20000'])->assertOk();
        $this->assertDatabaseHas('clientes', ['id' => $id, 'correo' => $email, 'nombre' => 'Nombre Editado', 'codigo_postal' => '20000']);
        $this->postJson('/auth/logout')->assertOk()->assertJsonPath('user', null);
        $this->getJson('/auth/perfil')->assertUnauthorized();
        $this->postJson('/auth/login', ['email' => $email, 'password' => $password])->assertOk()->assertJsonPath('user.name', 'Nombre Editado');
        $this->getJson('/auth/perfil')->assertJsonPath('cliente.id', $id);
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_support_tickets_are_private_and_create_crm_interactions(): void
    {
        [$actor, $client] = $this->customer();
        [$other] = $this->customer();
        $this->actingAs($actor);
        $ticket = $this->postJson('/atencion/ticket', ['asunto' => 'Entrega de mi pedido', 'mensaje' => '¿Cuándo llega mi pedido?'])
            ->assertCreated()->assertJsonPath('cliente_id', $client->id)->json('id');
        $this->assertDatabaseHas('interacciones', ['cliente_id' => $client->id, 'usuario_id' => $actor->id, 'tipo' => 'ticket']);
        $url = '/atencion/ticket/'.$ticket;
        $this->putJson($url.'/respuesta', ['respuesta' => 'Pronto'])->assertForbidden();
        $this->actingAs($other)->getJson($url)->assertNotFound();
        $this->getJson('/atencion/tickets')->assertJsonCount(0, 'data');
        $support = $this->staff('ventas');
        $this->actingAs($support)->putJson($url.'/respuesta', ['respuesta' => 'Enviado hoy.'])->assertOk()->assertJsonPath('estado', 'Respondido');
        $this->assertDatabaseHas('interacciones', ['cliente_id' => $client->id, 'usuario_id' => $support->id, 'tipo' => 'ticket']);
        $this->actingAs($actor)->getJson($url)->assertOk()->assertJsonPath('respuesta', 'Enviado hoy.');
        $this->assertDatabaseCount('interacciones', 2);
    }

    public function test_seeded_catalog_photos_and_demo_access_are_available_in_demo_mode(): void
    {
        config(['commerce.demo' => true]);
        $this->seed();
        $this->artisan('hf:verificar-imagenes')->assertSuccessful();
        $this->postJson('/auth/demo')->assertOk()->assertJsonPath('user.role', 'cliente');
        $this->getJson('/auth/perfil')->assertJsonPath('saldo_cents', 500000);
        config(['commerce.demo' => false]);
        $this->postJson('/auth/demo')->assertNotFound();
    }

    public function test_catalog_sorts_final_prices_and_targets_promotions_to_one_product(): void
    {
        $first = $this->product(['price_cents' => 11000]);
        $second = $this->product(['price_cents' => 10000]);
        $this->promotion(['producto_id' => $first->id, 'valor' => 40]);
        $this->getJson('/catalogo?orden=precio-asc')->assertOk()
            ->assertJsonPath('productos.0.id', $first->id)->assertJsonPath('productos.0.precio', 6600)
            ->assertJsonPath('productos.1.precio', 10000);
        $this->getJson('/catalogo?orden=nuevos')->assertOk()->assertJsonPath('productos.0.id', $second->id);
        config(['commerce.simulate_payments' => true]);
        [$actor] = $this->customer();
        $this->actingAs($actor)->add($second, 1)->assertOk();
        Notification::fake();
        $payload = $this->payment(['metodo_pago' => 'paypal', 'recipient_name' => 'Persona destinataria']);
        $orderId = $this->postJson('/pagos/procesar', $payload)->assertCreated()
            ->assertJsonPath('pago.metodo_pago', 'paypal')->assertJsonPath('entrega.nombre', 'Persona destinataria')->json('database_id');
        $this->getJson('/tienda/pedidos/'.$orderId)->assertOk()->assertJsonPath('entrega.nombre', 'Persona destinataria');
        $this->postJson('/pagos/procesar', $payload)->assertOk();
        Notification::assertSentToTimes($actor, OrderPaid::class, 1);
        $this->getJson('/catalogo?orden=populares')->assertOk()->assertJsonPath('productos.0.id', $second->id);
    }

    public function test_guest_cart_import_can_retry_without_doubling_quantities(): void
    {
        [$actor, $client] = $this->customer();
        $product = $this->product();
        $this->actingAs($actor);
        $payload = ['request_key' => Str::uuid()->toString(), 'items' => [
            ['producto_id' => $product->id, 'cantidad' => 2, 'talla' => 'M'],
            ['producto_id' => $product->id, 'cantidad' => 9, 'talla' => 'L'],
        ]];
        $this->postJson('/carrito/importar', $payload)->assertOk()->assertJsonPath('cantidad', 2)->assertJsonPath('rechazados.0.indice', 1);
        $this->postJson('/carrito/importar', $payload)->assertOk()->assertJsonPath('cantidad', 2);
        $this->postJson('/carrito/importar', [...$payload, 'items' => [$payload['items'][0]]])->assertConflict();
        $this->getJson('/carrito/'.$client->id)->assertJsonPath('cantidad', 2);
    }

    public function test_wallet_demo_recharges_and_staff_adjustments_are_traced_and_authorized(): void
    {
        [$actor, $client] = $this->customer();
        $this->actingAs($actor);
        $recharge = ['importe' => 250, 'metodo' => 'paypal', 'request_key' => Str::uuid()->toString()];
        config(['commerce.simulate_payments' => false]);
        $this->postJson('/mi-monedero/recargas', $recharge)->assertForbidden();
        config(['commerce.simulate_payments' => true]);
        $this->postJson('/mi-monedero/recargas', $recharge)->assertCreated()->assertJsonPath('saldo_cents', 25000);
        $this->postJson('/mi-monedero/recargas', $recharge)->assertOk()->assertJsonPath('saldo_cents', 25000);
        $this->postJson('/mi-monedero/recargas', [...$recharge, 'importe' => 500])->assertConflict();
        $this->getJson('/operacion/monederos/'.$client->id)->assertForbidden();
        $this->actingAs($this->staff('admin'));
        $adjustment = ['importe' => 50, 'tipo' => 'cargo', 'concepto' => 'Ajuste validado', 'request_key' => Str::uuid()->toString()];
        $this->postJson('/operacion/monederos/'.$client->id.'/ajustes', $adjustment)->assertCreated()->assertJsonPath('saldo_cents', 20000);
        $this->postJson('/operacion/monederos/'.$client->id.'/ajustes', $adjustment)->assertOk()->assertJsonPath('saldo_cents', 20000);
        $this->get('/operacion/monederos/reporte?csv=1')->assertOk()->assertDownload('movimientos-monedero.csv');
        $this->actingAs($actor)->getJson('/mi-monedero')->assertOk()->assertJsonPath('saldo_cents', 20000)
            ->assertJsonCount(2, 'movimientos.data')->assertJsonPath('movimientos.data.0.importe_cents', -5000);
    }

    public function test_ticket_search_and_timeline_preserve_all_support_updates(): void
    {
        [$actor] = $this->customer();
        $this->actingAs($actor);
        $id = $this->postJson('/atencion/ticket', [
            'categoria' => 'Facturación', 'asunto' => 'Factura de prueba', 'mensaje' => 'Solicito la factura de mi pedido.',
        ])->assertCreated()->json('id');
        $this->actingAs($this->staff('ventas'));
        $url = '/atencion/ticket/'.$id;
        $this->putJson($url.'/respuesta', ['estado' => 'En proceso'])->assertOk()->assertJsonPath('estado', 'En proceso');
        $this->putJson($url.'/respuesta', ['estado' => 'Respondido', 'respuesta' => 'Factura preparada.'])->assertOk();
        $this->putJson($url.'/respuesta', ['estado' => 'Cerrado', 'respuesta' => 'Factura confirmada.'])->assertOk();
        $this->putJson($url.'/respuesta', ['estado' => 'Respondido', 'respuesta' => 'Reabrir ticket.'])->assertConflict();
        $this->actingAs($actor);
        $this->getJson('/atencion/tickets?estado=Cerrado&q=T-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.categoria', 'Facturación');
        $this->getJson($url)->assertOk()->assertJsonCount(3, 'eventos')->assertJsonPath('eventos.1.mensaje', 'Factura preparada.');
        $this->assertDatabaseCount('interacciones', 4);
    }

    public function test_password_can_be_reset_with_a_single_use_email_link(): void
    {
        Notification::fake();
        [$actor] = $this->customer();
        $this->postJson('/auth/recuperar', ['email' => $actor->email])->assertOk();
        $notification = Notification::sent($actor, ResetPassword::class)->sole();
        $this->get('/auth/restablecer/'.$notification->token.'?email='.urlencode($actor->email))->assertOk();
        $password = 'NuevaContraseñaSegura.2026';
        $payload = ['email' => $actor->email, 'token' => $notification->token, 'password' => $password, 'password_confirmation' => $password];
        $this->post('/auth/restablecer', $payload)->assertRedirect('/?vista=cuenta');
        $this->assertTrue(Hash::check($password, $actor->fresh()->password));
        $this->post('/auth/restablecer', $payload)->assertSessionHasErrors('email');
    }

    public function test_management_menu_shares_navigation_and_enforces_permissions(): void
    {
        $this->actingAs($this->staff('admin'));
        $this->get('/gestion')->assertRedirect('/gestion/operaciones');
        foreach (['clientes', 'inventario', 'operaciones', 'tienda'] as $module) {
            $this->get('/gestion/'.$module)->assertOk()->assertSee('Menú de gestión')->assertSee('/gestion/clientes');
        }
        $this->actingAs($this->staff('logistica'))->get('/gestion/inventario')->assertOk()->assertDontSee('/gestion/clientes');
        $this->getJson('/gestion/clientes')->assertForbidden();
        [$actor] = $this->customer();
        $this->actingAs($actor)->getJson('/gestion/tienda')->assertForbidden();
    }

    public function test_checkout_and_receipt_have_dedicated_pages_and_enforce_ownership(): void
    {
        config(['commerce.simulate_payments' => true]);
        $this->get('/checkout')->assertRedirect('/?vista=cuenta&continuar=checkout');
        [$actor] = $this->customer();
        $product = $this->product();
        $this->actingAs($actor);
        $this->get('/checkout')->assertOk()->assertSee('Tu carrito está vacío.');
        $this->add($product, 1)->assertOk();
        $this->get('/checkout')->assertOk()->assertSee('Datos de entrega')->assertSee($product->name)->assertDontSee('checkout-panel');
        $payload = $this->payment();
        $response = $this->post('/pagos/procesar', $payload)->assertStatus(303);
        $url = $response->headers->get('Location');
        $this->assertStringContainsString('/compras/', $url);
        $this->get($url)->assertOk()->assertSee('Compra confirmada.')->assertSee($product->name);
        [$other] = $this->customer();
        $this->actingAs($other)->get($url)->assertNotFound();
        $this->actingAs($this->staff('admin'))->get($url)->assertOk()->assertSee('Pedidos de la tienda');
        $this->assertDatabaseCount('orders', 1);
    }

    private function product(array $data = []): Product
    {
        return Product::create([...[
            'name' => 'Hoodie Carbón', 'category' => 'Hoodie', 'stock' => 5, 'active' => true,
            'price_cents' => 10000, 'image' => 'images/hf-studios/productos/hoodie-esencial.webp', 'description' => 'Hoodie de prueba.',
        ], ...$data]);
    }

    private function promotion(array $data): Promocion
    {
        return Promocion::create([...[
            'nombre' => 'Prueba '.Str::random(5), 'tipo_descuento' => 'porcentaje', 'valor' => 10,
            'fecha_inicio' => now()->subDay(), 'fecha_fin' => now()->addHour(), 'estado' => true,
        ], ...$data]);
    }

    private function customer(): array
    {
        $user = User::factory()->create();
        $client = Cliente::create(['nombre' => $user->name, 'correo' => $user->email]);
        $user->forceFill(['cliente_id' => $client->id])->save();

        return [$user, $client];
    }

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function add(Product $product, int $quantity, string $size = 'M')
    {
        return $this->postJson('/carrito/agregar', ['producto_id' => $product->id, 'talla' => $size, 'cantidad' => $quantity]);
    }

    private function payment(array $data = []): array
    {
        return [...[
            'checkout_key' => (string) Str::uuid(), 'address' => 'Calle Uno 123', 'city' => 'Aguascalientes',
            'zip' => '20000', 'metodo_pago' => 'tarjeta',
        ], ...$data];
    }
}
