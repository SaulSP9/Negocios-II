@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard CRM</h1>

    <!-- Tarjetas de Contadores -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Tarjeta 1 -->
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
            <h2 class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Total Clientes</h2>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ $totalClientes }}</p>
        </div>
        
        <!-- Tarjeta 2 -->
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
            <h2 class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Clientes Activos</h2>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ $clientesActivos }}</p>
        </div>
        
        <!-- Tarjeta 3 -->
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-gray-400">
            <h2 class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Clientes Inactivos</h2>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ $clientesInactivos }}</p>
        </div>
        
        <!-- Tarjeta 4 -->
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-purple-500">
            <h2 class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Promedio Interacciones</h2>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ $promedioInteracciones }}</p>
        </div>
    </div>

    <!-- Sección de Gráfica y Tabla -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Gráfica de Pastel (Chart.js) -->
        <div class="bg-white p-6 rounded-lg shadow-md lg:col-span-1">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Estado de Clientes</h3>
            <div class="relative h-64">
                <canvas id="clientesChart"></canvas>
            </div>
        </div>

        <!-- Tabla Clientes en Riesgo -->
        <div class="bg-white p-6 rounded-lg shadow-md lg:col-span-2">
            <h3 class="text-lg font-bold text-red-600 mb-4 border-b pb-2">
                <svg class="w-5 h-5 inline-block mr-1 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Clientes en Riesgo (+30 días)
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm whitespace-nowrap">
                    <thead class="uppercase tracking-wider border-b-2 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Teléfono</th>
                            <th class="px-6 py-3">Etapa CRM</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        @forelse($clientesEnRiesgo as $cliente)
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-6 py-4">{{ $cliente->id }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-800">{{ $cliente->nombre }}</td>
                                <td class="px-6 py-4">{{ $cliente->telefono }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-bold text-white uppercase 
                                        {{ $cliente->etapa_crm === 'Prospecto' ? 'bg-blue-500' : '' }}
                                        {{ $cliente->etapa_crm === 'Activo' ? 'bg-green-500' : '' }}
                                        {{ $cliente->etapa_crm === 'Frecuente' ? 'bg-purple-500' : '' }}
                                        {{ $cliente->etapa_crm === 'Inactivo' ? 'bg-gray-500' : '' }}">
                                        {{ $cliente->etapa_crm }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    No hay clientes en riesgo en este momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Script para inicializar Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('clientesChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Activos', 'Inactivos'],
                datasets: [{
                    data: [{{ $clientesActivos }}, {{ $clientesInactivos }}],
                    backgroundColor: ['#10B981', '#9CA3AF'], // Colores Tailwind: Emerald-500 y Gray-400
                    hoverBackgroundColor: ['#059669', '#6B7280'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    });
</script>
@endsection