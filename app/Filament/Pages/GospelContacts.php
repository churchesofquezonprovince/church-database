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

    public string $viewMode = 'active';

    public function mount(): void
    {
        $requestedView = (string) request()->query(
            'view',
            ''
        );

        $legacyStatus = (string) request()->query(
            'status',
            ''
        );

        $this->viewMode =
            $requestedView === 'archived'
            || $legacyStatus === 'linked'
                ? 'archived'
                : 'active';
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
                $this->viewMode === 'archived',
                fn ($query) =>
                    $query->whereNotNull('person_id'),
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
            'active' =>
                GospelContact::query()
                    ->whereNull('person_id')
                    ->count(),

            'archived' =>
                GospelContact::query()
                    ->whereNotNull('person_id')
                    ->count(),

            'historical_total' =>
                GospelContact::query()->count(),
        ];
    }

    public function localityOptions(): array
    {
        return LocalityOptions::groupedActiveConfigured();
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
