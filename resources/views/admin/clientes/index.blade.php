@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold m-0">CLIENTES</h1>
        <!-- Botón conectado para la creación de un nuevo cliente -->
        <a href="{{ route('clientes.create') }}" class="btn btn-dark text-uppercase fw-bold px-4 py-2">
            + NUEVO
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tarjetas de métricas superiores -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0">
                <small class="text-muted text-uppercase fw-bold">INGRESOS TOTALES</small>
                <h2 class="fw-bold m-0">$124,500</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0">
                <small class="text-muted text-uppercase fw-bold">PEDIDOS ACTIVOS</small>
                <h2 class="fw-bold m-0">{{ $totalPedidosActivos }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0">
                <small class="text-muted text-uppercase fw-bold">CLIENTES REGISTRADOS</small>
                <h2 class="fw-bold m-0">{{ $totalClientes }}</h2>
            </div>
        </div>
    </div>

    <!-- Tabla interactiva conectada a la base de datos -->
    <div class="card border-0 shadow-sm p-3">
        <table class="table table-hover align-middle m-0">
            <thead class="table-light">
                <tr>
                    <th>USUARIO / NOMBRE</th>
                    <th>EMAIL</th>
                    <th>TELÉFONO</th>
                    <th>ETAPA CRM</th>
                    <th>ESTADO</th>
                    <th class="text-end">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                    <tr>
                        <td class="fw-bold">{{ $cliente->nombre }}</td>
                        <td class="text-muted">{{ $cliente->correo }}</td>
                        <td>{{ $cliente->telefono ?? 'Sin registrar' }}</td>
                        <td>
                            <span class="badge bg-dark">{{ $cliente->etapa_crm }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $cliente->estado == 'activo' ? 'bg-success' : 'bg-secondary' }}">
                                {{ strtoupper($cliente->estado) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-sm btn-outline-dark fw-bold me-1">
                                HISTORIAL
                            </a>
                            <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn btn-sm btn-outline-secondary">
                                EDITAR
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No hay clientes registrados en el sistema. Haz clic en <strong>+ NUEVO</strong> para agregar uno.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($clientes, 'links'))
            <div class="mt-3">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection