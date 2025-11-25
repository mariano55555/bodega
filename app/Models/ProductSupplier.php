<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductSupplier extends Model
{
    /** @use HasFactory<\Database\Factories\ProductSupplierFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'product_supplier';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'product_id',
        'supplier_id',
        'supplier_code',
        'supplier_description',
        'supplier_cost',
        'supplier_unit_measure_code',
        'supplier_unit_measure_name',
        'last_purchase_at',
        'last_purchase_price',
        'is_preferred',
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
            'supplier_cost' => 'decimal:4',
            'last_purchase_price' => 'decimal:4',
            'supplier_unit_measure_code' => 'integer',
            'last_purchase_at' => 'datetime',
            'is_preferred' => 'boolean',
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

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (is_null($model->active_at) && $model->is_active) {
                $model->active_at = now();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
            if ($model->isDirty('is_active')) {
                $model->active_at = $model->is_active ? now() : null;
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['supplier_code', 'supplier_cost', 'is_preferred', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Producto-Proveedor '{$this->supplier_code}' {$eventName}");
    }

    /**
     * Get the company that owns this record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the supplier.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by supplier.
     */
    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope a query to filter by supplier code.
     */
    public function scopeBySupplierCode($query, string $code)
    {
        return $query->where('supplier_code', $code);
    }

    /**
     * Find a product by supplier code within a company.
     */
    public static function findBySupplierCode(int $companyId, int $supplierId, string $code): ?self
    {
        return static::forCompany($companyId)
            ->forSupplier($supplierId)
            ->bySupplierCode($code)
            ->first();
    }
}
