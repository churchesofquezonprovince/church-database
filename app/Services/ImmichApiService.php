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
): array {
    $timezone = config('app.timezone', 'Asia/Manila');

    $start = \Carbon\CarbonImmutable::parse(
        $date,
        $timezone,
    )->startOfDay();

    $end = $start->endOfDay();

    return $this->client()
        ->post('/api/search/metadata', [
            'albumIds' => [$albumId],

            /*
             * Restrict the search to the Attendance Session's day.
             */
            'takenAfter' => $start->toIso8601String(),
            'takenBefore' => $end->toIso8601String(),

            'page' => $page,
            'size' => $size,

            'withPeople' => true,
            'withExif' => false,
            'withDeleted' => false,
            'withStacked' => false,
        ])
        ->throw()
        ->json();
}



public function peopleFromAlbumForDate(
    string $albumId,
    string $date,
): array {
    $people = [];

    $page = 1;

    do {
        $response = $this->searchAlbumAssetsForDate(
            albumId: $albumId,
            date: $date,
            page: $page,
            size: 1000,
        );

        $items = data_get($response, 'assets.items', []);

        foreach ($items as $asset) {
            foreach (($asset['people'] ?? []) as $person) {
                if (! is_array($person)) {
                    continue;
                }

                $personId = $person['id'] ?? null;

                if (! $personId) {
                    continue;
                }

                $people[$personId] = [
                    'id' => $personId,
                    'name' => trim((string) ($person['name'] ?? '')),
                    'thumbnailPath' => $person['thumbnailPath'] ?? null,
                    'updatedAt' => $person['updatedAt'] ?? null,
                ];
            }
        }

        $nextPage = data_get($response, 'assets.nextPage');

        if (! $nextPage) {
            break;
        }

        $page = (int) $nextPage;
    } while ($page <= 100);

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
