<?php

namespace App\Http\Controllers;

use App\Models\TicketAtencion;
use App\Services\CommerceCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AtencionController extends Controller
{
    public function __construct(private readonly CommerceCart $carts) {}

    public function index(Request $request)
    {
        $filters = $request->validate(['estado' => 'nullable|in:Abierto,En proceso,Respondido,Cerrado', 'q' => 'nullable|string|max:100']);
        $query = TicketAtencion::latest('id');
        if ($request->user()->hasPermission('commerce.support')) {
            $query->with('cliente:id,nombre,correo');
        } else {
            $query->where('cliente_id', $this->carts->client($request->user())->id);
        }
        if ($state = $filters['estado'] ?? null) {
            $query->where('estado', $state);
        }
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('asunto', 'like', '%'.$search.'%');
                if (ctype_digit(ltrim($search, '#T-'))) {
                    $query->orWhere('id', (int) ltrim($search, '#T-'));
                }
            });
        }

        return $query->paginate(30);
    }

    public function show(Request $request, TicketAtencion $ticket)
    {
        abort_unless($request->user()->hasPermission('commerce.support')
            || $this->carts->client($request->user())->id === $ticket->cliente_id, 404);

        return $ticket->load(['eventos' => fn ($query) => $query->select(['id', 'ticket_id', 'estado', 'mensaje', 'created_at'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['asunto' => 'required|string|min:3|max:150', 'mensaje' => 'required|string|min:5|max:4000', 'categoria' => ['sometimes', Rule::in(TicketAtencion::CATEGORIES)]]);
        $client = $this->carts->client($request->user());
        $ticket = DB::transaction(function () use ($client, $request, $data) {
            $ticket = TicketAtencion::create([...$data, 'cliente_id' => $client->id, 'fecha' => now(), 'estado' => 'Abierto']);
            $client->interacciones()->create([
                'usuario_id' => $request->user()->id, 'tipo' => 'ticket',
                'descripcion' => 'Ticket #'.$ticket->id.' abierto: '.$ticket->asunto, 'fecha' => now(),
            ]);

            return $ticket;
        }, 3);

        return response()->json($ticket, 201);
    }

    public function answer(Request $request, TicketAtencion $ticket)
    {
        $data = $request->validate(['respuesta' => 'required_unless:estado,En proceso|nullable|string|min:3|max:4000', 'estado' => 'sometimes|in:En proceso,Respondido,Cerrado']);

        return DB::transaction(function () use ($request, $ticket, $data) {
            $ticket = TicketAtencion::lockForUpdate()->findOrFail($ticket->id);
            $state = $data['estado'] ?? 'Respondido';
            $message = $data['respuesta'] ?? null;
            if ($ticket->estado === $state && ($message === null || $ticket->respuesta === $message)) {
                return $ticket;
            }
            abort_if($ticket->estado === 'Cerrado', 409, 'El ticket está cerrado.');
            $ticket->update(['respuesta' => $message ?? $ticket->respuesta, 'estado' => $state]);
            $ticket->eventos()->create(['user_id' => $request->user()->id, 'estado' => $state, 'mensaje' => $message ?? 'Estamos revisando tu solicitud.']);
            $ticket->cliente->interacciones()->create([
                'usuario_id' => $request->user()->id, 'tipo' => 'ticket',
                'descripcion' => 'Ticket #'.$ticket->id.' ('.$state.'): '.($message ?? 'Revisión iniciada.'), 'fecha' => now(),
            ]);

            return $ticket;
        }, 3);
    }
}
