<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'contacto', 'correo', 'telefono'];

    public function productos()
    {
        return $this->hasMany(Product::class, 'proveedor_id');
    }
}
