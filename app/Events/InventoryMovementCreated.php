<?php

namespace App\Events;

use App\Models\InventoryMovement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryMovementCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public InventoryMovement $movement
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
            new PrivateChannel('warehouse.'.$this->movement->warehouse_id),
            new PrivateChannel('product.'.$this->movement->product_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'inventory.movement.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'movement' => [
                'id' => $this->movement->id,
                'product_id' => $this->movement->product_id,
                'warehouse_id' => $this->movement->warehouse_id,
                'movement_type' => $this->movement->movement_type,
                'quantity' => $this->movement->quantity,
                'created_at' => $this->movement->created_at,
            ],
            'product' => [
                'id' => $this->movement->product->id,
                'name' => $this->movement->product->name,
                'sku' => $this->movement->product->sku,
            ],
            'warehouse' => [
                'id' => $this->movement->warehouse->id,
                'name' => $this->movement->warehouse->name,
                'code' => $this->movement->warehouse->code,
            ],
        ];
    }
}
