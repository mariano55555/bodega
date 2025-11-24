<?php

namespace App\Events;

use App\Models\ProductLot;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LotExpirationAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ProductLot $lot,
        public int $daysUntilExpiration,
        public string $alertType = 'expiring_soon' // 'expiring_soon', 'expired'
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('inventory.alerts'),
            new PrivateChannel("product.{$this->lot->product_id}"),
            new PrivateChannel('warehouse.managers'), // Para notificar a gerentes de almacén
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $alertMessages = [
            'expiring_soon' => "Lote {$this->lot->lot_number} vence en {$this->daysUntilExpiration} días",
            'expired' => "Lote {$this->lot->lot_number} ha vencido",
        ];

        return [
            'lot_id' => $this->lot->id,
            'lot_number' => $this->lot->lot_number,
            'product_id' => $this->lot->product_id,
            'product_name' => $this->lot->product->name ?? 'Producto',
            'expiration_date' => $this->lot->expiration_date?->format('Y-m-d'),
            'days_until_expiration' => $this->daysUntilExpiration,
            'quantity_remaining' => $this->lot->quantity_remaining,
            'alert_type' => $this->alertType,
            'priority' => $this->alertType === 'expired' ? 'high' : 'medium',
            'message' => $alertMessages[$this->alertType] ?? 'Alerta de vencimiento de lote',
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'lot.expiration_alert';
    }
}
