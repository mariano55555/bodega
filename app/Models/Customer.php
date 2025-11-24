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

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
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
        'description',
        'company_id',
        'type',
        'business_name',
        'registration_number',
        'tax_id',
        'email',
        'phone',
        'mobile',
        'website',
        'contact_name',
        'contact_email',
        'contact_phone',
        'contact_position',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'same_as_billing',
        'payment_terms_days',
        'payment_method',
        'currency',
        'credit_limit',
        'status',
        'categories',
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
            'credit_limit' => 'decimal:2',
            'categories' => 'array',
            'settings' => 'array',
            'same_as_billing' => 'boolean',
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

        static::creating(function ($customer) {
            if (empty($customer->slug)) {
                $customer->slug = Str::slug($customer->name);
            }
            if (empty($customer->code)) {
                $customer->code = 'CUST-'.Str::upper(Str::random(6));
            }
            if (auth()->check()) {
                $customer->created_by = auth()->id();
            }
            if (is_null($customer->active_at) && $customer->is_active) {
                $customer->active_at = now();
            }
        });

        static::updating(function ($customer) {
            if ($customer->isDirty('name') && empty($customer->slug)) {
                $customer->slug = Str::slug($customer->name);
            }
            if (auth()->check()) {
                $customer->updated_by = auth()->id();
            }
            if ($customer->isDirty('is_active')) {
                $customer->active_at = $customer->is_active ? now() : null;
            }
        });

        static::deleting(function ($customer) {
            if (auth()->check()) {
                $customer->deleted_by = auth()->id();
                $customer->save();
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'type', 'tax_id', 'email', 'phone', 'is_active', 'credit_limit'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Cliente '{$this->name}' {$eventName}");
    }

    /**
     * Get the company that owns this customer.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this customer.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this customer.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this customer.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the inventory movements related to this customer.
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get the dispatches related to this customer.
     */
    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    /**
     * Scope a query to only include active customers.
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
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by categories.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->whereJsonContains('categories', $category);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the display name (business name or regular name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->type === 'business' && $this->business_name
            ? $this->business_name
            : $this->name;
    }

    /**
     * Get the full billing address formatted as a string.
     */
    public function getFullBillingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->billing_address,
            $this->billing_city,
            $this->billing_state,
            $this->billing_postal_code,
            $this->billing_country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the full shipping address formatted as a string.
     */
    public function getFullShippingAddressAttribute(): string
    {
        if ($this->same_as_billing) {
            return $this->full_billing_address;
        }

        $parts = array_filter([
            $this->shipping_address,
            $this->shipping_city,
            $this->shipping_state,
            $this->shipping_postal_code,
            $this->shipping_country,
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
     * Get the available credit.
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

    /**
     * Get the badge color for customer type.
     */
    public function getTypeBadgeColor(): string
    {
        return match ($this->type) {
            'individual' => 'zinc',
            'business' => 'blue',
            default => 'zinc',
        };
    }

    /**
     * Get the translated label for customer type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'individual' => 'Individual',
            'business' => 'Empresa',
            default => 'Individual',
        };
    }
}
