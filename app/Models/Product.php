<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'stock' => 'integer', 'stock_minimo' => 'integer', 'stock_objetivo' => 'integer', 'inventory_version' => 'integer'];
    }

    protected static function booted(): void
    {
        static::created(fn (Product $p) => app(InventoryService::class)->initialize($p, auth()->id()));
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function inventario()
    {
        return $this->hasOne(Inventario::class, 'producto_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function presentation(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'color' => $this->color, 'category' => $this->category,
            'price' => $this->price_cents / 100, 'originalPrice' => $this->original_price_cents ? $this->original_price_cents / 100 : null,
            'stock' => $this->stock, 'image' => $this->image, 'badge' => $this->badge, 'description' => $this->description, 'active' => $this->active,
            'inventory_version' => $this->inventory_version];
    }

    public function scmPresentation(): array
    {
        return ['id' => $this->id, 'nombre' => $this->name, 'descripcion' => $this->description, 'categoria' => $this->category,
            'stock_actual' => $this->stock, 'stock_minimo' => $this->stock_minimo, 'stock_objetivo' => $this->stock_objetivo,
            'proveedor_id' => $this->proveedor_id, 'proveedor' => $this->proveedor?->nombre, 'costo_unitario' => $this->costo_unitario_cents / 100,
            'precio_venta' => $this->price_cents / 100, 'imagen' => $this->image, 'color' => $this->color, 'activo' => $this->active,
            'estrategia_logistica' => $this->estrategia_logistica, 'stock_bajo' => $this->stock <= $this->stock_minimo,
            'ubicacion' => $this->inventario?->ubicacion, 'inventory_version' => $this->inventory_version, 'deleted_at' => $this->deleted_at];
    }
}
