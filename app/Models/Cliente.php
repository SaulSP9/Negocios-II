<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function interacciones(): HasMany
    {
        return $this->hasMany(Interaccion::class);
    }
}