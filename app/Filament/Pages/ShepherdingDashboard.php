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

public array $listUrls = [];

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
    'total' => [
        'label' => 'Total People',
        'count' => (clone $baseQuery)->count(),
        'url' => $this->peopleTableUrl(),
    ],

    'active' => [
        'label' => 'Active',
        'count' => (clone $baseQuery)
            ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'Active'))
            ->count(),
        'url' => $this->peopleTableUrl([
            'church_status' => 'Active',
        ]),
    ],

    'new_ones' => [
        'label' => 'New Ones',
        'count' => (clone $baseQuery)
            ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'New One'))
            ->count(),
        'url' => $this->peopleTableUrl([
            'church_status' => 'New One',
        ]),
    ],

    'gospel_friends' => [
        'label' => 'Gospel Friends',
        'count' => (clone $baseQuery)
            ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'Gospel Friend'))
            ->count(),
        'url' => $this->peopleTableUrl([
            'church_status' => 'Gospel Friend',
        ]),
    ],

    'dormant' => [
        'label' => 'Dormant',
        'count' => (clone $baseQuery)
            ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', 'Dormant'))
            ->count(),
        'url' => $this->peopleTableUrl([
            'church_status' => 'Dormant',
        ]),
    ],

    'without_shepherd' => [
        'label' => 'No Shepherd',
        'count' => (clone $baseQuery)
            ->whereHas('churchProfile', fn (Builder $query) => $query->whereNull('shepherd_id'))
            ->count(),
        'url' => $this->peopleTableUrl([
            'shepherd_status' => 'without_shepherd',
        ]),
    ],

    'without_service' => [
        'label' => 'No Shepherding Group',
        'count' => (clone $baseQuery)
            ->whereHas('churchProfile', fn (Builder $query) => $query->whereNull('service')->orWhere('service', ''))
            ->count(),
        'url' => $this->peopleTableUrl([
            'shepherding_group' => '__none',
        ]),
    ],
];


$this->listUrls = [
    'without_shepherd' => $this->peopleTableUrl([
        'shepherd_status' => 'without_shepherd',
    ]),

    'dormant' => $this->peopleTableUrl([
        'church_status' => 'Dormant',
    ]),

    'new_ones' => $this->peopleTableUrl([
        'church_status' => 'New One',
    ]),

    'gospel_friends' => $this->peopleTableUrl([
        'church_status' => 'Gospel Friend',
    ]),

    'without_service' => $this->peopleTableUrl([
        'shepherding_group' => '__none',
    ]),
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


private function peopleTableUrl(array $filters = []): string
{
    $queryFilters = [];

    if (filled($this->locality)) {
        $queryFilters['locality'] = [
            'value' => $this->locality,
        ];
    }

    foreach ($filters as $filter => $value) {
        $queryFilters[$filter] = [
            'value' => (string) $value,
        ];
    }

    return PersonResource::getUrl('index') . '?' . http_build_query([
        'filters' => $queryFilters,
    ]);
}

}
