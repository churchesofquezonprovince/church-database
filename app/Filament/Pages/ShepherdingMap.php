<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Households\HouseholdResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\Household;
use App\Models\Person;
use App\Support\ChurchProfileOptions;
use App\Support\LocalityOptions;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ShepherdingMap extends Page
{
    protected string $view = 'filament.pages.shepherding-map';

    public function getTitle(): string
    {
        return 'Shepherding Map';
    }

    public static function getNavigationLabel(): string
    {
        return 'Map';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-map';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function mapPoints(): Collection
    {
        if ($this->selectedNeedsOnly()) {
            return collect();
        }

        $householdPoints = Household::query()
            ->with(['head.churchProfile', 'members.churchProfile'])
            ->orderBy('household_name')
            ->get()
            ->filter(fn (Household $household): bool => $this->householdMatchesFilters($household))
            ->map(fn (Household $household): ?array => $this->householdPoint($household))
            ->filter()
            ->values();

        $individualPoints = Person::query()
            ->with('churchProfile')
            ->whereNull('household_id')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->filter(fn (Person $person): bool => $this->personMatchesFilters($person))
            ->map(fn (Person $person): ?array => $this->individualPoint($person))
            ->filter()
            ->values();

        return $householdPoints
            ->merge($individualPoints)
            ->values();
    }

    public function needsMapLocation(): Collection
    {
        $households = Household::query()
            ->with(['head.churchProfile', 'members.churchProfile'])
            ->orderBy('household_name')
            ->get()
            ->filter(fn (Household $household): bool => $this->householdMatchesFilters($household))
            ->filter(fn (Household $household): bool => $this->householdPoint($household) === null)
            ->map(function (Household $household): array {
                return [
                    'type' => 'Household',
                    'name' => $household->display_name,
                    'locality' => $household->locality ?: 'No locality',
                    'reason' => 'No usable coordinates from household head or members',
                    'url' => HouseholdResource::getUrl('edit', ['record' => $household->id]),
                ];
            });

        $peopleWithoutHousehold = Person::query()
            ->with('churchProfile')
            ->whereNull('household_id')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->filter(fn (Person $person): bool => $this->personMatchesFilters($person))
            ->filter(fn (Person $person): bool => $this->coordinatesFromPerson($person) === null)
            ->map(function (Person $person): array {
                return [
                    'type' => 'Person',
                    'name' => $person->display_name,
                    'locality' => $person->locality ?: 'No locality',
                    'reason' => 'No household and no usable coordinates',
                    'url' => PersonResource::getUrl('edit', ['record' => $person->id]),
                ];
            });

        return $households
            ->merge($peopleWithoutHousehold)
            ->values();
    }

    public function localityOptions(): array
    {
        return LocalityOptions::quezonProvince();
    }

    public function statusOptions(): array
    {
        return ChurchProfileOptions::statuses();
    }

    public function categoryOptions(): array
    {
        return ChurchProfileOptions::categories();
    }

    public function shepherdingGroupOptions(): array
    {
        return ChurchProfileOptions::shepherdingServices();
    }

    public function selectedLocality(): ?string
    {
        return $this->selectedOption('locality', $this->localityOptions());
    }

    public function selectedStatus(): ?string
    {
        return $this->selectedOption('status', $this->statusOptions());
    }

    public function selectedCategory(): ?string
    {
        return $this->selectedOption('category', $this->categoryOptions());
    }

    public function selectedShepherdingGroup(): ?string
    {
        return $this->selectedOption('shepherding_group', $this->shepherdingGroupOptions());
    }

    public function selectedNeedsOnly(): bool
    {
        return request()->query('needs_only') === '1';
    }

    public function hasActiveFilters(): bool
    {
        return filled($this->selectedLocality())
            || filled($this->selectedStatus())
            || filled($this->selectedCategory())
            || filled($this->selectedShepherdingGroup())
            || $this->selectedNeedsOnly();
    }

    public function clearFiltersUrl(): string
    {
        return self::getUrl();
    }

    private function selectedOption(string $queryKey, array $options): ?string
    {
        $value = request()->query($queryKey);

        if (blank($value)) {
            return null;
        }

        $value = (string) $value;

        if (array_key_exists($value, $options) || in_array($value, $options, true)) {
            return $value;
        }

        return null;
    }

    private function householdMatchesFilters(Household $household): bool
    {
        if (filled($this->selectedLocality()) && $household->locality !== $this->selectedLocality()) {
            return false;
        }

        if (! filled($this->selectedStatus()) && ! filled($this->selectedCategory()) && ! filled($this->selectedShepherdingGroup())) {
            return true;
        }

        return $household->members
            ->push($household->head)
            ->filter()
            ->unique('id')
            ->contains(fn (Person $person): bool => $this->personMatchesChurchFilters($person));
    }

    private function personMatchesFilters(Person $person): bool
    {
        if (filled($this->selectedLocality()) && $person->locality !== $this->selectedLocality()) {
            return false;
        }

        return $this->personMatchesChurchFilters($person);
    }

    private function personMatchesChurchFilters(Person $person): bool
    {
        $profile = $person->churchProfile;

        if (filled($this->selectedStatus()) && $profile?->status !== $this->selectedStatus()) {
            return false;
        }

        if (filled($this->selectedCategory()) && $profile?->category !== $this->selectedCategory()) {
            return false;
        }

        if (filled($this->selectedShepherdingGroup()) && ! $this->personHasShepherdingGroup($person, $this->selectedShepherdingGroup())) {
            return false;
        }

        return true;
    }

    private function personHasShepherdingGroup(Person $person, string $group): bool
    {
        $service = $person->churchProfile?->service;

        if (blank($service)) {
            return false;
        }

        if (is_array($service)) {
            $groups = $service;
        } else {
            $decoded = json_decode((string) $service, true);

            $groups = is_array($decoded)
                ? $decoded
                : explode(',', (string) $service);
        }

        return collect($groups)
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->contains(fn (string $value): bool => strtolower($value) === strtolower($group));
    }

    private function householdPoint(Household $household): ?array
    {
        $coordinatePerson = null;
        $coordinates = null;

        if ($household->head) {
            $coordinates = $this->coordinatesFromPerson($household->head);
            $coordinatePerson = $coordinates ? $household->head : null;
        }

        if (! $coordinates) {
            $coordinatePerson = $household->members
                ->first(fn (Person $person): bool => $this->coordinatesFromPerson($person) !== null);

            $coordinates = $coordinatePerson
                ? $this->coordinatesFromPerson($coordinatePerson)
                : null;
        }

        if (! $coordinates) {
            return null;
        }

        $members = $household->members;
        $head = $household->head;

        return [
            'type' => 'household',
            'title' => $household->display_name,
            'subtitle' => 'Household',
            'lat' => $coordinates['lat'],
            'lng' => $coordinates['lng'],
            'locality' => $household->locality ?: 'No locality',
            'head' => $head?->display_name ?: 'No household head',
            'members_count' => $members->count(),
            'coordinate_source' => $coordinatePerson?->display_name,
            'url' => HouseholdResource::getUrl('view', ['record' => $household->id]),
        ];
    }

    private function individualPoint(Person $person): ?array
    {
        $coordinates = $this->coordinatesFromPerson($person);

        if (! $coordinates) {
            return null;
        }

        return [
            'type' => 'person',
            'title' => $person->display_name,
            'subtitle' => 'No household assigned',
            'lat' => $coordinates['lat'],
            'lng' => $coordinates['lng'],
            'locality' => $person->locality ?: 'No locality',
            'head' => null,
            'members_count' => 1,
            'coordinate_source' => $person->display_name,
            'url' => PersonResource::getUrl('view', ['record' => $person->id]),
        ];
    }

    private function coordinatesFromPerson(?Person $person): ?array
    {
        if (! $person || blank($person->geocoordinates)) {
            return null;
        }

        return $this->parseCoordinates($person->geocoordinates);
    }

    private function parseCoordinates(?string $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (! preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $value, $matches)) {
            return null;
        }

        $lat = (float) $matches[1];
        $lng = (float) $matches[2];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return [
            'lat' => $lat,
            'lng' => $lng,
        ];
    }
}
