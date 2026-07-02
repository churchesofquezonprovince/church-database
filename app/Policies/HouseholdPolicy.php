<?php

namespace App\Policies;

use App\Models\Household;
use App\Models\User;

class HouseholdPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Household $household): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageRecords();
    }

    public function update(User $user, Household $household): bool
    {
        return $user->canManageRecords();
    }

    public function delete(User $user, Household $household): bool
    {
        return $user->canDeleteRecords();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canDeleteRecords();
    }

    public function restore(User $user, Household $household): bool
    {
        return $user->canDeleteRecords();
    }

    public function forceDelete(User $user, Household $household): bool
    {
        return $user->canDeleteRecords();
    }
}
