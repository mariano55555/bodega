<?php

namespace App\Events;

use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovementApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public InventoryMovement $movement,
        public User $approvedBy,
        public ?string $notes = null
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
            'approved_by' => $this->approvedBy->name,
            'approved_by_id' => $this->approvedBy->id,
            'approved_at' => $this->movement->approved_at,
            'approval_notes' => $this->notes,
            'message' => "Movimiento de inventario aprobado por {$this->approvedBy->name}",
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'movement.approved';
    }
}
