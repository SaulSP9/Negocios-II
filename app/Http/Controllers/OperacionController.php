<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Impuesto;
use App\Models\Monedero;
use App\Models\Order;
use App\Models\Promocion;
use App\Services\CommercePricing;
use App\Services\WalletLedger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperacionController extends Controller
{
    public function promotions(Request $request, CommercePricing $pricing)
    {
        if (! $request->expectsJson()) {
            return view('editorial', ['initialView' => 'campana']);
        }
        if ($request->boolean('administrar')) {
            abort_unless($request->user()?->hasPermission('commerce.configure'), 403);

            return Promocion::with('categoria')->latest('id')->get();
        }

        return $pricing->promotions();
    }

    public function savePromotion(Request $request, ?Promocion $promocion = null)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:120', 'descripcion' => 'nullable|string|max:1500',
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id', Rule::prohibitedIf($request->filled('producto_id'))],
            'producto_id' => 'nullable|integer|exists:products,id',
            'tipo_descuento' => 'required|in:porcentaje,fijo',
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', $request->input('tipo_descuento') === 'porcentaje' ? 'max:100' : 'max:1000000'],
            'fecha_inicio' => 'required|date', 'fecha_fin' => 'required|date|after:fecha_inicio',
            'estado' => 'required|boolean',
        ]);
        $data += ['categoria_id' => null, 'producto_id' => null];
        if ($promocion) {
            $promocion->update($data);

            return $promocion;
        }

        return response()->json(Promocion::create($data), 201);
    }

    public function taxes(Request $request)
    {
        return Impuesto::query()
            ->when(! $request->user()?->hasPermission('commerce.configure'), fn ($query) => $query->where('estado', true))
            ->orderBy('id')->get();
    }

    public function saveTax(Request $request, ?Impuesto $impuesto = null)
    {
        $unique = Rule::unique('impuestos', 'nombre');
        if ($impuesto) {
            $unique->ignore($impuesto);
        }
        $data = $request->validate(['nombre' => ['required', 'string', 'max:120', $unique],
            'porcentaje' => 'required|numeric|decimal:0,2|between:0,100', 'estado' => 'required|boolean']);
        if ($impuesto) {
            $impuesto->update($data);

            return $impuesto;
        }

        return response()->json(Impuesto::create($data), 201);
    }

    public function wallets()
    {
        return Monedero::with('cliente:id,nombre,correo')->orderBy('id')->paginate(30);
    }

    public function credit(Request $request, Cliente $cliente, WalletLedger $ledger)
    {
        $data = $request->validate(['importe' => 'required|numeric|decimal:0,2|between:0.01,1000000']);
        $ledger->post($cliente->id, (int) round($data['importe'] * 100), 'ajuste', 'Abono administrativo', [
            'user_id' => $request->user()->id,
        ]);

        return Monedero::where('cliente_id', $cliente->id)->firstOrFail();
    }

    public function orders()
    {
        $page = Order::with(['items', 'user', 'pago', 'cargoEnMonedero'])->latest('id')->paginate(30);
        $page->through(fn ($order) => $order->presentation());

        return $page;
    }
}
