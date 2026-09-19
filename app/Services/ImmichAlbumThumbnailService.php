<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImmichAlbumThumbnailService
{
    /**
     * Cache album thumbnails locally.
     *
     * This follows the same pattern used by
     * ImmichPeopleThumbnailService:
     *
     * Immich API -> Laravel -> local public storage -> browser.
     *
     * The Immich API key never reaches the browser.
     */
    public function syncAlbums(
        array|Collection $albums
    ): Collection {
        return collect($albums)
            ->map(
                function (
                    array $album
                ): array {
                    try {
                        return $this->syncAlbum(
                            $album
                        );
                    } catch (Throwable $e) {
                        Log::warning(
                            'Unable to cache Immich album thumbnail.',
                            [
                                'immich_album_id' =>
                                    $album['id']
                                    ?? null,

                                'thumbnail_asset_id' =>
                                    $album[
                                        'albumThumbnailAssetId'
                                    ]
                                    ?? null,

                                'message' =>
                                    $e->getMessage(),
                            ]
                        );

                        $album[
                            'localThumbnailUrl'
                        ] = null;

                        return $album;
                    }
                }
            )
            ->values();
    }

    protected function syncAlbum(
        array $album
    ): array {
        $albumId =
            trim(
                (string) (
                    $album['id']
                    ?? ''
                )
            );

        $assetId =
            trim(
                (string) (
                    $album[
                        'albumThumbnailAssetId'
                    ]
                    ?? ''
                )
            );

        if (
            $albumId === ''
            || $assetId === ''
        ) {
            $album[
                'localThumbnailUrl'
            ] = null;

            return $album;
        }

        /*
         * Include BOTH Album ID and current thumbnail Asset ID.
         *
         * If Immich changes the album cover, the Asset ID changes
         * and the next Refresh automatically downloads the new image.
         */
        $directory =
            'immich-albums/'
            . $albumId;

        $path =
            $directory
            . '/'
            . $assetId
            . '.webp';

        $disk =
            Storage::disk(
                'public'
            );

        /*
         * Do not redownload an unchanged album thumbnail.
         */
        if (! $disk->exists($path)) {
            $response =
                Http::baseUrl(
                    rtrim(
                        (string)
                        config(
                            'services.immich.url'
                        ),
                        '/'
                    )
                )
                    ->withHeaders([
                        'x-api-key' =>
                            config(
                                'services.immich.api_key'
                            ),

                        'Accept' =>
                            'image/*',
                    ])
                    ->timeout(20)
                    ->get(
                        '/api/assets/'
                        . rawurlencode(
                            $assetId
                        )
                        . '/thumbnail',
                        [
                            'size' =>
                                'thumbnail',
                        ]
                    );

            if (! $response->successful()) {
                throw new \RuntimeException(
                    'Immich thumbnail request failed with HTTP '
                    . $response->status()
                    . '.'
                );
            }

            $contentType =
                strtolower(
                    (string)
                    $response->header(
                        'Content-Type'
                    )
                );

            if (
                ! str_starts_with(
                    $contentType,
                    'image/'
                )
            ) {
                throw new \RuntimeException(
                    'Immich thumbnail response was not an image.'
                );
            }

            $disk->put(
                $path,
                $response->body()
            );

            /*
             * The new thumbnail is safely cached.
             *
             * Delete older cached covers for this same album so
             * changing an album cover does not accumulate files.
             */
            foreach (
                $disk->files(
                    $directory
                )
                as $existingPath
            ) {
                if (
                    $existingPath
                    === $path
                ) {
                    continue;
                }

                $disk->delete(
                    $existingPath
                );
            }
        }

        $album[
            'localThumbnailUrl'
        ] =
            $disk->url(
                $path
            );

        return $album;
    }
}
