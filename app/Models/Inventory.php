<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'location',
        'lot_number',
        'expiration_date',
        'unit_cost',
        'total_value',
        'is_active',
        'active_at',
        'last_count_quantity',
        'last_counted_at',
        'last_counted_by',
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
            'quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'available_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_value' => 'decimal:4',
            'last_count_quantity' => 'decimal:4',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
            'expiration_date' => 'date',
            'last_counted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($inventory) {
            if (auth()->check()) {
                $inventory->created_by = auth()->id();
            }
            if (is_null($inventory->active_at) && $inventory->is_active) {
                $inventory->active_at = now();
            }
            $inventory->calculateAvailableQuantity();
            $inventory->calculateTotalValue();
        });

        static::updating(function ($inventory) {
            if (auth()->check()) {
                $inventory->updated_by = auth()->id();
            }
            if ($inventory->isDirty('is_active')) {
                $inventory->active_at = $inventory->is_active ? now() : null;
            }
            if ($inventory->isDirty(['quantity', 'reserved_quantity'])) {
                $inventory->calculateAvailableQuantity();
            }
            if ($inventory->isDirty(['quantity', 'unit_cost'])) {
                $inventory->calculateTotalValue();
            }
        });

        static::deleting(function ($inventory) {
            if (auth()->check()) {
                $inventory->deleted_by = auth()->id();
                $inventory->save();
            }
        });
    }

    /**
     * Get the product that owns this inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse that owns this inventory.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this inventory record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this inventory record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this inventory record.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the storage location that owns this inventory.
     */
    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    /**
     * Get the user who last counted this inventory.
     */
    public function lastCounter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_counted_by');
    }

    /**
     * Scope a query to only include active inventory.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query to only include inventory with available stock.
     */
    public function scopeAvailable($query)
    {
        return $query->where('available_quantity', '>', 0);
    }

    /**
     * Scope a query to only include inventory that is expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now(), now()->addDays($days)]);
    }

    /**
     * Calculate and update the available quantity.
     */
    public function calculateAvailableQuantity(): void
    {
        $this->available_quantity = $this->quantity - $this->reserved_quantity;
    }

    /**
     * Calculate and update the total value.
     */
    public function calculateTotalValue(): void
    {
        $this->total_value = $this->quantity * ($this->unit_cost ?? 0);
    }

    /**
     * Check if this inventory is below minimum stock.
     */
    public function isBelowMinimumStock(): bool
    {
        return $this->available_quantity < $this->product->minimum_stock;
    }

    /**
     * Check if this inventory is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    /**
     * Check if this inventory is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiration_date && $this->expiration_date->isBefore(now()->addDays($days));
    }
}
