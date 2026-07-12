<?php

namespace App\Filament\Pages;

use App\Filament\Resources\People\PersonResource;
use App\Models\Person;
use App\Models\StudentNucleusMembership;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class StudentNucleus extends Page
{
    protected string $view = 'filament.pages.student-nucleus';

    public function getTitle(): string
    {
        return 'Student Nucleus';
    }

    public static function getNavigationLabel(): string
    {
        return 'Student Nucleus';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Campus Work';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-academic-cap';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function members(): Collection
    {
        return StudentNucleusMembership::query()
            ->with([
                'person.educationProfile',
            ])
            ->get()
            ->sortBy(function (StudentNucleusMembership $membership): string {
                $person = $membership->person;

                return mb_strtolower(implode('|', [
                    $person?->educationProfile?->school_workplace ?? '',
                    $person?->lastname ?? '',
                    $person?->firstname ?? '',
                ]));
            })
            ->values();
    }

    public function groupedMembers(): Collection
    {
        return $this->members()
            ->groupBy(
                fn (StudentNucleusMembership $membership): string =>
                    $membership->person?->educationProfile?->school_workplace
                    ?: 'School not recorded'
            )
            ->sortKeys();
    }

    public function availablePeople(): Collection
    {
        $existingPersonIds = StudentNucleusMembership::query()
            ->pluck('person_id');

        return Person::query()
            ->with(['educationProfile'])
            ->whereNotIn('id', $existingPersonIds)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function personUrl(Person $person): string
    {
        return PersonResource::getUrl('view', [
            'record' => $person->id,
        ]);
    }
}
