<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoMonedero extends Model
{
    protected $table = 'movimientos_monedero';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['importe_cents' => 'integer', 'saldo_cents' => 'integer'];
    }

    public function pedido()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
