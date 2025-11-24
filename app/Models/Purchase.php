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

class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'supplier_id',
        'purchase_number',
        'slug',
        'document_type',
        'document_number',
        'document_date',
        'due_date',
        'purchase_type',
        'payment_status',
        'payment_method',
        'acquisition_type',
        'project_name',
        'agreement_number',
        'is_retroactive',
        'fund_source',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total',
        'status',
        'approved_at',
        'approved_by',
        'received_at',
        'received_by',
        'notes',
        'admin_notes',
        'attachments',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
            'attachments' => 'array',
            'is_active' => 'boolean',
            'is_retroactive' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (empty($purchase->purchase_number)) {
                $purchase->purchase_number = 'PUR-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
            }
            if (empty($purchase->slug)) {
                $purchase->slug = Str::slug($purchase->purchase_number);
            }
            if (auth()->check()) {
                $purchase->created_by = auth()->id();
            }
            if (is_null($purchase->active_at) && $purchase->is_active) {
                $purchase->active_at = now();
            }
        });

        static::updating(function ($purchase) {
            if (auth()->check()) {
                $purchase->updated_by = auth()->id();
            }
            if ($purchase->isDirty('is_active')) {
                $purchase->active_at = $purchase->is_active ? now() : null;
            }
        });

        static::deleting(function ($purchase) {
            if (auth()->check()) {
                $purchase->deleted_by = auth()->id();
                $purchase->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['purchase_number', 'status', 'total', 'supplier_id', 'warehouse_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Compra '{$this->purchase_number}' {$eventName}");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
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

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopeByAcquisitionType($query, string $type)
    {
        return $query->where('acquisition_type', $type);
    }

    public function scopeRetroactive($query)
    {
        return $query->where('is_retroactive', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getAcquisitionTypeLabel(): string
    {
        return match ($this->acquisition_type) {
            'normal' => 'Compra Normal',
            'convenio' => 'Convenio',
            'proyecto' => 'Proyecto',
            'otro' => 'Otro',
            default => 'Desconocido',
        };
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->details->sum(fn ($detail) => $detail->quantity * $detail->unit_cost);
        $this->tax_amount = $this->details->sum('tax_amount');
        $this->discount_amount = $this->details->sum('discount_amount');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount + $this->shipping_cost;
        $this->save();
    }

    /**
     * Submit the purchase for approval (borrador -> pendiente)
     */
    public function submit(): bool
    {
        if ($this->status !== 'borrador') {
            return false;
        }

        $this->status = 'pendiente';

        return $this->save();
    }

    public function approve(int $userId): bool
    {
        if ($this->status !== 'pendiente') {
            return false;
        }

        $this->status = 'aprobado';
        $this->approved_by = $userId;
        $this->approved_at = now();

        return $this->save();
    }

    public function receive(int $userId): bool
    {
        if ($this->status !== 'aprobado') {
            return false;
        }

        // Use database transaction to ensure consistency
        \DB::beginTransaction();
        try {
            // Update purchase status
            $this->status = 'recibido';
            $this->received_by = $userId;
            $this->received_at = now();
            $this->save();

            // Get the purchase receive movement reason
            $movementReason = MovementReason::where('code', 'PURCH_RCV')->firstOrFail();

            // Create inventory movements for each purchase detail
            foreach ($this->details as $detail) {
                // Get current stock for this product in this warehouse
                $currentStock = InventoryMovement::where('warehouse_id', $this->warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->orderBy('movement_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
                $newBalance = $previousBalance + $detail->quantity;

                // Create the inventory movement
                InventoryMovement::create([
                    'company_id' => $this->company_id,
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $detail->product_id,
                    'movement_reason_id' => $movementReason->id,
                    'purchase_id' => $this->id,
                    'movement_type' => 'purchase',
                    'movement_date' => $this->received_at ?? now(),
                    'quantity' => $detail->quantity,
                    'quantity_in' => $detail->quantity,
                    'quantity_out' => 0,
                    'balance_quantity' => $newBalance,
                    'unit_cost' => $detail->unit_cost,
                    'total_cost' => $detail->total,
                    'lot_number' => $detail->lot_number,
                    'expiration_date' => $detail->expiration_date,
                    'document_type' => $this->document_type,
                    'document_number' => $this->document_number,
                    'notes' => "Recepción de compra {$this->purchase_number}",
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => $userId,
                ]);

                // Update or create inventory record for stock tracking
                $inventory = Inventory::firstOrNew([
                    'product_id' => $detail->product_id,
                    'warehouse_id' => $this->warehouse_id,
                ]);

                $inventory->quantity = ($inventory->quantity ?? 0) + $detail->quantity;
                $inventory->unit_cost = $detail->unit_cost;
                $inventory->lot_number = $detail->lot_number;
                $inventory->expiration_date = $detail->expiration_date;
                $inventory->is_active = true;
                $inventory->active_at = $inventory->active_at ?? now();
                $inventory->save();
            }

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error receiving purchase: '.$e->getMessage());

            return false;
        }
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['recibido', 'cancelado'])) {
            return false;
        }

        $this->status = 'cancelado';

        return $this->save();
    }
}
