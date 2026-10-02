<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function initialize(Product $product, ?int $userId = null): void
    {
        Inventario::firstOrCreate(['producto_id' => $product->id]);
        if ($product->stock > 0 && ! MovimientoInventario::where('producto_id', $product->id)->exists()) {
            MovimientoInventario::create(['producto_id' => $product->id, 'tipo' => 'entrada', 'cantidad' => $product->stock, 'motivo' => 'ajuste', 'fecha' => now(), 'stock_anterior' => 0, 'stock_resultante' => $product->stock, 'referencia' => 'Inventario inicial', 'usuario_id' => $userId]);
        }
    }

    public function move(int $productId, string $type, int $quantity, string $reason, ?int $userId = null, array $extra = []): MovimientoInventario
    {
        return DB::transaction(function () use ($productId, $type, $quantity, $reason, $userId, $extra) {
            if (! in_array($type, ['entrada', 'salida'], true) || $quantity < 1 || ! in_array($reason, ['venta', 'ajuste', 'reposición'], true)) {
                throw ValidationException::withMessages(['cantidad' => 'Movimiento de inventario inválido.']);
            }
            $product = Product::lockForUpdate()->findOrFail($productId);
            if ($key = $extra['request_key'] ?? null) {
                $existing = MovimientoInventario::where('request_key', $key)->first();
                if ($existing) {
                    abort_unless($existing->producto_id === $productId && $existing->tipo === $type && $existing->cantidad === $quantity && $existing->motivo === $reason && $existing->usuario_id === $userId, 409, 'La clave pertenece a un movimiento diferente.');

                    return $existing;
                }
            }
            $before = (int) $product->stock;
            $after = $before + ($type === 'entrada' ? $quantity : -$quantity);
            if ($after < 0 || $after > 1000000) {
                throw ValidationException::withMessages(['cantidad' => 'El movimiento dejaría existencias fuera del rango de 0 a 1,000,000.']);
            }
            $changed = Product::whereKey($productId)->where('inventory_version', $product->inventory_version)->update(['stock' => $after, 'inventory_version' => $product->inventory_version + 1, 'updated_at' => now()]);
            abort_unless($changed, 409, 'El inventario cambió. Actualiza y reintenta.');
            $movement = MovimientoInventario::create(['producto_id' => $productId, 'tipo' => $type, 'cantidad' => $quantity, 'motivo' => $reason, 'fecha' => now(), 'stock_anterior' => $before, 'stock_resultante' => $after, 'usuario_id' => $userId, ...$extra]);
            $this->checkPush($productId, $userId);

            return $movement;
        }, 3);
    }

    public function setStock(int $productId, int $desired, ?int $userId = null): void
    {
        $product = Product::lockForUpdate()->findOrFail($productId);
        $difference = $desired - $product->stock;
        if ($difference !== 0) {
            $this->move($productId, $difference > 0 ? 'entrada' : 'salida', abs($difference), 'ajuste', $userId, ['referencia' => 'Ajuste desde administración de catálogo']);
        }
    }

    public function checkPush(int $productId, ?int $userId = null): ?Pedido
    {
        return DB::transaction(function () use ($productId, $userId) {
            $product = Product::lockForUpdate()->findOrFail($productId);
            if ($product->estrategia_logistica !== 'PUSH' || $product->stock > $product->stock_minimo || ! $product->proveedor_id) {
                return null;
            }
            $pending = Pedido::where('producto_id', $productId)->where('tipo', 'reposicion')->where('estado', 'pendiente')->sum('cantidad');
            $needed = max(0, $product->stock_objetivo - $product->stock - $pending);
            if (! $needed) {
                return null;
            }
            $order = Pedido::where('automatico_key', 'push:'.$productId)->lockForUpdate()->first();
            if ($order) {
                $order->increment('cantidad', $needed);

                return $order->fresh();
            }

            return Pedido::create(['producto_id' => $productId, 'cantidad' => $needed, 'tipo' => 'reposicion', 'estado' => 'pendiente', 'automatico' => true, 'automatico_key' => 'push:'.$productId, 'usuario_id' => $userId]);
        }, 3);
    }

    public function createOrder(array $data, ?int $userId): Pedido
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::lockForUpdate()->findOrFail($data['producto_id']);
            if ($key = $data['request_key'] ?? null) {
                $existing = Pedido::where('request_key', $key)->first();
                if ($existing) {
                    abort_unless($existing->producto_id === $product->id && $existing->tipo === $data['tipo'] && $existing->cantidad === (int) $data['cantidad'] && $existing->usuario_id === $userId, 409, 'La clave corresponde a otro pedido.');

                    return $existing;
                }
            }
            if ($data['tipo'] === 'reposicion' && ! $product->proveedor_id) {
                throw ValidationException::withMessages(['producto_id' => 'Asocia un proveedor antes de solicitar reposición.']);
            }
            if ($data['tipo'] === 'reposicion') {
                $pending = Pedido::where('producto_id', $product->id)->where('tipo', 'reposicion')->where('estado', 'pendiente')->sum('cantidad');
                if ($product->stock + $pending + (int) $data['cantidad'] > 1000000) {
                    throw ValidationException::withMessages(['cantidad' => 'La reposición excede la capacidad máxima.']);
                }
            }

            return Pedido::create([...$data, 'usuario_id' => $userId, 'automatico' => false, 'estado' => 'pendiente']);
        }, 3);
    }

    public function receiveDirect(array $data, int $userId): MovimientoInventario
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::lockForUpdate()->findOrFail($data['producto_id']);
            if ($key = $data['request_key'] ?? null) {
                $existing = MovimientoInventario::where('request_key', $key)->first();
                if ($existing) {
                    abort_unless($existing->producto_id === $product->id && $existing->cantidad === (int) $data['cantidad'] && $existing->motivo === 'reposición' && $existing->usuario_id === $userId, 409, 'La clave pertenece a otro movimiento.');

                    return $existing;
                }
            }
            abort_if(Pedido::where('producto_id', $product->id)->where('tipo', 'reposicion')->where('estado', 'pendiente')->exists(), 422, 'Ya existe una reposición pendiente. Recíbela desde Pedidos SCM para evitar duplicarla.');
            $order = $this->createOrder(['producto_id' => $product->id, 'cantidad' => $data['cantidad'], 'tipo' => 'reposicion'], $userId);
            $this->fulfill($order->id, $userId);
            $movement = MovimientoInventario::where('pedido_id', $order->id)->firstOrFail();
            $movement->update(array_intersect_key($data, array_flip(['referencia', 'request_key'])));

            return $movement;
        }, 3);
    }

    public function fulfill(int $orderId, ?int $userId): Pedido
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $lookup = Pedido::findOrFail($orderId);
            Product::whereKey($lookup->producto_id)->lockForUpdate()->firstOrFail();
            $order = Pedido::lockForUpdate()->findOrFail($orderId);
            if ($order->estado === 'surtido') {
                return $order;
            }
            $changed = Pedido::whereKey($orderId)->where('estado', 'pendiente')->update(['estado' => 'surtido', 'automatico_key' => null, 'fecha_surtido' => now(), 'updated_at' => now()]);
            abort_unless($changed, 409, 'El pedido cambió. Actualiza e inténtalo de nuevo.');
            $this->move($order->producto_id, $order->tipo === 'reposicion' ? 'entrada' : 'salida', $order->cantidad, $order->tipo === 'reposicion' ? 'reposición' : 'venta', $userId, ['pedido_id' => $orderId, 'referencia' => 'Pedido SCM #'.$orderId]);

            return $order->fresh();
        }, 3);
    }
}
