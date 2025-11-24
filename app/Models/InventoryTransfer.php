<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryTransfer extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryTransferFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'transfer_number';
    }

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'reason',
        'notes',
        'metadata',
        'requested_at',
        'approved_at',
        'shipped_at',
        'received_at',
        'completed_at',
        'cancelled_at',
        'requested_by',
        'approved_by',
        'approval_notes',
        'shipped_by',
        'tracking_number',
        'carrier',
        'shipping_cost',
        'received_by',
        'receiving_notes',
        'receiving_discrepancies',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'receiving_discrepancies' => 'array',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'shipping_cost' => 'decimal:2',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = 'TRF-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
            }
            if (auth()->check()) {
                $transfer->created_by = auth()->id();
                $transfer->requested_by = $transfer->requested_by ?? auth()->id();
                $transfer->requested_at = $transfer->requested_at ?? now();
            }
            if (is_null($transfer->active_at) && $transfer->is_active) {
                $transfer->active_at = now();
            }
        });

        static::updating(function ($transfer) {
            if (auth()->check()) {
                $transfer->updated_by = auth()->id();
            }
            if ($transfer->isDirty('is_active')) {
                $transfer->active_at = $transfer->is_active ? now() : null;
            }
        });

        static::deleting(function ($transfer) {
            if (auth()->check()) {
                $transfer->deleted_by = auth()->id();
                $transfer->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['transfer_number', 'status', 'from_warehouse_id', 'to_warehouse_id', 'reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Traslado '{$this->transfer_number}' {$eventName}");
    }

    // Relationships
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'transfer_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(InventoryTransferDetail::class, 'transfer_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    // Workflow Methods
    public function approve(int $userId, ?string $notes = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->approval_notes = $notes;

        if ($this->save()) {
            // Notify the requester
            if ($this->requestedBy) {
                $this->requestedBy->notify(new \App\Notifications\TransferApprovedNotification($this));
            }

            return true;
        }

        return false;
    }

    public function ship(int $userId, ?string $trackingNumber = null, ?string $carrier = null): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        \DB::beginTransaction();
        try {
            // Update transfer status
            $this->status = 'in_transit';
            $this->shipped_by = $userId;
            $this->shipped_at = now();
            $this->tracking_number = $trackingNumber;
            $this->carrier = $carrier;
            $this->save();

            // Get the transfer outbound movement reason
            $movementReason = MovementReason::where('code', 'TRANSFER_OUT')->first();
            if (! $movementReason) {
                $movementReason = MovementReason::where('movement_type', 'out')
                    ->where('category', 'transfer')
                    ->first();
            }

            if (! $movementReason) {
                throw new \Exception('Movement reason TRANSFER_OUT not found');
            }

            // Create outbound inventory movements from transfer details
            foreach ($this->details as $detail) {
                // Get current stock at origin warehouse
                $currentStock = InventoryMovement::where('warehouse_id', $this->from_warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->whereNotNull('balance_quantity')
                    ->orderBy('movement_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
                $newBalance = $previousBalance - $detail->quantity;

                // Create outbound movement (subtract from origin)
                InventoryMovement::create([
                    'company_id' => $this->fromWarehouse->company_id,
                    'warehouse_id' => $this->from_warehouse_id,
                    'product_id' => $detail->product_id,
                    'movement_reason_id' => $movementReason->id,
                    'transfer_id' => $this->id,
                    'movement_type' => 'transfer_out',
                    'movement_date' => now(),
                    'quantity' => -$detail->quantity,
                    'quantity_in' => 0,
                    'quantity_out' => $detail->quantity,
                    'balance_quantity' => $newBalance,
                    'unit_cost' => 0,
                    'total_cost' => 0,
                    'notes' => $detail->notes ?? "Envío de traslado {$this->transfer_number}",
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => $userId,
                ]);
            }

            \DB::commit();

            // Notify requester and warehouse staff
            if ($this->requestedBy) {
                $this->requestedBy->notify(new \App\Notifications\TransferShippedNotification($this));
            }

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error shipping transfer: '.$e->getMessage());

            return false;
        }
    }

    public function receive(int $userId, ?array $discrepancies = null, ?string $notes = null): bool
    {
        if ($this->status !== 'in_transit') {
            return false;
        }

        \DB::beginTransaction();
        try {
            // Update transfer status
            $this->status = 'received';
            $this->received_by = $userId;
            $this->received_at = now();
            $this->receiving_notes = $notes;
            $this->receiving_discrepancies = $discrepancies;
            $this->completed_at = now();
            $this->save();

            // Create inbound inventory movements (add to destination warehouse)
            $movementReason = MovementReason::where('code', 'TRANSFER_IN')->first();
            if (! $movementReason) {
                $movementReason = MovementReason::where('movement_type', 'in')
                    ->where('category', 'transfer')
                    ->first();
            }

            if ($movementReason) {
                foreach ($this->inventoryMovements()->where('movement_type', 'transfer_out')->get() as $outboundMovement) {
                    // Get current stock at destination
                    $currentStock = InventoryMovement::where('warehouse_id', $this->to_warehouse_id)
                        ->where('product_id', $outboundMovement->product_id)
                        ->whereNotNull('balance_quantity')
                        ->orderBy('movement_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();

                    $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
                    $receivedQuantity = $outboundMovement->quantity_out; // Use the shipped quantity
                    $newBalance = $previousBalance + $receivedQuantity;

                    // Create inbound movement at destination
                    InventoryMovement::create([
                        'company_id' => $outboundMovement->company_id,
                        'warehouse_id' => $this->to_warehouse_id,
                        'product_id' => $outboundMovement->product_id,
                        'movement_reason_id' => $movementReason->id,
                        'transfer_id' => $this->id,
                        'movement_type' => 'transfer_in',
                        'movement_date' => now(),
                        'quantity' => $receivedQuantity,
                        'quantity_in' => $receivedQuantity,
                        'quantity_out' => 0,
                        'balance_quantity' => $newBalance,
                        'unit_cost' => $outboundMovement->unit_cost,
                        'total_cost' => $outboundMovement->unit_cost * $receivedQuantity,
                        'lot_number' => $outboundMovement->lot_number,
                        'expiration_date' => $outboundMovement->expiration_date,
                        'notes' => "Recepción de traslado {$this->transfer_number}",
                        'is_active' => true,
                        'active_at' => now(),
                        'created_by' => $userId,
                    ]);
                }
            }

            \DB::commit();

            // Notify requester and relevant parties
            if ($this->requestedBy) {
                $this->requestedBy->notify(new \App\Notifications\TransferReceivedNotification($this));
            }
            if ($this->approvedBy && $this->approvedBy->id !== $this->requestedBy?->id) {
                $this->approvedBy->notify(new \App\Notifications\TransferReceivedNotification($this));
            }

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error receiving transfer: '.$e->getMessage());

            return false;
        }
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['received', 'cancelled'])) {
            return false;
        }

        // If already shipped, cannot cancel
        if ($this->status === 'in_transit') {
            return false;
        }

        $this->status = 'cancelled';
        $this->cancelled_at = now();

        // Delete any pending inventory movements
        $this->inventoryMovements()->where('movement_type', 'pending')->delete();

        return $this->save();
    }
}
