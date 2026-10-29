<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Orden;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ErpOrders
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function create(array $data, User $actor, bool $process): Orden
    {
        return DB::transaction(function () use ($data, $actor, $process) {
            // Serializa reintentos de una misma cuenta antes de consultar la clave única.
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $rows = collect($data['productos'])->sortBy('producto_id')->values();
            $hash = hash('sha256', json_encode(['cliente_id' => (int) $data['cliente_id'], 'notas' => $data['notas'] ?? '',
                'productos' => $rows->map(fn ($r) => ['producto_id' => (int) $r['producto_id'], 'cantidad' => (int) $r['cantidad'], 'precio_unitario_cents' => isset($r['precio_unitario_cents']) ? (int) $r['precio_unitario_cents'] : null])->all()], JSON_THROW_ON_ERROR));
            $order = Orden::where('request_key', $data['request_key'])->lockForUpdate()->first();
            if ($order) {
                abort_unless($order->usuario_id === $actor->id && hash_equals($order->payload_hash, $hash), 409, 'Esta clave ya pertenece a otra operación. Inicia una nueva orden.');

                return $process ? $this->process($order->id, $actor) : $order;
            }
            $client = Cliente::whereKey($data['cliente_id'])->lockForUpdate()->firstOrFail();
            abort_unless($client->estado === 'activo', 422, 'El cliente está inactivo en el CRM.');
            $products = Product::whereIn('id', $rows->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $order = Orden::create(['cliente_id' => $client->id, 'usuario_id' => $actor->id, 'fecha' => now(), 'estado' => 'pendiente',
                'total_cents' => 0, 'request_key' => $data['request_key'], 'payload_hash' => $hash, 'notas' => $data['notas'] ?? null]);
            $total = 0;
            foreach ($rows as $row) {
                $product = $products->get($row['producto_id']);
                abort_unless($product, 422, 'Un producto ya no está disponible. Actualiza el catálogo.');
                if (isset($row['precio_unitario_cents']) && (int) $row['precio_unitario_cents'] !== (int) $product->price_cents) {
                    throw ValidationException::withMessages(['productos' => 'El precio de '.$product->name.' cambió. Actualiza el catálogo y revisa el total.']);
                }
                $subtotal = (int) $product->price_cents * (int) $row['cantidad'];
                $order->productos()->create(['producto_id' => $product->id, 'nombre' => $product->name, 'cantidad' => (int) $row['cantidad'],
                    'precio_unitario_cents' => $product->price_cents, 'subtotal_cents' => $subtotal]);
                $total += $subtotal;
            }
            $order->update(['folio' => 'OR-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT), 'total_cents' => $total]);
            $this->history($order, $actor, 'creada', 'Orden creada con cliente CRM y precios del catálogo SCM.');

            return $process ? $this->process($order->id, $actor) : $order;
        }, 3);
    }

    public function process(int $id, User $actor): Orden
    {
        return DB::transaction(function () use ($id, $actor) {
            $order = Orden::lockForUpdate()->findOrFail($id);
            if (in_array($order->estado, ['procesada', 'completada'], true)) {
                return $order;
            }
            abort_unless($order->estado === 'pendiente', 409, 'Una orden cancelada no puede procesarse.');
            $client = Cliente::whereKey($order->cliente_id)->lockForUpdate()->firstOrFail();
            abort_unless($client->estado === 'activo', 422, 'Reactiva al cliente en el CRM antes de procesar la orden.');
            $lines = $order->productos()->orderBy('producto_id')->get();
            abort_if($lines->isEmpty(), 422, 'Agrega al menos un producto.');
            $products = Product::whereIn('id', $lines->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($lines as $line) {
                $product = $products->get($line->producto_id);
                if (! $product || $product->stock < $line->cantidad) {
                    throw ValidationException::withMessages(['productos' => 'Stock insuficiente o producto archivado: '.$line->nombre.'. Disponible: '.($product?->stock ?? 0).'.']);
                }
            }
            foreach ($lines as $line) {
                $this->inventory->move($line->producto_id, 'salida', $line->cantidad, 'venta', $actor->id,
                    ['orden_empresarial_id' => $order->id, 'referencia' => 'Venta ERP '.$order->folio]);
            }
            $order->update(['estado' => 'procesada', 'procesada_at' => now()]);
            if ($client->etapa_crm === 'Prospecto') {
                $client->update(['etapa_crm' => 'Activo']);
            }
            $this->history($order, $actor, 'procesada', 'Inventario descontado, movimientos SCM registrados y cliente CRM vinculado.');
            $order->procesos()->create(['usuario_id' => $actor->id, 'tipo' => 'venta', 'estado' => 'completado', 'finalizado_at' => now(),
                'pasos' => ['Cliente CRM validado', 'Precios de catálogo confirmados', 'Existencias verificadas', 'Stock descontado y reglas Push/Pull evaluadas', 'Movimientos e historial guardados']]);

            return $order->fresh();
        }, 3);
    }

    public function changeState(int $id, string $state, User $actor, ?string $reason = null): Orden
    {
        if ($state === 'procesada') {
            abort_unless($actor->hasPermission('erp.write'), 403);

            return $this->process($id, $actor);
        }

        return DB::transaction(function () use ($id, $state, $actor, $reason) {
            $order = Orden::lockForUpdate()->findOrFail($id);
            if ($state === 'completada') {
                abort_unless($actor->hasPermission('erp.complete'), 403, 'Solo administración o logística puede completar entregas.');
                if ($order->estado === 'completada') {
                    return $order;
                }
                abort_unless($order->estado === 'procesada', 409, 'Primero procesa la orden antes de completar su entrega.');
                $order->update(['estado' => 'completada']);
                $this->history($order, $actor, 'completada', 'Entrega confirmada. El inventario ya fue descontado al procesar.');
            } else {
                abort_unless($actor->hasPermission('erp.cancel') || $actor->role === 'admin', 403);
                abort_unless($actor->role === 'admin' || $order->estado === 'pendiente' || $order->estado === 'cancelada', 403, 'Solo el administrador puede cancelar una orden ya procesada.');
                if ($order->estado === 'cancelada') {
                    return $order;
                }
                if (in_array($order->estado, ['procesada', 'completada'], true)) {
                    foreach ($order->productos()->orderBy('producto_id')->get() as $line) {
                        abort_if(! Product::find($line->producto_id), 409, 'El producto está archivado. Reactívalo antes de devolver existencias.');
                        $this->inventory->move($line->producto_id, 'entrada', $line->cantidad, 'ajuste', $actor->id,
                            ['orden_empresarial_id' => $order->id, 'referencia' => 'Cancelación ERP '.$order->folio]);
                    }
                }
                $order->update(['estado' => 'cancelada', 'cancelada_at' => now()]);
                $this->history($order, $actor, 'cancelada', 'Orden cancelada. '.($reason ?? 'Sin observaciones.'));
                $order->procesos()->create(['usuario_id' => $actor->id, 'tipo' => 'cancelacion', 'estado' => 'completado', 'finalizado_at' => now(),
                    'pasos' => ['Orden bloqueada', 'Existencias restituidas si hubo venta', 'Venta excluida de reportes', 'Historial conservado']]);
            }

            return $order->fresh();
        }, 3);
    }

    private function history(Orden $order, User $actor, string $event, string $description): void
    {
        $order->historial()->create(['usuario_id' => $actor->id, 'evento' => $event, 'descripcion' => $description, 'fecha' => now()]);
    }
}
