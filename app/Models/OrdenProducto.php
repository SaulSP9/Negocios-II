<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenProducto extends Model
{
    protected $table = 'orden_productos';

    protected $guarded = ['id'];

    protected $appends = ['precio_unitario', 'subtotal'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'precio_unitario_cents' => 'integer', 'subtotal_cents' => 'integer'];
    }

    public function getPrecioUnitarioAttribute(): float
    {
        return $this->precio_unitario_cents / 100;
    }

    public function getSubtotalAttribute(): float
    {
        return $this->subtotal_cents / 100;
    }

    public function producto()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
