<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoTicketAtencion extends Model
{
    protected $table = 'eventos_ticket_atencion';

    protected $fillable = ['user_id', 'estado', 'mensaje'];
}
