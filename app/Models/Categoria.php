<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $guarded = ['id'];

    public const LABELS = ['Hoodie' => 'Hoodies & Outerwear', 'T-Shirt' => 'Camisetas / Tees', 'Pants' => 'Pantalones', 'Accessory' => 'Accesorios'];

    public function getEtiquetaAttribute(): string
    {
        return self::LABELS[$this->nombre] ?? $this->nombre;
    }
}
