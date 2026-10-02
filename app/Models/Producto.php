<?php

namespace App\Models;

// Comparte producto y stock con la tienda: no se crea un catálogo paralelo.
class Producto extends Product
{
    protected $table = 'products';

    public function getNombreAttribute()
    {
        return $this->name;
    }

    public function getDescripcionAttribute()
    {
        return $this->description;
    }

    public function getCategoriaAttribute()
    {
        return $this->category;
    }

    public function getStockActualAttribute()
    {
        return $this->stock;
    }

    public function getCostoUnitarioAttribute()
    {
        return $this->costo_unitario_cents / 100;
    }
}
