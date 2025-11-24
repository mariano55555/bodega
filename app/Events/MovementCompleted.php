<?php

namespace App\Events;

use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovementCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public InventoryMovement $movement,
        public User $completedBy,
        public array $inventoryChanges = []
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
            new PrivateChannel("product.{$this->movement->product_id}"),
            new PrivateChannel("user.{$this->movement->created_by}"),
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
            'completed_by' => $this->completedBy->name,
            'completed_by_id' => $this->completedBy->id,
            'completed_at' => $this->movement->completed_at,
            'inventory_changes' => $this->inventoryChanges,
            'message' => "Movimiento de inventario completado: {$this->movement->movement_type_spanish} de {$this->movement->quantity} unidades",
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'movement.completed';
    }
}
