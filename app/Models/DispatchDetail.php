<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchDetail extends Model
{
    /** @use HasFactory<\Database\Factories\DispatchDetailFactory> */
    use HasFactory;

    protected $fillable = [
        'dispatch_id',
        'product_id',
        'product_lot_id',
        'quantity',
        'quantity_dispatched',
        'quantity_delivered',
        'unit_of_measure_id',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'subtotal',
        'total',
        'notes',
        'batch_number',
        'expiration_date',
        'is_reserved',
        'reserved_at',
        'reserved_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:5',
            'quantity_dispatched' => 'decimal:5',
            'quantity_delivered' => 'decimal:5',
            'unit_price' => 'decimal:5',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:5',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:5',
            'subtotal' => 'decimal:5',
            'total' => 'decimal:5',
            'expiration_date' => 'date',
            'is_reserved' => 'boolean',
            'reserved_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($detail) {
            // Ensure numeric values
            $quantity = (float) ($detail->quantity ?? 0);
            $unitPrice = (float) ($detail->unit_price ?? 0);
            $discountPercent = (float) ($detail->discount_percent ?? 0);
            $taxPercent = (float) ($detail->tax_percent ?? 0);

            // Calculate subtotal: quantity * unit_price
            $detail->subtotal = $quantity * $unitPrice;

            // Calculate discount amount if percentage is set
            $detail->discount_amount = 0;
            if ($discountPercent > 0) {
                $detail->discount_amount = $detail->subtotal * ($discountPercent / 100);
            }

            // Calculate tax amount if percentage is set
            $detail->tax_amount = 0;
            if ($taxPercent > 0) {
                $taxableAmount = $detail->subtotal - $detail->discount_amount;
                $detail->tax_amount = $taxableAmount * ($taxPercent / 100);
            }

            // Calculate total
            $detail->total = $detail->subtotal - $detail->discount_amount + $detail->tax_amount;
        });
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productLot(): BelongsTo
    {
        return $this->belongsTo(ProductLot::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function reserver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function calculateTotal(): void
    {
        $quantity = (float) ($this->quantity ?? 0);
        $unitPrice = (float) ($this->unit_price ?? 0);
        $discountPercent = (float) ($this->discount_percent ?? 0);
        $taxPercent = (float) ($this->tax_percent ?? 0);

        $this->subtotal = $quantity * $unitPrice;

        $this->discount_amount = 0;
        if ($discountPercent > 0) {
            $this->discount_amount = $this->subtotal * ($discountPercent / 100);
        }

        $this->tax_amount = 0;
        if ($taxPercent > 0) {
            $taxableAmount = $this->subtotal - $this->discount_amount;
            $this->tax_amount = $taxableAmount * ($taxPercent / 100);
        }

        $this->total = $this->subtotal - $this->discount_amount + $this->tax_amount;
    }

    public function reserve(int $userId): bool
    {
        $this->is_reserved = true;
        $this->reserved_by = $userId;
        $this->reserved_at = now();

        return $this->save();
    }

    public function releaseReservation(): bool
    {
        $this->is_reserved = false;
        $this->reserved_by = null;
        $this->reserved_at = null;

        return $this->save();
    }

    public function isReserved(): bool
    {
        return $this->is_reserved === true;
    }
}
