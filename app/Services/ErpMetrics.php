<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Orden;
use App\Models\Order;
use App\Models\Pedido;
use App\Models\ProcesoInterno;
use App\Models\Product;
use App\Models\Proveedor;
use App\Models\Recurso;
use App\Models\ReporteErp;

class ErpMetrics
{
    public function summary(): array
    {
        $erp = Orden::with('productos')->whereIn('estado', ['procesada', 'completada'])->get();
        $store = Order::with('items')->whereIn('status', ['Enviado', 'Entregado'])->get();
        $catalog = Product::with('inventario')->get();
        $series = collect(range(5, 0))->map(function ($ago) use ($erp, $store) {
            $month = now()->startOfMonth()->subMonths($ago);
            $total = $erp->filter(fn ($o) => $o->procesada_at?->format('Y-m') === $month->format('Y-m'))->sum('total_cents')
                + $store->filter(fn ($o) => $o->created_at->format('Y-m') === $month->format('Y-m'))->sum('total_cents');

            return ['mes' => $month->format('Y-m'), 'etiqueta' => $month->locale('es')->translatedFormat('M Y'), 'total_cents' => $total, 'total' => $total / 100];
        });
        $sold = [];
        foreach ($erp as $o) {
            foreach ($o->productos as $line) {
                $key = $line->producto_id;
                $sold[$key] ??= ['id' => $key, 'nombre' => $line->nombre, 'unidades' => 0];
                $sold[$key]['unidades'] += $line->cantidad;
            }
        }
        foreach ($store as $o) {
            foreach ($o->items as $line) {
                $key = $line->product_id;
                $sold[$key] ??= ['id' => $key, 'nombre' => $line->name, 'unidades' => 0];
                $sold[$key]['unidades'] += $line->quantity;
            }
        }
        $top = collect($sold)->sortByDesc('unidades')->values();
        $stock = $catalog->sum('stock');
        $critical = $catalog->filter(fn ($p) => $p->stock <= $p->stock_minimo);
        $total = $erp->sum('total_cents') + $store->sum('total_cents');

        return ['ventas_totales' => $total / 100, 'ventas_totales_cents' => $total,
            'ventas_erp_cents' => $erp->sum('total_cents'), 'ventas_tienda_cents' => $store->sum('total_cents'),
            'ordenes_procesadas' => $erp->count(), 'productos_vendidos' => $top->sum('unidades'),
            'clientes_activos' => Cliente::where('estado', 'activo')->count(), 'inventario_disponible' => $stock,
            'ordenes_pendientes' => Orden::where('estado', 'pendiente')->count(), 'ordenes_canceladas' => Orden::where('estado', 'cancelada')->count(),
            'pedidos_tienda_surtidos' => $store->count(), 'reposiciones_scm_pendientes' => Pedido::where('tipo', 'reposicion')->where('estado', 'pendiente')->count(),
            'proveedores' => Proveedor::count(), 'recursos_disponibles' => Recurso::where('disponible', true)->count(),
            'ventas_por_mes' => $series, 'productos_mas_vendidos' => $top->take(10)->values(),
            'inventario_critico' => $critical->map->scmPresentation()->values(),
            'distribucion_inventario' => ['disponible' => $stock, 'criticos' => $critical->count(), 'productos' => $catalog->count()],
            'integracion' => ['clientes_crm' => Cliente::count(), 'productos_scm' => $catalog->count(), 'ordenes_erp' => Orden::count(), 'procesos_completados' => ProcesoInterno::where('estado', 'completado')->count()],
            'generado_at' => now()->toISOString()];
    }

    public function evidence(): array
    {
        return ['clientes' => Cliente::where('estado', 'activo')->exists(),
            'inventario' => Product::whereHas('inventario')->exists(),
            'ordenes' => Orden::whereIn('estado', ['procesada', 'completada'])->exists(),
            'movimientos' => \App\Models\MovimientoInventario::whereNotNull('orden_empresarial_id')->exists(),
            'reportes' => ReporteErp::exists(), 'procesos' => ProcesoInterno::where('estado', 'completado')->exists()];
    }
}
