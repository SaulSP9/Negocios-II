@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Mi Actividad</h1>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Historial de Interacciones Personales
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Detalle de todas las acciones que has registrado con clientes en el sistema.
            </p>
        </div>
        <div class="border-t border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($interacciones as $interaccion)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($interaccion->fecha)->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                <a href="{{ route('clientes.show', $interaccion->cliente_id) }}" class="hover:underline">
                                    {{ $interaccion->cliente->nombre ?? 'Cliente Eliminado' }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $interaccion->tipo == 'llamada' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $interaccion->tipo == 'correo' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $interaccion->tipo == 'reunion' ? 'bg-purple-100 text-purple-800' : '' }}">
                                    {{ ucfirst($interaccion->tipo) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $interaccion->descripcion }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 text-center">
                                Aún no tienes interacciones registradas. Empieza creando una desde el perfil de un cliente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection