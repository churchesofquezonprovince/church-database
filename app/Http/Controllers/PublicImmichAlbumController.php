<?php

namespace App\Http\Controllers;

use App\Services\ImmichAlbumThumbnailService;
use App\Services\ImmichApiService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PublicImmichAlbumController extends Controller
{
    private const CACHE_KEY = 'public_immich_albums_v1';

    public function __invoke(
        ImmichApiService $immich,
        ImmichAlbumThumbnailService $thumbnails,
    ): View {
        $error = null;

        try {
            $albums = $this->loadAlbums(
                $immich,
                $thumbnails,
            );

            Cache::put(
                self::CACHE_KEY,
                $albums,
                now()->addMinutes(5),
            );
        } catch (Throwable $e) {
            Log::warning(
                'Unable to refresh public Immich Albums dashboard.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            $albums = Cache::get(
                self::CACHE_KEY,
                []
            );

            if (empty($albums)) {
                $error =
                    'Photo albums are temporarily unavailable.';
            }
        }

        return view(
            'immich-albums.public-dashboard',
            [
                'albums' => $albums,
                'error' => $error,
            ]
        );
    }

    private function loadAlbums(
        ImmichApiService $immich,
        ImmichAlbumThumbnailService $thumbnails,
    ): array {
        $sharedLinks = collect(
            $immich->sharedLinks()
        )
            ->filter(
                fn ($link): bool =>
                    $this->isUsableAlbumLink(
                        $link
                    )
            )
            ->groupBy(
                fn (array $link): string =>
                    (string) data_get(
                        $link,
                        'album.id'
                    )
            )
            ->map(
                function (
                    Collection $links
                ): array {
                    return $links
                        ->sort(
                            function (
                                array $a,
                                array $b
                            ): int {
                                $aSlug =
                                    filled(
                                        $a['slug']
                                        ?? null
                                    )
                                        ? 1
                                        : 0;

                                $bSlug =
                                    filled(
                                        $b['slug']
                                        ?? null
                                    )
                                        ? 1
                                        : 0;

                                if ($aSlug !== $bSlug) {
                                    return $bSlug
                                        <=>
                                        $aSlug;
                                }

                                return strcmp(
                                    (string) (
                                        $b['createdAt']
                                        ?? ''
                                    ),
                                    (string) (
                                        $a['createdAt']
                                        ?? ''
                                    )
                                );
                            }
                        )
                        ->first();
                }
            );

        $albums = collect(
            $immich->albums()
        )
            ->filter(
                fn ($album): bool =>
                    is_array($album)
            )
            ->map(
                function (
                    array $album
                ) use (
                    $sharedLinks
                ): ?array {
                    $albumId = trim(
                        (string) (
                            $album['id']
                            ?? ''
                        )
                    );

                    if ($albumId === '') {
                        return null;
                    }

                    $link =
                        $sharedLinks->get(
                            $albumId
                        );

                    if (! is_array($link)) {
                        return null;
                    }

                    $sharedUrl =
                        $this->sharedLinkUrl(
                            $link
                        );

                    if (blank($sharedUrl)) {
                        return null;
                    }

                    return [
                        'id' => $albumId,

                        'albumName' =>
                            (string) (
                                $album['albumName']
                                ?? 'Untitled Album'
                            ),

                        'description' =>
                            (string) (
                                $album['description']
                                ?? ''
                            ),

                        'albumThumbnailAssetId' =>
                            $album[
                                'albumThumbnailAssetId'
                            ]
                            ?? null,

                        'assetCount' =>
                            (int) (
                                $album['assetCount']
                                ?? 0
                            ),

                        'createdAt' =>
                            $album['createdAt']
                            ?? null,

                        'updatedAt' =>
                            $album['updatedAt']
                            ?? null,

                        'startDate' =>
                            $album['startDate']
                            ?? null,

                        'endDate' =>
                            $album['endDate']
                            ?? null,

                        'lastModifiedAssetTimestamp' =>
                            $album[
                                'lastModifiedAssetTimestamp'
                            ]
                            ?? null,

                        'sharedUrl' =>
                            $sharedUrl,
                    ];
                }
            )
            ->filter()
            ->sortByDesc(
                fn (array $album): string =>
                    (string) (
                        $album[
                            'lastModifiedAssetTimestamp'
                        ]
                        ?? $album['updatedAt']
                        ?? ''
                    )
            )
            ->values();

        /*
         * Reuse the same local thumbnail cache used by the
         * authenticated Immich Albums page.
         */
        return $thumbnails
            ->syncAlbums($albums)
            ->map(
                fn (array $album): array => [
                    'albumName' =>
                        $album['albumName'],

                    'description' =>
                        $album['description'],

                    'assetCount' =>
                        $album['assetCount'],

                    'createdAt' =>
                        $album['createdAt'],

                    'updatedAt' =>
                        $album['updatedAt'],

                    'startDate' =>
                        $album['startDate'],

                    'endDate' =>
                        $album['endDate'],

                    'lastModifiedAssetTimestamp' =>
                        $album[
                            'lastModifiedAssetTimestamp'
                        ],

                    'sharedUrl' =>
                        $album['sharedUrl'],

                    'localThumbnailUrl' =>
                        $album[
                            'localThumbnailUrl'
                        ]
                        ?? null,
                ]
            )
            ->values()
            ->all();
    }

    private function isUsableAlbumLink(
        mixed $link
    ): bool {
        if (! is_array($link)) {
            return false;
        }

        if (
            strtoupper(
                (string) (
                    $link['type']
                    ?? ''
                )
            )
            !== 'ALBUM'
        ) {
            return false;
        }

        if (
            blank(
                data_get(
                    $link,
                    'album.id'
                )
            )
        ) {
            return false;
        }

        $expiresAt =
            $link['expiresAt']
            ?? null;

        if (blank($expiresAt)) {
            return true;
        }

        try {
            return CarbonImmutable::parse(
                $expiresAt
            )->isFuture();
        } catch (Throwable) {
            return false;
        }
    }

    private function sharedLinkUrl(
        array $link
    ): ?string {
        $baseUrl = rtrim(
            (string) (
                config(
                    'services.immich.public_url'
                )
                ?: config(
                    'services.immich.url'
                )
            ),
            '/'
        );

        $slug = trim(
            (string) (
                $link['slug']
                ?? ''
            )
        );

        if ($slug !== '') {
            return $baseUrl
                . '/s/'
                . rawurlencode(
                    $slug
                );
        }

        $key = trim(
            (string) (
                $link['key']
                ?? ''
            )
        );

        if ($key === '') {
            return null;
        }

        return $baseUrl
            . '/share/'
            . rawurlencode(
                $key
            );
    }
}
