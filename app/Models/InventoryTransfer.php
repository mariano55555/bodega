<?php

namespace App\Models;

use App\Exceptions\InventoryReversalException;
use App\Jobs\UpdateInventoryLevels;
use App\Notifications\TransferApprovedNotification;
use App\Notifications\TransferReceivedNotification;
use App\Notifications\TransferShippedNotification;
use App\Services\InventoryValuationService;
use App\Services\KardexService;
use Database\Factories\InventoryTransferFactory;
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
    /** @use HasFactory<InventoryTransferFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'transfer_number';
    }

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'document_date',
        'physical_document_number',
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
        'cancelled_by',
        'cancellation_reason',
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
            'document_date' => 'date',
            'metadata' => 'array',
            'receiving_discrepancies' => 'array',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'shipping_cost' => 'decimal:5',
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
                $this->requestedBy->notify(new TransferApprovedNotification($this));
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
            // Validate origin warehouse period is not closed
            InventoryClosure::validatePeriodOpen($this->fromWarehouse->company_id, $this->from_warehouse_id, $this->document_date ?? now());

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

            // Validate stock availability before creating movements
            $stockErrors = [];
            foreach ($this->details->load('product') as $detail) {
                $inventory = Inventory::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $this->from_warehouse_id)
                    ->lockForUpdate()
                    ->first();

                $available = $inventory?->available_quantity ?? 0;
                if ($available < $detail->quantity) {
                    $stockErrors[] = "{$detail->product->name}: disponible ".number_format($available, 2).', solicitado '.number_format($detail->quantity, 2);
                }
            }

            if (! empty($stockErrors)) {
                throw new \Exception('Stock insuficiente en bodega origen. '.implode('; ', $stockErrors));
            }

            $kardexService = app(KardexService::class);
            $valuationService = app(InventoryValuationService::class);

            // Create outbound inventory movements from transfer details
            foreach ($this->details as $detail) {
                // Outbound cost is always the current weighted average of the origin warehouse
                $unitCost = $valuationService->currentAverageCost((int) $detail->product_id, (int) $this->from_warehouse_id);
                $detail->unit_cost = $unitCost;
                $detail->save();

                // Create outbound movement (subtract from origin) with automatic balance recalculation
                $movement = $kardexService->createMovement([
                    'company_id' => $this->fromWarehouse->company_id,
                    'warehouse_id' => $this->from_warehouse_id,
                    'product_id' => $detail->product_id,
                    'movement_reason_id' => $movementReason->id,
                    'transfer_id' => $this->id,
                    'movement_type' => 'transfer_out',
                    'movement_date' => $this->document_date ?? now(),
                    'quantity' => -$detail->quantity,
                    'quantity_in' => 0,
                    'quantity_out' => $detail->quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $detail->quantity,
                    'notes' => $detail->notes ?? "Envío de traslado {$this->transfer_number}",
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => $userId,
                ]);

                // Update inventory levels synchronously
                UpdateInventoryLevels::dispatchSync($movement);
            }

            \DB::commit();

            // Notify requester and warehouse staff
            if ($this->requestedBy) {
                $this->requestedBy->notify(new TransferShippedNotification($this));
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
            // Validate destination warehouse period is not closed
            InventoryClosure::validatePeriodOpen($this->toWarehouse->company_id, $this->to_warehouse_id, $this->document_date ?? now());

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
                $kardexService = app(KardexService::class);

                foreach ($this->inventoryMovements()->where('movement_type', 'transfer_out')->get() as $outboundMovement) {
                    $receivedQuantity = $outboundMovement->quantity_out; // Use the shipped quantity

                    // Create inbound movement at destination with automatic balance recalculation
                    $movement = $kardexService->createMovement([
                        'company_id' => $outboundMovement->company_id,
                        'warehouse_id' => $this->to_warehouse_id,
                        'product_id' => $outboundMovement->product_id,
                        'movement_reason_id' => $movementReason->id,
                        'transfer_id' => $this->id,
                        'movement_type' => 'transfer_in',
                        'movement_date' => $this->document_date ?? now(),
                        'quantity' => $receivedQuantity,
                        'quantity_in' => $receivedQuantity,
                        'quantity_out' => 0,
                        'unit_cost' => $outboundMovement->unit_cost,
                        'total_cost' => $outboundMovement->unit_cost * $receivedQuantity,
                        'lot_number' => $outboundMovement->lot_number,
                        'expiration_date' => $outboundMovement->expiration_date,
                        'notes' => "Recepción de traslado {$this->transfer_number}",
                        'is_active' => true,
                        'active_at' => now(),
                        'created_by' => $userId,
                    ]);

                    // Update inventory levels synchronously
                    UpdateInventoryLevels::dispatchSync($movement);
                }
            }

            \DB::commit();

            // Notify requester and relevant parties
            if ($this->requestedBy) {
                $this->requestedBy->notify(new TransferReceivedNotification($this));
            }
            if ($this->approvedBy && $this->approvedBy->id !== $this->requestedBy?->id) {
                $this->approvedBy->notify(new TransferReceivedNotification($this));
            }

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error receiving transfer: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Cancel the transfer. A transfer that already moved stock is reversed:
     * the quantities return to the origin warehouse and leave the destination.
     *
     * @throws InventoryReversalException when the reversal is not possible (closed period or stock already consumed at destination)
     */
    public function cancel(?int $userId = null, ?string $reason = null): bool
    {
        if (in_array($this->status, ['cancelled', 'completed'])) {
            return false;
        }

        \DB::beginTransaction();
        try {
            if ($this->status === 'received') {
                $this->reverseInboundMovements($userId);
            }

            if (in_array($this->status, ['in_transit', 'received'])) {
                $this->reverseOutboundMovements($userId);
            }

            $this->status = 'cancelled';
            $this->cancelled_at = now();
            $this->cancelled_by = $userId;
            $this->cancellation_reason = $reason;
            $this->save();

            // Delete any pending inventory movements
            $this->inventoryMovements()->where('movement_type', 'pending')->delete();

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error cancelling transfer: '.$e->getMessage());

            throw new InventoryReversalException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Put the shipped quantities back into the origin warehouse at the cost they left with.
     */
    protected function reverseOutboundMovements(?int $userId): void
    {
        $outboundMovements = $this->inventoryMovements()
            ->where('movement_type', 'transfer_out')
            ->where('warehouse_id', $this->from_warehouse_id)
            ->get();

        if ($outboundMovements->isEmpty()) {
            return;
        }

        InventoryClosure::validatePeriodOpen($this->fromWarehouse->company_id, $this->from_warehouse_id, now());

        $movementReason = $this->resolveMovementReason('TRANSFER_IN', 'in');
        $kardexService = app(KardexService::class);
        $valuationService = app(InventoryValuationService::class);

        foreach ($outboundMovements as $movement) {
            $quantity = (float) $movement->quantity_out;

            $reversal = $kardexService->createMovement([
                'company_id' => $movement->company_id,
                'warehouse_id' => $this->from_warehouse_id,
                'product_id' => $movement->product_id,
                'movement_reason_id' => $movementReason->id,
                'transfer_id' => $this->id,
                'movement_type' => 'transfer_in',
                'movement_date' => now(),
                'quantity' => $quantity,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $quantity * (float) $movement->unit_cost,
                'document_type' => 'transfer_cancellation',
                'metadata' => ['reverses_movement_id' => $movement->id],
                'notes' => "Anulación de traslado {$this->transfer_number}: devolución a bodega origen",
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $userId,
            ]);

            $valuationService->applyMovement($reversal);
        }
    }

    /**
     * Take the received quantities out of the destination warehouse. Fails if the destination already consumed them.
     */
    protected function reverseInboundMovements(?int $userId): void
    {
        $inboundMovements = $this->inventoryMovements()
            ->where('movement_type', 'transfer_in')
            ->where('warehouse_id', $this->to_warehouse_id)
            ->with('product')
            ->get();

        if ($inboundMovements->isEmpty()) {
            return;
        }

        InventoryClosure::validatePeriodOpen($this->toWarehouse->company_id, $this->to_warehouse_id, now());

        $movementReason = $this->resolveMovementReason('TRANSFER_OUT', 'out');
        $kardexService = app(KardexService::class);
        $valuationService = app(InventoryValuationService::class);

        foreach ($inboundMovements as $movement) {
            $quantity = (float) $movement->quantity_in;

            $inventory = Inventory::where('product_id', $movement->product_id)
                ->where('warehouse_id', $this->to_warehouse_id)
                ->lockForUpdate()
                ->first();

            $available = (float) ($inventory?->available_quantity ?? 0);
            if ($available < $quantity) {
                $productName = $movement->product?->name ?? "Producto #{$movement->product_id}";

                throw new InventoryReversalException(
                    "No se puede anular el traslado: la bodega destino {$this->toWarehouse->name} ya no tiene las "
                    .number_format($quantity, 2)." unidades de {$productName} (disponible ".number_format($available, 2).')'
                );
            }

            $reversal = $kardexService->createMovement([
                'company_id' => $movement->company_id,
                'warehouse_id' => $this->to_warehouse_id,
                'product_id' => $movement->product_id,
                'movement_reason_id' => $movementReason->id,
                'transfer_id' => $this->id,
                'movement_type' => 'transfer_out',
                'movement_date' => now(),
                'quantity' => -$quantity,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $quantity * (float) $movement->unit_cost,
                'document_type' => 'transfer_cancellation',
                'metadata' => ['reverses_movement_id' => $movement->id],
                'notes' => "Anulación de traslado {$this->transfer_number}: salida de bodega destino",
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $userId,
            ]);

            $valuationService->applyMovement($reversal);
        }
    }

    protected function resolveMovementReason(string $code, string $movementType): MovementReason
    {
        $movementReason = MovementReason::where('code', $code)->first()
            ?? MovementReason::where('movement_type', $movementType)->where('category', 'transfer')->first()
            ?? MovementReason::where('movement_type', $movementType)->first();

        if (! $movementReason) {
            throw new InventoryReversalException("No existe un motivo de movimiento {$code} para registrar la anulación.");
        }

        return $movementReason;
    }
}
