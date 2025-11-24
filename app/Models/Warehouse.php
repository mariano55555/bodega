<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'warehouse_type',
        'parent_warehouse_id',
        'level',
        'description',
        'company_id',
        'branch_id',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'total_capacity',
        'capacity_unit',
        'manager_id',
        'is_active',
        'active_at',
        'operating_hours',
        'settings',
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
            'total_capacity' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'operating_hours' => 'array',
            'settings' => 'array',
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

        static::creating(function ($warehouse) {
            if (empty($warehouse->slug)) {
                $warehouse->slug = Str::slug($warehouse->name);
            }
            if (auth()->check()) {
                $warehouse->created_by = auth()->id();
            }
            if (is_null($warehouse->active_at) && $warehouse->is_active) {
                $warehouse->active_at = now();
            }
        });

        static::updating(function ($warehouse) {
            if ($warehouse->isDirty('name') && empty($warehouse->slug)) {
                $warehouse->slug = Str::slug($warehouse->name);
            }
            if (auth()->check()) {
                $warehouse->updated_by = auth()->id();
            }
            if ($warehouse->isDirty('is_active')) {
                $warehouse->active_at = $warehouse->is_active ? now() : null;
            }
        });

        static::deleting(function ($warehouse) {
            if (auth()->check()) {
                $warehouse->deleted_by = auth()->id();
                $warehouse->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'warehouse_type', 'is_active', 'manager_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Bodega '{$this->name}' {$eventName}");
    }

    /**
     * Get the company that owns this warehouse.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch this warehouse belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the storage locations in this warehouse.
     */
    public function storageLocations(): HasMany
    {
        return $this->hasMany(StorageLocation::class);
    }

    /**
     * Get the manager of this warehouse.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the user who created this warehouse.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this warehouse.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this warehouse.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the inventory records for this warehouse.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the inventory movements for this warehouse.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get the inventory alerts for this warehouse.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(InventoryAlert::class);
    }

    /**
     * Get transfers originating from this warehouse.
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'from_warehouse_id');
    }

    /**
     * Get transfers destined for this warehouse.
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'to_warehouse_id');
    }

    /**
     * Get the parent warehouse (for fractional warehouses).
     */
    public function parentWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'parent_warehouse_id');
    }

    /**
     * Get the child warehouses (fractional warehouses under this general warehouse).
     */
    public function childWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'parent_warehouse_id');
    }

    /**
     * Check if this is a general warehouse.
     */
    public function isGeneral(): bool
    {
        return $this->warehouse_type === 'general';
    }

    /**
     * Check if this is a fractional warehouse.
     */
    public function isFractional(): bool
    {
        return $this->warehouse_type === 'fractional';
    }

    /**
     * Scope a query to only include active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query to only include general warehouses.
     */
    public function scopeGeneral($query)
    {
        return $query->where('warehouse_type', 'general');
    }

    /**
     * Scope a query to only include fractional warehouses.
     */
    public function scopeFractional($query)
    {
        return $query->where('warehouse_type', 'fractional');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
