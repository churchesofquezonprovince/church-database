<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ImmichApiService
{
    protected function client(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.immich.url'), '/');
        $apiKey = (string) config('services.immich.api_key');

        if (blank($baseUrl)) {
            throw new RuntimeException('IMMICH_URL is not configured.');
        }

        if (blank($apiKey)) {
            throw new RuntimeException('IMMICH_API_KEY is not configured.');
        }

        return Http::baseUrl($baseUrl)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(30);
    }

    public function ping(): bool
    {
        return $this->client()
            ->get('/api/server/ping')
            ->successful();
    }

    public function albums(?string $name = null): array
    {
        return $this->client()
            ->get('/api/albums', array_filter([
                'name' => $name,
            ]))
            ->throw()
            ->json();
    }

    public function album(string $albumId): array
    {
        return $this->client()
            ->get("/api/albums/{$albumId}")
            ->throw()
            ->json();
    }

public function people(
    int $page = 1,
    int $size = 500,
    bool $withHidden = false,
): array {
    $response = $this->client()
        ->get('/api/people', [
            'page' => $page,
            'size' => $size,
            'withHidden' => $withHidden ? 'true' : 'false',
        ])
        ->throw()
        ->json();

    return [
        'people' => array_values(
            array_filter(
                $response['people'] ?? [],
                fn ($person): bool =>
                    is_array($person)
                    && filled($person['id'] ?? null)
            )
        ),
        'hasNextPage' => (bool) ($response['hasNextPage'] ?? false),
        'total' => (int) ($response['total'] ?? 0),
        'hidden' => (int) ($response['hidden'] ?? 0),
    ];
}

public function allPeople(
    bool $withHidden = false,
    int $size = 500,
): array {
    $allPeople = [];
    $page = 1;

    do {
        $response = $this->people(
            page: $page,
            size: $size,
            withHidden: $withHidden,
        );

        $allPeople = array_merge(
            $allPeople,
            $response['people'],
        );

        if (! $response['hasNextPage']) {
            break;
        }

        $page++;
    } while ($page <= 100);

    return $allPeople;
}

public function searchAlbumAssetsForDate(
    string $albumId,
    string $date,
    int $page = 1,
    int $size = 1000,
    ?\Carbon\CarbonInterface $updatedAfter = null,
): array {
    /*
     * Immich's server-side takenAfter/takenBefore filtering can produce
     * incorrect results when an asset's localDateTime timezone metadata
     * differs from the application's timezone.
     *
     * Fetch the album assets normally, then filter locally using the
     * date portion of Immich's localDateTime value.
     */
    $response = $this->searchAlbumAssets(
        albumId: $albumId,
        page: $page,
        size: $size,
        updatedAfter: $updatedAfter?->toIso8601String(),
    );

    $items = collect(
        data_get($response, 'assets.items', [])
    )->filter(function ($asset) use ($date): bool {
        if (! is_array($asset)) {
            return false;
        }

        $localDateTime = $asset['localDateTime'] ?? null;

        if (! filled($localDateTime)) {
            return false;
        }

        /*
         * Compare the date portion directly.
         *
         * Example:
         * 2026-07-29T19:54:05.000Z
         *              ↓
         * 2026-07-29
         */
        return str_starts_with(
            (string) $localDateTime,
            $date,
        );
    })->values();

    return [
        'assets' => [
            ...($response['assets'] ?? []),
            'items' => $items->all(),
        ],
    ];
}

public function peopleFromAlbum(
    string $albumId,
): array {
    $people = [];

    $page = 1;

    do {
        $response = $this->client()
            ->post('/api/search/metadata', [
                'albumIds' => [$albumId],
                'page' => $page,
                'size' => 1000,
                'withPeople' => true,
                'withExif' => false,
                'withDeleted' => false,
                'withStacked' => false,
            ])
            ->throw()
            ->json();

        $assets = collect(
            data_get($response, 'assets.items', [])
        );

        foreach ($assets as $asset) {
            foreach (($asset['people'] ?? []) as $person) {
                if (! is_array($person)) {
                    continue;
                }

                $id = $person['id'] ?? null;

                if (! $id) {
                    continue;
                }

                $people[$id] = [
                    'id' => $id,
                    'name' => trim(
                        (string) ($person['name'] ?? '')
                    ),
                    'thumbnailPath' =>
                        $person['thumbnailPath'] ?? null,
                ];
            }
        }

        $nextPage = data_get(
            $response,
            'assets.nextPage'
        );

        $page++;

    } while ($nextPage !== null);

    return array_values($people);
}

public function peopleFromAlbumForDate(
    string $albumId,
    string $date,
    int $page = 1,
    int $size = 1000,
    ?\Carbon\CarbonInterface $updatedAfter = null,
): array {
    $response = $this->searchAlbumAssetsForDate(
        albumId: $albumId,
        date: $date,
        page: $page,
        size: $size,
        updatedAfter: $updatedAfter,
    );

    $people = [];

    foreach (data_get($response, 'assets.items', []) as $asset) {
        foreach (($asset['people'] ?? []) as $person) {
            if (! is_array($person)) {
                continue;
            }

            $id = $person['id'] ?? null;

            if (! $id) {
                continue;
            }

            $people[$id] = [
                'id' => $id,
                'name' => trim(
                    (string) ($person['name'] ?? '')
                ),
                'thumbnailPath' =>
                    $person['thumbnailPath'] ?? null,
            ];
        }
    }

    return array_values($people);
}

    public function searchAlbumAssets(
        string $albumId,
        int $page = 1,
        int $size = 1000,
        ?string $updatedAfter = null,
    ): array {
        $payload = [
            'albumIds' => [$albumId],
            'page' => $page,
            'size' => $size,
            'withPeople' => true,
            'withExif' => false,
            'withDeleted' => false,
            'withStacked' => false,
        ];

        if ($updatedAfter) {
            $payload['updatedAfter'] = $updatedAfter;
        }

        return $this->client()
            ->post('/api/search/metadata', $payload)
            ->throw()
            ->json();
    }
}
