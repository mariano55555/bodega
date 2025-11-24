<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasPermissionTo('roles.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            // Company admins can view roles in their company and global roles
            return $role->isGlobal() || $role->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('roles.view') && $this->canAccessRole($user, $role);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasPermissionTo('roles.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only super admins can modify global roles
        if ($role->isGlobal()) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return $role->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('roles.update') && $this->canAccessRole($user, $role);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        if ($user->isSuperAdmin()) {
            // Super admins can delete any role except system roles
            return ! $this->isSystemRole($role);
        }

        // Only super admins can delete global roles
        if ($role->isGlobal()) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return $role->company_id === $user->company_id && ! $this->isSystemRole($role);
        }

        return $user->hasPermissionTo('roles.delete')
            && $this->canAccessRole($user, $role)
            && ! $this->isSystemRole($role);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        return $this->delete($user, $role);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        // Only super admins can force delete roles
        return $user->isSuperAdmin() && ! $this->isSystemRole($role);
    }

    /**
     * Determine whether the user can assign the role to users.
     */
    public function assign(User $user, Role $role): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $role->canBeAssignedInCompany($user->company_id);
        }

        return $user->hasPermissionTo('roles.assign') && $this->canAccessRole($user, $role);
    }

    /**
     * Determine whether the user can manage role permissions.
     */
    public function managePermissions(User $user, Role $role): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only super admins can modify global role permissions
        if ($role->isGlobal()) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return $role->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('roles.manage-permissions') && $this->canAccessRole($user, $role);
    }

    /**
     * Determine whether the user can manage role hierarchies.
     */
    public function manageHierarchies(User $user, Role $role): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only super admins can modify global role hierarchies
        if ($role->isGlobal()) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return $role->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('roles.manage-hierarchies') && $this->canAccessRole($user, $role);
    }

    /**
     * Check if user can access the role based on company context.
     */
    private function canAccessRole(User $user, Role $role): bool
    {
        // Global roles are accessible to all
        if ($role->isGlobal()) {
            return true;
        }

        // Role must be in user's company
        return $role->company_id === $user->company_id;
    }

    /**
     * Check if role is a system role that cannot be deleted.
     */
    private function isSystemRole(Role $role): bool
    {
        $systemRoles = ['super-admin', 'company-admin', 'warehouse-manager', 'warehouse-operator'];

        return in_array($role->name, $systemRoles);
    }
}
