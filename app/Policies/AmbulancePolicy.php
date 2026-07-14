<?php

namespace App\Policies;

use App\Models\Ambulance;
use App\Models\User;

class AmbulancePolicy
{
    /**
     * Determine whether the user can update the ambulance.
     */
    public function update(User $user, Ambulance $ambulance): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'institution_admin'
            && $user->institution_id !== null
            && $user->institution_id === $ambulance->institution_id;
    }
}
