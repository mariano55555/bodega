<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AlertSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'low_stock_threshold_days',
        'critical_stock_percentage',
        'high_stock_percentage',
        'medium_stock_percentage',
        'expiring_soon_days',
        'expiring_critical_days',
        'expiring_high_days',
        'email_alerts_enabled',
        'email_recipients',
        'email_on_critical_only',
        'email_on_low_stock',
        'email_on_out_of_stock',
        'email_on_expiring',
        'email_on_expired',
        'email_frequency',
        'digest_time',
        'browser_notifications_enabled',
        'dashboard_alerts_enabled',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_threshold_days' => 'integer',
            'critical_stock_percentage' => 'decimal:2',
            'high_stock_percentage' => 'decimal:2',
            'medium_stock_percentage' => 'decimal:2',
            'expiring_soon_days' => 'integer',
            'expiring_critical_days' => 'integer',
            'expiring_high_days' => 'integer',
            'email_alerts_enabled' => 'boolean',
            'email_recipients' => 'array',
            'email_on_critical_only' => 'boolean',
            'email_on_low_stock' => 'boolean',
            'email_on_out_of_stock' => 'boolean',
            'email_on_expiring' => 'boolean',
            'email_on_expired' => 'boolean',
            'browser_notifications_enabled' => 'boolean',
            'dashboard_alerts_enabled' => 'boolean',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
            'digest_time' => 'datetime:H:i',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($setting) {
            if (empty($setting->slug)) {
                $setting->slug = Str::slug($setting->name);
            }
            if (auth()->check()) {
                $setting->created_by = auth()->id();
            }
            if (is_null($setting->active_at) && $setting->is_active) {
                $setting->active_at = now();
            }
        });

        static::updating(function ($setting) {
            if (auth()->check()) {
                $setting->updated_by = auth()->id();
            }
            if ($setting->isDirty('is_active')) {
                $setting->active_at = $setting->is_active ? now() : null;
            }
        });

        static::deleting(function ($setting) {
            if (auth()->check()) {
                $setting->deleted_by = auth()->id();
                $setting->save();
            }
        });
    }

    /**
     * Get the company that owns this alert setting
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this setting
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this setting
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this setting
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope for email enabled
     */
    public function scopeEmailEnabled($query)
    {
        return $query->where('email_alerts_enabled', true);
    }

    /**
     * Check if email should be sent for given alert type and priority
     */
    public function shouldSendEmail(string $alertType, string $priority): bool
    {
        if (! $this->email_alerts_enabled) {
            return false;
        }

        // If only critical emails are enabled, check priority
        if ($this->email_on_critical_only && $priority !== 'critical') {
            return false;
        }

        // Check specific alert type settings
        return match ($alertType) {
            'low_stock' => $this->email_on_low_stock,
            'out_of_stock' => $this->email_on_out_of_stock,
            'expiring_soon' => $this->email_on_expiring,
            'expired' => $this->email_on_expired,
            default => false,
        };
    }

    /**
     * Get priority based on stock percentage
     */
    public function getPriorityForStockPercentage(float $percentage): string
    {
        if ($percentage <= $this->critical_stock_percentage) {
            return 'critical';
        } elseif ($percentage <= $this->high_stock_percentage) {
            return 'high';
        } elseif ($percentage <= $this->medium_stock_percentage) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get priority for expiring products based on days until expiry
     */
    public function getPriorityForExpiringDays(int $daysUntilExpiry): string
    {
        if ($daysUntilExpiry <= $this->expiring_critical_days) {
            return 'critical';
        } elseif ($daysUntilExpiry <= $this->expiring_high_days) {
            return 'high';
        }

        return 'medium';
    }

    /**
     * Get the default alert setting for a company
     */
    public static function getForCompany(int $companyId): ?self
    {
        return static::forCompany($companyId)->active()->first();
    }
}
