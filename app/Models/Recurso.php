<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recurso extends Model
{
    protected $table = 'recursos_erp';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['disponible' => 'boolean'];
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
