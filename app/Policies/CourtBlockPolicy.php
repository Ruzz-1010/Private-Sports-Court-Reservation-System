<?php

namespace App\Policies;

use App\Models\CourtBlock;
use App\Models\User;

class CourtBlockPolicy
{
    public function delete(User $user, CourtBlock $courtBlock): bool
    {
        return $user->isAdmin();
    }
}
