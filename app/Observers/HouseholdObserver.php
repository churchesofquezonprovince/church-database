<?php

namespace App\Observers;

use App\Models\Household;
use App\Support\ActivityLogger;

class HouseholdObserver
{
    public function created(Household $household): void
    {
        ActivityLogger::log(
            action: 'household.created',
            subject: $household,
            description: 'Created household record.',
            newValues: $household->getAttributes(),
        );
    }

    public function updated(Household $household): void
    {
        ActivityLogger::log(
            action: 'household.updated',
            subject: $household,
            description: 'Updated household record.',
            oldValues: $household->getOriginal(),
            newValues: $household->getChanges(),
        );
    }

    public function deleted(Household $household): void
    {
        ActivityLogger::log(
            action: 'household.deleted',
            subject: $household,
            description: 'Deleted household record.',
            oldValues: $household->getOriginal(),
        );
    }
}
