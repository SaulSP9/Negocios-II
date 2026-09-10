@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <a href="{{ route('clientes.index') }}" class="btn btn-outline-dark fw-bold">← Volver a Clientes</a>
    </div>

    <!-- Detalle del Cliente -->
    <div class="card p-4 mb-4 shadow-sm border-0">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold m-0">{{ $cliente->nombre }}</h2>
                <p class="text-muted mb-0">{{ $cliente->correo }} | {{ $cliente->telefono ?? 'Sin Teléfono' }}</p>
            </div>
            <div>
                <span class="badge bg-dark fs-6">{{ $cliente->etapa_crm }}</span>
                <span class="badge {{ $cliente->estado == 'activo' ? 'bg-success' : 'bg-secondary' }} fs-6">
                    {{ strtoupper($cliente->estado) }}
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulario para agregar una nueva interacción -->
        <div class="col-md-5 mb-4">
            <div class="card p-4 shadow-sm border-0">
                <h5 class="fw-bold mb-3">REGISTRAR INTERACCIÓN</h5>

                <form action="{{ route('interacciones.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Interacción</label>
                        <select name="tipo" class="form-select" required>
                            <option value="llamada">Llamada</option>
                            <option value="correo">Correo Electrónico</option>
                            <option value="reunion">Reunión</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción / Notas</label>
                        <textarea name="descripcion" class="form-control" rows="4" required placeholder="Escribe los detalles del seguimiento..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 fw-bold">AGREGAR AL HISTORIAL</button>
                </form>
            </div>
        </div>

        <!-- Línea de tiempo del Historial -->
        <div class="col-md-7">
            <div class="card p-4 shadow-sm border-0">
                <h5 class="fw-bold mb-3">HISTORIAL DE ACTIVIDAD</h5>

                <ul class="list-group list-group-flush">
                    @forelse($cliente->interacciones as $interaccion)
                        <li class="list-group-item px-0 py-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-dark text-uppercase">{{ $interaccion->tipo }}</span>
                                <small class="text-muted">{{ $interaccion->fecha ?? $interaccion->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            <p class="m-0 text-secondary">{{ $interaccion->descripcion }}</p>
                            <small class="text-muted">Registrado por: {{ $interaccion->usuario->name ?? 'Usuario' }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">
                            Este cliente aún no tiene interacciones registradas en su historial.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection