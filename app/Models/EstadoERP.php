<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoERP extends Model
{
    protected $table = 'estado_erp';

    protected $guarded = ['id'];

    public $incrementing = false;

    protected function casts(): array
    {
        return ['checklist' => 'array'];
    }
}
