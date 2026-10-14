<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'registration_number',
        'tax_id',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'currency',
        'timezone',
        'date_format',
        'time_format',
        'logo_path',
        'settings',
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

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
            if (auth()->check()) {
                $company->created_by = auth()->id();
            }
            if (is_null($company->active_at) && $company->is_active) {
                $company->active_at = now();
            }
        });

        static::updating(function ($company) {
            if ($company->isDirty('name') && empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
            if (auth()->check()) {
                $company->updated_by = auth()->id();
            }
            if ($company->isDirty('is_active')) {
                $company->active_at = $company->is_active ? now() : null;
            }
        });

        static::deleting(function ($company) {
            if (auth()->check()) {
                $company->deleted_by = auth()->id();
                $company->save();
            }
        });
    }

    /**
     * Get the user who created this company.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this company.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this company.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the branches for this company.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the users associated with this company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the suppliers for this company.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Get the customers for this company.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the products for this company.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the warehouses for this company.
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Whether users may type the unit cost by hand in transfers and dispatches.
     * Enabled per company by a super admin, optionally until a given date (inclusive).
     */
    public function allowsManualUnitCost(): bool
    {
        if (! ($this->settings['manual_unit_cost_enabled'] ?? false)) {
            return false;
        }

        $until = $this->manualUnitCostUntil();

        return $until === null || ! now()->startOfDay()->greaterThan($until);
    }

    /**
     * Last day (inclusive) the manual unit cost stays enabled, or null when it has no end date.
     */
    public function manualUnitCostUntil(): ?CarbonImmutable
    {
        $value = $this->settings['manual_unit_cost_until'] ?? null;

        if (empty($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Whether the manual unit cost of the given company is enabled right now.
     */
    public static function manualUnitCostAllowedFor(?int $companyId): bool
    {
        if (! $companyId) {
            return false;
        }

        return (bool) static::query()->find($companyId)?->allowsManualUnitCost();
    }

    /**
     * Get the configured minimum allowed age (in years back) for document dates.
     * Returns null when the company has no restriction configured.
     */
    public function documentDateMinYearsBack(): ?int
    {
        $value = $this->settings['document_date_min_years_back'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    /**
     * Get the earliest allowed document date based on the company configuration.
     * Returns null when no restriction is configured.
     */
    public function minDocumentDate(): ?CarbonImmutable
    {
        $yearsBack = $this->documentDateMinYearsBack();

        if ($yearsBack === null) {
            return null;
        }

        return CarbonImmutable::now()->startOfYear()->subYears($yearsBack);
    }

    /**
     * Get the full address formatted as a string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
