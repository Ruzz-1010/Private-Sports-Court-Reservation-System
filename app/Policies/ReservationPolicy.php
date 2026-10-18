<?php

namespace App\Policies;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /**
     * Customers may only see their own reservations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin() || $reservation->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    /**
     * A customer may cancel their own booking, but only while it is still
     * pending or confirmed and has not started.
     */
    public function cancel(User $user, Reservation $reservation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $reservation->user_id === $user->getKey()
            && in_array($reservation->status, [
                ReservationStatus::Pending,
                ReservationStatus::Confirmed,
            ], true)
            && $reservation->starts_at->isFuture();
    }
}
