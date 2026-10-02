<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';

    protected $fillable = ['cliente_id', 'usuario_id', 'puntuacion', 'observaciones'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
