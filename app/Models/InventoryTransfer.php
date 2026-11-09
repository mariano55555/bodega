<?php

namespace App\Models;

use App\Exceptions\InventoryReversalException;
use App\Jobs\UpdateInventoryLevels;
use App\Notifications\TransferApprovedNotification;
use App\Notifications\TransferReceivedNotification;
use App\Notifications\TransferShippedNotification;
use App\Services\DocumentStockService;
use App\Services\InventoryValuationService;
use App\Services\KardexService;
use Database\Factories\InventoryTransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    /**
     * Corrections applied after this document moved stock, newest first.
     */
    public function corrections(): MorphMany
    {
        return $this->morphMany(DocumentCorrection::class, 'correctable')->latest();
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
            $manualCostAllowed = (bool) $this->fromWarehouse->company?->allowsManualUnitCost();

            // Create outbound inventory movements from transfer details
            foreach ($this->details as $detail) {
                $averageCost = $valuationService->currentAverageCost((int) $detail->product_id, (int) $this->from_warehouse_id);

                // The weighted average is the rule; the typed cost is honoured only while the company allows it
                $unitCost = ($manualCostAllowed && $detail->unit_cost !== null) ? (float) $detail->unit_cost : $averageCost;
                $isManualCost = abs($unitCost - $averageCost) > 0.000005;

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
                    'metadata' => $isManualCost ? ['manual_unit_cost' => true, 'average_unit_cost' => $averageCost, 'manual_cost_user_id' => $userId] : null,
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

                // Receive the net quantity that left the origin, so corrections made in transit are respected
                $shipped = app(DocumentStockService::class)->netEffects(
                    $this->inventoryMovements()->where('warehouse_id', $this->from_warehouse_id)->get()
                );

                foreach ($shipped as $effect) {
                    if ($effect['quantity'] >= 0) {
                        continue;
                    }

                    $receivedQuantity = round(abs($effect['quantity']), 5);
                    $unitCost = round(abs($effect['value'] / $effect['quantity']), 5);

                    // Create inbound movement at destination with automatic balance recalculation
                    $movement = $kardexService->createMovement([
                        'company_id' => $this->fromWarehouse->company_id,
                        'warehouse_id' => $this->to_warehouse_id,
                        'product_id' => $effect['product_id'],
                        'movement_reason_id' => $movementReason->id,
                        'transfer_id' => $this->id,
                        'movement_type' => 'transfer_in',
                        'movement_date' => $this->document_date ?? now(),
                        'quantity' => $receivedQuantity,
                        'quantity_in' => $receivedQuantity,
                        'quantity_out' => 0,
                        'unit_cost' => $unitCost,
                        'total_cost' => $unitCost * $receivedQuantity,
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
            if (in_array($this->status, ['in_transit', 'received'])) {
                $this->reverseStock($userId);
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
     * Undo the net stock effect of this transfer, including any correction applied to it:
     * quantities return to where they came from at the cost they moved with.
     */
    protected function reverseStock(?int $userId): void
    {
        $movements = $this->inventoryMovements()->get();

        if ($movements->isEmpty()) {
            return;
        }

        $service = app(DocumentStockService::class);
        $companyId = (int) $this->fromWarehouse->company_id;

        $service->validatePeriods($companyId, $movements, [], now());

        $service->rebalance($service->netEffects($movements), [], [
            'company_id' => $companyId,
            'movement_date' => now(),
            'links' => ['transfer_id' => $this->id],
            'document_type' => 'transfer_cancellation',
            'inbound_type' => 'transfer_in',
            'outbound_type' => 'transfer_out',
            'inbound_reason' => $service->reason('TRANSFER_IN', 'in', 'transfer'),
            'outbound_reason' => $service->reason('TRANSFER_OUT', 'out', 'transfer'),
            'inbound_notes' => "Anulación de traslado {$this->transfer_number}: devolución a bodega",
            'outbound_notes' => "Anulación de traslado {$this->transfer_number}: salida de bodega",
            'metadata' => ['cancellation' => true],
            'user_id' => $userId,
            'shortage_message' => fn (int $warehouseId, int $productId, float $needed, float $available) => 'No se puede anular el traslado: la bodega '
                .(Warehouse::find($warehouseId)?->name ?? "#{$warehouseId}").' ya no tiene las '.number_format($needed, 2)
                .' unidades de '.(Product::find($productId)?->name ?? "Producto #{$productId}").' (disponible '.number_format($available, 2).')',
        ]);
    }
}
