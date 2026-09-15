<?php

namespace App\Support;

use InvalidArgumentException;

class HymnalNetCollectionCatalog
{
    public static function all(): array
    {
        return [
            'h' => [
                'code' =>
                    'h',

                'label' =>
                    'Classic Hymnal',

                'language' =>
                    'english',

                'min_number' =>
                    1,

                /*
                 * Known-good Hymnal.net boundary.
                 *
                 * h/1361 currently returns corrupted /
                 * mismatched page content, so automatic
                 * synchronization MUST stop at 1360.
                 */
                'max_number' =>
                    1360,

                'canonical_book_source' =>
                    'songbase',

                'canonical_book_source_id' =>
                    '2',

                'canonical_book_name' =>
                    'Hymnal',
            ],
        ];
    }

    public static function get(
        string $collection
    ): array {
        $collection =
            strtolower(
                trim($collection)
            );

        $config =
            self::all()[$collection]
            ?? null;

        if (! $config) {
            throw new InvalidArgumentException(
                'Unsupported Hymnal.net '
                . 'collection: '
                . $collection
            );
        }

        return $config;
    }

    public static function url(
        string $collection,
        int|string $number
    ): string {
        $config =
            self::get($collection);

        return
            'https://www.hymnal.net/en/hymn/'
            . $config['code']
            . '/'
            . $number;
    }
}
