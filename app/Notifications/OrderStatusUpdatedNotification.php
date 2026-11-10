<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly string $newStatus,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $orderCode = '#PED-'.str_pad((string) $this->order->id, 6, '0', STR_PAD_LEFT);

        return (new MailMessage)
            ->subject("DigitalCard (Demonstração Acadêmica) — Pedido {$orderCode}: {$this->newStatus}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O status do seu pedido demonstrativo {$orderCode} foi atualizado para: **{$this->newStatus}**.")
            ->when($this->order->tracking_code, fn (MailMessage $mail) => $mail->line("Código de rastreio simulado: {$this->order->tracking_code}"))
            ->action('Acompanhar Pedido', route('orders.show', $this->order))
            ->line('Aviso: Este é um projeto universitário de demonstração. Nenhum pagamento ou entrega é real.');
    }
}
