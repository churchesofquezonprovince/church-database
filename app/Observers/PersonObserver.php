<?php

namespace App\Observers;

use App\Models\Person;
use App\Support\ActivityLogger;

class PersonObserver
{
    public function created(Person $person): void
    {
        ActivityLogger::log(
            action: 'person.created',
            subject: $person,
            description: 'Created person record.',
            newValues: $person->getAttributes(),
        );
    }

    public function updated(Person $person): void
    {
        ActivityLogger::log(
            action: 'person.updated',
            subject: $person,
            description: 'Updated person record.',
            oldValues: $person->getOriginal(),
            newValues: $person->getChanges(),
        );
    }

    public function deleted(Person $person): void
    {
        ActivityLogger::log(
            action: 'person.deleted',
            subject: $person,
            description: 'Deleted person record.',
            oldValues: $person->getOriginal(),
        );
    }
}
