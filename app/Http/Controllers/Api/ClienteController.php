<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%')
                  ->orWhere('correo', 'like', '%' . $request->buscar . '%')
                  ->orWhere('empresa', 'like', '%' . $request->buscar . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('etapa_crm')) {
            $query->where('etapa_crm', $request->etapa_crm);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:clientes,correo',
            'telefono' => 'nullable|string|max:20',
            'empresa' => 'nullable|string|max:255',
            'estado' => 'in:activo,inactivo',
            'etapa_crm' => 'in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        $cliente = Cliente::create($validated);
        return response()->json($cliente, 201);
    }

    public function show($id)
    {
        $cliente = Cliente::with('interacciones.usuario')->findOrFail($id);
        return response()->json($cliente);
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'correo' => 'sometimes|email|unique:clientes,correo,' . $id,
            'telefono' => 'nullable|string|max:20',
            'empresa' => 'nullable|string|max:255',
            'estado' => 'in:activo,inactivo',
            'etapa_crm' => 'in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        $cliente->update($validated);
        return response()->json($cliente);
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();
        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }

    public function cambiarEtapa(Request $request, $id)
    {
        $request->validate([
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        $cliente = Cliente::findOrFail($id);
        $cliente->etapa_crm = $request->etapa_crm;
        $cliente->save();

        return response()->json($cliente);
    }
}