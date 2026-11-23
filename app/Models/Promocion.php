<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    protected $table = 'promociones';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['estado' => 'boolean', 'fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime', 'valor' => 'decimal:2'];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function scopeVigentes($query)
    {
        $now = now();

        return $query->where('estado', true)->where('fecha_inicio', '<=', $now)->where('fecha_fin', '>', $now);
    }
}
