<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductLot extends Model
{
    /** @use HasFactory<\Database\Factories\ProductLotFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'supplier_id',
        'lot_number',
        'slug',
        'manufactured_date',
        'expiration_date',
        'quantity_produced',
        'quantity_remaining',
        'unit_cost',
        'status',
        'batch_certificate',
        'quality_attributes',
        'notes',
        'metadata',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manufactured_date' => 'date',
            'expiration_date' => 'date',
            'quantity_produced' => 'decimal:4',
            'quantity_remaining' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'quality_attributes' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($lot) {
            if (empty($lot->slug)) {
                $lot->slug = Str::slug($lot->lot_number);
            }
            if (auth()->check()) {
                $lot->created_by = auth()->id();
            }
            if (is_null($lot->active_at) && $lot->is_active) {
                $lot->active_at = now();
            }
            if (is_null($lot->quantity_remaining)) {
                $lot->quantity_remaining = $lot->quantity_produced;
            }
        });

        static::updating(function ($lot) {
            if ($lot->isDirty('lot_number') && empty($lot->slug)) {
                $lot->slug = Str::slug($lot->lot_number);
            }
            if (auth()->check()) {
                $lot->updated_by = auth()->id();
            }
            if ($lot->isDirty('is_active')) {
                $lot->active_at = $lot->is_active ? now() : null;
            }
        });

        static::deleting(function ($lot) {
            if (auth()->check()) {
                $lot->deleted_by = auth()->id();
                $lot->save();
            }
        });
    }

    /**
     * Get the product that owns this lot.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the supplier that provided this lot.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created this lot.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this lot.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this lot.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the inventory movements for this lot.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_lot_id');
    }

    /**
     * Get the inventory records for this lot.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'lot_number', 'lot_number')
            ->where('product_id', $this->product_id);
    }

    /**
     * Scope a query to only include active lots.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('status', 'active')
            ->whereNotNull('active_at');
    }

    /**
     * Scope a query to only include expired lots.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiration_date', '<', now()->toDateString())
            ->orWhere('status', 'expired');
    }

    /**
     * Scope a query to only include lots expiring soon.
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('expiration_date', '<=', now()->addDays($days)->toDateString())
            ->where('expiration_date', '>', now()->toDateString())
            ->where('status', 'active');
    }

    /**
     * Scope a query to order by FIFO (First In, First Out).
     */
    public function scopeFifo(Builder $query): Builder
    {
        return $query->orderBy('manufactured_date', 'asc')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Scope a query to order by FEFO (First Expired, First Out).
     */
    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderBy('expiration_date', 'asc')
            ->orderBy('manufactured_date', 'asc');
    }

    /**
     * Scope a query for lots with available quantity.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('quantity_remaining', '>', 0)
            ->where('status', 'active');
    }

    /**
     * Scope a query for a specific product.
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Calculate remaining quantity based on movements.
     */
    public function calculateRemainingQuantity(): float
    {
        $inMovements = $this->movements()
            ->whereIn('movement_type', ['in', 'adjustment'])
            ->where('status', 'completed')
            ->sum('quantity');

        $outMovements = $this->movements()
            ->whereIn('movement_type', ['out', 'transfer'])
            ->where('status', 'completed')
            ->sum('quantity');

        return $this->quantity_produced + $inMovements - $outMovements;
    }

    /**
     * Check if the lot is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    /**
     * Check if the lot is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiration_date &&
               $this->expiration_date->isAfter(now()) &&
               $this->expiration_date->isBefore(now()->addDays($days));
    }

    /**
     * Get days until expiration.
     */
    public function daysUntilExpiration(): ?int
    {
        if (! $this->expiration_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiration_date, false);
    }

    /**
     * Get the percentage of quantity remaining.
     */
    public function getQuantityRemainingPercentageAttribute(): float
    {
        if ($this->quantity_produced == 0) {
            return 0;
        }

        return ($this->quantity_remaining / $this->quantity_produced) * 100;
    }

    /**
     * Get the quantity used from this lot.
     */
    public function getQuantityUsedAttribute(): float
    {
        return $this->quantity_produced - $this->quantity_remaining;
    }

    /**
     * Check if lot is available for use.
     */
    public function isAvailable(): bool
    {
        return $this->is_active &&
               $this->status === 'active' &&
               $this->quantity_remaining > 0 &&
               ! $this->isExpired();
    }

    /**
     * Update the lot status based on expiration date.
     */
    public function updateExpirationStatus(): void
    {
        if ($this->isExpired() && $this->status === 'active') {
            $this->update(['status' => 'expired']);
        }
    }

    /**
     * Reduce the remaining quantity.
     */
    public function reduceQuantity(float $quantity): bool
    {
        if ($this->quantity_remaining < $quantity) {
            return false;
        }

        $this->decrement('quantity_remaining', $quantity);

        return true;
    }

    /**
     * Increase the remaining quantity.
     */
    public function increaseQuantity(float $quantity): bool
    {
        $this->increment('quantity_remaining', $quantity);

        return true;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
