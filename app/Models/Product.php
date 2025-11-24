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

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'category_id',
        'unit_of_measure',
        'unit_of_measure_id',
        'company_id',
        'cost',
        'price',
        'barcode',
        'attributes',
        'image_path',
        'track_inventory',
        'is_active',
        'active_at',
        'valuation_method',
        'minimum_stock',
        'maximum_stock',
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
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
            'maximum_stock' => 'decimal:2',
            'attributes' => 'array',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'cost', 'price', 'is_active', 'minimum_stock', 'maximum_stock'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Producto '{$this->name}' {$eventName}");
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (auth()->check()) {
                $product->created_by = auth()->id();
            }
            if (is_null($product->active_at) && $product->is_active) {
                $product->active_at = now();
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (auth()->check()) {
                $product->updated_by = auth()->id();
            }
            if ($product->isDirty('is_active')) {
                $product->active_at = $product->is_active ? now() : null;
            }
        });

        static::deleting(function ($product) {
            if (auth()->check()) {
                $product->deleted_by = auth()->id();
                $product->save();
            }
        });
    }

    /**
     * Get the company that owns the product.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the unit of measure for this product.
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_of_measure_id');
    }

    /**
     * Get the user who created this product.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this product.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this product.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the inventory records for this product.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the product lots for this product.
     */
    public function lots(): HasMany
    {
        return $this->hasMany(ProductLot::class);
    }

    /**
     * Get the inventory movements for this product.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get the inventory alerts for this product.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(InventoryAlert::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query to only include tracked inventory products.
     */
    public function scopeTracked($query)
    {
        return $query->where('track_inventory', true);
    }

    /**
     * Get the total stock quantity across all warehouses.
     */
    public function getTotalStockAttribute(): float
    {
        return $this->inventory()->sum('quantity');
    }

    /**
     * Get the total available stock quantity across all warehouses.
     */
    public function getAvailableStockAttribute(): float
    {
        return $this->inventory()->sum('available_quantity');
    }

    /**
     * Get the total value of this product across all warehouses.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->inventory()->sum('total_value');
    }

    /**
     * Check if the product is below minimum stock level.
     */
    public function isBelowMinimumStock(): bool
    {
        return $this->total_stock < $this->minimum_stock;
    }

    /**
     * Check if the product is above maximum stock level.
     */
    public function isAboveMaximumStock(): bool
    {
        return $this->maximum_stock && $this->total_stock > $this->maximum_stock;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
