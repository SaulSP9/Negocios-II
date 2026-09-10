<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Cliente - HF.ADMIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="{{ route('clientes.index') }}" class="btn btn-outline-dark mb-3">← Volver a Clientes</a>
    
    <div class="card p-4 mb-4 shadow-sm">
        <h3>{{ $cliente->nombre }}</h3>
        <p class="mb-1"><strong>Correo:</strong> {{ $cliente->correo }}</p>
        <p class="mb-1"><strong>Teléfono:</strong> {{ $cliente->telefono }}</p>
        <p class="mb-1"><strong>Empresa:</strong> {{ $cliente->empresa }}</p>

        <!-- Selector de Etapa CRM -->
        <form action="{{ route('clientes.updateEtapa', $cliente->id) }}" method="POST" class="mt-3 d-flex align-items-center gap-2">
            @csrf
            @method('PUT')
            <label class="fw-bold">Etapa CRM:</label>
            <select name="etapa_crm" class="form-select w-auto">
                <option value="Prospecto" {{ $cliente->etapa_crm == 'Prospecto' ? 'selected' : '' }}>Prospecto</option>
                <option value="Activo" {{ $cliente->etapa_crm == 'Activo' ? 'selected' : '' }}>Activo</option>
                <option value="Frecuente" {{ $cliente->etapa_crm == 'Frecuente' ? 'selected' : '' }}>Frecuente</option>
                <option value="Inactivo" {{ $cliente->etapa_crm == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
            <button type="submit" class="btn btn-dark">Actualizar Etapa</button>
        </form>
    </div>

    <!-- Formulario Registrar Interacción -->
    <div class="card p-4 mb-4 shadow-sm">
        <h5>Registrar Nueva Interacción</h5>
        <form action="{{ route('interacciones.store') }}" method="POST">
            @csrf
            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
            <div class="mb-3">
                <label class="form-label">Tipo de Interacción</label>
                <select name="tipo" class="form-select" required>
                    <option value="llamada">Llamada</option>
                    <option value="correo">Correo</option>
                    <option value="reunion">Reunión</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Interacción</button>
        </form>
    </div>

    <!-- Línea de Tiempo de Interacciones -->
    <div class="card p-4 shadow-sm">
        <h5>Línea de Tiempo de Interacciones</h5>
        <ul class="list-group list-group-flush mt-3">
            @forelse($cliente->interacciones as $interaccion)
                <li class="list-group-item">
                    <span class="badge bg-secondary text-uppercase">{{ $interaccion->tipo }}</span>
                    <small class="text-muted ms-2">{{ $interaccion->fecha }}</small>
                    <p class="mt-2 mb-1">{{ $interaccion->descripcion }}</p>
                    <small class="text-secondary">Atendido por: {{ $interaccion->usuario->name ?? 'Usuario' }}</small>
                </li>
            @empty
                <li class="list-group-item text-muted">No hay interacciones registradas para este cliente.</li>
            @endforelse
        </ul>
    </div>
</div>
</body>
</html>