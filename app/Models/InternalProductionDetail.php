<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalProductionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_production_id',
        'product_id',
        'description',
        'unit_of_measure_id',
        'quantity',
        'unit_price',
        'subtotal',
        'total',
        'notes',
        'batch_number',
        'expiration_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:5',
            'unit_price' => 'decimal:5',
            'subtotal' => 'decimal:5',
            'total' => 'decimal:5',
            'expiration_date' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($detail) {
            $quantity = (float) ($detail->quantity ?? 0);
            $unitPrice = (float) ($detail->unit_price ?? 0);

            $detail->subtotal = $quantity * $unitPrice;
            $detail->total = $detail->subtotal;
        });
    }

    public function internalProduction(): BelongsTo
    {
        return $this->belongsTo(InternalProduction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function calculateTotal(): void
    {
        $quantity = (float) ($this->quantity ?? 0);
        $unitPrice = (float) ($this->unit_price ?? 0);

        $this->subtotal = $quantity * $unitPrice;
        $this->total = $this->subtotal;
    }
}
