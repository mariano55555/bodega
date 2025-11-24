<?php

namespace App\Policies;

use App\Models\InventoryTransfer;
use App\Models\User;

class InventoryTransferPolicy
{
    /**
     * Check if user belongs to the same company as the transfer (via warehouse).
     */
    private function belongsToSameCompany(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        // Super admins can access all transfers
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Get company from the origin warehouse
        $warehouseCompanyId = $inventoryTransfer->fromWarehouse?->company_id;

        return $user->company_id !== null && $user->company_id === $warehouseCompanyId;
    }

    /**
     * Check if status matches (supports both English and Spanish values).
     */
    private function hasStatus(InventoryTransfer $transfer, array $statuses): bool
    {
        return in_array($transfer->status, $statuses);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // User can view transfers if they have access to any warehouse in their company
        return $user->company_id !== null || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        return $this->belongsToSameCompany($user, $inventoryTransfer);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // User can create transfers if they belong to a company
        return $user->company_id !== null || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        // User can only update transfers from their company that are still pending/draft
        return $this->belongsToSameCompany($user, $inventoryTransfer)
            && $this->hasStatus($inventoryTransfer, ['pending', 'pendiente', 'draft']);
    }

    /**
     * Determine whether the user can approve the transfer.
     */
    public function approve(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        // User can approve if same company and transfer is pending
        return $this->belongsToSameCompany($user, $inventoryTransfer)
            && $this->hasStatus($inventoryTransfer, ['pending', 'pendiente']);
    }

    /**
     * Determine whether the user can ship the transfer.
     */
    public function ship(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        // User can ship if same company and transfer is approved
        return $this->belongsToSameCompany($user, $inventoryTransfer)
            && $this->hasStatus($inventoryTransfer, ['approved', 'aprobado']);
    }

    /**
     * Determine whether the user can receive the transfer.
     */
    public function receive(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        // User can receive if same company and transfer is in transit
        return $this->belongsToSameCompany($user, $inventoryTransfer)
            && $this->hasStatus($inventoryTransfer, ['in_transit', 'en_transito']);
    }

    /**
     * Determine whether the user can cancel the transfer.
     */
    public function cancel(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        // User can cancel if same company and transfer is pending or approved
        return $this->belongsToSameCompany($user, $inventoryTransfer)
            && $this->hasStatus($inventoryTransfer, ['pending', 'pendiente', 'approved', 'aprobado']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        // Only allow deletion of cancelled transfers from same company
        return $this->belongsToSameCompany($user, $inventoryTransfer)
            && $this->hasStatus($inventoryTransfer, ['cancelled', 'cancelado']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        return $this->belongsToSameCompany($user, $inventoryTransfer);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InventoryTransfer $inventoryTransfer): bool
    {
        // Prevent permanent deletion in normal circumstances
        return false;
    }
}
