<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HF.ADMIN - Panel de Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { width: 260px; min-height: 100vh; background-color: #000; color: #fff; }
        .sidebar .nav-link { color: #aaa; font-weight: bold; padding: 12px 20px; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #fff; background-color: #222; }
        .card-stat { border: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- BARRA LATERAL (SIDEBAR) -->
    <div class="sidebar d-flex flex-column justify-content-between p-3">
        <div>
            <h3 class="fw-bold text-white mb-0">HF.ADMIN</h3>
            <small class="text-secondary d-block mb-4">HOLA, {{ strtoupper(Auth::user()->name ?? 'USUARIO') }}</small>
            
            <nav class="nav flex-column">
                <a class="nav-link active mb-2" href="{{ route('admin.dashboard') }}">📊 DASHBOARD</a>
                <a class="nav-link mb-2" href="{{ route('clientes.index') }}">👥 CLIENTES</a>
            </nav>
        </div>

        <!-- BOTONES DE ACCIÓN FUNCIONALES -->
        <div class="d-grid gap-2 mt-auto">
            <a href="{{ route('tienda') }}" class="btn btn-outline-light w-100 fw-bold py-2">
                VER TIENDA
            </a>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-danger w-100 fw-bold py-2">
                    CERRAR SESIÓN
                </button>
            </form>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL DEL DASHBOARD -->
    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold mb-4">Panel del Sistema CRM</h2>

        <!-- TARJETAS DE MÉTRICAS -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-stat bg-white p-3 border-start border-primary border-4">
                    <span class="text-muted fw-bold">TOTAL CLIENTES</span>
                    <h2 class="fw-bold text-dark mt-2 mb-0">{{ $totalClientes ?? 0 }}</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat bg-white p-3 border-start border-success border-4">
                    <span class="text-muted fw-bold">INTERACCIONES</span>
                    <h2 class="fw-bold text-dark mt-2 mb-0">{{ $totalInteracciones ?? 0 }}</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat bg-white p-3 border-start border-warning border-4">
                    <span class="text-muted fw-bold">ETAPAS ACTIVAS</span>
                    <h2 class="fw-bold text-dark mt-2 mb-0">4</h2>
                </div>
            </div>
        </div>

        <!-- TABLA DE ULTIMOS CLIENTES -->
        <div class="card card-stat bg-white p-4">
            <h5 class="fw-bold mb-3">Últimos Clientes Registrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Etapa CRM</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientesRecientes ?? [] as $cliente)
                            <tr>
                                <td>{{ $cliente->nombre }}</td>
                                <td>{{ $cliente->email }}</td>
                                <td><span class="badge bg-info text-dark">{{ $cliente->etapa }}</span></td>
                                <td><a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-sm btn-dark">Ver Historial</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No hay clientes registrados en el sistema.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>