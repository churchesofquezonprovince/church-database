<?php

namespace App\Filament\Pages;

use App\Filament\Resources\People\PersonResource;
use App\Models\CampusWorkTerm;
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

    public function terms(): Collection
    {
        return CampusWorkTerm::query()
            ->orderByDesc('is_active')
            ->orderByDesc('academic_year')
            ->orderByDesc('id')
            ->get();
    }

    public function selectedTerm(): ?CampusWorkTerm
    {
        $termId = request()->integer('termId');

        if ($termId) {
            $selectedTerm = CampusWorkTerm::query()->find($termId);

            if ($selectedTerm) {
                return $selectedTerm;
            }
        }

        return CampusWorkTerm::query()
            ->where('is_active', true)
            ->latest('id')
            ->first()
            ?? CampusWorkTerm::query()
                ->latest('id')
                ->first();
    }

    public function termUrl(CampusWorkTerm $term): string
    {
        return static::getUrl() . '?' . http_build_query([
            'termId' => $term->id,
        ]);
    }

    public function members(): Collection
    {
        $term = $this->selectedTerm();

        if (! $term) {
            return collect();
        }

        return StudentNucleusMembership::query()
            ->where('campus_work_term_id', $term->id)
            ->with([
                'person.educationProfile',
                'term',
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
        $term = $this->selectedTerm();

        if (! $term) {
            return collect();
        }

        $existingPersonIds = StudentNucleusMembership::query()
            ->where('campus_work_term_id', $term->id)
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
