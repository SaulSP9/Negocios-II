<header class="site-head management-head">
    @include('partials.site-brand', ['brandUrl' => '/gestion', 'brandSubtitle' => 'GESTIÓN EMPRESARIAL'])
    @include('partials.gestion-menu')
    <div class="management-account">
        <span>{{ auth()->user()->name }} · {{ auth()->user()->role_label }}</span>
        <button class="management-logout" id="{{ $logoutId ?? 'logout' }}" type="button">Cerrar sesión</button>
    </div>
</header>
