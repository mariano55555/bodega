<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
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
        'primary_supplier_id',
        'unit_of_measure',
        'unit_of_measure_id',
        'company_id',
        'cost',
        // 'price', // Comentado por petición del cliente: quitar precio de venta
        'barcode',
        'attributes',
        'image_path',
        'track_inventory',
        'is_active',
        'active_at',
        // 'valuation_method', // Comentado por petición del cliente: quitar método de valuación
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
            'cost' => 'decimal:5',
            // 'price' => 'decimal:2', // Comentado por petición del cliente: quitar precio de venta
            'minimum_stock' => 'decimal:5',
            'maximum_stock' => 'decimal:5',
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
            ->logOnly(['name', 'sku', 'cost', /* 'price', */ 'is_active', 'minimum_stock', 'maximum_stock']) // price comentado por petición del cliente
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
            // Auto-generar SKU si está configurado y no se proporcionó
            if (empty($product->sku) && $product->company_id) {
                $company = \App\Models\Company::find($product->company_id);
                if ($company && ($company->settings['auto_generate_sku'] ?? false)) {
                    $product->sku = static::generateCategorySku($product);
                }
            }

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

        static::created(function ($product) {
            static::clearSelectCache($product->company_id);
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

        static::updated(function ($product) {
            static::clearSelectCache($product->company_id);
        });

        static::deleting(function ($product) {
            if (auth()->check()) {
                $product->deleted_by = auth()->id();
                $product->save();
            }
        });

        static::deleted(function ($product) {
            static::clearSelectCache($product->company_id);
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
     * Get the primary supplier for this product.
     */
    public function primarySupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'primary_supplier_id');
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

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
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
     * Get cached products for select/combobox (with unit info).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function cachedForSelect(int $companyId): \Illuminate\Support\Collection
    {
        return Cache::remember(
            "company_{$companyId}_products_select",
            now()->addHours(24),
            fn () => static::where('products.company_id', $companyId)
                ->where('products.is_active', true)
                ->leftJoin('units_of_measure', 'units_of_measure.id', '=', 'products.unit_of_measure_id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    'products.cost',
                    'products.unit_of_measure_id',
                    'units_of_measure.abbreviation as unit_abbreviation',
                    'units_of_measure.name as unit_name'
                )
                ->orderBy('products.name')
                ->get()
        );
    }

    /**
     * Clear the cached products for a company.
     */
    public static function clearSelectCache(int $companyId): void
    {
        Cache::forget("company_{$companyId}_products_select");
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate SKU based on category hierarchy.
     * Format: {parent_legacy_code}-{subcategory_legacy_code}-{correlative}
     * Example: 54-54101-00001
     */
    public static function generateCategorySku(self $product): string
    {
        // 1. Get the subcategory
        $subcategory = ProductCategory::find($product->category_id);

        if (! $subcategory || ! $subcategory->legacy_code) {
            // Fallback to old format if no category or legacy_code
            return static::generateFallbackSku($product->company_id);
        }

        // 2. Get parent's legacy_code
        $parentLegacyCode = '';
        if ($subcategory->parent_id) {
            $parent = ProductCategory::find($subcategory->parent_id);
            $parentLegacyCode = $parent?->legacy_code ?? '';
        } else {
            // If no parent, use own legacy_code as parent
            $parentLegacyCode = $subcategory->legacy_code;
        }

        if (! $parentLegacyCode) {
            return static::generateFallbackSku($product->company_id);
        }

        // 3. Count existing products in this subcategory for correlative
        $count = static::where('company_id', $product->company_id)
            ->where('category_id', $product->category_id)
            ->count();

        // 4. Generate correlative (next number, 5 digits)
        $correlative = str_pad($count + 1, 5, '0', STR_PAD_LEFT);

        // 5. Build SKU: parent-subcategory-correlative
        $sku = "{$parentLegacyCode}-{$subcategory->legacy_code}-{$correlative}";

        // 6. Verify uniqueness and adjust if necessary
        while (static::where('company_id', $product->company_id)
            ->where('sku', $sku)
            ->exists()) {
            $count++;
            $correlative = str_pad($count + 1, 5, '0', STR_PAD_LEFT);
            $sku = "{$parentLegacyCode}-{$subcategory->legacy_code}-{$correlative}";
        }

        return $sku;
    }

    /**
     * Generate fallback SKU when category-based generation is not possible.
     * Format: PRO-XXXXXX
     */
    protected static function generateFallbackSku(int $companyId): string
    {
        do {
            $sku = 'PRO-'.strtoupper(Str::random(6));
            $exists = static::where('company_id', $companyId)
                ->where('sku', $sku)
                ->exists();
        } while ($exists);

        return $sku;
    }

    /**
     * Preview SKU generation for a given category.
     * Useful for showing the user what SKU will be generated.
     */
    public static function previewSkuForCategory(int $companyId, int $categoryId): string
    {
        $subcategory = ProductCategory::find($categoryId);

        if (! $subcategory || ! $subcategory->legacy_code) {
            return 'Se generará automáticamente (PRO-XXXXXX)';
        }

        $parentLegacyCode = '';
        if ($subcategory->parent_id) {
            $parent = ProductCategory::find($subcategory->parent_id);
            $parentLegacyCode = $parent?->legacy_code ?? '';
        } else {
            $parentLegacyCode = $subcategory->legacy_code;
        }

        if (! $parentLegacyCode) {
            return 'Se generará automáticamente (PRO-XXXXXX)';
        }

        $count = static::where('company_id', $companyId)
            ->where('category_id', $categoryId)
            ->count();

        $correlative = str_pad($count + 1, 5, '0', STR_PAD_LEFT);

        return "{$parentLegacyCode}-{$subcategory->legacy_code}-{$correlative}";
    }
}
