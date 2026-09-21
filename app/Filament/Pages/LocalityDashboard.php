<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Households\HouseholdResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\Household;
use App\Models\Locality;
use App\Models\Person;
use App\Models\ProvinceSetting;
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
        return 40;
    }

    private function loadDashboard(): void
    {
        $provinceId = ProvinceSetting::query()
            ->value('primary_province_id');

        if (! $provinceId) {
            $this->summary = [
                'total_people' => 0,
                'total_households' => 0,
                'total_localities' => 0,
                'people_without_locality' => Person::query()
                    ->whereNull('locality_id')
                    ->count(),
                'households_without_locality' => Household::query()
                    ->whereNull('locality_id')
                    ->count(),
            ];

            $this->localities = [];

            return;
        }

        $localities = Locality::query()
            ->where('province_id', $provinceId)
            ->where('is_active', true)
            ->withCount('people')
            ->orderBy('name')
            ->get();

        $localityIds = $localities->pluck('id');

        $householdsByLocality = Household::query()
            ->whereIn('locality_id', $localityIds)
            ->selectRaw('locality_id, COUNT(*) as total')
            ->groupBy('locality_id')
            ->pluck('total', 'locality_id');

        $rows = $localities
            ->map(function (Locality $locality) use ($householdsByLocality): array {
                $peopleCount = (int) $locality->people_count;
                $householdCount = (int) (
                    $householdsByLocality[$locality->id] ?? 0
                );

                return [
                    'id' => $locality->id,
                    'name' => $locality->name,
                    'people_count' => $peopleCount,
                    'household_count' => $householdCount,
                    'total_count' => $peopleCount + $householdCount,
                    'people_url' => $this->peopleUrl($locality->id),
                    'households_url' => $this->householdsUrl($locality->id),
                ];
            })
            ->filter(
                fn (array $row): bool => $row['total_count'] > 0
            )
            ->sortByDesc('total_count')
            ->values();

        $this->summary = [
            'total_people' => (int) $localities->sum('people_count'),
            'total_households' => (int) $householdsByLocality->sum(),
            'total_localities' => $rows->count(),
            'people_without_locality' => Person::query()
                ->whereNull('locality_id')
                ->count(),
            'households_without_locality' => Household::query()
                ->whereNull('locality_id')
                ->count(),
        ];

        $this->localities = $rows->all();
    }

    private function peopleUrl(int $localityId): string
    {
        return PersonResource::getUrl('index') . '?' . http_build_query([
            'filters' => [
                'locality_id' => [
                    'value' => $localityId,
                ],
            ],
        ]);
    }

    private function householdsUrl(int $localityId): string
    {
        return HouseholdResource::getUrl('index') . '?' . http_build_query([
            'filters' => [
                'locality_id' => [
                    'value' => $localityId,
                ],
            ],
        ]);
    }

}
