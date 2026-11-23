@php
    $managementUser = auth()->user();
    $sections = [
        'clientes' => ['Clientes · CRM', ['crm']],
        'inventario' => ['Inventario · SCM', ['scm.read']],
        'operaciones' => ['Órdenes · ERP', ['erp.read']],
        'tienda' => ['Tienda y atención', ['commerce.configure', 'commerce.orders', 'commerce.support']],
    ];
@endphp
<nav class="site-nav gestion-menu" aria-label="Menú de gestión">
    @foreach ($sections as $slug => [$label, $permissions])
        @if ($managementUser && collect($permissions)->contains(fn ($permission) => $managementUser->hasPermission($permission)))
            <a href="/gestion/{{ $slug }}" @if (($managementSection ?? '') === $slug) aria-current="page" @endif>{{ $label }}</a>
        @endif
    @endforeach
    <a href="/">Ir a la tienda</a>
</nav>
