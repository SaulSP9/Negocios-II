<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventarios';

    protected $fillable = ['producto_id', 'ubicacion', 'unidad'];

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id')->withTrashed();
    }
}
