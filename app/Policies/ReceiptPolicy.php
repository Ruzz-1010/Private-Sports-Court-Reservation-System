<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    /**
     * A customer may only download a receipt for their own reservation.
     *
     * Administrators may download any receipt for reconciliation. Receipts
     * carry the customer name, email, and schedule, so leaking another
     * customer's receipt would disclose personal information.
     */
    public function download(User $user, Receipt $receipt): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $reservation = $receipt->relationLoaded('reservation')
            ? $receipt->reservation
            : $receipt->reservation()->first();

        return $reservation !== null && $reservation->user_id === $user->getKey();
    }
}
