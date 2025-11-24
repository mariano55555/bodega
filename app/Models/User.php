<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'branch_id',
        'active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'active_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->active_at !== null;
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'company_id', 'branch_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Usuario {$eventName}");
    }

    /**
     * Get the company this user belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch this user belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('active_at');
    }

    /**
     * Scope a query to only include inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->whereNull('active_at');
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
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the user's display name (prefer profile full name over user name).
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->profile && $this->profile->full_name !== 'No name provided') {
            return $this->profile->full_name;
        }

        return $this->name;
    }

    /**
     * Get the user's avatar path (prefer profile avatar over default).
     */
    public function getAvatarPathAttribute(): ?string
    {
        return $this->profile?->avatar_path;
    }

    /**
     * Get the user's primary phone (from profile).
     */
    public function getPrimaryPhoneAttribute(): ?string
    {
        return $this->profile?->primary_phone;
    }

    /**
     * Check if user is a super admin (can access all companies).
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is a company admin (can manage their company).
     */
    public function isCompanyAdmin(): bool
    {
        return $this->hasRole('company-admin');
    }

    /**
     * Check if user is a branch manager (can manage assigned branches).
     */
    public function isBranchManager(): bool
    {
        return $this->hasRole('branch-manager');
    }

    /**
     * Check if user is a warehouse manager (can manage assigned warehouses).
     */
    public function isWarehouseManager(): bool
    {
        return $this->hasRole('warehouse-manager');
    }

    /**
     * Check if user is a warehouse operator (read-only access).
     */
    public function isWarehouseOperator(): bool
    {
        return $this->hasRole('warehouse-operator');
    }

    /**
     * Check if user can access a specific company.
     */
    public function canAccessCompany(int $companyId): bool
    {
        return $this->isSuperAdmin() || $this->company_id === $companyId;
    }

    /**
     * Check if user can access a specific branch.
     */
    public function canAccessBranch(int $branchId): bool
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin()) {
            return true;
        }

        // Branch managers can only access their assigned branch
        if ($this->isBranchManager()) {
            return $this->branch_id === $branchId;
        }

        // Warehouse managers and operators can access branches with their warehouses
        if ($this->isWarehouseManager() || $this->isWarehouseOperator()) {
            return $this->company->branches()->where('id', $branchId)->exists();
        }

        return false;
    }

    /**
     * Get warehouses accessible by this user.
     */
    public function accessibleWarehouses()
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Warehouse::query();
        }

        if ($this->isCompanyAdmin()) {
            return \App\Models\Warehouse::where('company_id', $this->company_id);
        }

        if ($this->isBranchManager()) {
            return \App\Models\Warehouse::where('branch_id', $this->branch_id);
        }

        // For warehouse managers and operators, this would need additional
        // user_warehouse assignments table - for now assume all warehouses in branch
        if ($this->isWarehouseManager() || $this->isWarehouseOperator()) {
            return \App\Models\Warehouse::where('branch_id', $this->branch_id);
        }

        return \App\Models\Warehouse::whereRaw('0 = 1'); // No access
    }

    /**
     * Get branches accessible by this user.
     */
    public function accessibleBranches()
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Branch::query();
        }

        if ($this->isCompanyAdmin()) {
            return \App\Models\Branch::where('company_id', $this->company_id);
        }

        // Branch managers, warehouse managers, and operators can only access their branch
        return \App\Models\Branch::where('id', $this->branch_id);
    }

    /**
     * Get user's warehouse access records.
     */
    public function warehouseAccess(): HasMany
    {
        return $this->hasMany(UserWarehouseAccess::class);
    }

    /**
     * Get active warehouse access records.
     */
    public function activeWarehouseAccess(): HasMany
    {
        return $this->warehouseAccess()->active();
    }

    /**
     * Get user's activity logs.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    /**
     * Check if user has access to a specific warehouse.
     */
    public function hasWarehouseAccess(int $warehouseId, ?string $permission = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isCompanyAdmin()) {
            // Company admins have access to all warehouses in their company
            $warehouse = \App\Models\Warehouse::find($warehouseId);

            return $warehouse && $warehouse->company_id === $this->company_id;
        }

        // Check specific warehouse access
        $access = $this->activeWarehouseAccess()
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $access) {
            return false;
        }

        return $permission ? $access->hasPermission($permission) : true;
    }

    /**
     * Grant warehouse access to user.
     */
    public function grantWarehouseAccess(int $warehouseId, string $accessType = 'full', ?array $permissions = null): UserWarehouseAccess
    {
        return UserWarehouseAccess::create([
            'user_id' => $this->id,
            'warehouse_id' => $warehouseId,
            'company_id' => $this->company_id,
            'access_type' => $accessType,
            'permissions' => $permissions,
            'is_active' => true,
            'active_at' => now(),
            'granted_at' => now(),
            'assigned_by' => auth()->id(),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Revoke warehouse access from user.
     */
    public function revokeWarehouseAccess(int $warehouseId): bool
    {
        return $this->warehouseAccess()
            ->where('warehouse_id', $warehouseId)
            ->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]) > 0;
    }

    /**
     * Get accessible warehouses with their access details.
     */
    public function getAccessibleWarehousesWithAccess()
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Warehouse::with('company')->get();
        }

        if ($this->isCompanyAdmin()) {
            return \App\Models\Warehouse::where('company_id', $this->company_id)
                ->with('company')
                ->get();
        }

        // Get warehouses through access records
        return \App\Models\Warehouse::whereIn('id',
            $this->activeWarehouseAccess()->pluck('warehouse_id')
        )->with('company')->get();
    }

    /**
     * Check if user has any active roles in a company.
     */
    public function hasActiveRolesInCompany(int $companyId): bool
    {
        return $this->roles()
            ->whereHas('pivot', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->where('is_active', true);
            })
            ->exists();
    }

    /**
     * Get user's roles for a specific company.
     */
    public function rolesInCompany(int $companyId)
    {
        return $this->roles()
            ->whereHas('pivot', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->where('is_active', true);
            });
    }

    /**
     * Assign role to user in specific company context.
     */
    public function assignRoleInCompany(string $roleName, int $companyId): void
    {
        $role = \App\Models\Role::where('name', $roleName)
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id'); // Global roles
            })
            ->first();

        if ($role) {
            $this->assignRole($role);

            // Update the pivot to include company context
            $this->roles()->updateExistingPivot($role->id, [
                'company_id' => $companyId,
                'is_active' => true,
                'active_at' => now(),
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Log the activity
            UserActivityLog::logRoleAssignment($this, $role, true);
        }
    }

    /**
     * Remove role from user in specific company context.
     */
    public function removeRoleInCompany(string $roleName, int $companyId): void
    {
        $role = \App\Models\Role::where('name', $roleName)->first();

        if ($role) {
            $this->roles()->updateExistingPivot($role->id, [
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);

            // Log the activity
            UserActivityLog::logRoleAssignment($this, $role, false);
        }
    }
}
