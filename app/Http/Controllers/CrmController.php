<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Http\Requests\InteraccionRequest;
use App\Models\Cliente;
use App\Models\Evaluacion;
use App\Models\Interaccion;
use App\Models\User;
use App\Services\CrmMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:120', 'estado' => 'nullable|in:activo,inactivo', 'etapa' => 'nullable|in:Prospecto,Activo,Frecuente,Inactivo', 'page' => 'nullable|integer|min:1']);

        return Cliente::withCount('interacciones')->withMax('interacciones', 'fecha')
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('nombre', 'like', "%$s%")->orWhere('correo', 'like', "%$s%")->orWhere('empresa', 'like', "%$s%")))
            ->when($filters['estado'] ?? null, fn ($q, $s) => $q->where('estado', $s))
            ->when($filters['etapa'] ?? null, fn ($q, $s) => $q->where('etapa_crm', $s))->latest('id')->paginate(15);
    }

    public function store(ClienteRequest $request)
    {
        return response()->json(Cliente::create($request->validated()), 201);
    }

    public function show(Cliente $cliente)
    {
        return $cliente->loadCount('interacciones')->load(['evaluaciones.usuario:id,name']);
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return $cliente->fresh();
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->noContent();
    }

    public function etapa(Request $request, Cliente $cliente)
    {
        $cliente->update($request->validate(['etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo']));

        return $cliente->fresh();
    }

    public function interaction(InteraccionRequest $request)
    {
        return response()->json(Interaccion::create([...$request->validated(), 'usuario_id' => $request->user()->id])->load('usuario:id,name'), 201);
    }

    public function history(Cliente $cliente)
    {
        return $cliente->interacciones()->with('usuario:id,name')->orderByDesc('fecha')->orderByDesc('id')->get();
    }

    public function activity(Request $request)
    {
        return Interaccion::with(['cliente:id,nombre', 'usuario:id,name'])->where('usuario_id', $request->user()->id)->orderByDesc('fecha')->paginate(15);
    }

    public function metrics(CrmMetrics $metrics)
    {
        return $metrics->summary();
    }

    public function evaluate(Request $request, Cliente $cliente)
    {
        $data = $request->validate(['puntuacion' => 'required|integer|between:1,5', 'observaciones' => 'nullable|string|max:2000']);

        return response()->json(Evaluacion::create([...$data, 'cliente_id' => $cliente->id, 'usuario_id' => $request->user()->id]), 201);
    }

    public function users()
    {
        return User::select('id', 'name', 'email', 'role')->orderBy('name')->get();
    }

    public function createUser(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128', 'role' => 'required|in:admin,usuario']);
        $user = new User($data);
        $user->role = $data['role'];
        $user->save();

        return response()->json($user, 201);
    }

    public function contacts()
    {
        return DB::table('contact_messages')->orderByDesc('id')->paginate(15);
    }

    public function handleContact(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $contact = DB::table('contact_messages')->where('id', $id)->lockForUpdate()->first();
            abort_unless($contact, 404);
            abort_if($contact->handled, 409, 'El mensaje ya fue registrado en el CRM.');
            $client = Cliente::firstOrCreate(['correo' => $contact->email], ['nombre' => $contact->name]);
            Interaccion::create(['cliente_id' => $client->id, 'usuario_id' => $request->user()->id, 'tipo' => 'correo', 'descripcion' => $contact->message, 'fecha' => $contact->created_at]);
            DB::table('contact_messages')->where('id', $id)->update(['handled' => true, 'updated_at' => now()]);

            return response()->json($client);
        });
    }
}
