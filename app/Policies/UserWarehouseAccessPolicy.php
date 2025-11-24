<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserWarehouseAccess;

class UserWarehouseAccessPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasPermissionTo('warehouse-access.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserWarehouseAccess $userWarehouseAccess): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $userWarehouseAccess->company_id === $user->company_id;
        }

        // Users can view their own access records
        if ($user->id === $userWarehouseAccess->user_id) {
            return true;
        }

        return $user->hasPermissionTo('warehouse-access.view')
            && $userWarehouseAccess->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasPermissionTo('warehouse-access.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserWarehouseAccess $userWarehouseAccess): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $userWarehouseAccess->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('warehouse-access.update')
            && $userWarehouseAccess->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserWarehouseAccess $userWarehouseAccess): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $userWarehouseAccess->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('warehouse-access.delete')
            && $userWarehouseAccess->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserWarehouseAccess $userWarehouseAccess): bool
    {
        return $this->delete($user, $userWarehouseAccess);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserWarehouseAccess $userWarehouseAccess): bool
    {
        return $user->isSuperAdmin()
            && $userWarehouseAccess->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can grant warehouse access.
     */
    public function grant(User $user, UserWarehouseAccess $userWarehouseAccess): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $userWarehouseAccess->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('warehouse-access.grant')
            && $userWarehouseAccess->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can revoke warehouse access.
     */
    public function revoke(User $user, UserWarehouseAccess $userWarehouseAccess): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $userWarehouseAccess->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('warehouse-access.revoke')
            && $userWarehouseAccess->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can extend warehouse access.
     */
    public function extend(User $user, UserWarehouseAccess $userWarehouseAccess): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $userWarehouseAccess->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('warehouse-access.extend')
            && $userWarehouseAccess->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can manage warehouse access for a specific user.
     */
    public function manageForUser(User $user, User $targetUser): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $targetUser->company_id === $user->company_id;
        }

        return $user->hasPermissionTo('warehouse-access.manage')
            && $targetUser->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can manage warehouse access for a specific warehouse.
     */
    public function manageForWarehouse(User $user, int $warehouseId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if user has access to the warehouse
        return $user->hasWarehouseAccess($warehouseId, 'manage')
            || $user->hasPermissionTo('warehouse-access.manage');
    }
}
