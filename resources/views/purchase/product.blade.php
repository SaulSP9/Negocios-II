@extends('purchase.layout')

@section('title', $product['nombre'])
@section('full-header', 'yes')

@section('content')
@php
    $mxn = fn ($cents) => '$'.number_format($cents / 100, 2);
    $views = ['full' => 'Vista completa', 'crop' => 'Encuadre de la prenda', 'detail' => 'Acercamiento'];
    $category = $product['categoria'] ?? '';
    $image = $product['imagen_url'];
@endphp
<article class="shell product-page" data-product-page data-initial-product="{{ $product['id'] }}" data-family="{{ $category }}">
    <nav class="purchase-path" aria-label="Ruta del producto">
        <a href="/catalogo">Colección</a><span aria-hidden="true">/</span>
        <a href="/catalogo?categoria={{ urlencode($category) }}">{{ $product['familia'] }}</a>
    </nav>
    <div class="product-layout">
        <div class="gallery">
            <div class="gallery-thumbs" aria-label="Vistas del producto">
                @foreach ($views as $key => $label)
                    <button type="button" data-gallery-view="{{ $key }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="{{ $label }}">
                        <span class="gallery-image" data-frame="{{ $key }}"><img src="{{ $image }}" alt="" width="72" height="90"></span>
                    </button>
                @endforeach
            </div>
            <figure class="gallery-stage" id="product-gallery" data-frame="full">
                <img src="{{ $image }}" alt="{{ $product['nombre'] }} · vista completa" width="720" height="900" fetchpriority="high">
                <figcaption aria-live="polite" id="gallery-caption">Vista completa <span>01 / 03</span></figcaption>
            </figure>
        </div>
        <section class="product-sheet" aria-labelledby="product-title">
            <div class="product-heading">
                <div><p class="overline">{{ $product['familia'] }}</p><h1 id="product-title">{{ $product['nombre'] }}</h1></div>
                <button type="button" class="favorite-product" data-favorite="{{ $product['id'] }}" aria-pressed="false" aria-label="Favorito: {{ $product['nombre'] }}">♡</button>
            </div>
            <div class="sheet-price">
                <strong>{{ $mxn($product['precio']) }}</strong>
                @if ($product['precio_anterior'])<del>{{ $mxn($product['precio_anterior']) }}</del>@endif
                <span class="overline">MXN</span>
            </div>
            <p class="sheet-description">{{ $product['descripcion'] }}</p>
            <div class="sheet-color">
                <img src="{{ $image }}" width="50" height="60" alt="">
                <div><p class="overline">Color</p><strong>{{ $product['color'] }}</strong></div>
            </div>
            @if ($product['stock_actual'] > 0)
                <form id="product-form" class="form-stack">
                    <fieldset class="sheet-sizes">
                        <legend class="overline">Talla</legend>
                        <div class="size-options">
                            @foreach ($product['tallas'] as $size)
                                <label><input type="radio" name="talla" value="{{ $size }}" required @checked(count($product['tallas']) === 1)><span>{{ $size }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                    <details class="sheet-guide"><summary>Guía para elegir talla</summary>
                        <p>{{ match ($category) {
                            'accesorios' => 'Esta pieza se ofrece en talla única.',
                            'pantalones' => 'Compara la talla con la de un pantalón que ya uses y te quede bien.',
                            default => 'Usa como referencia la talla de una prenda con un corte similar que ya te quede bien.',
                        } }}</p>
                    </details>
                    <div class="sheet-quantity">
                        <label for="product-quantity">Cantidad</label>
                        <div class="quantity"><button type="button" data-product-qty="-1" aria-label="Restar una unidad">−</button><input type="number" name="cantidad" id="product-quantity" min="1" max="{{ min($product['stock_actual'], 99) }}" value="1" required><button type="button" data-product-qty="1" aria-label="Sumar una unidad">+</button></div>
                        <span class="product-stock">{{ $product['stock_actual'] }} disponibles</span>
                    </div>
                    <button type="submit" class="button primary sheet-add">Agregar al carrito</button>
                </form>
            @else
                <p class="flag">Agotado</p>
            @endif
        </section>
    </div>
    @if ($related->isNotEmpty())
        <section class="related-products" aria-labelledby="related-title">
            <h2 id="related-title" class="subheading">Más de {{ $product['familia'] }}</h2>
            <div class="product-grid">
                @foreach ($related as $item)
                    <article class="product-card">
                        <a class="product-media" href="/catalogo/{{ $item['id'] }}"><img src="{{ $item['imagen_url'] }}" alt="{{ $item['nombre'] }}" width="720" height="900" loading="lazy"></a>
                        <div class="product-meta"><h3><a href="/catalogo/{{ $item['id'] }}">{{ $item['nombre'] }}</a></h3><p class="product-color">{{ $item['color'] }}</p><p class="price">{{ $mxn($item['precio']) }}</p></div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</article>
@endsection
