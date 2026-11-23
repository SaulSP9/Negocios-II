<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime', 'monto_cents' => 'integer'];
    }

    public function pedido()
    {
        return $this->belongsTo(Order::class, 'pedido_id');
    }
}
