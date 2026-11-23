<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarritoDetalle extends Model
{
    protected $table = 'carrito_detalle';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'precio_unitario_cents' => 'integer'];
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id')->withTrashed();
    }
}
