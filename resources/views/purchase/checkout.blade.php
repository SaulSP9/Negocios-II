@extends('purchase.layout')

@section('title', 'Finalizar compra')
@section('back-url', '/catalogo?carrito=1')
@section('back-text', 'Volver al carrito')

@section('content')
@php
    $simulated = config('commerce.simulate_payments');
    $methods = $simulated ? ['tarjeta' => 'Tarjeta', 'paypal' => 'PayPal', 'transferencia' => 'Transferencia'] : [];
    $methods['monedero'] = 'Monedero HF Studios';
    $ready = count($cart['items']) && collect($cart['items'])->every(fn ($item) => $item['cantidad_producto'] <= $item['stock']);
@endphp
<section class="shell purchase-shell" data-checkout-page>
    <div class="purchase-title">
        <p class="overline">Carrito / Finalizar compra</p>
        <h1>Finalizar compra.</h1>
    </div>
    @if ($cart['items'])
        <div class="checkout-layout">
            <form id="checkout-form" class="checkout-form" action="{{ url('/pagos/procesar') }}" method="post" data-simulated="{{ $simulated ? '1' : '0' }}">
                @csrf
                <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
                <input type="hidden" name="total_esperado" value="{{ $cart['total'] }}">
                @if ($errors->any())
                    <ul class="checkout-message">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif
                <fieldset class="checkout-section">
                    <legend>Contacto</legend>
                    <div class="checkout-contact"><strong>{{ $email }}</strong><a class="text-link" href="/?vista=cuenta#mis-datos">Mi cuenta</a></div>
                </fieldset>
                <fieldset class="checkout-section">
                    <legend>Datos de entrega</legend>
                    <div class="checkout-fields">
                        <label class="span-two">Nombre de quien recibe <input name="recipient_name" autocomplete="name" value="{{ old('recipient_name', $client->nombre) }}" minlength="2" maxlength="120" required></label>
                        <label class="span-two">Calle, número y referencias <input name="address" autocomplete="street-address" value="{{ old('address', $client->direccion) }}" minlength="5" maxlength="255" required></label>
                        <label>Ciudad <input name="city" autocomplete="address-level2" value="{{ old('city', $client->ciudad) }}" minlength="2" maxlength="120" required></label>
                        <label>Código postal <input name="zip" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('zip', $client->codigo_postal) }}" required></label>
                    </div>
                </fieldset>
                <fieldset class="checkout-section">
                    <legend>Forma de pago</legend>
                    <div class="checkout-methods">
                        @foreach ($methods as $key => $name)
                            <label class="checkout-method">
                                <input type="radio" name="metodo_pago" value="{{ $key }}" required @checked(old('metodo_pago') === $key) @disabled($key === 'monedero' && $wallet < $cart['total'])>
                                <span>{{ $name }} @if ($key !== 'monedero')<small>Simulación</small>@endif</span>
                                @if ($key === 'monedero')<strong id="checkout-wallet">${{ number_format($wallet / 100, 2) }}</strong>@endif
                            </label>
                        @endforeach
                    </div>
                    <p id="wallet-shortfall" class="checkout-hint" @if ($wallet >= $cart['total']) hidden @endif>Saldo insuficiente. <a class="text-link" href="/?vista=cuenta#mi-monedero">Ver mi monedero</a></p>
                </fieldset>
                <p id="checkout-message" class="checkout-message" tabindex="-1" role="alert" @if ($ready) hidden @endif>{{ $ready ? '' : 'Ajusta las cantidades en el carrito para continuar.' }}</p>
                <button class="button primary checkout-submit" id="checkout-pay" type="submit" @disabled(! $ready || (! $simulated && $wallet < $cart['total']))>Confirmar y pagar</button>
            </form>
            <aside id="checkout-summary" class="purchase-summary" aria-labelledby="summary-title">
                <h2 id="summary-title">Resumen del pedido</h2>
                @include('purchase.summary', ['items' => $cart['items'], 'subtotal' => $cart['subtotal'],
                    'discount' => $cart['descuento'], 'tax' => $cart['impuesto'], 'total' => $cart['total']])
            </aside>
        </div>
    @else
        <div class="purchase-empty"><h2>Tu carrito está vacío.</h2><p>Agrega una prenda para continuar con el pago.</p><a class="button primary" href="/catalogo">Explorar colección</a></div>
    @endif
</section>
@endsection
