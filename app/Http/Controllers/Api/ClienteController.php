<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Muestra la lista general de clientes y métricas
     */
    public function index(Request $request)
    {
        $clientes = Cliente::withCount('interacciones')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $totalClientes = Cliente::count();
        $clientesActivos = Cliente::where('estado', 'activo')->count();
        $totalPedidosActivos = 45; // Dato estático o relación con modelos de pedidos

        if ($request->wantsJson()) {
            return response()->json($clientes);
        }

        return view('admin.clientes.index', compact('clientes', 'totalClientes', 'clientesActivos', 'totalPedidosActivos'));
    }

    /**
     * Formulario para crear un nuevo cliente
     */
    public function create()
    {
        return view('admin.clientes.create');
    }

    /**
     * Guarda un nuevo cliente en la base de datos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'    => 'required|string|max:255',
            'correo'    => 'required|email|unique:clientes,correo',
            'telefono'  => 'nullable|string|max:20',
            'empresa'   => 'nullable|string|max:255',
            'estado'    => 'required|in:activo,inactivo',
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        $cliente = Cliente::create($validated);

        if ($request->wantsJson()) {
            return response()->json($cliente, 201);
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado correctamente.');
    }

    /**
     * Muestra el detalle e historial de interacciones de un cliente
     */
    public function show(Request $request, $id)
    {
        $cliente = Cliente::with(['interacciones.usuario'])->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json($cliente);
        }

        return view('admin.clientes.show', compact('cliente'));
    }

    /**
     * Formulario para editar un cliente
     */
    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('admin.clientes.edit', compact('cliente'));
    }

    /**
     * Actualiza la información de un cliente
     */
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'nombre'    => 'required|string|max:255',
            'correo'    => 'required|email|unique:clientes,correo,' . $id,
            'telefono'  => 'nullable|string|max:20',
            'empresa'   => 'nullable|string|max:255',
            'estado'    => 'required|in:activo,inactivo',
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        $cliente->update($validated);

        if ($request->wantsJson()) {
            return response()->json($cliente);
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Elimina un cliente
     */
    public function destroy(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Cliente eliminado correctamente.']);
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    /**
     * Actualiza únicamente la etapa del CRM
     */
    public function updateEtapa(Request $request, $id)
    {
        $validated = $request->validate([
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        $cliente = Cliente::findOrFail($id);
        $cliente->update($validated);

        return response()->json(['message' => 'Etapa actualizada correctamente', 'cliente' => $cliente]);
    }
}