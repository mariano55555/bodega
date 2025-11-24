<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleHierarchy extends Model
{
    use SoftDeletes;

    protected $table = 'role_hierarchies';

    protected $fillable = [
        'parent_role_id',
        'child_role_id',
        'company_id',
        'is_active',
        'active_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'active_at' => 'datetime',
        ];
    }

    /**
     * Get the parent role.
     */
    public function parentRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_role_id');
    }

    /**
     * Get the child role.
     */
    public function childRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'child_role_id');
    }

    /**
     * Get the company this hierarchy belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this hierarchy.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this hierarchy.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this hierarchy.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope to filter active hierarchies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by parent role.
     */
    public function scopeForParentRole($query, int $parentRoleId)
    {
        return $query->where('parent_role_id', $parentRoleId);
    }

    /**
     * Scope to filter by child role.
     */
    public function scopeForChildRole($query, int $childRoleId)
    {
        return $query->where('child_role_id', $childRoleId);
    }

    /**
     * Check if hierarchy is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && ($this->active_at === null || $this->active_at <= now());
    }

    /**
     * Activate this hierarchy.
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
     * Deactivate this hierarchy.
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Check if creating this hierarchy would create a circular dependency.
     */
    public static function wouldCreateCircularDependency(int $parentRoleId, int $childRoleId, int $companyId): bool
    {
        // Direct check - child becomes parent
        if ($parentRoleId === $childRoleId) {
            return true;
        }

        // Check if child role is already a parent of the proposed parent role
        return self::where('parent_role_id', $childRoleId)
            ->where('child_role_id', $parentRoleId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all descendant roles for a given parent role.
     */
    public static function getDescendantRoles(int $parentRoleId, int $companyId): array
    {
        $descendants = [];
        $queue = [$parentRoleId];
        $processed = [];

        while (! empty($queue)) {
            $currentRoleId = array_shift($queue);

            if (in_array($currentRoleId, $processed)) {
                continue; // Prevent infinite loops
            }

            $processed[] = $currentRoleId;

            $children = self::where('parent_role_id', $currentRoleId)
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->pluck('child_role_id')
                ->toArray();

            foreach ($children as $childRoleId) {
                if (! in_array($childRoleId, $descendants)) {
                    $descendants[] = $childRoleId;
                    $queue[] = $childRoleId;
                }
            }
        }

        return $descendants;
    }

    /**
     * Get all ancestor roles for a given child role.
     */
    public static function getAncestorRoles(int $childRoleId, int $companyId): array
    {
        $ancestors = [];
        $queue = [$childRoleId];
        $processed = [];

        while (! empty($queue)) {
            $currentRoleId = array_shift($queue);

            if (in_array($currentRoleId, $processed)) {
                continue; // Prevent infinite loops
            }

            $processed[] = $currentRoleId;

            $parents = self::where('child_role_id', $currentRoleId)
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->pluck('parent_role_id')
                ->toArray();

            foreach ($parents as $parentRoleId) {
                if (! in_array($parentRoleId, $ancestors)) {
                    $ancestors[] = $parentRoleId;
                    $queue[] = $parentRoleId;
                }
            }
        }

        return $ancestors;
    }
}
