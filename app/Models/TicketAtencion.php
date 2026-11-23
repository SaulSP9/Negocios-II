<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAtencion extends Model
{
    public const CATEGORIES = ['Pedido', 'Cambio o devolución', 'Facturación', 'Pago o monedero', 'General'];

    protected $table = 'tickets_atencion';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function eventos()
    {
        return $this->hasMany(EventoTicketAtencion::class, 'ticket_id')->orderBy('id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
