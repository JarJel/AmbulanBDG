<?php

namespace App\Policies;

use App\Models\StaffProfile;
use App\Models\User;

class StaffProfilePolicy
{
    /**
     * Determine whether the user can verify the staff profile.
     */
    public function verify(User $user, StaffProfile $staff): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'institution_admin'
            && $user->institution_id !== null
            && $staff->user !== null
            && $user->institution_id === $staff->user->institution_id;
    }
}
