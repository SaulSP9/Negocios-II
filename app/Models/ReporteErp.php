<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteErp extends Model
{
    protected $table = 'reportes_erp';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['resumen' => 'array'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
