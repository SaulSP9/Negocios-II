<?php

namespace App\Services;

use App\Models\Impuesto;
use App\Models\Product;
use App\Models\Promocion;
use App\Support\Editorial;
use Illuminate\Support\Collection;

class CommercePricing
{
    public function promotions(): Collection
    {
        return Promocion::vigentes()->with('categoria')->orderBy('fecha_fin')->orderBy('id')->get();
    }

    public function discount(Product $product, Collection $promotions): int
    {
        // Se aplica una sola promoción por unidad: la vigente que más beneficia al cliente.
        return (int) $promotions->filter(fn ($promo) => ($promo->producto_id === null || (int) $promo->producto_id === $product->id)
            && ($promo->categoria_id === null || (int) $promo->categoria_id === (int) $product->categoria_id))
            ->map(fn ($promo) => min($product->price_cents, $promo->tipo_descuento === 'porcentaje'
                ? $this->percent($product->price_cents, $promo->valor)
                : (int) round($promo->valor * 100)))
            ->max();
    }

    public function present(Product $product, Collection $promotions): array
    {
        $discount = $this->discount($product, $promotions);
        $price = (int) $product->price_cents;
        $final = $price - $discount;

        return [
            'id' => $product->id, 'nombre' => $product->name, 'descripcion' => $product->description,
            'color' => $product->color, 'categoria' => $product->familia?->slug,
            'familia' => $product->familia?->etiqueta ?? $product->category,
            'precio' => $final, 'precio_base' => $price,
            'precio_anterior' => $discount ? $price : ((int) $product->original_price_cents > $price ? (int) $product->original_price_cents : null),
            'descuento' => $discount, 'stock_actual' => $product->stock,
            'imagen_url' => asset(Editorial::productImage($product->image)), 'tallas' => $product->tallas(),
            'destacado' => str_contains((string) $product->badge, 'DESTACADO'),
            'etiqueta' => $discount ? '-'.round($discount * 100 / $price).'%' : (($product->stock > 0 && $product->stock <= 5) ? 'ÚLTIMAS PIEZAS' : $product->badge),
        ];
    }

    public function quote(Collection $lines, ?Collection $promotions = null): array
    {
        $promotions ??= $this->promotions();
        $subtotal = $discount = 0;
        foreach ($lines as $line) {
            $subtotal += $line->producto->price_cents * $line->cantidad;
            $discount += $this->discount($line->producto, $promotions) * $line->cantidad;
        }
        $net = $subtotal - $discount;
        $taxes = Impuesto::where('estado', true)->orderBy('id')->get()
            ->map(fn ($tax) => ['nombre' => $tax->nombre, 'porcentaje' => $tax->porcentaje, 'importe' => $this->percent($net, $tax->porcentaje)]);
        $tax = $taxes->sum('importe');

        return ['subtotal' => $subtotal, 'descuento' => $discount, 'impuesto' => $tax, 'impuestos' => $taxes, 'total' => $net + $tax];
    }

    private function percent(int $cents, string $percentage): int
    {
        $basisPoints = (int) round((float) $percentage * 100);

        return intdiv($cents * $basisPoints + 5000, 10000);
    }
}
