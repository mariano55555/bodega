<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Donation $donation): bool
    {
        return $user->company_id === $donation->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, Donation $donation): bool
    {
        return $user->company_id === $donation->company_id
            && $donation->canBeEdited();
    }

    public function delete(User $user, Donation $donation): bool
    {
        return $user->company_id === $donation->company_id
            && in_array($donation->status, ['borrador', 'pendiente']);
    }

    public function restore(User $user, Donation $donation): bool
    {
        return $user->company_id === $donation->company_id;
    }

    public function forceDelete(User $user, Donation $donation): bool
    {
        return false; // Never allow permanent deletion
    }

    public function approve(User $user, Donation $donation): bool
    {
        // TODO: Add permission check for donation approval
        return $user->company_id === $donation->company_id
            && $donation->canBeApproved();
    }

    public function receive(User $user, Donation $donation): bool
    {
        // TODO: Add permission check for donation reception
        return $user->company_id === $donation->company_id
            && $donation->canBeReceived();
    }

    public function cancel(User $user, Donation $donation): bool
    {
        return $user->company_id === $donation->company_id
            && $donation->canBeCancelled();
    }
}
