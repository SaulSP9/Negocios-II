<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesoInterno extends Model
{
    protected $table = 'procesos_internos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['pasos' => 'array', 'finalizado_at' => 'datetime'];
    }
}
