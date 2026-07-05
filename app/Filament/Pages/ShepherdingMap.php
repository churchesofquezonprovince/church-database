<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Households\HouseholdResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\Household;
use App\Models\Person;
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
        $householdPoints = Household::query()
            ->with(['head', 'members'])
            ->orderBy('household_name')
            ->get()
            ->map(fn (Household $household): ?array => $this->householdPoint($household))
            ->filter()
            ->values();

        $individualPoints = Person::query()
            ->whereNull('household_id')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
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
            ->with(['head', 'members'])
            ->orderBy('household_name')
            ->get()
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
            ->whereNull('household_id')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
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
