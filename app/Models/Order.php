<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function presentation(): array
    {
        return ['id' => $this->folio, 'database_id' => $this->id, 'user' => $this->user->name, 'date' => $this->created_at->format('d/m/Y'),
            'status' => $this->status, 'total' => $this->total_cents / 100, 'discount' => $this->discount_cents / 100,
            'items' => $this->items->map(fn ($i) => ['name' => $i->name, 'price' => $i->price_cents / 100, 'quantity' => $i->quantity])->all()];
    }
}
