<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Product;
use App\Models\OrderItem;
use App\Services\CommercePricing;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    public function index(Request $request, CommercePricing $pricing)
    {
        if (! $request->expectsJson()) {
            return view('editorial', ['initialView' => 'coleccion']);
        }

        $filters = $request->validate(['categoria' => 'nullable|string|max:100', 'buscar' => 'nullable|string|max:120', 'orden' => 'nullable|in:coleccion,nuevos,precio-asc,precio-desc,populares']);
        $query = Product::with('familia')->where('active', true);
        if ($category = $filters['categoria'] ?? null) {
            $query->whereHas('familia', fn ($q) => $q->where('slug', $category)->orWhere('nombre', $category));
        }
        $search = trim($filters['buscar'] ?? '');
        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }
        $order = $filters['orden'] ?? 'coleccion';
        if ($order === 'populares') {
            $query->addSelect(['unidades_vendidas' => OrderItem::query()
                ->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereColumn('order_items.product_id', 'products.id')
                ->whereIn('orders.status', ['Pagado', 'Enviado', 'Entregado'])])
                ->orderByDesc('unidades_vendidas');
        }
        $query->orderBy('id', $order === 'nuevos' ? 'desc' : 'asc');
        $promotions = $pricing->promotions();
        $products = $query->get()->map(fn ($product) => $pricing->present($product, $promotions));
        if (in_array($order, ['precio-asc', 'precio-desc'], true)) {
            $products = $products->sortBy('precio', SORT_NUMERIC, $order === 'precio-desc')->values();
        }

        return [
            'categorias' => Categoria::orderBy('id')->get()->map(fn ($c) => [
                'id' => $c->id, 'slug' => $c->slug, 'nombre' => $c->etiqueta, 'imagen_url' => asset($c->imagen_url),
            ]),
            'productos' => $products,
        ];
    }

    public function show(Request $request, Product $producto, CommercePricing $pricing)
    {
        abort_unless($producto->active, 404);
        $promotions = $pricing->promotions();
        $product = $pricing->present($producto->load('familia'), $promotions);
        if ($request->expectsJson()) {
            return $product;
        }

        $related = Product::with('familia')->where('active', true)->where('categoria_id', $producto->categoria_id)
            ->whereKeyNot($producto->id)->limit(4)->get()->map(fn ($item) => $pricing->present($item, $promotions));

        return view('purchase.product', compact('product', 'related'));
    }
}
