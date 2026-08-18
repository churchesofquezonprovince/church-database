<?php

namespace App\Services;

use App\Models\ImmichPersonCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImmichPeopleThumbnailService
{
    /**
     * Cache multiple Immich people.
     *
     * Only people whose Immich updatedAt changed, or who do not
     * have a local thumbnail yet, will cause a download.
     */
    public function syncPeople(array|Collection $people): Collection
    {
        $people = collect($people)
            ->filter(fn ($person) => is_array($person))
            ->filter(fn ($person) => filled($person['id'] ?? null))
            ->values();

        if ($people->isEmpty()) {
            return collect();
        }

        $ids = $people
            ->pluck('id')
            ->unique()
            ->values();

        $cached = ImmichPersonCache::query()
            ->whereIn('immich_person_id', $ids)
            ->get()
            ->keyBy('immich_person_id');

        return $people
            ->map(function (array $person) use ($cached): array {
                $cache = $cached->get($person['id']);

                $cache = $this->syncPerson(
                    person: $person,
                    existingCache: $cache,
                );

                $person['localThumbnailUrl'] =
                    $cache?->thumbnail_path
                        ? Storage::disk('public')->url(
                            $cache->thumbnail_path
                        )
                        : null;

                return $person;
            })
            ->values();
    }

    /**
     * Cache one Immich person's metadata and thumbnail.
     */
    public function syncPerson(
        array $person,
        ?ImmichPersonCache $existingCache = null,
    ): ?ImmichPersonCache {
        $immichPersonId = $person['id'] ?? null;

        if (! $immichPersonId) {
            return $existingCache;
        }

        $immichUpdatedAt = $person['updatedAt'] ?? null;

        $cache = $existingCache ?: ImmichPersonCache::firstOrNew([
            'immich_person_id' => $immichPersonId,
        ]);

        $needsThumbnail = blank($cache->thumbnail_path);

        $immichChanged = false;

        if ($immichUpdatedAt) {
            $newUpdatedAt = \Carbon\CarbonImmutable::parse(
                $immichUpdatedAt
            );

            $immichChanged =
                ! $cache->immich_updated_at
                || $newUpdatedAt->gt($cache->immich_updated_at);
        }

        if (! $needsThumbnail && ! $immichChanged) {
            /*
             * Name may still be populated if the old cache was created
             * before name support.
             */
            if ($cache->immich_name !== ($person['name'] ?? null)) {
                $cache->immich_name = $person['name'] ?? null;
                $cache->save();
            }

            return $cache;
        }

        $path = $cache->thumbnail_path
            ?: 'immich-people/' . $immichPersonId . '.jpg';

        $response = Http::baseUrl(
            rtrim(config('services.immich.url'), '/')
        )
            ->withHeaders([
                'x-api-key' => config('services.immich.api_key'),
                'Accept' => 'image/*',
            ])
            ->timeout(20)
            ->get(
                '/api/people/'
                . rawurlencode($immichPersonId)
                . '/thumbnail'
            );

        if (! $response->successful()) {
            /*
             * Keep an existing cache if Immich is temporarily unavailable.
             */
            if ($cache->exists && filled($cache->thumbnail_path)) {
                return $cache;
            }

            throw new RuntimeException(
                'Unable to download Immich thumbnail for '
                . $immichPersonId
                . '. HTTP '
                . $response->status()
            );
        }

        Storage::disk('public')->put(
            $path,
            $response->body()
        );

        $cache->immich_name = $person['name'] ?? null;
        $cache->immich_updated_at = $immichUpdatedAt
            ? \Carbon\CarbonImmutable::parse($immichUpdatedAt)
            : $cache->immich_updated_at;
        $cache->thumbnail_path = $path;
        $cache->thumbnail_synced_at = now();

        $cache->save();

        return $cache;
    }
}
