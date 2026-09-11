<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Listar clientes con filtros de búsqueda, estado y etapa_crm.
     * Soporta respuesta Web (Blade) y API (JSON).
     */
    public function index(Request $request)
    {
        $query = Cliente::query();

        // Búsqueda por nombre, correo o empresa
        if ($request->filled('buscar')) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->buscar . '%')
                  ->orWhere('correo', 'like', '%' . $request->buscar . '%')
                  ->orWhere('empresa', 'like', '%' . $request->buscar . '%');
            });
        }

        // Filtro por estado (activo / inactivo)
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por Etapa CRM (Prospecto, Activo, Frecuente, Inactivo)
        if ($request->filled('etapa_crm')) {
            $query->where('etapa_crm', $request->etapa_crm);
        }

        // Ordenar por los más recientes por defecto
        $query->latest();

        // Si la petición es desde el navegador (Vista Blade)
        if (!$request->wantsJson()) {
            $clientes = $query->paginate(10)->appends($request->all());
            $totalClientes = Cliente::count();
            return view('clientes.index', compact('clientes', 'totalClientes'));
        }

        // Si es una llamada API (Postman / Fetch / Mobile)
        return response()->json($query->get(), 200);
    }

    /**
     * Formulario para crear un cliente (Web).
     */
    public function create()
    {
        return view('clientes.create');
    }

    /**
     * Guardar un nuevo cliente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'       => 'required|string|max:255',
            'correo'       => 'required|email|unique:clientes,correo',
            'telefono'     => 'nullable|string|max:20',
            'empresa'      => 'nullable|string|max:255',
            'fecha_registro' => 'nullable|date',
            'estado'       => 'nullable|in:activo,inactivo',
            'etapa_crm'    => 'nullable|in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        // Asignar valores por defecto según especificación
        $validated['fecha_registro'] = $validated['fecha_registro'] ?? now()->toDateString();
        $validated['estado'] = $validated['estado'] ?? 'activo';
        $validated['etapa_crm'] = $validated['etapa_crm'] ?? 'Prospecto';

        $cliente = Cliente::create($validated);

        if (!$request->wantsJson()) {
            return redirect()->route('clientes.index')->with('success', 'Cliente registrado con éxito.');
        }

        return response()->json($cliente, 201);
    }

    /**
     * Mostrar el detalle de un cliente con su historial de interacciones.
     */
    public function show(Request $request, $id)
    {
        $cliente = Cliente::with('interacciones.usuario')->findOrFail($id);

        if (!$request->wantsJson()) {
            return view('clientes.show', compact('cliente'));
        }

        return response()->json($cliente, 200);
    }

    /**
     * Formulario para editar un cliente (Web).
     */
    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('clientes.edit', compact('cliente'));
    }

    /**
     * Actualizar datos de un cliente.
     */
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'nombre'     => 'sometimes|string|max:255',
            'correo'     => 'sometimes|email|unique:clientes,correo,' . $id,
            'telefono'   => 'nullable|string|max:20',
            'empresa'    => 'nullable|string|max:255',
            'estado'     => 'sometimes|in:activo,inactivo',
            'etapa_crm'  => 'sometimes|in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        $cliente->update($validated);

        if (!$request->wantsJson()) {
            return redirect()->route('clientes.show', $cliente->id)->with('success', 'Información del cliente actualizada.');
        }

        return response()->json($cliente, 200);
    }

    /**
     * Cambiar específicamente la Etapa CRM (endpoint PUT /clientes/{id}/etapa).
     */
    public function cambiarEtapa(Request $request, $id)
    {
        $validated = $request->validate([
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo',
        ]);

        $cliente = Cliente::findOrFail($id);
        $cliente->update(['etapa_crm' => $validated['etapa_crm']]);

        if (!$request->wantsJson()) {
            return redirect()->back()->with('success', 'Etapa CRM actualizada a ' . $cliente->etapa_crm);
        }

        return response()->json($cliente, 200);
    }

    /**
     * Eliminar un cliente.
     */
    public function destroy(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        if (!$request->wantsJson()) {
            return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
        }

        return response()->json(['message' => 'Cliente eliminado correctamente'], 200);
    }
    /**
     * Actualiza la etapa CRM del cliente.
     */
    public function actualizarEtapa(Request $request, $id)
    {
        $request->validate([
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo'
        ]);

        $cliente = \App\Models\Cliente::findOrFail($id);
        $cliente->etapa_crm = $request->etapa_crm;
        $cliente->save();

        // Si la petición viene de la API, devolvemos JSON
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Etapa CRM actualizada correctamente',
                'cliente' => $cliente
            ]);
        }

        // Si es una petición web, redirigimos a la vista con un mensaje de éxito
        return redirect()->route('clientes.show', $cliente->id)
                         ->with('success', 'La etapa del cliente se ha actualizado correctamente.');
    }
}