<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InteraccionController extends Controller
{
    /**
     * Registrar una nueva interacción para un cliente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id'  => 'required|exists:clientes,id',
            'tipo'        => 'required|in:llamada,correo,reunion',
            'descripcion' => 'required|string',
        ]);

        $validated['usuario_id'] = Auth::id() ?? 1;

        $interaccion = Interaccion::create($validated);

        if ($request->wantsJson()) {
            return response()->json($interaccion, 201);
        }

        return back()->with('success', 'Interacción añadida al historial correctamente.');
    }

    /**
     * Obtener interacciones asociadas a un cliente (JSON API)
     */
    public function getByCliente($cliente_id)
    {
        $interacciones = Interaccion::where('cliente_id', $cliente_id)
            ->with('usuario')
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($interacciones);
    }

    /**
     * Obtener historial registrado por el usuario autenticado
     */
    public function byCliente(Request $request)
    {
        $userId = Auth::id() ?? 1;
        $interacciones = Interaccion::where('usuario_id', $userId)
            ->with('cliente')
            ->orderBy('fecha', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json($interacciones);
        }

        return view('admin.mi_actividad', compact('interacciones'));
    }
}