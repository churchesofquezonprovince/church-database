<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Households\HouseholdResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\Household;
use App\Models\Person;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class LocalityDashboard extends Page
{
    protected string $view = 'filament.pages.locality-dashboard';

    public array $localities = [];

    public array $summary = [];

    public function mount(): void
    {
        $this->loadDashboard();
    }

    public function getTitle(): string
    {
        return 'Locality Dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return 'Locality Dashboard';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Church Database';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-map';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    private function loadDashboard(): void
    {
        $peopleByLocality = Person::query()
            ->selectRaw("COALESCE(NULLIF(locality, ''), 'No Locality') as locality_name, COUNT(*) as total")
            ->groupBy('locality_name')
            ->orderBy('locality_name')
            ->pluck('total', 'locality_name');

        $householdsByLocality = Household::query()
            ->selectRaw("COALESCE(NULLIF(locality, ''), 'No Locality') as locality_name, COUNT(*) as total")
            ->groupBy('locality_name')
            ->orderBy('locality_name')
            ->pluck('total', 'locality_name');

        $allLocalities = collect()
            ->merge($peopleByLocality->keys())
            ->merge($householdsByLocality->keys())
            ->unique()
            ->sort()
            ->values();

        $this->summary = [
            'total_people' => Person::query()->count(),
            'total_households' => Household::query()->count(),
            'total_localities' => $allLocalities->reject(fn (string $locality): bool => $locality === 'No Locality')->count(),
            'people_without_locality' => Person::query()
                ->where(fn ($query) => $query->whereNull('locality')->orWhere('locality', ''))
                ->count(),
            'households_without_locality' => Household::query()
                ->where(fn ($query) => $query->whereNull('locality')->orWhere('locality', ''))
                ->count(),
        ];

        $this->localities = $allLocalities
            ->map(function (string $locality) use ($peopleByLocality, $householdsByLocality): array {
                $peopleCount = (int) ($peopleByLocality[$locality] ?? 0);
                $householdCount = (int) ($householdsByLocality[$locality] ?? 0);

                return [
                    'name' => $locality,
                    'people_count' => $peopleCount,
                    'household_count' => $householdCount,
                    'total_count' => $peopleCount + $householdCount,
                    'people_url' => $this->peopleUrl($locality),
                    'households_url' => $this->householdsUrl($locality),
                ];
            })
            ->sortByDesc('total_count')
            ->values()
            ->all();
    }

    private function peopleUrl(string $locality): string
    {
        if ($locality === 'No Locality') {
            return PersonResource::getUrl('index');
        }

        return PersonResource::getUrl('index') . '?' . http_build_query([
            'filters' => [
                'locality' => [
                    'value' => $locality,
                ],
            ],
        ]);
    }

    private function householdsUrl(string $locality): string
    {
        if ($locality === 'No Locality') {
            return HouseholdResource::getUrl('index');
        }

        return HouseholdResource::getUrl('index') . '?' . http_build_query([
            'filters' => [
                'locality' => [
                    'value' => $locality,
                ],
            ],
        ]);
    }
}
