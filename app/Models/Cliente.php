<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'empresa',
        'fecha_registro',
        'estado',
        'etapa_crm',
    ];

    public function interacciones()
    {
        return $this->hasMany(Interaccion::class);
    }
}