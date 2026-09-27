<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Households\HouseholdResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\Household;
use App\Models\Person;
use App\Support\ChurchProfileOptions;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    public array $statusReports = [];

    public array $categoryReports = [];

    public array $shepherdingGroupReports = [];

    public array $qualityReports = [];

    public array $householdReports = [];

    public function mount(): void
    {
        $this->loadReports();
    }

    public function getTitle(): string
    {
        return 'Reports';
    }

    public static function getNavigationLabel(): string
    {
        return 'Reports';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Church Database';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationSort(): ?int
    {
        return 60;
    }

    private function loadReports(): void
    {
        $this->statusReports = collect(ChurchProfileOptions::statuses())
            ->map(fn (string $label, string $status): array => [
                'label' => $label,
                'count' => Person::query()
                    ->whereHas('churchProfile', fn (Builder $query) => $query->where('status', $status))
                    ->count(),
                'url' => $this->peopleTableUrl([
                    'church_status' => $status,
                ]),
            ])
            ->values()
            ->all();

        $this->categoryReports = collect(ChurchProfileOptions::categories())
            ->map(fn (string $label, string $category): array => [
                'label' => $label,
                'count' => Person::query()
                    ->whereHas('churchProfile', fn (Builder $query) => $query->where('category', $category))
                    ->count(),
                'url' => $this->peopleTableUrl([
                    'church_category' => $category,
                ]),
            ])
            ->values()
            ->all();

        $this->shepherdingGroupReports = collect([
            '__none' => 'No Shepherding Group',
            ...ChurchProfileOptions::shepherdingServices(),
        ])
            ->map(fn (string $label, string $group): array => [
                'label' => $label,
                'count' => $this->countByShepherdingGroup($group),
                'url' => $this->peopleTableUrl([
                    'shepherding_group' => $group,
                ]),
            ])
            ->values()
            ->all();

        $this->qualityReports = [
            [
                'label' => 'People Without Shepherd',
                'description' => 'People with no assigned shepherd.',
                'count' => Person::query()
                    ->whereHas('churchProfile', fn (Builder $query) => $query->whereNull('shepherd_id'))
                    ->count(),
                'url' => $this->peopleTableUrl([
                    'shepherd_status' => 'without_shepherd',
                ]),
            ],
            [
                'label' => 'People Without Contact Number',
                'description' => 'People with no encoded contact number.',
                'count' => Person::query()
                    ->where(fn (Builder $query) => $query->whereNull('contact_number')->orWhere('contact_number', ''))
                    ->count(),
                'url' => $this->peopleTableUrl([
                    'missing_data' => 'no_contact',
                ]),
            ],
            [
                'label' => 'People Without Locality',
                'description' => 'People with no locality set.',
                'count' => Person::query()
                    ->whereNull('locality_id')
                    ->count(),
                'url' => $this->peopleTableUrl([
                    'missing_data' => 'no_locality',
                ]),
            ],
            [
                'label' => 'People Without Household',
                'description' => 'People not linked to any household.',
                'count' => Person::query()
                    ->whereNull('household_id')
                    ->count(),
                'url' => $this->peopleTableUrl([
                    'missing_data' => 'no_household',
                ]),
            ],
        ];

        $this->householdReports = [
            [
                'label' => 'Total Households',
                'description' => 'All household records.',
                'count' => Household::query()->count(),
                'url' => HouseholdResource::getUrl('index'),
            ],
            [
                'label' => 'Households Without Head',
                'description' => 'Households with no household head assigned.',
                'count' => Household::query()
                    ->whereNull(
                        'household_head_id'
                    )
                    ->whereNull(
                        'gospel_contact_head_id'
                    )
                    ->count(),
                'url' => $this->householdTableUrl([
                    'missing_data' => 'no_head',
                ]),
            ],
            [
                'label' => 'Households Without Locality',
                'description' => 'Households with no locality set.',
                'count' => Household::query()
                    ->whereNull('locality_id')
                    ->count(),
                'url' => $this->householdTableUrl([
                    'missing_data' => 'no_locality',
                ]),
            ],
        ];
    }

    private function countByShepherdingGroup(string $group): int
    {
        if ($group === '__none') {
            return Person::query()
                ->whereHas(
                    'churchProfile',
                    fn (Builder $query) =>
                        $query
                            ->whereNull('service')
                            ->orWhere('service', '')
                            ->orWhere('service', '[]')
                )
                ->count();
        }

        return Person::query()
            ->whereHas(
                'churchProfile',
                fn (Builder $query) =>
                    $query->whereJsonContains(
                        'service',
                        $group
                    )
            )
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

private function householdTableUrl(array $filters = []): string
    {
        if (empty($filters)) {
            return HouseholdResource::getUrl('index');
        }

        $queryFilters = [];

        foreach ($filters as $filter => $value) {
            $queryFilters[$filter] = [
                'value' => (string) $value,
            ];
        }

        return HouseholdResource::getUrl('index') . '?' . http_build_query([
            'filters' => $queryFilters,
        ]);
    }
}
