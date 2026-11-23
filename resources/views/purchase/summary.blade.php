@php($formatMoney = fn ($value) => '$'.number_format($value / 100, 2))
<ul class="summary-items" id="summary-items">
    @foreach ($items as $line)
        <li>
            <div class="summary-thumb"><img src="{{ $line['imagen_url'] }}" alt="" width="56" height="65"><span>{{ $line['cantidad'] }}</span></div>
            <div><strong>{{ $line['nombre'] }}</strong>@if ($line['talla'])<small>Talla {{ $line['talla'] }}</small>@endif</div>
            <span>{{ $formatMoney(($line['precio_unitario'] - ($line['discount'] ?? 0)) * $line['cantidad']) }}</span>
        </li>
    @endforeach
</ul>
<dl class="totals summary-totals">
    <div><dt>Subtotal</dt><dd id="summary-subtotal">{{ $formatMoney($subtotal) }}</dd></div>
    <div><dt>Descuento</dt><dd id="summary-discount">{{ $discount ? '−' : '' }}{{ $formatMoney($discount) }}</dd></div>
    <div><dt>Impuestos</dt><dd id="summary-tax">{{ $formatMoney($tax) }}</dd></div>
    <div class="total"><dt>Total <small>MXN</small></dt><dd id="summary-total">{{ $formatMoney($total) }}</dd></div>
</dl>
