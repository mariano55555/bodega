<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    /** @use HasFactory<\Database\Factories\UserProfileFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'branch_id',
        'first_name',
        'last_name',
        'phone',
        'mobile',
        'date_of_birth',
        'gender',
        'employee_id',
        'department',
        'job_title',
        'manager_id',
        'hire_date',
        'salary',
        'employment_type',
        'address',
        'departamento_id',
        'ciudad_id',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'timezone',
        'language',
        'date_format',
        'time_format',
        'avatar_path',
        'skills',
        'certifications',
        'settings',
        'bio',
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
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'salary' => 'decimal:2',
            'skills' => 'array',
            'certifications' => 'array',
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

        static::creating(function ($profile) {
            if (auth()->check()) {
                $profile->created_by = auth()->id();
            }
            if (is_null($profile->active_at) && $profile->is_active) {
                $profile->active_at = now();
            }
        });

        static::updating(function ($profile) {
            if (auth()->check()) {
                $profile->updated_by = auth()->id();
            }
            if ($profile->isDirty('is_active')) {
                $profile->active_at = $profile->is_active ? now() : null;
            }
        });
    }

    /**
     * Get the user that owns this profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company this profile belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch this profile belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the manager for this profile.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the departamento for this profile.
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Get the ciudad for this profile.
     */
    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }

    /**
     * Get the user who created this profile.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this profile.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active profiles.
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
     * Scope a query to filter by branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to filter by department.
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope a query to filter by employment type.
     */
    public function scopeByEmploymentType($query, string $type)
    {
        return $query->where('employment_type', $type);
    }

    /**
     * Get the full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);

        return implode(' ', $parts) ?: 'No name provided';
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
     * Get the years of service.
     */
    public function getYearsOfServiceAttribute(): float
    {
        if (! $this->hire_date) {
            return 0;
        }

        return $this->hire_date->diffInYears(now(), false);
    }

    /**
     * Get the primary contact phone.
     */
    public function getPrimaryPhoneAttribute(): ?string
    {
        return $this->mobile ?: $this->phone;
    }

    /**
     * Get the emergency contact formatted.
     */
    public function getEmergencyContactAttribute(): string
    {
        if (! $this->emergency_contact_name) {
            return 'No emergency contact';
        }

        $contact = $this->emergency_contact_name;

        if ($this->emergency_contact_relationship) {
            $contact .= " ({$this->emergency_contact_relationship})";
        }

        if ($this->emergency_contact_phone) {
            $contact .= " - {$this->emergency_contact_phone}";
        }

        return $contact;
    }

    /**
     * Check if the user has a specific skill.
     */
    public function hasSkill(string $skill): bool
    {
        if (! $this->skills) {
            return false;
        }

        return in_array($skill, $this->skills);
    }

    /**
     * Check if the user has a specific certification.
     */
    public function hasCertification(string $certification): bool
    {
        if (! $this->certifications) {
            return false;
        }

        return collect($this->certifications)->contains(function ($cert) use ($certification) {
            return is_string($cert) ? $cert === $certification : ($cert['name'] ?? '') === $certification;
        });
    }
}
