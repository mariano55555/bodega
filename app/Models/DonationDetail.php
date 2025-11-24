<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationDetail extends Model
{
    /** @use HasFactory<\Database\Factories\DonationDetailFactory> */
    use HasFactory;

    protected $fillable = [
        'donation_id',
        'product_id',
        'quantity',
        'estimated_unit_value',
        'estimated_total_value',
        'condition',
        'condition_notes',
        'lot_number',
        'expiration_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'estimated_unit_value' => 'decimal:2',
            'estimated_total_value' => 'decimal:2',
            'expiration_date' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($detail) {
            // Calculate estimated total value
            $detail->estimated_total_value = $detail->quantity * $detail->estimated_unit_value;
        });
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
