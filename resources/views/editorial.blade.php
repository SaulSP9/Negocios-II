@php
    use App\Support\Editorial;

    $initialView ??= 'coleccion';
    $management = request()->is('gestion/tienda');
    $requestedView = request()->query('vista');
    $viewAliases = ['catalogo' => 'coleccion', 'login' => 'cuenta', 'registro' => 'cuenta', 'perfil' => 'cuenta', 'admin' => 'operacion'];
    if (!$management && is_string($requestedView)) {
        $requestedView = $viewAliases[$requestedView] ?? $requestedView;
        if (isset(Editorial::SECTIONS[$requestedView]) || $requestedView === 'operacion') {
            $initialView = $requestedView;
        }
    }
    $fallback = asset(Editorial::FALLBACK);
    $operation = [
        'promociones' => ['Promociones', 'commerce.configure'],
        'impuestos' => ['Impuestos', 'commerce.configure'],
        'monederos' => ['Monederos', 'commerce.configure'],
        'pedidos' => ['Pedidos', 'commerce.orders'],
        'atencion' => ['Atención', 'commerce.support'],
    ];
    $operationInitial = $management
        ? array_key_first(array_filter($operation, fn (array $tab) => auth()->user()->hasPermission($tab[1])))
        : null;
    $demo = app()->environment('local', 'testing') && config('commerce.demo');
@endphp
<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Colección, lanzamientos y archivo de streetwear de HF Studios.">
    <title>HF Studios. · Original Design</title>
    <link rel="preload" href="{{ asset('fonts/inter-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/app.css')
    @endif
    <link rel="stylesheet" href="{{ asset('css/editorial.css') }}?v={{ filemtime(public_path('css/editorial.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/site-navigation.css') }}?v={{ filemtime(public_path('css/site-navigation.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/gestion.css') }}?v={{ filemtime(public_path('css/gestion.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/account.css') }}?v={{ filemtime(public_path('css/account.css')) }}">
    <script type="module" src="{{ asset('js/editorial/store.js') }}?v={{ filemtime(public_path('js/editorial/store.js')) }}"></script>
</head>
<body data-fallback="{{ $fallback }}"
      data-simulate="{{ config('commerce.simulate_payments') ? '1' : '0' }}">
    <a class="skip" href="#contenido">Ir al contenido</a>
    @if ($management)
        @include('partials.gestion-header', ['managementSection' => 'tienda', 'logoutId' => 'management-logout'])
        @include('partials.commerce-navigation')
    @else
        @include('partials.site-ticker')
        @include('partials.site-header', ['activeStoreView' => $initialView])
    @endif

    <main id="contenido">
        <section id="hero" class="hero" @if (!request()->is('/') || request()->query('vista')) hidden @endif aria-roledescription="carrusel" aria-label="Campañas de HF Studios">
            <div class="hero-slides">
                @foreach (Editorial::SLIDES as [$slug, $title, $label, $href])
                    <article class="hero-slide{{ $loop->first ? ' is-active' : '' }}" data-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}" @if (!$loop->first) inert @endif>
                        <img src="{{ asset(Editorial::slide($slug)) }}" alt="" width="1800" height="1000" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif onerror="this.onerror=null;this.src='{{ $fallback }}'">
                        <div class="hero-copy">
                            <p class="overline">{{ $loop->first ? 'REPOSICIÓN / COLECCIÓN 2026' : ($loop->last ? 'COLECCIÓN / BÁSICOS' : 'HF STUDIOS / LOOKBOOK') }}</p>
                            <p class="hero-title{{ $loop->first ? ' hero-title-stock' : '' }}">{{ $title }}</p>
                            <a class="button hero-link" href="{{ $href }}">{{ $label }} <span aria-hidden="true">↗</span></a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="hero-controls">
                <button type="button" data-slide-change="-1" aria-label="Campaña anterior">‹</button>
                <div class="hero-dots" aria-label="Elegir campaña">
                    @foreach (Editorial::SLIDES as [$slug, $title])
                        <button type="button" data-slide-index="{{ $loop->index }}" aria-label="{{ $title }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"><span></span></button>
                    @endforeach
                </div>
                <button type="button" data-slide-change="1" aria-label="Campaña siguiente">›</button>
                <button type="button" id="hero-pause" aria-label="Pausar carrusel" aria-pressed="false"></button>
            </div>
            <div class="hero-progress" aria-hidden="true"><i></i></div>
        </section>

        @foreach (Editorial::SECTIONS as $key => [$index, $title, $description])
            <section class="view {{ $key === 'cuenta' ? 'account-view' : 'shell' }}" data-view="{{ $key }}" @if ($key !== $initialView) hidden @endif>
                @if ($key !== 'cuenta')
                <header class="section-lead">
                    <div>
                        @if ($index)<p class="overline">{{ $index }}</p>@endif
                        <h1 tabindex="-1">{{ $title }}</h1>
                        <p>{{ $description }}</p>
                    </div>
                    <img src="{{ asset(Editorial::section($key)) }}" alt="{{ $index ? 'Fotografía editorial de '.Illuminate\Support\Str::after($index, ' / ') : '' }}" width="1150" height="640" loading="lazy" onerror="this.onerror=null;this.src='{{ $fallback }}'">
                </header>
                @endif

                @switch($key)
                    @case('coleccion')
                        <div class="catalog-tools">
                            <div class="chips" id="category-tabs" aria-label="Categorías"></div>
                            <form id="search-form" role="search">
                                <label class="sr-only" for="catalog-search">Buscar por nombre</label>
                                <input type="search" id="catalog-search" placeholder="Buscar por nombre" maxlength="120" autocomplete="off">
                                <button type="submit" class="button" aria-label="Buscar">↗</button>
                            </form>
                        </div>
                        <div class="catalog-options">
                            <label>Ordenar por <select id="catalog-sort">
                                <option value="coleccion">Colección</option><option value="nuevos">Novedades</option><option value="populares">Más vendidos</option>
                                <option value="precio-asc">Menor precio</option><option value="precio-desc">Mayor precio</option>
                            </select></label>
                            <label class="favorite-filter"><input type="checkbox" id="catalog-favorites"> Solo favoritos</label>
                        </div>
                        <p class="result-count" id="catalog-count" aria-live="polite"></p>
                        <div class="product-grid" id="catalog-products" aria-live="polite"></div>
                        @break
                    @case('novedades')
                        <div class="product-grid" id="new-arrival-products" aria-live="polite">
                            <p class="empty">Cargando novedades…</p>
                        </div>
                        <div class="actions new-arrivals-actions">
                            <a class="button" href="/catalogo?orden=nuevos">Ver catálogo completo ↗</a>
                        </div>
                        @break
                    @case('tienda')
                        <div id="families"></div>
                        @break
                    @case('campana')
                        <div class="promo-list" id="campaign-promos"></div>
                        <h2 class="subheading">EN PROMOCIÓN.</h2>
                        <div class="product-grid" id="campaign-products"></div>
                        <h2 class="subheading">PIEZAS DESTACADAS.</h2>
                        <div class="product-grid" id="campaign-featured"></div>
                        @break
                    @case('comunidad')
                        <div class="content-grid">
                            <form class="card form-stack" id="comment-form">
                                <h2>Comparte un comentario</h2>
                                <label>Comentario <textarea name="text" minlength="3" maxlength="2000" rows="5" required></textarea></label>
                                <button class="button primary">Publicar</button>
                            </form>
                            <div class="stack" id="comments"></div>
                        </div>
                        @break
                    @case('subasta')
                        <div class="content-grid" id="auction"></div>
                        @break
                    @case('mis-publicaciones')
                        <div class="content-grid" id="publications-gate">
                            <form id="publication-form" class="card form-stack" hidden>
                                <h2 id="publication-heading">Nueva publicación</h2>
                                <input type="hidden" name="id">
                                <label>Nombre <input name="name" required minlength="2" maxlength="120"></label>
                                <label>Precio MXN <input name="price" type="number" min="0.01" step=".01" required></label>
                                <label>URL de la foto <input name="image" type="url" required></label>
                                <label>Descripción <textarea name="description" rows="4" minlength="3" maxlength="3000" required></textarea></label>
                                <div class="actions"><button class="button primary">Guardar</button><button type="button" class="button" id="publication-reset">Nueva</button></div>
                            </form>
                            <div class="stack" id="publications"></div>
                        </div>
                        @break
                    @case('ayuda')
                        <div class="content-grid">
                            <div>
                                <p class="card" id="support-gate" hidden>Inicia sesión para crear un ticket. <a class="text-link" href="/?vista=cuenta">Ir a Mi cuenta ↗</a></p>
                                <form class="card form-stack" id="ticket-form" hidden>
                                    <h2>Nueva solicitud</h2>
                                    <label>Categoría <select name="categoria">
                                        @foreach (\App\Models\TicketAtencion::CATEGORIES as $ticketCategory)
                                            <option>{{ $ticketCategory }}</option>
                                        @endforeach
                                    </select></label>
                                    <label>Asunto <input name="asunto" required minlength="3" maxlength="150"></label>
                                    <label>Mensaje <textarea name="mensaje" minlength="5" maxlength="4000" rows="6" required></textarea></label>
                                    <button class="button primary">Crear ticket</button>
                                </form>
                            </div>
                            <section>
                                <h2 class="subheading compact">Historial de tickets</h2>
                                <form id="ticket-filter" class="ticket-filters" hidden>
                                    <label>Buscar ticket <input name="q" type="search" placeholder="Folio o asunto" maxlength="100"></label>
                                    <label>Estado <select name="estado"><option value="">Todos</option><option>Abierto</option><option>En proceso</option><option>Respondido</option><option>Cerrado</option></select></label>
                                    <button class="button">Filtrar</button>
                                </form>
                                <div class="stack" id="tickets" aria-live="polite"></div>
                            </section>
                        </div>
                        @break
                    @case('cuenta')
                        @include('partials.account')
                        @break
                @endswitch
            </section>
        @endforeach

        <section class="{{ $management ? 'management-content' : 'view shell' }}" data-view="operacion" @if ($initialView !== 'operacion') hidden @endif>
            @if ($management)
                <header class="management-commerce-heading">
                    <h1 tabindex="-1">Tienda y atención</h1>
                </header>
            @else
                <header class="section-lead operation-lead">
                    <div><h1 tabindex="-1">Operación comercial</h1></div>
                    @include('partials.gestion-menu', ['managementSection' => 'tienda'])
                </header>
                @include('partials.commerce-navigation')
            @endif
            <p id="operation-gate" class="card" hidden>Acceso reservado al personal. <a class="text-link" href="/?vista=cuenta">Iniciar sesión ↗</a></p>
            <div id="operation-content" hidden>
                @foreach (array_keys($operation) as $key)
                    <section id="operacion-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" tabindex="0" hidden></section>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="shell footer-inner">
            <a class="brand" href="/"><strong>HF STUDIOS.</strong><span>ORIGINAL DESIGN — 2026</span></a>
            <div class="footer-links">
                <a href="/?vista=ayuda">Ayuda</a>
                <a href="/?vista=cuenta">Mi cuenta</a>
                <a href="/gestion" data-staff-link hidden>Gestión empresarial ↗</a>
            </div>
            <form id="newsletter-form" class="newsletter">
                <label for="newsletter-email">Recibe los lanzamientos</label>
                <div><input type="email" name="email" id="newsletter-email" required placeholder="Tu correo electrónico"><button aria-label="Suscribirme" class="button">↗</button></div>
            </form>
        </div>
    </footer>

    @unless ($management)
        @include('partials.cart-dialog')
    @endunless
    <dialog id="password-dialog" class="dialog sheet-dialog" aria-labelledby="password-dialog-title">
        <button type="button" class="dialog-close" data-close aria-label="Cerrar">×</button>
        <form id="forgot-form" class="form-stack">
            <h2 id="password-dialog-title">Recuperar contraseña</h2>
            <label>Correo electrónico <input name="email" type="email" required autocomplete="email"></label>
            <button class="button primary">Enviar enlace</button>
        </form>
    </dialog>
    <dialog id="ticket-dialog" class="dialog sheet-dialog" aria-labelledby="ticket-title">
        <button type="button" class="dialog-close" data-close aria-label="Cerrar ticket">×</button>
        <div id="ticket-body"></div>
    </dialog>
    <output class="toast" id="message" role="status" aria-live="polite"></output>
    <noscript><p class="noscript">Activa JavaScript para explorar el catálogo y usar el carrito.</p></noscript>
</body>
</html>
