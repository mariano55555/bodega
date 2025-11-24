<?php

namespace App\Notifications;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Product $product,
        public Carbon $expirationDate,
        public int $daysUntilExpiry,
        public string $warehouseName,
        public ?string $lotNumber = null
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
        $lotInfo = $this->lotNumber ? " (Lote: {$this->lotNumber})" : '';

        return (new MailMessage)
            ->subject("Alerta: Producto Próximo a Vencer - {$this->product->name}")
            ->greeting('Alerta de Vencimiento')
            ->line("El producto **{$this->product->name}**{$lotInfo} está próximo a vencer.")
            ->line("**Bodega:** {$this->warehouseName}")
            ->line("**Fecha de vencimiento:** {$this->expirationDate->format('d/m/Y')}")
            ->line("**Días hasta vencimiento:** {$this->daysUntilExpiry}")
            ->action('Ver Alertas de Stock', route('inventory.alerts.index'))
            ->line('Se recomienda tomar acciones para evitar pérdidas.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'product_expiring',
            'icon' => 'clock',
            'color' => 'orange',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'expiration_date' => $this->expirationDate->format('Y-m-d'),
            'days_until_expiry' => $this->daysUntilExpiry,
            'warehouse_name' => $this->warehouseName,
            'lot_number' => $this->lotNumber,
            'message' => "Por vencer: {$this->product->name} - {$this->daysUntilExpiry} días",
            'url' => route('inventory.alerts.index'),
        ];
    }
}
