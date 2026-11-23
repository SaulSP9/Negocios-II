<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Monedero;
use App\Models\MovimientoInventario;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommerceCheckout
{
    public function __construct(
        private readonly CommerceCart $carts,
        private readonly CommercePricing $pricing,
        private readonly ErpOrders $erp,
        private readonly WalletLedger $ledger,
    ) {}

    public function pay(User $actor, Cliente $client, array $data): array
    {
        $signature = [$data['metodo_pago'], $data['address'], $data['city'], $data['zip'], $data['total_esperado'] ?? null];
        if (isset($data['recipient_name'])) {
            $signature[] = $data['recipient_name'];
        }
        $hash = hash('sha256', json_encode($signature, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $client, $data, $hash) {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $cart = $this->carts->lockedBag($client);
            $existing = Order::with('pago')->where('user_id', $actor->id)->where('checkout_key', $data['checkout_key'])->first();
            if ($existing) {
                abort_unless($existing->pago && $existing->checkout_hash && hash_equals($existing->checkout_hash, $hash), 409, 'La clave pertenece a otro pago.');
                return [$existing, false];
            }

            $lines = $cart->detalles()->orderBy('producto_id')->orderBy('id')->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['carrito' => 'Agrega al menos una pieza al carrito.']);
            }
            $byProduct = $lines->groupBy('producto_id');
            $products = Product::whereIn('id', $byProduct->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $erpLines = [];
            foreach ($byProduct as $id => $sizes) {
                $product = $products->get($id);
                $quantity = $sizes->sum('cantidad');
                if (! $product || ! $product->active || $quantity > $product->stock
                    || $sizes->contains(fn ($line) => ! in_array($line->talla, $product->tallas(), true))) {
                    throw ValidationException::withMessages(['carrito' => 'Una pieza de tu carrito ya no tiene la talla o existencia solicitada.']);
                }
                foreach ($sizes as $line) {
                    $line->setRelation('producto', $product);
                }
                $erpLines[] = ['producto_id' => $id, 'cantidad' => $quantity];
            }

            $quote = $this->pricing->quote($lines);
            abort_if(isset($data['total_esperado']) && (int) $data['total_esperado'] !== $quote['total'], 409, 'El total cambió. Revisa nuevamente los importes.');
            $method = $data['metodo_pago'];
            if ($method === 'monedero') {
                $wallet = Monedero::where('cliente_id', $client->id)->lockForUpdate()->first();
                if (! $wallet || $wallet->saldo_cents < $quote['total']) {
                    throw ValidationException::withMessages(['metodo_pago' => 'Saldo de monedero insuficiente.']);
                }
            } else {
                abort_unless(config('commerce.simulate_payments'), 422, 'Selecciona saldo de monedero. Los pagos simulados están desactivados.');
            }

            $folio = 'HF-'.Str::ulid()->toBase32();
            $businessOrder = $this->erp->create([
                'cliente_id' => $client->id, 'request_key' => (string) Str::uuid(),
                'productos' => $erpLines, 'notas' => 'Compra en tienda '.$folio,
            ], $actor, true, $quote['total']);
            $order = Order::create([
                'folio' => $folio, 'user_id' => $actor->id, 'cliente_id' => $client->id,
                'orden_empresarial_id' => $businessOrder->id, 'checkout_key' => $data['checkout_key'],
                'checkout_hash' => $hash, 'recipient_name' => $data['recipient_name'] ?? $client->nombre, 'address' => $data['address'], 'city' => $data['city'], 'zip' => $data['zip'],
                'subtotal_cents' => $quote['subtotal'], 'discount_cents' => $quote['descuento'],
                'tax_cents' => $quote['impuesto'], 'total_cents' => $quote['total'], 'status' => 'Pagado',
            ]);
            $order->items()->createMany($lines->map(fn ($line) => [
                'product_id' => $line->producto_id, 'name' => $line->producto->name, 'price_cents' => $line->producto->price_cents,
                'quantity' => $line->cantidad, 'talla' => $line->talla,
            ])->all());
            $order->pago()->create(['monto_cents' => $quote['total'], 'metodo_pago' => $method, 'estado' => 'Pagado', 'fecha' => now()]);
            if ($method === 'monedero') {
                $this->ledger->post($client->id, -$quote['total'], 'compra', 'Pago de pedido', [
                    'order_id' => $order->id, 'user_id' => $actor->id, 'metodo' => 'monedero',
                ]);
            }
            MovimientoInventario::where('orden_empresarial_id', $businessOrder->id)->update(['order_id' => $order->id]);
            $cart->detalles()->delete();

            return [$order, true];
        }, 3);
    }
}
