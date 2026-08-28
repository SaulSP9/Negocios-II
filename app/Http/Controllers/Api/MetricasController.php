<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Carbon\Carbon;

class MetricasController extends Controller
{
    public function index()
    {
        $totalClientes = Cliente::count();
        $activos = Cliente::where('estado', 'activo')->count();
        $inactivos = Cliente::where('estado', 'inactivo')->count();

        $interaccionesPorCliente = Cliente::withCount('interacciones')
            ->get(['id', 'nombre', 'interacciones_count']);

        $haceUnMes = Carbon::now()->subDays(30);
        $clientesEnRiesgo = Cliente::whereDoesntHave('interacciones', function ($query) use ($haceUnMes) {
            $query->where('fecha', '>=', $haceUnMes);
        })->get(['id', 'nombre', 'correo', 'etapa_crm']);

        return response()->json([
            'total_clientes' => $totalClientes,
            'activos_vs_inactivos' => [
                'activos' => $activos,
                'inactivos' => $inactivos,
            ],
            'interacciones_por_cliente' => $interaccionesPorCliente,
            'clientes_en_riesgo' => $clientesEnRiesgo,
        ]);
    }
}