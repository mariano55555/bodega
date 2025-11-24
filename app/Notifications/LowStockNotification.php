<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Product $product,
        public float $currentQuantity,
        public float $minimumStock,
        public string $warehouseName
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Alerta: Stock Bajo - {$this->product->name}")
            ->greeting('Alerta de Stock Bajo')
            ->line("El producto **{$this->product->name}** tiene stock bajo en la bodega **{$this->warehouseName}**.")
            ->line("**Stock actual:** {$this->currentQuantity}")
            ->line("**Stock mínimo:** {$this->minimumStock}")
            ->action('Ver Inventario', route('inventory.stock.query'))
            ->line('Se recomienda realizar un pedido de reabastecimiento.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'icon' => 'exclamation-triangle',
            'color' => 'yellow',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'current_quantity' => $this->currentQuantity,
            'minimum_stock' => $this->minimumStock,
            'warehouse_name' => $this->warehouseName,
            'message' => "Stock bajo: {$this->product->name} ({$this->currentQuantity} unidades)",
            'url' => route('inventory.stock.query'),
        ];
    }
}
