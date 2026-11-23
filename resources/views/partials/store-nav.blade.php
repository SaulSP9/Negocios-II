@php
    $nav = ['coleccion' => 'Colección', 'campana' => 'Campaña', 'comunidad' => 'Comunidad', 'subasta' => 'Subasta',
        'tienda' => 'Tienda completa', 'mis-publicaciones' => 'Mis publicaciones', 'ayuda' => 'Ayuda', 'cuenta' => 'Mi cuenta'];
@endphp
<nav aria-label="Tienda" class="site-nav">
    @foreach ($nav as $view => $label)
        <a href="{{ $view === 'coleccion' ? '/' : '/?vista='.$view }}" data-view-link="{{ $view }}" @if (($activeStoreView ?? null) === $view) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
