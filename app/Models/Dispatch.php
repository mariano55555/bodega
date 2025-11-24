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

class Dispatch extends Model
{
    /** @use HasFactory<\Database\Factories\DispatchFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'customer_id',
        'dispatch_number',
        'slug',
        'dispatch_type',
        'destination_unit',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'delivery_address',
        'document_type',
        'document_number',
        'document_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total',
        'status',
        'approved_at',
        'approved_by',
        'approval_notes',
        'dispatched_at',
        'dispatched_by',
        'carrier',
        'tracking_number',
        'delivered_at',
        'delivered_by',
        'received_by_name',
        'delivery_notes',
        'notes',
        'admin_notes',
        'attachments',
        'justification',
        'project_code',
        'cost_center',
        'is_internal_use',
        'internal_use_reason',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'attachments' => 'array',
            'is_active' => 'boolean',
            'is_internal_use' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($dispatch) {
            if (empty($dispatch->dispatch_number)) {
                $dispatch->dispatch_number = 'DIS-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
            }
            if (empty($dispatch->slug)) {
                $dispatch->slug = Str::slug($dispatch->dispatch_number);
            }
            if (auth()->check()) {
                $dispatch->created_by = auth()->id();
            }
            if (is_null($dispatch->active_at) && $dispatch->is_active) {
                $dispatch->active_at = now();
            }
        });

        static::updating(function ($dispatch) {
            if (auth()->check()) {
                $dispatch->updated_by = auth()->id();
            }
            if ($dispatch->isDirty('is_active')) {
                $dispatch->active_at = $dispatch->is_active ? now() : null;
            }
        });

        static::deleting(function ($dispatch) {
            if (auth()->check()) {
                $dispatch->deleted_by = auth()->id();
                $dispatch->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['dispatch_number', 'status', 'dispatch_type', 'total', 'customer_id', 'warehouse_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Despacho '{$this->dispatch_number}' {$eventName}");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DispatchDetail::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
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
        return $this->hasMany(InventoryMovement::class);
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
        return $query->where('dispatch_type', $type);
    }

    public function scopeInternalUse($query)
    {
        return $query->where('is_internal_use', true);
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopeDispatched($query)
    {
        return $query->where('status', 'despachado');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->details->sum(fn ($detail) => $detail->subtotal);
        $this->tax_amount = $this->details->sum('tax_amount');
        $this->discount_amount = $this->details->sum('discount_amount');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount + $this->shipping_cost;
        $this->save();
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

    public function dispatch(int $userId, ?string $carrier = null, ?string $trackingNumber = null): bool
    {
        if ($this->status !== 'aprobado') {
            return false;
        }

        // Use database transaction to ensure consistency
        \DB::beginTransaction();
        try {
            // Reserve stock and update dispatch status
            foreach ($this->details as $detail) {
                $detail->is_reserved = true;
                $detail->reserved_by = $userId;
                $detail->reserved_at = now();
                $detail->quantity_dispatched = $detail->quantity;
                $detail->save();
            }

            // Update dispatch status
            $this->status = 'despachado';
            $this->dispatched_by = $userId;
            $this->dispatched_at = now();
            $this->carrier = $carrier;
            $this->tracking_number = $trackingNumber;
            $this->save();

            // Get the dispatch movement reason based on dispatch type
            $movementReasonCode = match ($this->dispatch_type) {
                'venta' => 'DISPATCH_SALE',
                'interno' => 'DISPATCH_INTERNAL',
                'externo' => 'DISPATCH_EXTERNAL',
                'donacion' => 'DISPATCH_DONATION',
                default => 'DISPATCH_INTERNAL',
            };

            $movementReason = MovementReason::where('code', $movementReasonCode)->first();

            if (! $movementReason) {
                // Fallback to a generic outbound reason
                $movementReason = MovementReason::where('movement_type', 'out')->first();
            }

            if (! $movementReason) {
                throw new \Exception('Movement reason for dispatch not found');
            }

            // Determine the movement_type based on dispatch_type
            // Valid ENUM values: purchase, sale, transfer_out, transfer_in, adjustment, return, damage, theft, expiry, production, count
            $movementType = match ($this->dispatch_type) {
                'venta' => 'sale',
                'interno' => 'transfer_out',
                'externo' => 'transfer_out',
                'donacion' => 'sale', // Donations treated as outbound sales for inventory purposes
                default => 'sale',
            };

            // Create inventory movements for each dispatch detail
            foreach ($this->details as $detail) {
                // Get current stock for this product in this warehouse
                $currentStock = InventoryMovement::where('warehouse_id', $this->warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->whereNotNull('balance_quantity')
                    ->orderBy('movement_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
                $newBalance = $previousBalance - $detail->quantity_dispatched;

                // Create the inventory movement (outbound)
                InventoryMovement::create([
                    'company_id' => $this->company_id,
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $detail->product_id,
                    'movement_reason_id' => $movementReason->id,
                    'dispatch_id' => $this->id,
                    'movement_type' => $movementType,
                    'movement_date' => $this->dispatched_at ?? now(),
                    'quantity' => $detail->quantity_dispatched,
                    'quantity_in' => 0,
                    'quantity_out' => $detail->quantity_dispatched,
                    'balance_quantity' => $newBalance,
                    'previous_quantity' => $previousBalance,
                    'new_quantity' => $newBalance,
                    'unit_cost' => $detail->unit_price,
                    'total_cost' => $detail->quantity_dispatched * $detail->unit_price,
                    'document_type' => $this->document_type,
                    'document_number' => $this->document_number,
                    'notes' => $detail->notes ?? "Despacho {$this->dispatch_number} - {$this->dispatch_type}",
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => $userId,
                ]);
            }

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error dispatching dispatch: '.$e->getMessage());

            return false;
        }
    }

    public function deliver(int $userId, string $receivedByName, ?string $notes = null): bool
    {
        if ($this->status !== 'despachado') {
            return false;
        }

        // Update all details as delivered
        foreach ($this->details as $detail) {
            $detail->quantity_delivered = $detail->quantity_dispatched;
            $detail->save();
        }

        $this->status = 'entregado';
        $this->delivered_by = $userId;
        $this->delivered_at = now();
        $this->received_by_name = $receivedByName;
        $this->delivery_notes = $notes;

        return $this->save();
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['despachado', 'entregado'])) {
            return false; // Cannot cancel dispatched or delivered dispatches
        }

        // Release reservations if any
        foreach ($this->details as $detail) {
            if ($detail->is_reserved) {
                $detail->is_reserved = false;
                $detail->reserved_by = null;
                $detail->reserved_at = null;
                $detail->save();
            }
        }

        $this->status = 'cancelado';

        return $this->save();
    }

    public function getStatusSpanishAttribute(): string
    {
        $statuses = [
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'despachado' => 'Despachado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getDispatchTypeSpanishAttribute(): string
    {
        $types = [
            'venta' => 'Venta',
            'interno' => 'Interno',
            'externo' => 'Externo',
            'donacion' => 'Donación',
        ];

        return $types[$this->dispatch_type] ?? $this->dispatch_type;
    }

    public function submit(): bool
    {
        if ($this->status !== 'borrador') {
            return false;
        }

        $this->status = 'pendiente';

        return $this->save();
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'borrador';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pendiente';
    }

    public function canBeDispatched(): bool
    {
        return $this->status === 'aprobado';
    }

    public function canBeDelivered(): bool
    {
        return $this->status === 'despachado';
    }

    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, ['despachado', 'entregado', 'cancelado']);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['borrador', 'pendiente']);
    }
}
