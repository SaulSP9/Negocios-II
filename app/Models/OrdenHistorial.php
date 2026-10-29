<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenHistorial extends Model
{
    protected $table = 'orden_historial';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
