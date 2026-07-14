<?php

namespace App\Policies;

use App\Models\EventProposal;
use App\Models\User;

class EventProposalPolicy
{
    /**
     * Determine whether the user can verify the event proposal.
     */
    public function verify(User $user, EventProposal $proposal): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'institution_admin';
    }
}
