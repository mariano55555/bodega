<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UnitOfMeasure extends Model
{
    /** @use HasFactory<\Database\Factories\UnitOfMeasureFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'units_of_measure';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'symbol',
        'slug',
        'description',
        'type',
        'base_unit_ratio',
        'base_unit_id',
        'is_active',
        'active_at',
        'is_default',
        'company_id',
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
            'base_unit_ratio' => 'decimal:8',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($unit) {
            if (empty($unit->slug)) {
                $unit->slug = Str::slug($unit->name);
            }
            if (auth()->check()) {
                $unit->created_by = auth()->id();
            }
            if (is_null($unit->active_at) && $unit->is_active) {
                $unit->active_at = now();
            }
        });

        static::updating(function ($unit) {
            if ($unit->isDirty('name') && empty($unit->slug)) {
                $unit->slug = Str::slug($unit->name);
            }
            if (auth()->check()) {
                $unit->updated_by = auth()->id();
            }
            if ($unit->isDirty('is_active')) {
                $unit->active_at = $unit->is_active ? now() : null;
            }
        });

        static::deleting(function ($unit) {
            if (auth()->check()) {
                $unit->deleted_by = auth()->id();
                $unit->save();
            }
        });
    }

    /**
     * Get the company that owns this unit of measure.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the base unit for conversion.
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_unit_id');
    }

    /**
     * Get the units that use this as their base unit.
     */
    public function derivedUnits(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class, 'base_unit_id');
    }

    /**
     * Get the user who created this unit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this unit.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this unit.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the products that use this unit of measure.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_of_measure_id');
    }

    /**
     * Scope a query to only include active units.
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
     * Scope a query to only include default units.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, ?int $companyId = null)
    {
        if ($companyId) {
            return $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereNull('company_id'); // Include global units
            });
        }

        return $query->whereNull('company_id'); // Only global units
    }

    /**
     * Convert a quantity from this unit to the base unit.
     */
    public function convertToBase(float $quantity): float
    {
        return $quantity * $this->base_unit_ratio;
    }

    /**
     * Convert a quantity from the base unit to this unit.
     */
    public function convertFromBase(float $quantity): float
    {
        if ($this->base_unit_ratio == 0) {
            return 0;
        }

        return $quantity / $this->base_unit_ratio;
    }

    /**
     * Convert a quantity from this unit to another unit of the same type.
     */
    public function convertTo(float $quantity, UnitOfMeasure $targetUnit): float
    {
        if ($this->type !== $targetUnit->type) {
            throw new \InvalidArgumentException('Cannot convert between different unit types');
        }

        $baseQuantity = $this->convertToBase($quantity);

        return $targetUnit->convertFromBase($baseQuantity);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the display name with symbol.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->symbol})";
    }
}
