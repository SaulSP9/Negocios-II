<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaid extends Notification
{
    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Pedido confirmado · '.$this->order->folio)
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line('Confirmamos el pago de tu pedido '.$this->order->folio.'.')
            ->line('Total: $'.number_format($this->order->total_cents / 100, 2).' MXN.');
        foreach ($this->order->items as $item) {
            $message->line($item->quantity.' × '.$item->name.($item->talla ? ' · Talla '.$item->talla : ''));
        }

        return $message->action('Ver mis pedidos', url('/?vista=cuenta#mis-pedidos'));
    }
}
