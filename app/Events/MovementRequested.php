<?php

namespace App\Events;

use App\Models\InventoryMovement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovementRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public InventoryMovement $movement,
        public array $requestData = []
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
            new PrivateChannel('inventory.movements'),
            new PrivateChannel("warehouse.{$this->movement->warehouse_id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'movement_id' => $this->movement->id,
            'product_id' => $this->movement->product_id,
            'warehouse_id' => $this->movement->warehouse_id,
            'movement_type' => $this->movement->movement_type,
            'quantity' => $this->movement->quantity,
            'status' => $this->movement->status,
            'user_id' => $this->movement->created_by,
            'requested_at' => $this->movement->created_at,
            'message' => "Movimiento de inventario solicitado: {$this->movement->movement_type_spanish} de {$this->movement->quantity} unidades",
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'movement.requested';
    }
}
