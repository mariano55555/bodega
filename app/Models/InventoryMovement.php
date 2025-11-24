<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryMovementFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'product_id',
        'product_lot_id',
        'warehouse_id',
        'movement_type',
        'movement_date',
        'movement_reason_id',
        'quantity',
        'quantity_in',
        'quantity_out',
        'balance_quantity',
        'previous_quantity',
        'new_quantity',
        'unit_cost',
        'total_cost',
        'reference_number',
        'notes',
        'lot_number',
        'batch_number',
        'expiration_date',
        'location',
        'from_warehouse_id',
        'to_warehouse_id',
        'from_storage_location_id',
        'to_storage_location_id',
        'transfer_id',
        'dispatch_id',
        'purchase_id',
        'donation_id',
        'document_type',
        'document_number',
        'metadata',
        'movement_data',
        'status',
        'scheduled_at',
        'is_confirmed',
        'confirmed_at',
        'confirmed_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'completed_at',
        'completed_by',
        'requires_quality_check',
        'quality_approved',
        'quality_checked_by',
        'quality_checked_at',
        'quality_notes',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'quantity_in' => 'decimal:4',
            'quantity_out' => 'decimal:4',
            'balance_quantity' => 'decimal:4',
            'previous_quantity' => 'decimal:4',
            'new_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'movement_date' => 'date',
            'expiration_date' => 'date',
            'metadata' => 'array',
            'movement_data' => 'array',
            'scheduled_at' => 'datetime',
            'is_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
            'requires_quality_check' => 'boolean',
            'quality_approved' => 'boolean',
            'quality_checked_at' => 'datetime',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($movement) {
            if (auth()->check()) {
                $movement->created_by = auth()->id();
            }
            if (is_null($movement->active_at) && $movement->is_active) {
                $movement->active_at = now();
            }
            if (is_null($movement->status)) {
                $movement->status = 'pending';
            }
        });

        static::updating(function ($movement) {
            if (auth()->check()) {
                $movement->updated_by = auth()->id();
            }
            if ($movement->isDirty('is_active')) {
                $movement->active_at = $movement->is_active ? now() : null;
            }
        });

        static::deleting(function ($movement) {
            if (auth()->check()) {
                $movement->deleted_by = auth()->id();
                $movement->save();
            }
        });
    }

    /**
     * Get the company associated with this movement.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the product associated with this movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product lot associated with this movement.
     */
    public function productLot(): BelongsTo
    {
        return $this->belongsTo(ProductLot::class);
    }

    /**
     * Get the movement reason associated with this movement.
     */
    public function movementReason(): BelongsTo
    {
        return $this->belongsTo(MovementReason::class);
    }

    /**
     * Get the warehouse associated with this movement.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the from warehouse for transfers.
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the to warehouse for transfers.
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get the from storage location for transfers.
     */
    public function fromStorageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'from_storage_location_id');
    }

    /**
     * Get the to storage location for transfers.
     */
    public function toStorageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'to_storage_location_id');
    }

    /**
     * Get the transfer associated with this movement.
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'transfer_id');
    }

    /**
     * Get the dispatch associated with this movement.
     */
    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class, 'dispatch_id');
    }

    /**
     * Get the purchase associated with this movement.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * Get the donation associated with this movement.
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class, 'donation_id');
    }

    /**
     * Get the user who created this movement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this movement.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this movement.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who confirmed this movement.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get the user who approved this movement.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected this movement.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user who completed this movement.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get the user who performed quality check.
     */
    public function qualityCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_checked_by');
    }

    /**
     * Get the supplier associated with this movement.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the customer associated with this movement.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the storage location associated with this movement.
     */
    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    /**
     * Get the adjustment that created this movement.
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'id', 'inventory_movement_id');
    }

    /**
     * Scope a query to only include active movements.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query by movement type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope a query by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query for pending movements.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query for approved movements.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query for completed movements.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query for rejected movements.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query for cancelled movements.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query by date range.
     */
    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query for movements requiring approval.
     */
    public function scopeRequiresApproval(Builder $query): Builder
    {
        return $query->whereHas('movementReason', function ($q) {
            $q->where('requires_approval', true);
        });
    }

    /**
     * Scope a query for movements requiring quality check.
     */
    public function scopeRequiresQualityCheck(Builder $query): Builder
    {
        return $query->where('requires_quality_check', true);
    }

    /**
     * Scope a query for inbound movements.
     */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->whereIn('movement_type', ['in', 'receipt', 'purchase', 'return_customer']);
    }

    /**
     * Scope a query for outbound movements.
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->whereIn('movement_type', ['out', 'sale', 'shipment', 'return_supplier']);
    }

    /**
     * Scope a query for transfer movements.
     */
    public function scopeTransfers(Builder $query): Builder
    {
        return $query->where('movement_type', 'transfer');
    }

    /**
     * Scope a query for adjustments.
     */
    public function scopeAdjustments(Builder $query): Builder
    {
        return $query->where('movement_type', 'adjustment');
    }

    /**
     * Scope a query for a specific product.
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query for a specific warehouse.
     */
    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Check if the movement can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending' && $this->approved_at === null;
    }

    /**
     * Check if the movement can be rejected.
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending', 'approved']) && $this->rejected_at === null;
    }

    /**
     * Check if the movement can be completed.
     */
    public function canBeCompleted(): bool
    {
        $hasRequiredApproval = ! $this->movementReason?->requires_approval || $this->status === 'approved';
        $hasRequiredQualityCheck = ! $this->requires_quality_check || $this->quality_approved === true;

        return $hasRequiredApproval && $hasRequiredQualityCheck && $this->completed_at === null;
    }

    /**
     * Approve the movement.
     */
    public function approve(int $userId, ?string $notes = null): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        return $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Reject the movement.
     */
    public function reject(int $userId, string $reason): bool
    {
        if (! $this->canBeRejected()) {
            return false;
        }

        return $this->update([
            'status' => 'rejected',
            'rejected_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Complete the movement.
     */
    public function complete(int $userId): bool
    {
        if (! $this->canBeCompleted()) {
            return false;
        }

        return $this->update([
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancel the movement.
     */
    public function cancel(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        return $this->update(['status' => 'cancelled']);
    }

    /**
     * Perform quality check.
     */
    public function performQualityCheck(int $userId, bool $approved, ?string $notes = null): bool
    {
        return $this->update([
            'quality_approved' => $approved,
            'quality_checked_by' => $userId,
            'quality_checked_at' => now(),
            'quality_notes' => $notes,
        ]);
    }

    /**
     * Get the total value of the movement.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }

    /**
     * Get the status in Spanish.
     */
    public function getStatusSpanishAttribute(): string
    {
        $statuses = [
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get the movement type in Spanish.
     */
    public function getMovementTypeSpanishAttribute(): string
    {
        $types = [
            'in' => 'Entrada',
            'out' => 'Salida',
            'transfer' => 'Transferencia',
            'adjustment' => 'Ajuste',
            'receipt' => 'Recepción',
            'purchase' => 'Compra',
            'sale' => 'Venta',
            'shipment' => 'Envío',
            'return_customer' => 'Devolución Cliente',
            'return_supplier' => 'Devolución Proveedor',
        ];

        return $types[$this->movement_type] ?? $this->movement_type;
    }

    /**
     * Check if the movement affects inventory.
     */
    public function affectsInventory(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get the quantity with appropriate sign for inventory calculations.
     */
    public function getSignedQuantityAttribute(): float
    {
        $inboundTypes = ['in', 'receipt', 'purchase', 'return_customer'];
        $sign = in_array($this->movement_type, $inboundTypes) ? 1 : -1;

        return $this->quantity * $sign;
    }

    /**
     * Generate a unique movement number.
     */
    public static function generateMovementNumber(string $movementType): string
    {
        $prefix = match ($movementType) {
            'in' => 'ENT',
            'out' => 'SAL',
            'transfer' => 'TRF',
            'adjustment' => 'AJU',
            default => 'MOV',
        };

        $date = now()->format('Ymd');
        $sequence = static::whereDate('created_at', now())
            ->where('movement_type', $movementType)
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Check if the movement needs approval based on business rules.
     */
    public function needsApproval(): bool
    {
        $reason = $this->movementReason;
        if (! $reason) {
            return false;
        }

        if ($reason->requires_approval) {
            $totalValue = $this->quantity * ($this->unit_cost ?? 0);

            return $reason->requiresApprovalForValue($totalValue);
        }

        return false;
    }

    /**
     * Process the movement through its lifecycle.
     */
    public function processMovement(): array
    {
        try {
            // Validate business rules
            if (! $this->validateMovementRules()) {
                return [
                    'success' => false,
                    'message' => 'El movimiento no cumple con las reglas de negocio',
                ];
            }

            // Check if approval is needed
            if ($this->needsApproval() && $this->status === 'pending') {
                return [
                    'success' => true,
                    'message' => 'Movimiento requiere aprobación',
                    'requires_approval' => true,
                ];
            }

            // If approved or doesn't need approval, mark as ready to complete
            if ($this->status === 'approved' || ! $this->needsApproval()) {
                $this->update(['status' => 'ready_to_complete']);

                return [
                    'success' => true,
                    'message' => 'Movimiento listo para completar',
                    'ready_to_complete' => true,
                ];
            }

            return [
                'success' => false,
                'message' => 'Estado de movimiento no válido para procesamiento',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error procesando movimiento: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Validate movement business rules.
     */
    protected function validateMovementRules(): bool
    {
        // Check if product lot is expired for outbound movements
        if ($this->product_lot_id && in_array($this->movement_type, ['out', 'transfer'])) {
            $lot = $this->productLot;
            if ($lot && $lot->isExpired()) {
                return false;
            }
        }

        // Check inventory availability for outbound movements
        if (in_array($this->movement_type, ['out', 'transfer'])) {
            return $this->validateInventoryAvailability();
        }

        return true;
    }

    /**
     * Validate inventory availability.
     */
    protected function validateInventoryAvailability(): bool
    {
        if ($this->product_lot_id) {
            $lot = $this->productLot;

            return $lot && $lot->quantity_remaining >= $this->quantity;
        }

        // Check total available inventory for the product
        $totalAvailable = ProductLot::forProduct($this->product_id)
            ->available()
            ->sum('quantity_remaining');

        return $totalAvailable >= $this->quantity;
    }

    /**
     * Get movement summary for reporting.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'movement_number' => $this->movement_number,
            'type' => $this->movement_type_spanish,
            'product' => $this->product?->name,
            'quantity' => $this->quantity,
            'status' => $this->status_spanish,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'value' => $this->total_value,
        ];
    }

    /**
     * Scope for movements within date range.
     */
    public function scopeWithinDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for movements by value range.
     */
    public function scopeByValueRange(Builder $query, float $minValue, float $maxValue): Builder
    {
        return $query->whereRaw('(quantity * unit_cost) BETWEEN ? AND ?', [$minValue, $maxValue]);
    }

    /**
     * Scope for movements requiring attention.
     */
    public function scopeRequiresAttention(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'pending')
                ->where('created_at', '<', now()->subHours(24))
                ->orWhere(function ($subQ) {
                    $subQ->where('requires_quality_check', true)
                        ->whereNull('quality_checked_at');
                });
        });
    }

    /**
     * Check if movement is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->scheduled_at && $this->status !== 'completed') {
            return $this->scheduled_at->isPast();
        }

        // Consider movements pending for more than 24 hours as overdue
        return $this->status === 'pending' && $this->created_at->diffInHours() > 24;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'movement_number';
    }
}
