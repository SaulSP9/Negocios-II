<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monedero extends Model
{
    protected $table = 'monederos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['saldo_cents' => 'integer'];
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoMonedero::class, 'monedero_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
