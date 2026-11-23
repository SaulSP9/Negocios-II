<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    protected $table = 'carrito';

    protected $guarded = ['id'];

    public function detalles()
    {
        return $this->hasMany(CarritoDetalle::class, 'carrito_id');
    }
}
