<?php

namespace App\Filament\Pages;

use App\Filament\Resources\People\PersonResource;
use App\Models\GospelContact;
use App\Models\Person;
use App\Support\LocalityOptions;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class GospelContacts extends Page
{
    protected string $view =
        'filament.pages.gospel-contacts';

    protected static ?string $slug =
        'gospel-contacts';

    public string $search = '';

    public string $statusFilter = 'all';

    public string $existingPeopleSearch = '';

    public function mount(): void
    {
        $status = (string) request()->query(
            'status',
            'all'
        );

        $this->statusFilter = in_array(
            $status,
            [
                'all',
                'linked',
                'unlinked',
            ],
            true
        )
            ? $status
            : 'all';
    }

    public function getTitle(): string
    {
        return 'Gospel Contacts';
    }

    public static function getNavigationLabel(): string
    {
        return 'Gospel Contacts';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }

    public function contacts(): Collection
    {
        $search = trim($this->search);

        return GospelContact::query()
            ->with([
                'person.churchProfile',
                'localityRecord',
            ])
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $like = "%{$search}%";

                    $query->where(
                        function ($query) use ($like): void {
                            $query
                                ->where(
                                    'firstname',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'lastname',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'locality',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'contact_place',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'contact_number',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'facebook_account',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'notes',
                                    'like',
                                    $like
                                )
                                ->orWhereHas(
                                    'person',
                                    function ($query) use ($like): void {
                                        $query
                                            ->where(
                                                'firstname',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'lastname',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'nickname',
                                                'like',
                                                $like
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $this->statusFilter === 'linked',
                fn ($query) =>
                    $query->whereNotNull('person_id')
            )
            ->when(
                $this->statusFilter === 'unlinked',
                fn ($query) =>
                    $query->whereNull('person_id')
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function summary(): array
    {
        return [
            'total' =>
                GospelContact::query()->count(),

            'unlinked' =>
                GospelContact::query()
                    ->whereNull('person_id')
                    ->count(),

            'linked' =>
                GospelContact::query()
                    ->whereNotNull('person_id')
                    ->count(),
        ];
    }

    public function localityOptions(): array
    {
        return LocalityOptions::groupedActiveConfigured();
    }

    public function availableExistingPeople(): Collection
    {
        $search = trim(
            $this->existingPeopleSearch
        );

        if (mb_strlen($search) < 2) {
            return collect();
        }

        $like = "%{$search}%";

        return Person::query()
            ->select([
                'id',
                'firstname',
                'middlename',
                'lastname',
                'nickname',
                'sex',
                'locality',
                'locality_id',
                'contact_number',
                'email',
                'facebook_account',
            ])
            ->with([
                'churchProfile',
            ])
            ->whereDoesntHave(
                'gospelContact'
            )
            ->where(
                function ($query) use ($like): void {
                    $query
                        ->where(
                            'firstname',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'middlename',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'lastname',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'nickname',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'locality',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'contact_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'email',
                            'like',
                            $like
                        );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(50)
            ->get();
    }

    public function personUrl(
        Person $person
    ): string {
        return PersonResource::getUrl(
            'view',
            [
                'record' => $person->id,
            ]
        );
    }
}
