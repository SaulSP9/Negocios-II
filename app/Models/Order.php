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

    public function pago()
    {
        return $this->hasOne(Pago::class, 'pedido_id');
    }

    public function ordenEmpresarial()
    {
        return $this->belongsTo(Orden::class, 'orden_empresarial_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cargoEnMonedero()
    {
        return $this->hasOne(MovimientoMonedero::class)->where('tipo', 'compra');
    }

    public function presentation(): array
    {
        return ['id' => $this->folio, 'database_id' => $this->id, 'user' => $this->user->name, 'date' => $this->created_at->format('d/m/Y'),
            'status' => $this->status, 'entrega' => ['nombre' => $this->recipient_name, 'direccion' => $this->address, 'ciudad' => $this->city, 'codigo_postal' => $this->zip],
            'saldo_despues_pago_cents' => $this->pago?->metodo_pago === 'monedero' ? $this->cargoEnMonedero?->saldo_cents : null, 'subtotal' => $this->subtotal_cents / 100, 'impuesto' => $this->tax_cents / 100,
            'pago' => $this->pago?->only(['id', 'metodo_pago', 'estado', 'fecha']), 'total' => $this->total_cents / 100, 'discount' => $this->discount_cents / 100,
            'items' => $this->items->map(fn ($i) => ['name' => $i->name, 'price' => $i->price_cents / 100, 'quantity' => $i->quantity, 'talla' => $i->talla])->all()];
    }
}
