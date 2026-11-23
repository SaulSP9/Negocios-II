@extends('purchase.layout')

@section('title', 'Pedido '.$order->folio)
@section('back-url', $order->user_id === auth()->id() ? '/?vista=cuenta#mis-pedidos' : '/gestion/tienda#operacion-pedidos')
@section('back-text', $order->user_id === auth()->id() ? 'Mis pedidos' : 'Pedidos de la tienda')

@section('content')
@php
    $paid = $order->pago?->estado === 'Pagado';
    $methods = ['tarjeta' => 'Tarjeta (simulación)', 'paypal' => 'PayPal (simulación)',
        'transferencia' => 'Transferencia (simulación)', 'monedero' => 'Monedero HF Studios'];
    $lines = $order->items->map(fn ($line) => [
        'imagen_url' => asset(App\Support\Editorial::productImage($line->product?->image)),
        'cantidad' => $line->quantity, 'nombre' => $line->name, 'talla' => $line->talla,
        'precio_unitario' => $line->price_cents,
    ]);
@endphp
<article class="shell purchase-shell receipt-page" data-receipt-page>
    <div class="purchase-title">
        <p class="overline">Pedido {{ $order->folio }}</p>
        <h1>{{ $paid ? 'Compra confirmada.' : 'Resumen del pedido.' }}</h1>
    </div>
    <div class="checkout-layout receipt-layout">
        <section class="receipt-info">
            <div><p class="overline">Estado</p><strong class="receipt-status">{{ $order->status }}</strong></div>
            <div><p class="overline">Fecha del pedido</p><p>{{ $order->created_at->format('d/m/Y') }}</p></div>
            @if ($order->pago)
                <div><p class="overline">Pago · {{ $order->pago->estado }}</p><p>{{ $methods[$order->pago->metodo_pago] ?? $order->pago->metodo_pago }}</p></div>
            @endif
            <div><p class="overline">Entrega</p><p>{{ $order->recipient_name ?: $order->user->name }}</p><p>{{ $order->address }}, {{ $order->city }} · {{ $order->zip }}</p></div>
            @if ($order->cargoEnMonedero && $paid)
                <div><p class="overline">Saldo después del pago</p><p>${{ number_format($order->cargoEnMonedero->saldo_cents / 100, 2) }} MXN</p></div>
            @endif
            <div class="actions receipt-actions">
                <a href="/catalogo" class="button primary">Seguir comprando</a>
                <button type="button" class="button" id="print-receipt">Imprimir recibo</button>
            </div>
        </section>
        <aside class="purchase-summary" aria-labelledby="summary-title">
            <h2 id="summary-title">Artículos del pedido</h2>
            @include('purchase.summary', ['items' => $lines, 'subtotal' => $order->subtotal_cents,
                'discount' => $order->discount_cents, 'tax' => $order->tax_cents, 'total' => $order->total_cents])
        </aside>
    </div>
</article>
@endsection
