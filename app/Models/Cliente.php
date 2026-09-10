<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'empresa',
        'estado',
        'etapa_crm'
    ];

    /**
     * Relación con las interacciones del cliente
     */
    public function interacciones()
    {
        return $this->hasMany(Interaccion::class, 'cliente_id')->orderBy('fecha', 'desc');
    }
}