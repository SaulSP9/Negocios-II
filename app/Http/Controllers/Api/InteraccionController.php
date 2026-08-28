<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interaccion;
use App\Models\Cliente;
use Illuminate\Http\Request;

class InteraccionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tipo' => 'required|in:llamada,correo,reunion',
            'descripcion' => 'required|string',
        ]);

        $interaccion = Interaccion::create([
            'cliente_id' => $validated['cliente_id'],
            'usuario_id' => \Illuminate\Support\Facades\Auth::id() ?? 1,
            'tipo' => $validated['tipo'],
            'descripcion' => $validated['descripcion'],
            'fecha' => now(),
        ]);

        return response()->json($interaccion, 201);
    }

    public function porCliente($clienteId)
    {
        $cliente = Cliente::findOrFail($clienteId);
        $interacciones = $cliente->interacciones()->with('usuario')->orderBy('fecha', 'desc')->get();

        return response()->json($interacciones);
    }
}