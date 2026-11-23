<?php

namespace App\Http\Controllers;

use App\Models\CarritoDetalle;
use App\Models\Pago;
use App\Models\Order;
use App\Models\Monedero;
use App\Notifications\OrderPaid;
use App\Services\CommerceCart;
use App\Services\CommerceCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CarritoController extends Controller
{
    public function __construct(private readonly CommerceCart $carts) {}

    public function checkoutPage(Request $request)
    {
        if (! $request->user()) {
            return redirect('/?vista=cuenta&continuar=checkout');
        }

        $client = $this->carts->client($request->user());
        $cart = $this->carts->snapshot($this->carts->bag($client));
        $wallet = (int) Monedero::where('cliente_id', $client->id)->value('saldo_cents');
        if ($request->expectsJson()) {
            return ['cart' => $cart, 'wallet' => $wallet];
        }

        return view('purchase.checkout', [
            'client' => $client, 'cart' => $cart, 'wallet' => $wallet,
            'email' => $request->user()->email, 'checkoutKey' => (string) Str::uuid(),
        ]);
    }

    public function orderPage(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->hasPermission('commerce.orders'), 404);
        $order->load(['items.product:id,image', 'user', 'pago', 'cargoEnMonedero']);

        return view('purchase.receipt', ['order' => $order]);
    }

    public function show(Request $request, int $cliente_id)
    {
        $client = $this->carts->client($request->user());
        abort_unless($client->id === $cliente_id, 404);

        return $this->carts->snapshot($this->carts->bag($client));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'producto_id' => 'required|integer|min:1', 'talla' => 'required|string|max:12',
            'cantidad' => 'required|integer|between:1,99',
        ]);

        return $this->carts->save($this->carts->client($request->user()), $data['producto_id'], $data['talla'], $data['cantidad']);
    }

    public function merge(Request $request)
    {
        $data = $request->validate([
            'request_key' => 'required|uuid', 'items' => 'required|array|list|min:1|max:50',
            'items.*' => 'required|array:producto_id,talla,cantidad',
            'items.*.producto_id' => 'required|integer|min:1', 'items.*.talla' => 'required|string|max:12',
            'items.*.cantidad' => 'required|integer|between:1,99',
        ]);

        return $this->carts->merge($this->carts->client($request->user()), $data['request_key'], $data['items']);
    }

    public function update(Request $request, CarritoDetalle $item)
    {
        $data = $request->validate(['cantidad' => 'required|integer|between:1,99']);
        $client = $this->carts->client($request->user());
        abort_unless($this->carts->bag($client)->id === $item->carrito_id, 404);

        return $this->carts->save($client, $item->producto_id, $item->talla, $data['cantidad'], $item->id);
    }

    public function destroy(Request $request, int $item)
    {
        return $this->carts->remove($this->carts->client($request->user()), $item);
    }

    public function clear(Request $request, int $cliente_id)
    {
        $client = $this->carts->client($request->user());
        abort_unless($client->id === $cliente_id, 404);

        return $this->carts->remove($client);
    }

    public function pay(Request $request, CommerceCheckout $checkout)
    {
        $data = $request->validate([
            'recipient_name' => 'nullable|string|min:2|max:120', 'address' => 'required|string|min:5|max:255', 'city' => 'required|string|min:2|max:120',
            'zip' => 'required|digits:5', 'checkout_key' => 'required|uuid',
            'metodo_pago' => 'required|in:tarjeta,paypal,transferencia,monedero', 'total_esperado' => 'nullable|integer|min:0',
        ]);
        $actor = $request->user();
        [$order, $created] = $checkout->pay($actor, $this->carts->client($actor), $data);

        $confirmation = null;
        if ($created) {
            $confirmation = rescue(function () use ($actor, $order) {
                $actor->notify(new OrderPaid($order));

                return in_array(config('mail.default'), ['log', 'array'], true) ? 'registrada' : 'enviada';
            });
        }

        $url = route('purchase.receipt', ['order' => $order->folio], false);
        if (! $request->expectsJson()) {
            return redirect($url, 303);
        }

        return response()->json([
            ...$order->presentation(), 'confirmacion_email' => $confirmation, 'recibo_url' => $url,
        ], $created ? 201 : 200);
    }

    public function payment(Request $request, Pago $pago)
    {
        $order = $pago->pedido;
        abort_unless($order->user_id === $request->user()->id || $request->user()->hasPermission('commerce.orders'), 404);

        return ['monto_cents' => $pago->monto_cents, ...$order->presentation(), 'id' => $pago->id, 'folio' => $order->folio];
    }
}
