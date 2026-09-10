<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HF.ADMIN - Clientes</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Helvetica Neue', Arial, sans-serif; }
        body { background-color: #f8f9fa; color: #000; display: flex; min-height: 100vh; }
        
        /* Sidebar fijado con z-index alto para garantizar clics */
        .sidebar { width: 250px; background: #000; color: #fff; padding: 30px 20px; display: flex; flex-direction: column; justify-content: space-between; position: fixed; top: 0; bottom: 0; left: 0; z-index: 1000; }
        .sidebar .brand { font-size: 22px; font-weight: 900; letter-spacing: -1px; text-transform: uppercase; margin-bottom: 5px; }
        .sidebar .subbrand { font-size: 10px; color: #888; font-weight: 700; text-transform: uppercase; margin-bottom: 30px; letter-spacing: 1px; }
        
        .nav-menu { display: flex; flex-direction: column; gap: 5px; }
        .nav-link { display: flex; align-items: center; gap: 10px; color: #aaa; text-decoration: none; padding: 12px 15px; font-weight: 800; font-size: 11px; text-transform: uppercase; border-radius: 4px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #fff; background: #1a1a1a; }
        
        .sidebar-bottom { display: flex; flex-direction: column; gap: 10px; margin-top: auto; padding-top: 20px; }
        .btn-tienda { display: block; width: 100%; text-align: center; border: 1px solid #fff; color: #fff; background: transparent; padding: 10px; font-weight: 800; font-size: 11px; text-transform: uppercase; text-decoration: none; cursor: pointer; }
        .btn-tienda:hover { background: #fff; color: #000; }
        .btn-logout { width: 100%; border: none; color: #fff; background: #ff0000; padding: 10px; font-weight: 800; font-size: 11px; text-transform: uppercase; cursor: pointer; }
        .btn-logout:hover { background: #cc0000; }

        /* Contenido desplazado a la derecha para no encimarse */
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); z-index: 1; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 32px; font-weight: 900; text-transform: uppercase; letter-spacing: -1px; }
        .btn-nuevo { background: #000; color: #fff; padding: 12px 24px; text-decoration: none; font-weight: 800; font-size: 12px; text-transform: uppercase; display: inline-block; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; border: 1px solid #e5e5e5; padding: 25px; }
        .stat-label { font-size: 10px; font-weight: 800; color: #666; text-transform: uppercase; margin-bottom: 10px; display: block; }
        .stat-value { font-size: 36px; font-weight: 900; }

        /* Filtros y Buscador */
        .filter-container { background: #fff; border: 1px solid #e5e5e5; padding: 15px; margin-bottom: 25px; display: flex; gap: 15px; align-items: center; }
        .filter-input, .filter-select { padding: 10px; border: 1px solid #e5e5e5; font-size: 12px; font-weight: 700; outline: none; }
        .filter-input { flex: 1; }
        .filter-btn { background: #000; color: #fff; border: none; padding: 10px 20px; font-weight: 800; font-size: 11px; text-transform: uppercase; cursor: pointer; }

        .table-container { background: #fff; border: 1px solid #e5e5e5; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #fafafa; padding: 15px 20px; font-size: 10px; font-weight: 800; color: #666; text-transform: uppercase; border-bottom: 1px solid #e5e5e5; }
        td { padding: 18px 20px; font-size: 12px; font-weight: 700; border-bottom: 1px solid #e5e5e5; }
        
        /* Badges para CRM */
        .badge { display: inline-block; padding: 4px 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .badge-activo { background: #e6f4ea; color: #137333; }
        .badge-prospecto { background: #fef7e0; color: #b06000; }
        .badge-frecuente { background: #e8f0fe; color: #1a73e8; }
        .badge-inactivo { background: #fce8e6; color: #c5221f; }
        
        .link-action { color: #000; text-decoration: underline; font-weight: 800; }
        .alert-flash { background: #000; color: #fff; padding: 12px 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 20px; }
    </style>
</head>
<body>

    <!-- MENÚ LATERAL -->
    <aside class="sidebar">
        <div>
            <div class="brand">HF.ADMIN</div>
            <div class="subbrand">HOLA, USUARIO</div>

            <nav class="nav-menu">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">📊 DASHBOARD</a>
                <a href="{{ route('clientes.index') }}" class="nav-link active">👥 CLIENTES</a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <a href="/" class="btn-tienda">VER TIENDA</a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">CERRAR SESIÓN</button>
            </form>
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        @if(session('success'))
            <div class="alert-flash">{{ session('success') }}</div>
        @endif

        <div class="header">
            <h1>CLIENTES Y PEDIDOS</h1>
            <a href="{{ route('clientes.create') }}" class="btn-nuevo">+ NUEVO CLIENTE</a>
        </div>

        <!-- TARJETAS DE MÉTRICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">INGRESOS TOTALES</span>
                <div class="stat-value">$124,500</div>
            </div>
            <div class="stat-card">
                <span class="stat-label">PEDIDOS ACTIVOS</span>
                <div class="stat-value">45</div>
            </div>
            <div class="stat-card">
                <span class="stat-label">CLIENTES REGISTRADOS</span>
                <div class="stat-value">{{ $totalClientes ?? count($clientes) }}</div>
            </div>
        </div>

        <!-- FORMULARIO DE FILTROS Y BÚSQUEDA -->
        <form method="GET" action="{{ route('clientes.index') }}" class="filter-container">
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="BUSCAR POR NOMBRE, CORREO O EMPRESA..." class="filter-input">
            
            <select name="etapa_crm" class="filter-select">
                <option value="">TODAS LAS ETAPAS CRM</option>
                <option value="Prospecto" {{ request('etapa_crm') == 'Prospecto' ? 'selected' : '' }}>PROSPECTO</option>
                <option value="Activo" {{ request('etapa_crm') == 'Activo' ? 'selected' : '' }}>ACTIVO</option>
                <option value="Frecuente" {{ request('etapa_crm') == 'Frecuente' ? 'selected' : '' }}>FRECUENTE</option>
                <option value="Inactivo" {{ request('etapa_crm') == 'Inactivo' ? 'selected' : '' }}>INACTIVO</option>
            </select>

            <button type="submit" class="filter-btn">FILTRAR</button>
        </form>

        <!-- TABLA DINÁMICA DE CLIENTES -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID CLIENTE</th>
                        <th>NOMBRE</th>
                        <th>CORREO</th>
                        <th>ETAPA CRM</th>
                        <th>FECHA REGISTRO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                    <tr>
                        <td><strong>HF-{{ $cliente->id }}</strong></td>
                        <td>{{ strtoupper($cliente->nombre) }}</td>
                        <td>{{ $cliente->correo }}</td>
                        <td>
                            @php
                                $badgeClass = match(strtolower($cliente->etapa_crm)) {
                                    'prospecto' => 'badge-prospecto',
                                    'activo'    => 'badge-activo',
                                    'frecuente' => 'badge-frecuente',
                                    default     => 'badge-inactivo',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ strtoupper($cliente->etapa_crm ?? 'PROSPECTO') }}
                            </span>
                        </td>
                        <td>{{ $cliente->created_at ? $cliente->created_at->format('d/m/Y') : ($cliente->fecha_registro ?? 'N/A') }}</td>
                        <td>
                            <a href="{{ route('clientes.show', $cliente->id) }}" class="link-action">VER HISTORIAL</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #888; padding: 30px;">
                            No se encontraron clientes registrados con ese criterio.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($clientes, 'links'))
            <div style="margin-top: 20px;">
                {{ $clientes->links() }}
            </div>
        @endif
    </main>

</body>
</html>