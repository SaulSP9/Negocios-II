<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = ['nombre', 'correo', 'telefono', 'empresa', 'estado', 'etapa_crm'];

    protected function casts(): array
    {
        return ['fecha_registro' => 'datetime'];
    }

    public function interacciones()
    {
        return $this->hasMany(Interaccion::class);
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class);
    }
}
