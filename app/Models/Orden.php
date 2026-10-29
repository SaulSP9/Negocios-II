<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes_empresariales';

    protected $guarded = ['id'];

    protected $hidden = ['payload_hash', 'request_key'];

    protected $appends = ['total'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime', 'procesada_at' => 'datetime', 'cancelada_at' => 'datetime', 'total_cents' => 'integer'];
    }

    public function getTotalAttribute(): float
    {
        return $this->total_cents / 100;
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function productos()
    {
        return $this->hasMany(OrdenProducto::class);
    }

    public function historial()
    {
        return $this->hasMany(OrdenHistorial::class)->orderBy('id');
    }

    public function procesos()
    {
        return $this->hasMany(ProcesoInterno::class)->orderBy('id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'orden_empresarial_id');
    }
}
