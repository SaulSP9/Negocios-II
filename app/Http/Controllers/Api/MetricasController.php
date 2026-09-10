<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Interaccion;

class MetricasController extends Controller
{
    public function dashboard()
    {
        $totalClientes = Cliente::count();
        $totalInteracciones = Interaccion::count();
        $clientesRecientes = Cliente::latest()->take(5)->get();

        return view('admin.dashboard', compact('totalClientes', 'totalInteracciones', 'clientesRecientes'));
    }

    public function getMetricas()
    {
        return response()->json([
            'total_clientes' => Cliente::count(),
            'total_interacciones' => Interaccion::count(),
        ]);
    }
}