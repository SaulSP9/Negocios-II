<?php

namespace App\Services;

use App\Models\Monedero;
use App\Models\MovimientoMonedero;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletLedger
{
    public function post(int $clientId, int $amount, string $type, string $concept, array $details = []): MovimientoMonedero
    {
        return DB::transaction(function () use ($clientId, $amount, $type, $concept, $details) {
            $wallet = Monedero::where('cliente_id', $clientId)->lockForUpdate()->first();
            if (! $wallet) {
                $wallet = Monedero::firstOrCreate(['cliente_id' => $clientId], ['saldo_cents' => 0]);
                $wallet = Monedero::lockForUpdate()->findOrFail($wallet->id);
            }
            if (isset($details['request_key'])) {
                $prior = $wallet->movimientos()->where('request_key', $details['request_key'])->first();
                if ($prior) {
                    abort_unless($prior->importe_cents === $amount && $prior->tipo === $type && $prior->concepto === $concept
                        && $prior->metodo === ($details['metodo'] ?? null) && $prior->user_id === ($details['user_id'] ?? null),
                        409, 'La referencia ya se utilizó con otros datos.');

                    return $prior;
                }
            }
            $balance = $wallet->saldo_cents + $amount;
            if ($balance < 0) {
                throw ValidationException::withMessages(['importe' => 'Saldo de monedero insuficiente.']);
            }
            if ($balance > 10000000000 && $type !== 'reembolso') {
                throw ValidationException::withMessages(['importe' => 'El monedero excedería su límite.']);
            }
            $wallet->update(['saldo_cents' => $balance]);

            return $wallet->movimientos()->create([
                ...$details, 'importe_cents' => $amount, 'saldo_cents' => $balance, 'tipo' => $type, 'concepto' => $concept,
            ]);
        }, 3);
    }
}
