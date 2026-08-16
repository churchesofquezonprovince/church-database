<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\FamilyTree;
use App\Filament\Resources\Households\HouseholdResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\Household;
use App\Models\Person;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class ChurchDashboardWidget extends Widget
{
    protected string $view = 'filament.widgets.church-dashboard-widget';

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public array $stats = [];

    public array $quickLinks = [];

    public array $careAlerts = [];

    public array $recentPeople = [];

    public function mount(): void
    {
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $this->stats = [
            [
                'key' => 'people',
                'label' => 'Total People',
                'count' => Person::query()->count(),
                'url' => PersonResource::getUrl('index'),
            ],
            [
                'key' => 'households',
                'label' => 'Households',
                'count' => Household::query()->count(),
                'url' => HouseholdResource::getUrl('index'),
            ],
            [
                'key' => 'active',
                'label' => 'Active',
                'count' => $this->peopleWithStatus('Active'),
                'url' => $this->peopleTableUrl([
                    'church_status' => 'Active',
                ]),
            ],
        ];

        $this->quickLinks = [
            [
                'key' => 'add_person',
                'label' => 'Add Person',
                'description' => 'Encode a new person profile.',
                'url' => PersonResource::getUrl('create'),
            ],
            [
                'key' => 'people',
                'label' => 'People Database',
                'description' => 'Search, filter, and manage all people.',
                'url' => PersonResource::getUrl('index'),
            ],
            [
                'key' => 'households',
                'label' => 'Households',
                'description' => 'Manage families and household groups.',
                'url' => HouseholdResource::getUrl('index'),
            ],
            [
                'key' => 'family_tree',
                'label' => 'Family Tree',
                'description' => 'View visual family relationships.',
                'url' => FamilyTree::getUrl(),
            ],
        ];

        $this->careAlerts = [];

        $this->recentPeople = Person::query()
            ->with(['churchProfile', 'household'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Person $person): array => [
                'name' => $person->display_name,
                'initials' => $this->initials($person),
                'status' => $person->churchProfile?->status ?? 'Unknown',
                'category' => $person->churchProfile?->category ?? 'Unknown',
                'locality' => $person->locality ?: 'No locality',
                'url' => PersonResource::getUrl('view', [
                    'record' => $person->id,
                ]),
            ])
            ->all();
    }

    private function peopleWithStatus(string $status): int
    {
        return Person::query()
            ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', $status))
            ->count();
    }

    private function peopleTableUrl(array $filters = []): string
    {
        if (empty($filters)) {
            return PersonResource::getUrl('index');
        }

        $queryFilters = [];

        foreach ($filters as $filter => $value) {
            $queryFilters[$filter] = [
                'value' => (string) $value,
            ];
        }

        return PersonResource::getUrl('index') . '?' . http_build_query([
            'filters' => $queryFilters,
        ]);
    }

    private function initials(Person $person): string
    {
        return collect([
            $person->firstname,
            $person->lastname,
        ])
            ->filter()
            ->map(fn (string $part): string => strtoupper(substr(trim($part), 0, 1)))
            ->join('') ?: '?';
    }
}
