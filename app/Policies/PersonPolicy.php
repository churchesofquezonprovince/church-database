<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Person $person): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageRecords();
    }

    public function update(User $user, Person $person): bool
    {
        return $user->canManageRecords();
    }

    public function delete(User $user, Person $person): bool
    {
        return $user->canDeleteRecords();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canDeleteRecords();
    }

    public function restore(User $user, Person $person): bool
    {
        return $user->canDeleteRecords();
    }

    public function forceDelete(User $user, Person $person): bool
    {
        return $user->canDeleteRecords();
    }
}
