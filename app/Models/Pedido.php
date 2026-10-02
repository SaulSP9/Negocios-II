<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos_scm';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['automatico' => 'boolean', 'fecha_surtido' => 'datetime'];
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id')->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
