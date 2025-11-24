<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWarehouseAccess extends Model
{
    use SoftDeletes;

    protected $table = 'user_warehouse_access';

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'company_id',
        'access_type',
        'permissions',
        'is_active',
        'active_at',
        'granted_at',
        'expires_at',
        'assigned_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user this access belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the warehouse this access is for.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the company this access belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who assigned this access.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who created this access.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this access.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this access.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope to filter active access.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to filter by access type.
     */
    public function scopeWithAccessType($query, string $accessType)
    {
        return $query->where('access_type', $accessType);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by warehouse.
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope to filter expiring access.
     */
    public function scopeExpiring($query, int $days = 7)
    {
        return $query->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Check if access is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active
            && ($this->active_at === null || $this->active_at <= now())
            && ($this->expires_at === null || $this->expires_at > now());
    }

    /**
     * Check if access is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at < now();
    }

    /**
     * Check if access is expiring soon.
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isBefore(now()->addDays($days));
    }

    /**
     * Grant access to user.
     */
    public function grant(): void
    {
        $this->update([
            'is_active' => true,
            'active_at' => now(),
            'granted_at' => now(),
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Revoke access from user.
     */
    public function revoke(): void
    {
        $this->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Extend access expiration.
     */
    public function extend(int $days): void
    {
        $newExpirationDate = $this->expires_at ? $this->expires_at->addDays($days) : now()->addDays($days);

        $this->update([
            'expires_at' => $newExpirationDate,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Check if user has specific permission for this warehouse.
     */
    public function hasPermission(string $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->access_type === 'full') {
            return true;
        }

        if ($this->access_type === 'read_only') {
            return in_array($permission, ['view', 'read']);
        }

        if ($this->access_type === 'restricted' && $this->permissions) {
            return in_array($permission, $this->permissions);
        }

        return false;
    }

    /**
     * Get available access types.
     */
    public static function getAccessTypes(): array
    {
        return [
            'full' => 'Acceso Completo',
            'read_only' => 'Solo Lectura',
            'restricted' => 'Restringido',
        ];
    }
}
