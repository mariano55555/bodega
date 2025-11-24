<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StorageLocation extends Model
{
    /** @use HasFactory<\Database\Factories\StorageLocationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'company_id',
        'branch_id',
        'warehouse_id',
        'parent_location_id',
        'level',
        'sort_order',
        'type',
        'section',
        'aisle',
        'shelf',
        'bin',
        'location_path',
        'capacity',
        'capacity_unit_id',
        'length',
        'width',
        'height',
        'weight_limit',
        'barcode',
        'is_active',
        'active_at',
        'is_pickable',
        'is_receivable',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sort_order' => 'integer',
            'capacity' => 'decimal:4',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'weight_limit' => 'decimal:2',
            'is_active' => 'boolean',
            'is_pickable' => 'boolean',
            'is_receivable' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($location) {
            if (empty($location->slug)) {
                $location->slug = Str::slug($location->name);
            }
            if (empty($location->code)) {
                $location->code = 'LOC-'.Str::upper(Str::random(6));
            }
            if (auth()->check()) {
                $location->created_by = auth()->id();
            }
            if (is_null($location->active_at) && $location->is_active) {
                $location->active_at = now();
            }
        });

        static::updating(function ($location) {
            if ($location->isDirty('name') && empty($location->slug)) {
                $location->slug = Str::slug($location->name);
            }
            if (auth()->check()) {
                $location->updated_by = auth()->id();
            }
            if ($location->isDirty('is_active')) {
                $location->active_at = $location->is_active ? now() : null;
            }
        });

        static::deleting(function ($location) {
            if (auth()->check()) {
                $location->deleted_by = auth()->id();
                $location->save();
            }
        });
    }

    /**
     * Get the company that owns this storage location.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch this storage location belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the warehouse this storage location belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the capacity unit of measure.
     */
    public function capacityUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'capacity_unit_id');
    }

    /**
     * Get the parent storage location.
     */
    public function parentLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'parent_location_id');
    }

    /**
     * Get the child storage locations.
     */
    public function childLocations(): HasMany
    {
        return $this->hasMany(StorageLocation::class, 'parent_location_id')->orderBy('sort_order');
    }

    /**
     * Get the user who created this storage location.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this storage location.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this storage location.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the inventory records in this storage location.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the inventory movements related to this storage location.
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Scope a query to only include active storage locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by level.
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope a query to only include pickable locations.
     */
    public function scopePickable($query)
    {
        return $query->where('is_pickable', true);
    }

    /**
     * Scope a query to only include receivable locations.
     */
    public function scopeReceivable($query)
    {
        return $query->where('is_receivable', true);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by warehouse.
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope a query to get root locations (no parent).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_location_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the full location path (including parent hierarchy).
     */
    public function getFullPathAttribute(): string
    {
        $path = collect([$this->code]);
        $current = $this;

        while ($current->parent_location_id) {
            $current = $current->parentLocation;
            if ($current) {
                $path->prepend($current->code);
            } else {
                break;
            }
        }

        return $path->implode(' > ');
    }

    /**
     * Get the used capacity based on inventory.
     */
    public function getUsedCapacityAttribute(): float
    {
        return (float) $this->inventory()
            ->active()
            ->sum('quantity');
    }

    /**
     * Get the available capacity.
     */
    public function getAvailableCapacityAttribute(): float
    {
        if (! $this->capacity) {
            return 0;
        }

        return max(0, (float) $this->capacity - $this->used_capacity);
    }

    /**
     * Get the capacity utilization percentage.
     */
    public function getCapacityUtilizationAttribute(): float
    {
        if (! $this->capacity || $this->capacity == 0) {
            return 0;
        }

        return round(($this->used_capacity / (float) $this->capacity) * 100, 1);
    }

    /**
     * Get the available weight capacity.
     */
    public function getAvailableWeightAttribute(): float
    {
        if (! $this->weight_limit) {
            return 0;
        }

        // This would calculate current weight utilization
        // For now, return the full weight capacity as placeholder
        return (float) $this->weight_limit;
    }

    /**
     * Check if the location can accommodate a given quantity and weight.
     */
    public function canAccommodate(float $quantity = 0, float $weight = 0): bool
    {
        $capacityOk = ! $this->capacity || ($this->available_capacity >= $quantity);
        $weightOk = ! $this->weight_limit || ($this->available_weight >= $weight);

        return $capacityOk && $weightOk;
    }
}
