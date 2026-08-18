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

    public function people(): array
    {
        return $this->client()
            ->get('/api/people')
            ->throw()
            ->json();
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
