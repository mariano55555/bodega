<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Donor extends Model
{
    /** @use HasFactory<\Database\Factories\DonorFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'company_id',
        'donor_type',
        'tax_id',
        'email',
        'phone',
        'website',
        'contact_person',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'rating',
        'notes',
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
            'rating' => 'integer',
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

        static::creating(function ($donor) {
            if (empty($donor->slug)) {
                $donor->slug = Str::slug($donor->name);
            }
            if (auth()->check()) {
                $donor->created_by = auth()->id();
            }
            if (is_null($donor->active_at) && $donor->is_active) {
                $donor->active_at = now();
            }
        });

        static::updating(function ($donor) {
            if ($donor->isDirty('name') && empty($donor->slug)) {
                $donor->slug = Str::slug($donor->name);
            }
            if (auth()->check()) {
                $donor->updated_by = auth()->id();
            }
            if ($donor->isDirty('is_active')) {
                $donor->active_at = $donor->is_active ? now() : null;
            }
        });

        static::deleting(function ($donor) {
            if (auth()->check()) {
                $donor->deleted_by = auth()->id();
                $donor->save();
            }
        });
    }

    /**
     * Get the company that owns this donor.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this donor.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this donor.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this donor.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the donations related to this donor.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Scope a query to only include active donors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
    }

    /**
     * Scope a query to filter by donor type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('donor_type', $type);
    }

    /**
     * Scope a query to filter by rating.
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
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

    /**
     * Get the primary contact information.
     */
    public function getPrimaryContactAttribute(): string
    {
        if ($this->contact_person) {
            return $this->contact_person;
        }

        return 'No contact assigned';
    }

    /**
     * Get the total value of donations from this donor.
     */
    public function getTotalDonationsValueAttribute(): float
    {
        return (float) $this->donations()->sum('total_value');
    }

    /**
     * Get the donor type label in Spanish.
     */
    public function getDonorTypeLabel(): string
    {
        return match ($this->donor_type) {
            'individual' => 'Persona Individual',
            'organization' => 'Organización',
            'government' => 'Gobierno',
            'ngo' => 'ONG',
            'international' => 'Organización Internacional',
            default => $this->donor_type ?? 'No especificado',
        };
    }

    /**
     * Get the badge color for the donor type.
     */
    public function getDonorTypeBadgeColor(): string
    {
        return match ($this->donor_type) {
            'individual' => 'zinc',
            'organization' => 'blue',
            'government' => 'amber',
            'ngo' => 'green',
            'international' => 'purple',
            default => 'zinc',
        };
    }
}
