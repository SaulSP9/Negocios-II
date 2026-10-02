<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $extra = []): Product
    {
        return Product::create([...['name' => 'Hoodie', 'category' => 'Hoodie', 'price_cents' => 10000, 'stock' => 5, 'image' => 'https://example.com/image.jpg', 'description' => 'Producto de prueba', 'active' => true], ...$extra]);
    }

    private function checkout(Product $p, array $extra = []): array
    {
        return [...['items' => [['id' => $p->id, 'quantity' => 2]], 'address' => 'Calle Uno 123', 'city' => 'Aguascalientes', 'zip' => '20000', 'checkout_key' => (string) Str::uuid(), 'discount_code' => 'HF10'], ...$extra];
    }

    public function test_checkout_recalculates_price_persists_and_is_idempotent(): void
    {
        $p = $this->product();
        $this->actingAs(User::factory()->create());
        $body = $this->checkout($p, ['price' => 1, 'total' => 1]);
        $folio = $this->postJson('/tienda/pedidos', $body)->assertCreated()->assertJsonPath('total', 180)->json('id');
        $this->postJson('/tienda/pedidos', $body)->assertOk()->assertJsonPath('id', $folio);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(3, $p->fresh()->stock);
        $this->getJson('/tienda/pedidos')->assertJsonPath('0.total', 180)->assertJsonPath('0.status', 'Pendiente');
    }

    public function test_checkout_rolls_back_all_stock_if_one_item_fails(): void
    {
        $a = $this->product();
        $b = $this->product(['name' => 'Sin existencia', 'stock' => 0]);
        $this->actingAs(User::factory()->create());
        $body = $this->checkout($a, ['items' => [['id' => $a->id, 'quantity' => 2], ['id' => $b->id, 'quantity' => 1]]]);
        $this->postJson('/tienda/pedidos', $body)->assertUnprocessable();
        $this->assertSame(5, $a->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_invalid_discount_inactive_product_and_duplicate_items_are_rejected(): void
    {
        $p = $this->product();
        $this->actingAs(User::factory()->create());
        $this->postJson('/tienda/pedidos', $this->checkout($p, ['discount_code' => 'FAKE']))->assertUnprocessable();
        $this->postJson('/tienda/pedidos', $this->checkout($p, ['items' => [['id' => $p->id, 'quantity' => 1], ['id' => $p->id, 'quantity' => 1]]]))->assertUnprocessable();
        $p->update(['active' => false]);
        $this->getJson('/tienda/productos')->assertExactJson([]);
        $this->postJson('/tienda/pedidos', $this->checkout($p))->assertUnprocessable();
    }

    public function test_orders_are_private_and_cancel_restores_stock_once(): void
    {
        $p = $this->product();
        $this->actingAs(User::factory()->create());
        $this->postJson('/tienda/pedidos', $this->checkout($p))->assertCreated();
        $this->actingAs(User::factory()->create())->getJson('/tienda/pedidos')->assertExactJson([]);
        $order = Order::first();
        $this->putJson('/tienda/pedidos/'.$order->id.'/estado', ['status' => 'Cancelado'])->assertForbidden();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin);
        $this->putJson('/tienda/pedidos/'.$order->id.'/estado', ['status' => 'Cancelado'])->assertOk();
        $this->assertSame(5, $p->fresh()->stock);
        $this->putJson('/tienda/pedidos/'.$order->id.'/estado', ['status' => 'Cancelado'])->assertUnprocessable();
        $this->assertSame(5, $p->fresh()->stock);
    }

    public function test_publications_cannot_be_edited_by_another_user(): void
    {
        $this->actingAs(User::factory()->create());
        $body = ['name' => 'Chaqueta', 'price' => 200, 'description' => 'Chaqueta usada', 'image' => 'https://example.com/image.jpg'];
        $id = $this->postJson('/tienda/publicaciones', $body)->assertOk()->json('id');
        $this->actingAs(User::factory()->create())->putJson('/tienda/publicaciones/'.$id, $body)->assertForbidden();
        $this->deleteJson('/tienda/publicaciones/'.$id)->assertForbidden();
    }

    public function test_auction_rejects_lower_bids_and_closed_auctions(): void
    {
        $a = Auction::create(['name' => 'Subasta test', 'image' => 'https://example.com/image.jpg', 'current_bid_cents' => 10000, 'ends_at' => now()->addHour()]);
        $this->actingAs(User::factory()->create());
        $this->postJson('/tienda/subastas/'.$a->id.'/ofertas', ['amount' => 90])->assertUnprocessable();
        $this->postJson('/tienda/subastas/'.$a->id.'/ofertas', ['amount' => 120])->assertOk()->assertJsonPath('product.currentBid', 120);
        $a->update(['ends_at' => now()->subMinute()]);
        $this->postJson('/tienda/subastas/'.$a->id.'/ofertas', ['amount' => 140])->assertUnprocessable();
    }

    public function test_seeding_does_not_reset_existing_stock(): void
    {
        $this->seed();
        $p = Product::first();
        $p->update(['stock' => 3]);
        $this->seed();
        $this->assertSame(3, $p->fresh()->stock);
        $this->assertDatabaseCount('products', 6);
    }

    public function test_catalog_administration_requires_admin(): void
    {
        $p = $this->product();
        $this->actingAs(User::factory()->create())->patchJson('/tienda/productos/'.$p->id.'/visibilidad')->assertForbidden();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin);
        $this->patchJson('/tienda/productos/'.$p->id.'/visibilidad')->assertOk()->assertJsonPath('active', false);
        $this->putJson('/tienda/productos/'.$p->id, ['name' => 'Producto', 'category' => 'Pants', 'price' => 50, 'stock' => 10, 'image' => 'https://example.com/image.jpg', 'description' => 'Nueva descripción', 'active' => true])->assertOk()->assertJsonPath('price',50);
    }
}
