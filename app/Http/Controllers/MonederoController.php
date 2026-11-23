<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Monedero;
use App\Models\MovimientoMonedero;
use App\Services\CommerceCart;
use App\Services\WalletLedger;
use Illuminate\Http\Request;

class MonederoController extends Controller
{
    public function own(Request $request, CommerceCart $carts)
    {
        return $this->history($carts->client($request->user()));
    }

    public function history(Cliente $cliente)
    {
        $wallet = Monedero::where('cliente_id', $cliente->id)->first();
        $movements = MovimientoMonedero::query()->where('monedero_id', $wallet?->id ?? 0)
            ->with('pedido:id,folio')->latest('id')->paginate(15);
        $movements->through(fn ($event) => [
            ...$event->only(['id', 'tipo', 'importe_cents', 'saldo_cents', 'metodo', 'concepto', 'created_at']),
            'folio' => $event->pedido?->folio,
        ]);

        return ['saldo_cents' => $wallet?->saldo_cents ?? 0, 'movimientos' => $movements];
    }

    public function recharge(Request $request, CommerceCart $carts, WalletLedger $ledger)
    {
        abort_unless(config('commerce.simulate_payments'), 403, 'Las recargas de demostración están desactivadas.');
        $data = $request->validate([
            'importe' => 'required|numeric|decimal:0,2|between:10,5000',
            'metodo' => 'required|in:tarjeta,paypal,transferencia',
            'request_key' => 'required|uuid',
        ]);
        $client = $carts->client($request->user());
        $event = $ledger->post($client->id, (int) round($data['importe'] * 100), 'recarga', 'Recarga de demostración', [
            'request_key' => $data['request_key'], 'metodo' => $data['metodo'], 'user_id' => $request->user()->id,
        ]);

        return response()->json($event, $event->wasRecentlyCreated ? 201 : 200);
    }

    public function adjust(Request $request, Cliente $cliente, WalletLedger $ledger)
    {
        $data = $request->validate([
            'importe' => 'required|numeric|decimal:0,2|between:0.01,1000000',
            'tipo' => 'required|in:abono,cargo', 'concepto' => 'required|string|min:3|max:200',
            'request_key' => 'required|uuid',
        ]);
        $amount = (int) round($data['importe'] * 100) * ($data['tipo'] === 'cargo' ? -1 : 1);

        return $ledger->post($cliente->id, $amount, 'ajuste', $data['concepto'], [
            'request_key' => $data['request_key'], 'user_id' => $request->user()->id,
        ]);
    }

    public function report(Request $request)
    {
        $groups = MovimientoMonedero::query()->selectRaw('tipo, COUNT(*) as operaciones, SUM(importe_cents) as importe_cents')
            ->groupBy('tipo')->orderBy('tipo')->get();
        if (! $request->boolean('csv')) {
            return $groups;
        }

        return response()->streamDownload(function () use ($groups) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Tipo', 'Operaciones', 'Importe MXN'], ',', '"', '');
            foreach ($groups as $row) {
                fputcsv($stream, [$row->tipo, $row->operaciones, number_format($row->importe_cents / 100, 2, '.', '')], ',', '"', '');
            }
            fclose($stream);
        }, 'movimientos-monedero.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
