<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function manageLifecycle(User $user, Contract $contract): bool
    {
        return $user->isAdmin() && $user->status === User::STATUS_ACTIVE;
    }
}
