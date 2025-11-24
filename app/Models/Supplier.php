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

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

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
        'payment_terms',
        'credit_limit',
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
            'credit_limit' => 'decimal:2',
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

        static::creating(function ($supplier) {
            if (empty($supplier->slug)) {
                $supplier->slug = Str::slug($supplier->name);
            }
            if (auth()->check()) {
                $supplier->created_by = auth()->id();
            }
            if (is_null($supplier->active_at) && $supplier->is_active) {
                $supplier->active_at = now();
            }
        });

        static::updating(function ($supplier) {
            if ($supplier->isDirty('name') && empty($supplier->slug)) {
                $supplier->slug = Str::slug($supplier->name);
            }
            if (auth()->check()) {
                $supplier->updated_by = auth()->id();
            }
            if ($supplier->isDirty('is_active')) {
                $supplier->active_at = $supplier->is_active ? now() : null;
            }
        });

        static::deleting(function ($supplier) {
            if (auth()->check()) {
                $supplier->deleted_by = auth()->id();
                $supplier->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'tax_id', 'email', 'phone', 'is_active', 'credit_limit'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Proveedor '{$this->name}' {$eventName}");
    }

    /**
     * Get the company that owns this supplier.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this supplier.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this supplier.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this supplier.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the inventory movements related to this supplier.
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Scope a query to only include active suppliers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('active_at');
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
        if ($this->contact_name) {
            $contact = $this->contact_name;
            if ($this->contact_position) {
                $contact .= " ({$this->contact_position})";
            }

            return $contact;
        }

        return 'No contact assigned';
    }

    /**
     * Check if supplier is overdue on payments.
     */
    public function isOverdue(): bool
    {
        // This would be implemented based on actual payment/invoice tracking
        // For now, return false as placeholder
        return false;
    }

    /**
     * Get the credit available.
     */
    public function getAvailableCreditAttribute(): float
    {
        if (! $this->credit_limit) {
            return 0;
        }

        // This would calculate current outstanding balance
        // For now, return the full credit limit as placeholder
        return (float) $this->credit_limit;
    }
}
