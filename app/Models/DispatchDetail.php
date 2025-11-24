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
            'quantity' => 'decimal:4',
            'quantity_dispatched' => 'decimal:4',
            'quantity_delivered' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'expiration_date' => 'date',
            'is_reserved' => 'boolean',
            'reserved_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($detail) {
            // Calculate subtotal
            $detail->subtotal = $detail->quantity * $detail->unit_price;

            // Calculate discount amount if percentage is set
            if ($detail->discount_percent > 0) {
                $detail->discount_amount = $detail->subtotal * ($detail->discount_percent / 100);
            }

            // Calculate tax amount if percentage is set
            if ($detail->tax_percent > 0) {
                $taxableAmount = $detail->subtotal - $detail->discount_amount;
                $detail->tax_amount = $taxableAmount * ($detail->tax_percent / 100);
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
        $this->subtotal = $this->quantity * $this->unit_price;

        if ($this->discount_percent > 0) {
            $this->discount_amount = $this->subtotal * ($this->discount_percent / 100);
        }

        if ($this->tax_percent > 0) {
            $taxableAmount = $this->subtotal - $this->discount_amount;
            $this->tax_amount = $taxableAmount * ($this->tax_percent / 100);
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
