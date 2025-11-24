<?php

namespace App\Notifications;

use App\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Purchase $purchase,
        public string $supplierName,
        public float $totalAmount,
        public int $totalItems
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
            ->subject("Compra Aprobada - {$this->purchase->purchase_number}")
            ->greeting('Compra Aprobada')
            ->line("La compra **{$this->purchase->purchase_number}** ha sido aprobada.")
            ->line("**Proveedor:** {$this->supplierName}")
            ->line("**Total items:** {$this->totalItems}")
            ->line("**Monto total:** $".number_format($this->totalAmount, 2))
            ->action('Ver Compra', route('purchases.show', $this->purchase))
            ->line('La compra está lista para ser procesada.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'purchase_approved',
            'icon' => 'shopping-cart',
            'color' => 'green',
            'purchase_id' => $this->purchase->id,
            'purchase_number' => $this->purchase->purchase_number,
            'supplier_name' => $this->supplierName,
            'total_amount' => $this->totalAmount,
            'total_items' => $this->totalItems,
            'message' => "Compra aprobada: {$this->purchase->purchase_number} - {$this->supplierName}",
            'url' => route('purchases.show', $this->purchase),
        ];
    }
}
