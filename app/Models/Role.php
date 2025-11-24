<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use Sluggable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'company_id',
        'permissions',
        'level',
        'guard_name',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'level' => 'integer',
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    /**
     * Get the company this role belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this role.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this role.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this role.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get parent roles in hierarchy.
     */
    public function parentRoles(): HasMany
    {
        return $this->hasMany(RoleHierarchy::class, 'child_role_id')
            ->where('is_active', true);
    }

    /**
     * Get child roles in hierarchy.
     */
    public function childRoles(): HasMany
    {
        return $this->hasMany(RoleHierarchy::class, 'parent_role_id')
            ->where('is_active', true);
    }

    /**
     * Scope to filter active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, ?int $companyId)
    {
        if ($companyId === null) {
            return $query->whereNull('company_id'); // Global roles
        }

        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by level.
     */
    public function scopeWithLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to filter roles created by a user.
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Check if role is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && ($this->active_at === null || $this->active_at <= now());
    }

    /**
     * Check if role is global (not company-specific).
     */
    public function isGlobal(): bool
    {
        return $this->company_id === null;
    }

    /**
     * Get all permissions including inherited from parent roles.
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $permissions = collect($this->permissions ?? []);

        // Add permissions from parent roles
        $this->parentRoles()->with('parentRole.permissions')->get()
            ->pluck('parentRole')
            ->each(function ($parentRole) use (&$permissions) {
                if ($parentRole && $parentRole->permissions) {
                    $permissions = $permissions->merge($parentRole->permissions);
                }
            });

        return $permissions->unique()->values();
    }

    /**
     * Activate this role.
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'active_at' => now(),
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Deactivate this role.
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Check if this role can be assigned to users in the given company.
     */
    public function canBeAssignedInCompany(int $companyId): bool
    {
        return $this->isGlobal() || $this->company_id === $companyId;
    }

    /**
     * Check if user can manage this role.
     */
    public function canBeManaged(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin() && $this->company_id === $user->company_id) {
            return true;
        }

        return false;
    }
}
