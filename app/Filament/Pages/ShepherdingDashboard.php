<?php

namespace App\Filament\Pages;

use App\Filament\Resources\People\PersonResource;
use App\Models\Person;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class ShepherdingDashboard extends Page
{
    protected string $view = 'filament.pages.shepherding-dashboard';

    public ?string $locality = null;

    public array $localities = [];

    public array $stats = [];

    public array $peopleWithoutShepherd = [];

    public array $dormantPeople = [];

    public array $newOnes = [];

    public array $gospelFriends = [];

    public array $peopleWithoutService = [];

    public function mount(): void
    {
        $this->localities = Person::query()
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->distinct()
            ->orderBy('locality')
            ->pluck('locality')
            ->values()
            ->all();

        $this->loadDashboard();
    }

    public function getTitle(): string
    {
        return 'Shepherding Dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return 'Shepherding Dashboard';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Church Database';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-heart';
    }

    public function updatedLocality(): void
    {
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $baseQuery = $this->basePeopleQuery();

        $this->stats = [
            'total' => (clone $baseQuery)->count(),

            'active' => (clone $baseQuery)
                ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'Active'))
                ->count(),

            'new_ones' => (clone $baseQuery)
                ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'New One'))
                ->count(),

            'gospel_friends' => (clone $baseQuery)
                ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'Gospel Friend'))
                ->count(),

            'dormant' => (clone $baseQuery)
                ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'Dormant'))
                ->count(),

            'without_shepherd' => (clone $baseQuery)
                ->whereHas('churchProfile', fn (Builder $query) => $query->whereNull('shepherd_id'))
                ->count(),

            'without_service' => (clone $baseQuery)
                ->whereHas('churchProfile', fn (Builder $query) => $query->whereNull('service')->orWhere('service', ''))
                ->count(),
        ];

        $this->peopleWithoutShepherd = $this->peopleQuery()
            ->whereHas('churchProfile', fn (Builder $query) => $query->whereNull('shepherd_id'))
            ->limit(10)
            ->get()
            ->map(fn (Person $person) => $this->personRow($person))
            ->all();

        $this->dormantPeople = $this->peopleQuery()
            ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'Dormant'))
            ->limit(10)
            ->get()
            ->map(fn (Person $person) => $this->personRow($person))
            ->all();

        $this->newOnes = $this->peopleQuery()
            ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'New One'))
            ->limit(10)
            ->get()
            ->map(fn (Person $person) => $this->personRow($person))
            ->all();

        $this->gospelFriends = $this->peopleQuery()
            ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'Gospel Friend'))
            ->limit(10)
            ->get()
            ->map(fn (Person $person) => $this->personRow($person))
            ->all();

        $this->peopleWithoutService = $this->peopleQuery()
            ->whereHas('churchProfile', fn (Builder $query) => $query->whereNull('service')->orWhere('service', ''))
            ->limit(10)
            ->get()
            ->map(fn (Person $person) => $this->personRow($person))
            ->all();
    }

    private function basePeopleQuery(): Builder
    {
        return Person::query()
            ->when(
                filled($this->locality),
                fn (Builder $query) => $query->where('locality', $this->locality)
            );
    }

    private function peopleQuery(): Builder
    {
        return $this->basePeopleQuery()
            ->with(['churchProfile.shepherd', 'household'])
            ->orderBy('lastname')
            ->orderBy('firstname');
    }

    private function personRow(Person $person): array
    {
        return [
            'id' => $person->id,
            'name' => $person->display_name,
            'category' => $person->churchProfile?->category ?? 'Unknown',
            'status' => $person->churchProfile?->status ?? 'Unknown',
            'service' => $person->churchProfile?->service ?? 'None recorded',
            'shepherd' => $person->churchProfile?->shepherd?->display_name ?? 'None recorded',
            'locality' => $person->locality ?? 'None recorded',
            'url' => PersonResource::getUrl('view', [
                'record' => $person->id,
            ]),
        ];
    }
}
