<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleDocument;

class VehicleDocumentPolicy
{
    /**
     * Determine whether the user can verify the vehicle document.
     */
    public function verify(User $user, VehicleDocument $document): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'institution_admin'
            && $user->institution_id !== null
            && $document->ambulance !== null
            && $user->institution_id === $document->ambulance->institution_id;
    }
}
