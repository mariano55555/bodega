<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPriceHistory extends Model
{
    /** @use HasFactory<\Database\Factories\ProductPriceHistoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'old_cost',
        'new_cost',
        'cost_change',
        'change_percentage',
        'source_type',
        'source_id',
        'notes',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'old_cost' => 'decimal:5',
            'new_cost' => 'decimal:5',
            'cost_change' => 'decimal:5',
            'change_percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
}
