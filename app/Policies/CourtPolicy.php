<?php

namespace App\Policies;

use App\Models\Court;
use App\Models\User;

class CourtPolicy
{
    /**
     * Determine whether the user can view the court list and detail pages.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Court $court): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Court $court): bool
    {
        return $user->isAdmin();
    }

    /**
     * Deletion is restricted to administrators.
     *
     * A court that already has reservations is archived rather than removed, so
     * historical booking and payment records stay intact. See CourtController.
     */
    public function delete(User $user, Court $court): bool
    {
        return $user->isAdmin();
    }

    /**
     * Restoring an archived court is an administrative action.
     */
    public function restore(User $user, Court $court): bool
    {
        return $user->isAdmin();
    }
}
