<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    protected $table = 'impuestos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['estado' => 'boolean', 'porcentaje' => 'decimal:2'];
    }
}
