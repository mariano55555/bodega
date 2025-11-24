<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryClosureDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_closure_id',
        'product_id',
        'opening_quantity',
        'opening_unit_cost',
        'opening_total_value',
        'quantity_in',
        'quantity_out',
        'movement_count',
        'calculated_closing_quantity',
        'calculated_closing_unit_cost',
        'calculated_closing_value',
        'physical_count_quantity',
        'physical_count_unit_cost',
        'physical_count_value',
        'physical_count_date',
        'counted_by',
        'discrepancy_quantity',
        'discrepancy_value',
        'has_discrepancy',
        'discrepancy_notes',
        'adjusted_closing_quantity',
        'adjusted_closing_unit_cost',
        'adjusted_closing_value',
        'is_adjusted',
        'adjustment_notes',
        'is_active',
        'below_minimum',
        'above_maximum',
        'needs_reorder',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'opening_quantity' => 'decimal:4',
        'opening_unit_cost' => 'decimal:2',
        'opening_total_value' => 'decimal:2',
        'quantity_in' => 'decimal:4',
        'quantity_out' => 'decimal:4',
        'calculated_closing_quantity' => 'decimal:4',
        'calculated_closing_unit_cost' => 'decimal:2',
        'calculated_closing_value' => 'decimal:2',
        'physical_count_quantity' => 'decimal:4',
        'physical_count_unit_cost' => 'decimal:2',
        'physical_count_value' => 'decimal:2',
        'physical_count_date' => 'datetime',
        'discrepancy_quantity' => 'decimal:4',
        'discrepancy_value' => 'decimal:2',
        'has_discrepancy' => 'boolean',
        'adjusted_closing_quantity' => 'decimal:4',
        'adjusted_closing_unit_cost' => 'decimal:2',
        'adjusted_closing_value' => 'decimal:2',
        'is_adjusted' => 'boolean',
        'is_active' => 'boolean',
        'below_minimum' => 'boolean',
        'above_maximum' => 'boolean',
        'needs_reorder' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships

    public function closure(): BelongsTo
    {
        return $this->belongsTo(InventoryClosure::class, 'inventory_closure_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    // Business Logic Methods

    /**
     * Record physical count and calculate discrepancy
     */
    public function recordPhysicalCount(float $quantity, float $unitCost, int $countedBy): void
    {
        $physicalValue = $quantity * $unitCost;

        $this->update([
            'physical_count_quantity' => $quantity,
            'physical_count_unit_cost' => $unitCost,
            'physical_count_value' => $physicalValue,
            'physical_count_date' => now(),
            'counted_by' => $countedBy,
        ]);

        $this->calculateDiscrepancy();
    }

    /**
     * Calculate discrepancy between calculated and physical count
     */
    public function calculateDiscrepancy(): void
    {
        if ($this->physical_count_quantity === null) {
            return;
        }

        $quantityDiff = $this->physical_count_quantity - $this->calculated_closing_quantity;
        $valueDiff = $this->physical_count_value - $this->calculated_closing_value;

        $this->update([
            'discrepancy_quantity' => $quantityDiff,
            'discrepancy_value' => $valueDiff,
            'has_discrepancy' => abs($quantityDiff) > 0.0001, // Tolerance for floating point
        ]);

        // If there's a discrepancy, use physical count as adjusted closing
        if ($this->has_discrepancy) {
            $this->update([
                'adjusted_closing_quantity' => $this->physical_count_quantity,
                'adjusted_closing_unit_cost' => $this->physical_count_unit_cost,
                'adjusted_closing_value' => $this->physical_count_value,
                'is_adjusted' => true,
            ]);
        }
    }

    /**
     * Manually adjust closing balance
     */
    public function adjust(float $quantity, float $unitCost, string $notes): void
    {
        $adjustedValue = $quantity * $unitCost;

        $this->update([
            'adjusted_closing_quantity' => $quantity,
            'adjusted_closing_unit_cost' => $unitCost,
            'adjusted_closing_value' => $adjustedValue,
            'is_adjusted' => true,
            'adjustment_notes' => $notes,
        ]);
    }
}
