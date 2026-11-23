<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HF Studios · Gestión · {{ strtoupper($module) }}</title>
    <link rel="stylesheet" href="/css/{{ $module }}.css?v={{ filemtime(public_path('css/'.$module.'.css')) }}">
    <link rel="stylesheet" href="/css/site-navigation.css?v={{ filemtime(public_path('css/site-navigation.css')) }}">
    <link rel="stylesheet" href="/css/gestion.css?v={{ filemtime(public_path('css/gestion.css')) }}">
    <script src="/js/http.js?v={{ filemtime(public_path('js/http.js')) }}" defer></script>
    <script src="/js/{{ $module }}.js?v={{ filemtime(public_path('js/'.$module.'.js')) }}" defer></script>
</head>
@php($u = auth()->user())
<body class="gestion-workspace" data-permissions='@json($u->permissions)' data-role="{{ $u->role }}" data-user-id="{{ $u->id }}">
@include('partials.gestion-header', ['managementSection' => ['crm' => 'clientes', 'scm' => 'inventario', 'erp' => 'operaciones'][$module]])
<nav class="management-subnav" aria-label="Secciones de {{ strtoupper($module) }}">
    @yield('module-navigation')
</nav>
@yield('content')
</body>
</html>
