<?php

namespace App\Policies;

use App\Models\InventoryClosure;
use App\Models\User;

class InventoryClosurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, InventoryClosure $inventoryClosure): bool
    {
        return $user->company_id === $inventoryClosure->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, InventoryClosure $inventoryClosure): bool
    {
        return $user->company_id === $inventoryClosure->company_id
            && $inventoryClosure->canBeEdited();
    }

    public function delete(User $user, InventoryClosure $inventoryClosure): bool
    {
        return $user->company_id === $inventoryClosure->company_id
            && $inventoryClosure->status === 'en_proceso';
    }

    public function restore(User $user, InventoryClosure $inventoryClosure): bool
    {
        return $user->company_id === $inventoryClosure->company_id;
    }

    public function forceDelete(User $user, InventoryClosure $inventoryClosure): bool
    {
        return false; // Never allow permanent deletion
    }

    public function process(User $user, InventoryClosure $inventoryClosure): bool
    {
        return $user->company_id === $inventoryClosure->company_id
            && $inventoryClosure->canBeProcessed();
    }

    public function approve(User $user, InventoryClosure $inventoryClosure): bool
    {
        // TODO: Add permission check for closure approval
        return $user->company_id === $inventoryClosure->company_id
            && $inventoryClosure->canBeApproved();
    }

    public function close(User $user, InventoryClosure $inventoryClosure): bool
    {
        // TODO: Add permission check for closure finalization
        return $user->company_id === $inventoryClosure->company_id
            && $inventoryClosure->canBeClosed();
    }

    public function reopen(User $user, InventoryClosure $inventoryClosure): bool
    {
        // TODO: Add permission check for closure reopening
        return $user->company_id === $inventoryClosure->company_id
            && $inventoryClosure->canBeReopened();
    }

    public function cancel(User $user, InventoryClosure $inventoryClosure): bool
    {
        return $user->company_id === $inventoryClosure->company_id
            && $inventoryClosure->canBeCancelled();
    }
}
