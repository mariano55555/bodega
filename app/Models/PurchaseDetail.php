<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetail extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseDetailFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_cost',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'subtotal',
        'total',
        'lot_number',
        'expiration_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'expiration_date' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($detail) {
            // Calculate subtotal
            $detail->subtotal = $detail->quantity * $detail->unit_cost;

            // Calculate discount amount if percentage is set
            if ($detail->discount_percentage > 0) {
                $detail->discount_amount = $detail->subtotal * ($detail->discount_percentage / 100);
            }

            // Calculate tax amount if percentage is set
            if ($detail->tax_percentage > 0) {
                $taxableAmount = $detail->subtotal - $detail->discount_amount;
                $detail->tax_amount = $taxableAmount * ($detail->tax_percentage / 100);
            }

            // Calculate total
            $detail->total = $detail->subtotal - $detail->discount_amount + $detail->tax_amount;
        });
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
