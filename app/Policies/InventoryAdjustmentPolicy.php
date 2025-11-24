<?php

namespace App\Policies;

use App\Models\InventoryAdjustment;
use App\Models\User;

class InventoryAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->company_id === $inventoryAdjustment->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->company_id === $inventoryAdjustment->company_id
            && $inventoryAdjustment->canBeEdited();
    }

    public function delete(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->company_id === $inventoryAdjustment->company_id
            && in_array($inventoryAdjustment->status, ['borrador', 'rechazado']);
    }

    public function restore(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->company_id === $inventoryAdjustment->company_id;
    }

    public function forceDelete(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return false; // Never allow permanent deletion
    }

    public function submit(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->company_id === $inventoryAdjustment->company_id
            && $inventoryAdjustment->canBeSubmitted();
    }

    public function approve(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        // TODO: Add permission check for adjustment approval
        return $user->company_id === $inventoryAdjustment->company_id
            && $inventoryAdjustment->canBeApproved();
    }

    public function reject(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        // TODO: Add permission check for adjustment rejection
        return $user->company_id === $inventoryAdjustment->company_id
            && $inventoryAdjustment->canBeRejected();
    }

    public function process(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        // TODO: Add permission check for adjustment processing
        return $user->company_id === $inventoryAdjustment->company_id
            && $inventoryAdjustment->canBeProcessed();
    }

    public function cancel(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->company_id === $inventoryAdjustment->company_id
            && $inventoryAdjustment->canBeCancelled();
    }
}
