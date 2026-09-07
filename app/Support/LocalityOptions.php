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
    public static function quezonProvince(): array
    {
        return self::primaryProvince();
    }
}
