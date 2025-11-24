<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryClosure extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'closure_number',
        'slug',
        'year',
        'month',
        'closure_date',
        'period_start_date',
        'period_end_date',
        'status',
        'total_products',
        'total_movements',
        'total_value',
        'total_quantity',
        'products_with_discrepancies',
        'total_discrepancy_value',
        'discrepancy_notes',
        'requires_approval',
        'is_approved',
        'approved_by',
        'approved_at',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
        'reopening_reason',
        'notes',
        'observations',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
        'active_at',
    ];

    protected $casts = [
        'closure_date' => 'date',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'total_value' => 'decimal:2',
        'total_quantity' => 'decimal:4',
        'total_discrepancy_value' => 'decimal:2',
        'requires_approval' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'active_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($closure) {
            if (! $closure->closure_number) {
                $closure->closure_number = static::generateClosureNumber($closure->year, $closure->month);
            }

            if (! $closure->slug) {
                $closure->slug = Str::slug($closure->closure_number);
            }

            $closure->active_at = now();
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['closure_number', 'status', 'year', 'month', 'warehouse_id', 'is_approved'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Cierre '{$this->closure_number}' {$eventName}");
    }

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(InventoryClosureDetail::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
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

    // Scopes

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    public function scopeInProcess($query)
    {
        return $query->where('status', 'en_proceso');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'cerrado');
    }

    public function scopeReopened($query)
    {
        return $query->where('status', 'reabierto');
    }

    public function scopeWithDiscrepancies($query)
    {
        return $query->where('products_with_discrepancies', '>', 0);
    }

    // Business Logic Methods

    /**
     * Generate closure number in format: CLS-YYYYMM-XXXX
     */
    public static function generateClosureNumber(int $year, int $month): string
    {
        $yearMonth = sprintf('%04d%02d', $year, $month);
        $lastClosure = static::where('year', $year)
            ->where('month', $month)
            ->latest('id')
            ->first();

        $sequence = $lastClosure ? (int) substr($lastClosure->closure_number, -4) + 1 : 1;

        return sprintf('CLS-%s-%04d', $yearMonth, $sequence);
    }

    /**
     * Process the closure - calculate all balances for products
     */
    public function process(): bool
    {
        if ($this->status !== 'en_proceso') {
            return false;
        }

        DB::beginTransaction();
        try {
            // Get all products with movements in this warehouse during the period
            $products = $this->getProductsWithMovements();

            foreach ($products as $product) {
                $this->processProductClosure($product);
            }

            // Calculate totals
            $this->calculateTotals();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing closure: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get products with movements in the warehouse during the period
     */
    private function getProductsWithMovements()
    {
        return DB::table('inventory_movements')
            ->select('product_id')
            ->where('warehouse_id', $this->warehouse_id)
            ->where('company_id', $this->company_id)
            ->whereBetween('movement_date', [$this->period_start_date, $this->period_end_date])
            ->distinct()
            ->pluck('product_id');
    }

    /**
     * Process closure for a single product
     */
    private function processProductClosure($productId): void
    {
        // Get opening balance (closing of previous period)
        $opening = $this->getOpeningBalance($productId);

        // Calculate period movements
        $movements = $this->calculatePeriodMovements($productId);

        // Get the last balance from the period (most accurate closing balance)
        $lastMovementInPeriod = DB::table('inventory_movements')
            ->where('warehouse_id', $this->warehouse_id)
            ->where('product_id', $productId)
            ->whereBetween('movement_date', [$this->period_start_date, $this->period_end_date])
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        // Calculate closing balance - prefer balance_quantity from last movement if available
        if ($lastMovementInPeriod && $lastMovementInPeriod->balance_quantity !== null) {
            $closingQuantity = (float) $lastMovementInPeriod->balance_quantity;
            $unitCost = (float) ($lastMovementInPeriod->unit_cost ?? $opening['unit_cost']);
        } else {
            $closingQuantity = $opening['quantity'] + $movements['quantity_in'] - $movements['quantity_out'];
            $unitCost = $opening['unit_cost'];
        }

        $closingValue = $closingQuantity * $unitCost;

        // Create or update detail record
        InventoryClosureDetail::updateOrCreate(
            [
                'inventory_closure_id' => $this->id,
                'product_id' => $productId,
            ],
            [
                'opening_quantity' => $opening['quantity'],
                'opening_unit_cost' => $opening['unit_cost'],
                'opening_total_value' => $opening['value'],
                'quantity_in' => $movements['quantity_in'],
                'quantity_out' => $movements['quantity_out'],
                'movement_count' => $movements['count'],
                'calculated_closing_quantity' => $closingQuantity,
                'calculated_closing_unit_cost' => $unitCost,
                'calculated_closing_value' => $closingValue,
                'adjusted_closing_quantity' => $closingQuantity,
                'adjusted_closing_unit_cost' => $unitCost,
                'adjusted_closing_value' => $closingValue,
            ]
        );
    }

    /**
     * Get opening balance for a product
     */
    private function getOpeningBalance($productId): array
    {
        // Try to get from previous closure
        $previousClosure = static::where('company_id', $this->company_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->where('status', 'cerrado')
            ->where(function ($q) {
                $q->where('year', '<', $this->year)
                    ->orWhere(function ($q2) {
                        $q2->where('year', '=', $this->year)
                            ->where('month', '<', $this->month);
                    });
            })
            ->latest('year')
            ->latest('month')
            ->first();

        if ($previousClosure) {
            $detail = $previousClosure->details()->where('product_id', $productId)->first();
            if ($detail) {
                return [
                    'quantity' => $detail->adjusted_closing_quantity,
                    'unit_cost' => $detail->adjusted_closing_unit_cost,
                    'value' => $detail->adjusted_closing_value,
                ];
            }
        }

        // Fallback: get from inventory movements before period start
        $lastMovement = DB::table('inventory_movements')
            ->where('warehouse_id', $this->warehouse_id)
            ->where('product_id', $productId)
            ->where('movement_date', '<', $this->period_start_date)
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMovement) {
            return [
                'quantity' => $lastMovement->balance_quantity ?? 0,
                'unit_cost' => $lastMovement->unit_cost ?? 0,
                'value' => ($lastMovement->balance_quantity ?? 0) * ($lastMovement->unit_cost ?? 0),
            ];
        }

        return ['quantity' => 0, 'unit_cost' => 0, 'value' => 0];
    }

    /**
     * Calculate movements for a product during the period
     */
    private function calculatePeriodMovements($productId): array
    {
        $movements = DB::table('inventory_movements')
            ->where('warehouse_id', $this->warehouse_id)
            ->where('product_id', $productId)
            ->whereBetween('movement_date', [$this->period_start_date, $this->period_end_date])
            ->selectRaw('
                SUM(quantity_in) as total_in,
                SUM(quantity_out) as total_out,
                COUNT(*) as count
            ')
            ->first();

        return [
            'quantity_in' => $movements->total_in ?? 0,
            'quantity_out' => $movements->total_out ?? 0,
            'count' => $movements->count ?? 0,
        ];
    }

    /**
     * Calculate and update totals from details
     */
    public function calculateTotals(): void
    {
        $totals = $this->details()
            ->selectRaw('
                COUNT(*) as total_products,
                SUM(movement_count) as total_movements,
                SUM(adjusted_closing_value) as total_value,
                SUM(adjusted_closing_quantity) as total_quantity,
                SUM(CASE WHEN has_discrepancy = 1 THEN 1 ELSE 0 END) as products_with_discrepancies,
                SUM(discrepancy_value) as total_discrepancy_value
            ')
            ->first();

        $this->update([
            'total_products' => $totals->total_products ?? 0,
            'total_movements' => $totals->total_movements ?? 0,
            'total_value' => $totals->total_value ?? 0,
            'total_quantity' => $totals->total_quantity ?? 0,
            'products_with_discrepancies' => $totals->products_with_discrepancies ?? 0,
            'total_discrepancy_value' => $totals->total_discrepancy_value ?? 0,
        ]);
    }

    /**
     * Approve the closure
     */
    public function approve(int $userId): bool
    {
        if ($this->status !== 'en_proceso' || $this->is_approved) {
            return false;
        }

        $this->update([
            'is_approved' => true,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Close the period - mark as final
     */
    public function close(int $userId): bool
    {
        if ($this->status !== 'en_proceso' || ! $this->is_approved) {
            return false;
        }

        DB::beginTransaction();
        try {
            $this->update([
                'status' => 'cerrado',
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            // Update last_count fields in inventory table
            $this->updateInventoryLastCount($userId);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error closing inventory: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Update the last_count fields in inventory table from closure details
     */
    private function updateInventoryLastCount(int $userId): void
    {
        $closedAt = now();

        foreach ($this->details as $detail) {
            // Get the quantity to record (prefer physical count if done, otherwise calculated)
            $countQuantity = $detail->physical_count_quantity ?? $detail->adjusted_closing_quantity;

            // Update inventory records for this product in this warehouse
            Inventory::where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->update([
                    'last_count_quantity' => $countQuantity,
                    'last_counted_at' => $detail->physical_count_date ?? $closedAt,
                    'last_counted_by' => $detail->counted_by ?? $userId,
                    'updated_by' => $userId,
                ]);
        }
    }

    /**
     * Reopen a closed period
     */
    public function reopen(int $userId, string $reason): bool
    {
        if ($this->status !== 'cerrado') {
            return false;
        }

        $this->update([
            'status' => 'reabierto',
            'reopened_by' => $userId,
            'reopened_at' => now(),
            'reopening_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Cancel the closure
     */
    public function cancel(): bool
    {
        if ($this->status === 'cerrado') {
            return false;
        }

        $this->update(['status' => 'cancelado']);

        return true;
    }

    // Permission Helper Methods

    public function canBeApproved(): bool
    {
        return $this->status === 'en_proceso' && ! $this->is_approved;
    }

    public function canBeClosed(): bool
    {
        return $this->status === 'en_proceso' && $this->is_approved;
    }

    public function canBeReopened(): bool
    {
        return $this->status === 'cerrado';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['en_proceso', 'reabierto']);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['en_proceso', 'reabierto']);
    }

    public function canBeProcessed(): bool
    {
        return $this->status === 'en_proceso';
    }

    // Route Key

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
