<?php

namespace App\Notifications;

use App\Models\InventoryTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public InventoryTransfer $transfer)
    {
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
            ->subject("Traslado {$this->transfer->transfer_number} Aprobado")
            ->greeting('¡Traslado Aprobado!')
            ->line("El traslado {$this->transfer->transfer_number} ha sido aprobado.")
            ->line("**Origen:** {$this->transfer->fromWarehouse->name}")
            ->line("**Destino:** {$this->transfer->toWarehouse->name}")
            ->line("**Productos:** {$this->transfer->details->count()}")
            ->action('Ver Traslado', route('transfers.show', $this->transfer))
            ->line('El traslado está listo para ser enviado.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transfer_id' => $this->transfer->id,
            'transfer_number' => $this->transfer->transfer_number,
            'from_warehouse' => $this->transfer->fromWarehouse->name,
            'to_warehouse' => $this->transfer->toWarehouse->name,
            'status' => 'aprobado',
        ];
    }
}
