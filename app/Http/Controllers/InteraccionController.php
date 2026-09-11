<?php


namespace App\Http\Controllers;

use App\Models\Interaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InteraccionController extends Controller
{
    /**
     * Guarda una nueva interacción desde la vista del cliente.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tipo' => 'required|in:llamada,correo,reunion',
            'descripcion' => 'required|string',
            'fecha' => 'required|date',
        ]);

        Interaccion::create([
            'cliente_id' => $request->cliente_id,
            'usuario_id' => Auth::id(),
            'tipo' => $request->tipo,
            'descripcion' => $request->descripcion,
            'fecha' => $request->fecha,
        ]);

        return back()->with('success', 'Interacción registrada con éxito.');
    }

    /**
     * Muestra la vista de "Mi Actividad" para el usuario logueado.
     */
    public function miActividad()
    {
        $interacciones = Interaccion::with('cliente')
                            ->where('usuario_id', Auth::id())
                            ->orderBy('fecha', 'desc')
                            ->get();

        return view('admin.mi_actividad', compact('interacciones'));
    }
}
