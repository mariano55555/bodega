<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasPermissionTo('permissions.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Permission $permission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return true; // Company admins can view all permissions
        }

        return $user->hasPermissionTo('permissions.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermissionTo('permissions.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Permission $permission): bool
    {
        if ($user->isSuperAdmin()) {
            return ! $this->isSystemPermission($permission);
        }

        // Only super admins can modify permissions
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Permission $permission): bool
    {
        if ($user->isSuperAdmin()) {
            return ! $this->isSystemPermission($permission);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Permission $permission): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Permission $permission): bool
    {
        return $user->isSuperAdmin() && ! $this->isSystemPermission($permission);
    }

    /**
     * Determine whether the user can assign the permission to roles/users.
     */
    public function assign(User $user, Permission $permission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            // Company admins can assign most permissions except system ones
            return ! $this->isSystemPermission($permission);
        }

        return $user->hasPermissionTo('permissions.assign');
    }

    /**
     * Check if permission is a system permission that cannot be modified.
     */
    private function isSystemPermission(Permission $permission): bool
    {
        $systemPermissions = [
            'super-admin',
            'company-admin',
            'users.create-super-admin',
            'companies.create',
            'companies.delete',
        ];

        return in_array($permission->name, $systemPermissions);
    }
}
