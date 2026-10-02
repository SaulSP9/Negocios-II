<?php

namespace App\Services;

use App\Models\Cliente;

class CrmMetrics
{
    public function summary(): array
    {
        $cutoff = now()->subDays(30);
        $clients = Cliente::withCount('interacciones')->withMax('interacciones', 'fecha')->get();
        $risk = $clients->filter(fn ($c) => $c->estado === 'activo' && (! $c->interacciones_max_fecha || $c->interacciones_max_fecha < $cutoff->toDateTimeString()));

        return ['total' => $clients->count(), 'activos' => $clients->where('estado', 'activo')->count(),
            'inactivos' => $clients->where('estado', 'inactivo')->count(), 'interacciones' => $clients->sum('interacciones_count'),
            'dias_sin_contacto' => 30, 'clientes_en_riesgo' => $risk->values(),
            'interacciones_por_cliente' => $clients->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'total' => $c->interacciones_count])->values(),
            'por_etapa' => collect(['Prospecto', 'Activo', 'Frecuente', 'Inactivo'])->map(fn ($s) => ['etapa' => $s, 'total' => $clients->where('etapa_crm', $s)->count()])->values()];
    }
}
