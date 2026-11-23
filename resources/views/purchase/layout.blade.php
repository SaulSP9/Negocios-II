<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · HF Studios</title>
    <link rel="preload" href="{{ asset('fonts/inter-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @foreach (['editorial', 'site-navigation', 'purchase'] as $style)
        <link rel="stylesheet" href="{{ asset('css/'.$style.'.css') }}?v={{ filemtime(public_path('css/'.$style.'.css')) }}">
    @endforeach
    <script type="module" src="{{ asset('js/editorial/purchase.js') }}?v={{ filemtime(public_path('js/editorial/purchase.js')) }}"></script>
</head>
<body class="purchase-page" data-fallback="{{ asset(App\Support\Editorial::FALLBACK) }}">
    <a class="skip" href="#contenido">Ir al contenido</a>
    @hasSection('full-header')
        @include('partials.site-ticker')
        @include('partials.site-header', ['activeStoreView' => 'coleccion'])
        @include('partials.cart-dialog')
    @else
        <header class="purchase-head">
            <a class="text-link" href="@yield('back-url', '/catalogo')">← @yield('back-text', 'Volver a la tienda')</a>
            @include('partials.site-brand')
            <a class="text-link" href="/?vista=ayuda">Ayuda</a>
        </header>
    @endif
    <main id="contenido">@yield('content')</main>
    <footer class="purchase-footer"><span>HF STUDIOS.</span><a href="/?vista=ayuda">Atención al cliente</a></footer>
    <output class="toast" id="message" role="status" aria-live="polite"></output>
</body>
</html>
