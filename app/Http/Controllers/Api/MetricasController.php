<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MetricasController extends Controller
{
    public function index()
    {
        // 1. Total de clientes
        $totalClientes = Cliente::count();

        // 2. Clientes activos vs inactivos
        $clientesActivos = Cliente::where('estado', 'activo')->count();
        $clientesInactivos = Cliente::where('estado', 'inactivo')->count();

        // 3. Promedio de interacciones por cliente
        $promedioInteracciones = round(Cliente::withCount('interacciones')->get()->avg('interacciones_count') ?? 0, 1);

        // 4. Lista de clientes en riesgo (sin interacción en los últimos 30 días)
        $fechaLimite = Carbon::now()->subDays(30);
        $clientesEnRiesgo = Cliente::whereDoesntHave('interacciones', function ($query) use ($fechaLimite) {
            $query->where('fecha', '>=', $fechaLimite);
        })->get();

        return view('admin.dashboard', compact(
            'totalClientes',
            'clientesActivos',
            'clientesInactivos',
            'promedioInteracciones',
            'clientesEnRiesgo'
        ));
    }
}