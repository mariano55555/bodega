<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryAdjustment extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryAdjustmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'adjustment_number',
        'slug',
        'adjustment_type',
        'quantity',
        'unit_cost',
        'total_value',
        'reason',
        'justification',
        'corrective_actions',
        'reference_document',
        'reference_number',
        'attachments',
        'status',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'approval_notes',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'processed_at',
        'processed_by',
        'inventory_movement_id',
        'storage_location_id',
        'batch_number',
        'expiry_date',
        'notes',
        'admin_notes',
        'cost_center',
        'project_code',
        'department',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_value' => 'decimal:2',
            'expiry_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'processed_at' => 'datetime',
            'attachments' => 'array',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($adjustment) {
            if (empty($adjustment->adjustment_number)) {
                $adjustment->adjustment_number = 'ADJ-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
            }
            if (empty($adjustment->slug)) {
                $adjustment->slug = Str::slug($adjustment->adjustment_number);
            }
            if (auth()->check()) {
                $adjustment->created_by = auth()->id();
            }
            if (is_null($adjustment->active_at) && $adjustment->is_active) {
                $adjustment->active_at = now();
            }

            // Calculate total value if not set
            if (empty($adjustment->total_value) && ! empty($adjustment->quantity) && ! empty($adjustment->unit_cost)) {
                $adjustment->total_value = abs($adjustment->quantity) * $adjustment->unit_cost;
            }
        });

        static::updating(function ($adjustment) {
            if (auth()->check()) {
                $adjustment->updated_by = auth()->id();
            }
            if ($adjustment->isDirty('is_active')) {
                $adjustment->active_at = $adjustment->is_active ? now() : null;
            }

            // Recalculate total value if quantity or unit_cost changed
            if (($adjustment->isDirty('quantity') || $adjustment->isDirty('unit_cost')) && ! empty($adjustment->quantity) && ! empty($adjustment->unit_cost)) {
                $adjustment->total_value = abs($adjustment->quantity) * $adjustment->unit_cost;
            }
        });

        static::deleting(function ($adjustment) {
            if (auth()->check()) {
                $adjustment->deleted_by = auth()->id();
                $adjustment->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['adjustment_number', 'status', 'adjustment_type', 'quantity', 'product_id', 'warehouse_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Ajuste '{$this->adjustment_number}' {$eventName}");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
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

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('adjustment_type', $type);
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'procesado');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'borrador');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function submit(int $userId): bool
    {
        if ($this->status !== 'borrador') {
            return false;
        }

        $this->status = 'pendiente';
        $this->submitted_by = $userId;
        $this->submitted_at = now();

        return $this->save();
    }

    public function approve(int $userId, ?string $notes = null): bool
    {
        if ($this->status !== 'pendiente') {
            return false;
        }

        $this->status = 'aprobado';
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->approval_notes = $notes;

        return $this->save();
    }

    public function reject(int $userId, string $reason): bool
    {
        if ($this->status !== 'pendiente') {
            return false;
        }

        $this->status = 'rechazado';
        $this->rejected_by = $userId;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    public function process(int $userId): bool
    {
        if ($this->status !== 'aprobado') {
            return false;
        }

        \DB::beginTransaction();
        try {
            // Get current stock for this product in this warehouse
            $currentStock = InventoryMovement::where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $this->product_id)
                ->whereNotNull('balance_quantity')
                ->orderBy('movement_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
            $newBalance = $previousBalance + $this->quantity; // Can be positive or negative

            // Get movement reason for adjustments based on quantity direction
            $isPositiveAdjustment = $this->isPositiveAdjustment();
            $movementReasonCode = $isPositiveAdjustment ? 'ADJ_POS' : 'ADJ_NEG';

            $movementReason = MovementReason::where('code', $movementReasonCode)->first();

            if (! $movementReason) {
                // Fallback to generic adjustment reason based on quantity direction
                $movementType = $isPositiveAdjustment ? 'in' : 'out';
                $movementReason = MovementReason::where('movement_type', $movementType)
                    ->where('category', 'adjustment')
                    ->first();
            }

            if (! $movementReason) {
                // Last resort fallback - get any active movement reason of the right type
                $movementType = $isPositiveAdjustment ? 'in' : 'out';
                $movementReason = MovementReason::where('movement_type', $movementType)
                    ->where('is_active', true)
                    ->first();
            }

            if (! $movementReason) {
                throw new \Exception('Movement reason for adjustment not found');
            }

            // Determine if it's inbound or outbound
            $isInbound = $this->quantity >= 0;
            $absoluteQuantity = abs($this->quantity);

            // Create inventory movement
            $movement = InventoryMovement::create([
                'company_id' => $this->company_id,
                'warehouse_id' => $this->warehouse_id,
                'product_id' => $this->product_id,
                'movement_reason_id' => $movementReason->id,
                'movement_type' => 'adjustment',
                'movement_date' => now(),
                'quantity' => $absoluteQuantity,
                'quantity_in' => $isInbound ? $absoluteQuantity : 0,
                'quantity_out' => $isInbound ? 0 : $absoluteQuantity,
                'balance_quantity' => $newBalance,
                'previous_quantity' => $previousBalance,
                'new_quantity' => $newBalance,
                'unit_cost' => $this->unit_cost,
                'total_cost' => $this->total_value,
                'document_type' => 'adjustment',
                'document_number' => $this->adjustment_number,
                'notes' => $this->reason,
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $userId,
            ]);

            // Update adjustment with processing information
            $this->status = 'procesado';
            $this->processed_by = $userId;
            $this->processed_at = now();
            $this->inventory_movement_id = $movement->id;
            $this->save();

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error processing inventory adjustment: '.$e->getMessage());

            return false;
        }
    }

    public function cancel(): bool
    {
        if ($this->status === 'procesado') {
            return false; // Cannot cancel processed adjustments
        }

        $this->status = 'cancelado';

        return $this->save();
    }

    public function getStatusSpanishAttribute(): string
    {
        $statuses = [
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente Aprobación',
            'aprobado' => 'Aprobado',
            'procesado' => 'Procesado',
            'rechazado' => 'Rechazado',
            'cancelado' => 'Cancelado',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getAdjustmentTypeSpanishAttribute(): string
    {
        $types = [
            'positive' => 'Ajuste Positivo',
            'negative' => 'Ajuste Negativo',
            'damage' => 'Producto Dañado',
            'expiry' => 'Producto Vencido',
            'loss' => 'Pérdida/Robo',
            'correction' => 'Corrección de Conteo',
            'return' => 'Devolución',
            'other' => 'Otro',
        ];

        return $types[$this->adjustment_type] ?? $this->adjustment_type;
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'borrador';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pendiente';
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'pendiente';
    }

    public function canBeProcessed(): bool
    {
        return $this->status === 'aprobado';
    }

    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, ['procesado', 'cancelado']);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['borrador', 'rechazado']);
    }

    public function isPositiveAdjustment(): bool
    {
        return $this->quantity >= 0;
    }

    public function isNegativeAdjustment(): bool
    {
        return $this->quantity < 0;
    }
}
