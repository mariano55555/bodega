<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Check if user has permission
        if (! $user->can('view-branches')) {
            return false;
        }

        // Super admin can view branches without company restriction
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Other users must belong to a company
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        // Check permission and company access
        if (! $user->can('view-branches')) {
            return false;
        }

        // Super admin can view all branches
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admin can view all branches in their company
        if ($user->isCompanyAdmin()) {
            return $user->company_id === $branch->company_id;
        }

        // Branch managers can only view their assigned branch
        if ($user->isBranchManager()) {
            return $user->branch_id === $branch->id;
        }

        // Warehouse managers and operators can view branches with their warehouses
        if ($user->isWarehouseManager() || $user->isWarehouseOperator()) {
            return $user->company_id === $branch->company_id &&
                   $user->canAccessBranch($branch->id);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check if user has permission
        if (! $user->can('create-branches')) {
            return false;
        }

        // Super admin can create branches without company restriction
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admin can create branches in their company
        if ($user->isCompanyAdmin()) {
            return $user->company_id !== null;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        // Check permission first
        if (! $user->can('edit-branches')) {
            return false;
        }

        // Super admin can update any branch
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admin can update branches in their company
        if ($user->isCompanyAdmin()) {
            return $user->company_id === $branch->company_id;
        }

        // Branch managers can update their assigned branch
        if ($user->isBranchManager()) {
            return $user->branch_id === $branch->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Check permission first
        if (! $user->can('delete-branches')) {
            return false;
        }

        // Prevent deletion of main branches
        if ($branch->is_main_branch) {
            return false;
        }

        // Super admin can delete any branch
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only company admin can delete branches in their company
        if ($user->isCompanyAdmin()) {
            return $user->company_id === $branch->company_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Branch $branch): bool
    {
        // Similar logic to delete but for restoration
        if (! $user->can('delete-branches')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isCompanyAdmin() && $user->company_id === $branch->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        // Only super admin can force delete
        return $user->isSuperAdmin() &&
               $user->can('delete-branches') &&
               ! $branch->is_main_branch;
    }

    /**
     * Determine whether the user can filter branches by company.
     */
    public function filterByCompany(User $user, int $companyId): bool
    {
        // Check permission first
        if (! $user->can('filter-branches-by-company')) {
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
     * Determine whether the user can toggle branch status.
     */
    public function toggleStatus(User $user, Branch $branch): bool
    {
        // Check permission first
        if (! $user->can('manage-branch-status')) {
            return false;
        }

        // Use same logic as update
        return $this->update($user, $branch);
    }
}
