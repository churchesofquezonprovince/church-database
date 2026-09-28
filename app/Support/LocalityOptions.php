<?php

namespace App\Support;

use App\Models\Locality;
use App\Models\ProvinceSetting;
use Illuminate\Support\Collection;

class LocalityOptions
{
    /**
     * Active Localities belonging to the configured Primary Province.
     *
     * Format:
     * [
     *     'Lucban' => 'Lucban',
     *     'Lucena City' => 'Lucena City',
     * ]
     */
    public static function primaryProvince(): array
    {
        return self::primaryProvinceNames()
            ->mapWithKeys(
                fn (string $locality): array => [
                    $locality => $locality,
                ]
            )
            ->all();
    }

    /**
     * Active Primary Province Locality names.
     */
    public static function primaryProvinceNames(): Collection
    {
        $provinceId = ProvinceSetting::query()
            ->value('primary_province_id');

        if (! $provinceId) {
            return collect();
        }

        return Locality::query()
            ->where('province_id', $provinceId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name');
    }

    public static function primaryProvinceNamesWithPeople(): Collection
    {
        $provinceId = ProvinceSetting::query()
            ->value('primary_province_id');

        if (! $provinceId) {
            return collect();
        }

        return Locality::query()
            ->where('province_id', $provinceId)
            ->where('is_active', true)
            ->whereHas('people')
            ->orderBy('name')
            ->pluck('name');
    }

    public static function primaryProvinceLocality(?string $name): ?Locality
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $provinceId = ProvinceSetting::query()
            ->value('primary_province_id');

        if (! $provinceId) {
            return null;
        }

        return Locality::query()
            ->where('province_id', $provinceId)
            ->where('is_active', true)
            ->whereRaw(
                'LOWER(name) = ?',
                [mb_strtolower($name)]
            )
            ->first();
    }

    /**
     * Compatibility alias while older screens are migrated.
     */
    public static function groupedActiveConfigured(): array
    {
        $settings = ProvinceSetting::query()
            ->with('primaryProvince')
            ->first();

        $primaryProvinceId = $settings?->primary_province_id;

        $localities = Locality::query()
            ->with('province')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $groups = [];

        if ($primaryProvinceId) {
            $primary = $localities
                ->where('province_id', $primaryProvinceId)
                ->sortBy(fn (Locality $locality) => mb_strtolower($locality->name));

            if ($primary->isNotEmpty()) {
                $groups[
                    $settings?->primaryProvince?->name ?? 'Primary Province'
                ] = $primary
                    ->mapWithKeys(
                        fn (Locality $locality): array => [
                            $locality->id => $locality->name,
                        ]
                    )
                    ->all();
            }
        }

        $outsideGroups = $localities
            ->reject(
                fn (Locality $locality): bool =>
                    $primaryProvinceId
                    && (int) $locality->province_id === (int) $primaryProvinceId
            )
            ->groupBy('province_id')
            ->sortBy(
                fn (Collection $items): string =>
                    mb_strtolower(
                        (string) ($items->first()?->province?->name ?? '')
                    )
            );

        foreach ($outsideGroups as $items) {
            $provinceName =
                $items->first()?->province?->name ?? 'Other Province';

            $label = $primaryProvinceId
                ? 'Outside — ' . $provinceName
                : $provinceName;

            $groups[$label] = $items
                ->sortBy(fn (Locality $locality) => mb_strtolower($locality->name))
                ->mapWithKeys(
                    fn (Locality $locality): array => [
                        $locality->id => $locality->name,
                    ]
                )
                ->all();
        }

        return $groups;
    }

    public static function activeConfiguredLocalityByName(
        ?string $name
    ): ?Locality {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return Locality::query()
            ->where('is_active', true)
            ->whereRaw(
                'LOWER(name) = ?',
                [
                    mb_strtolower($name),
                ]
            )
            ->first();
    }

    public static function activeConfiguredLocality(?int $id): ?Locality
    {
        if (! $id) {
            return null;
        }

        return Locality::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->first();
    }

    public static function quezonProvince(): array
    {
        return self::primaryProvince();
    }
}
