<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Person;

class HouseholdService
{
    public function head(Household $household): ?Person
    {
        return $household->householdHead;
    }

    public function members(Household $household)
    {
        return $household->members;
    }

    public function memberCount(Household $household): int
    {
        return $household->members()->count();
    }
}
