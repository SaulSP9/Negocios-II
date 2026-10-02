<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ends_at' => 'datetime'];
    }
}
