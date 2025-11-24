<?php

namespace App\Notifications;

use App\Models\InventoryAdjustment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdjustmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public InventoryAdjustment $adjustment,
        public string $productName,
        public string $warehouseName,
        public float $quantityDifference
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
        $adjustmentTypeLabels = [
            'positive' => 'Ajuste Positivo',
            'negative' => 'Ajuste Negativo',
            'count' => 'Conteo Físico',
            'damage' => 'Daño/Merma',
            'expiry' => 'Vencimiento',
            'other' => 'Otro',
        ];
        $typeLabel = $adjustmentTypeLabels[$this->adjustment->adjustment_type] ?? 'Ajuste';

        return (new MailMessage)
            ->subject("Ajuste de Inventario Realizado - {$this->adjustment->adjustment_number}")
            ->greeting('Ajuste de Inventario')
            ->line("Se ha realizado un ajuste de inventario ({$typeLabel}).")
            ->line("**Producto:** {$this->productName}")
            ->line("**Bodega:** {$this->warehouseName}")
            ->line("**Diferencia:** ".($this->quantityDifference >= 0 ? '+' : '').number_format($this->quantityDifference, 2))
            ->line("**Razón:** {$this->adjustment->reason}")
            ->action('Ver Ajustes', route('adjustments.index'))
            ->line('Revise el ajuste si es necesario.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'adjustment_created',
            'icon' => 'adjustments-horizontal',
            'color' => $this->quantityDifference >= 0 ? 'blue' : 'red',
            'adjustment_id' => $this->adjustment->id,
            'adjustment_number' => $this->adjustment->adjustment_number,
            'adjustment_type' => $this->adjustment->adjustment_type,
            'product_name' => $this->productName,
            'warehouse_name' => $this->warehouseName,
            'quantity_difference' => $this->quantityDifference,
            'reason' => $this->adjustment->reason,
            'message' => "Ajuste: {$this->productName} ({$this->quantityDifference >= 0 ? '+' : ''}{$this->quantityDifference})",
            'url' => route('adjustments.index'),
        ];
    }
}
