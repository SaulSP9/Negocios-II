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
<!-- Sección de Etapa CRM -->
<div class="bg-white shadow overflow-hidden sm:rounded-lg mt-6 p-6 border-l-4 
    {{ $cliente->etapa_crm === 'Prospecto' ? 'border-blue-500' : '' }}
    {{ $cliente->etapa_crm === 'Activo' ? 'border-green-500' : '' }}
    {{ $cliente->etapa_crm === 'Frecuente' ? 'border-purple-500' : '' }}
    {{ $cliente->etapa_crm === 'Inactivo' ? 'border-gray-500' : '' }}">
    
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Clasificación de Cliente
            </h3>
            <div class="mt-2 flex items-center text-sm text-gray-500">
                <span class="mr-2 font-semibold text-gray-700">Etapa Actual:</span>
                
                <!-- Badge Dinámico -->
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full uppercase tracking-wide text-white
                    {{ $cliente->etapa_crm === 'Prospecto' ? 'bg-blue-500' : '' }}
                    {{ $cliente->etapa_crm === 'Activo' ? 'bg-green-500' : '' }}
                    {{ $cliente->etapa_crm === 'Frecuente' ? 'bg-purple-500' : '' }}
                    {{ $cliente->etapa_crm === 'Inactivo' ? 'bg-gray-500' : '' }}">
                    {{ $cliente->etapa_crm }}
                </span>
            </div>
        </div>

        <!-- Formulario de Actualización -->
        <div>
            <form action="{{ route('clientes.etapa', $cliente->id) }}" method="POST" class="flex items-center space-x-3">
                @csrf
                @method('PUT')
                
                <div>
                    <label for="etapa_crm" class="sr-only">Cambiar Etapa</label>
                    <select id="etapa_crm" name="etapa_crm" required class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                        <option value="Prospecto" {{ $cliente->etapa_crm == 'Prospecto' ? 'selected' : '' }}>Prospecto</option>
                        <option value="Activo" {{ $cliente->etapa_crm == 'Activo' ? 'selected' : '' }}>Activo</option>
                        <option value="Frecuente" {{ $cliente->etapa_crm == 'Frecuente' ? 'selected' : '' }}>Frecuente</option>
                        <option value="Inactivo" {{ $cliente->etapa_crm == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
                
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    Guardar
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Opcional: Mensaje de éxito si se acaba de actualizar -->
@if(session('success'))
    <div class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
@endif
<!-- Historial de Interacciones y Formulario -->
<div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Formulario para nueva interacción -->
    <div class="bg-white p-6 shadow sm:rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Nueva Interacción</h3>
        <form action="{{ route('interacciones.store') }}" method="POST">
            @csrf
            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
            
            <div class="mb-4">
                <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de Interacción</label>
                <select id="tipo" name="tipo" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="llamada">Llamada</option>
                    <option value="correo">Correo</option>
                    <option value="reunion">Reunión</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="fecha" class="block text-sm font-medium text-gray-700">Fecha</label>
                <!-- Asigna la fecha actual por defecto -->
                <input type="date" name="fecha" id="fecha" value="{{ date('Y-m-d') }}" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" required class="mt-1 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Guardar Interacción
                </button>
            </div>
        </form>
    </div>

    <!-- Línea de Tiempo (Timeline) -->
    <div class="bg-white p-6 shadow sm:rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Historial del Cliente</h3>
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                <!-- Iteramos sobre las interacciones, las más recientes primero -->
                @forelse($cliente->interacciones()->orderBy('fecha', 'desc')->get() as $interaccion)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white 
                                        {{ $interaccion->tipo == 'llamada' ? 'bg-green-500' : '' }}
                                        {{ $interaccion->tipo == 'correo' ? 'bg-blue-500' : '' }}
                                        {{ $interaccion->tipo == 'reunion' ? 'bg-purple-500' : '' }}
                                    ">
                                        @if($interaccion->tipo == 'llamada')
                                            <!-- Icono Teléfono -->
                                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                        @elseif($interaccion->tipo == 'correo')
                                            <!-- Icono Correo -->
                                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        @else
                                            <!-- Icono Reunión -->
                                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                        @endif
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            {{ ucfirst($interaccion->tipo) }} registrada por 
                                            <span class="font-medium text-gray-900">{{ $interaccion->usuario->name ?? 'Usuario Desconocido' }}</span>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-700">{{ $interaccion->descripcion }}</p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                        <time datetime="{{ $interaccion->fecha }}">{{ \Carbon\Carbon::parse($interaccion->fecha)->format('d/m/Y') }}</time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-gray-500 text-sm">No hay interacciones registradas aún.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
</body>
</html>