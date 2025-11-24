<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Check if user has permission
        if (! $user->can('view-warehouses')) {
            return false;
        }

        // Super admin can view warehouses without company restriction
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Other users must belong to a company
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        // Check permission first
        if (! $user->can('view-warehouses')) {
            return false;
        }

        // Super admin can view all warehouses
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admin can view all warehouses in their company
        if ($user->isCompanyAdmin()) {
            return $user->company_id === $warehouse->company_id;
        }

        // Branch managers can view warehouses in their branch
        if ($user->isBranchManager()) {
            return $user->company_id === $warehouse->company_id &&
                   $user->branch_id === $warehouse->branch_id;
        }

        // Warehouse managers and operators can view warehouses they have access to
        if ($user->isWarehouseManager() || $user->isWarehouseOperator()) {
            return $user->company_id === $warehouse->company_id &&
                   $user->accessibleWarehouses()->where('id', $warehouse->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check permission and role
        if (! $user->can('create-warehouses')) {
            return false;
        }

        // Super admin can create warehouses without company restriction
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admin can create warehouses in their company
        if ($user->isCompanyAdmin()) {
            return $user->company_id !== null;
        }

        // Branch managers can create warehouses in their branch
        if ($user->isBranchManager()) {
            return $user->company_id !== null && $user->branch_id !== null;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        // Check permission first
        if (! $user->can('edit-warehouses')) {
            return false;
        }

        // Super admin can update any warehouse
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admin can update warehouses in their company
        if ($user->isCompanyAdmin()) {
            return $user->company_id === $warehouse->company_id;
        }

        // Branch managers can update warehouses in their branch
        if ($user->isBranchManager()) {
            return $user->company_id === $warehouse->company_id &&
                   $user->branch_id === $warehouse->branch_id;
        }

        // Warehouse managers can update warehouses they manage
        if ($user->isWarehouseManager()) {
            return $user->company_id === $warehouse->company_id &&
                   $user->accessibleWarehouses()->where('id', $warehouse->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        // Check permission first
        if (! $user->can('delete-warehouses')) {
            return false;
        }

        // Super admin can delete any warehouse
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only company admin can delete warehouses
        if ($user->isCompanyAdmin()) {
            return $user->company_id === $warehouse->company_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Warehouse $warehouse): bool
    {
        // Similar logic to delete
        if (! $user->can('delete-warehouses')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isCompanyAdmin() && $user->company_id === $warehouse->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        // Only super admin can force delete
        return $user->isSuperAdmin() && $user->can('delete-warehouses');
    }

    /**
     * Determine whether the user can filter warehouses by company.
     */
    public function filterByCompany(User $user, int $companyId): bool
    {
        // Check permission first
        if (! $user->can('filter-warehouses-by-company')) {
            return false;
        }

        // Super admin can filter by any company
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can only filter by their own company
        return $user->canAccessCompany($companyId);
    }

    /**
     * Determine whether the user can filter warehouses by branch.
     */
    public function filterByBranch(User $user, int $branchId): bool
    {
        // Check permission first
        if (! $user->can('filter-warehouses-by-branch')) {
            return false;
        }

        // Verify the branch belongs to user's company
        $branch = Branch::where('id', $branchId)
            ->where('company_id', $user->company_id)
            ->first();

        if (! $branch) {
            return false;
        }

        // Super admin and company admin can filter by any branch in company
        if ($user->isSuperAdmin() || $user->isCompanyAdmin()) {
            return true;
        }

        // Others can only filter by branches they have access to
        return $user->canAccessBranch($branchId);
    }

    /**
     * Determine whether the user can view warehouse capacity.
     */
    public function viewCapacity(User $user, Warehouse $warehouse): bool
    {
        // Check permission first
        if (! $user->can('view-warehouse-capacity')) {
            return false;
        }

        // Use same logic as view
        return $this->view($user, $warehouse);
    }

    /**
     * Determine whether the user can toggle warehouse status.
     */
    public function toggleStatus(User $user, Warehouse $warehouse): bool
    {
        // Check permission first
        if (! $user->can('manage-warehouse-status')) {
            return false;
        }

        // Use same logic as update
        return $this->update($user, $warehouse);
    }

    /**
     * Determine whether the user can manage warehouse inventory.
     */
    public function manageInventory(User $user, Warehouse $warehouse): bool
    {
        // Check inventory permissions
        if (! $user->can('view-inventory')) {
            return false;
        }

        // Use same logic as view for warehouse access
        return $this->view($user, $warehouse);
    }
}
