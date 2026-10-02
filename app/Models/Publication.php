<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $guarded = ['id'];

    public function presentation(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'price' => $this->price_cents / 100, 'description' => $this->description, 'image' => $this->image];
    }
}
