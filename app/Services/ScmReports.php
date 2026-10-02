<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ScmReports
{
    public function summary(): array
    {
        $products = Product::with(['proveedor', 'inventario'])->orderBy('id')->get();
        $cutoff = now()->subDays(30);
        $sales = MovimientoInventario::query()->where('tipo', 'salida')->where('motivo', 'venta')
            ->where(fn ($q) => $q->whereNull('order_id')->orWhereIn('order_id', DB::table('orders')->select('id')->whereIn('status', ['Enviado', 'Entregado'])));
        $allSales = (clone $sales)->selectRaw('producto_id, SUM(cantidad) as total')->groupBy('producto_id')->pluck('total', 'producto_id');
        $recent = (clone $sales)->where('fecha', '>=', $cutoff)->selectRaw('producto_id, SUM(cantidad) as total')->groupBy('producto_id')->pluck('total', 'producto_id');
        $top = $products->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'unidades' => (int) ($allSales[$p->id] ?? 0)])->filter(fn ($p) => $p['unidades'] > 0)->sortByDesc('unidades')->take(10)->values();
        $slow = $products->filter(fn ($p) => $p->stock > 0 && (int) ($recent[$p->id] ?? 0) <= 2)->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'stock_actual' => $p->stock, 'unidades_30_dias' => (int) ($recent[$p->id] ?? 0)])->values();

        return ['total_productos' => $products->count(), 'unidades_stock' => $products->sum('stock'),
            'valor_inventario_cents' => $products->sum(fn ($p) => $p->stock * $p->costo_unitario_cents),
            'pedidos_pendientes' => Pedido::where('estado', 'pendiente')->count(),
            'inventario_critico' => $products->filter(fn ($p) => $p->stock <= $p->stock_minimo)->map->scmPresentation()->values(),
            'productos_mas_vendidos' => $top, 'rotacion_lenta' => $slow, 'periodo_rotacion_dias' => 30, 'umbral_rotacion_unidades' => 2,
            'comparacion' => collect(['PUSH', 'PULL'])->map(function ($strategy) use ($products) {
                $group = $products->where('estrategia_logistica', $strategy);

                return ['estrategia' => $strategy, 'productos' => $group->count(), 'stock' => $group->sum('stock'), 'criticos' => $group->filter(fn ($p) => $p->stock <= $p->stock_minimo)->count()];
            })->values()];
    }
}
