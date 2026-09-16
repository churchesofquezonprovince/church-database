<?php

namespace App\Support;

use InvalidArgumentException;

class HymnalNetCollectionCatalog
{
    /*
     * Logical Hymnal.net collections shown in the UI.
     *
     * The logical section is intentionally separate
     * from the physical hymn URL route.
     *
     * Example:
     *
     * New Songs
     * ├── /en/hymn/ns/...
     * └── /en/hymn/lb/...
     *
     * Discovery learns physical route families from
     * Hymnal.net's own indexes instead of requiring
     * every route to be hard-coded here.
     */
    public static function sections(): array
    {
        return [
            'classic' => [
                'code' =>
                    'classic',

                'label' =>
                    'Classic Hymns',

                'index_code' =>
                    'h',

                'index_url' =>
                    'https://www.hymnal.net/en/song-index/h/',

                'primary_route' =>
                    'h',

                'language' =>
                    'english',

                'match_strategy' =>
                    'classic',

                /*
                 * Numeric h/... pages belong to
                 * Classic Hymns. Suffixed h/... pages
                 * belong to Alternate Tunes.
                 */
                'discovery_number_pattern' =>
                    '/^[0-9]+$/',

                'canonical_book_source' =>
                    'songbase',

                'canonical_book_source_id' =>
                    '2',

                'canonical_book_name' =>
                    'Hymnal',
            ],

            'alternate_tunes' => [
                'code' =>
                    'alternate_tunes',

                'label' =>
                    'Alternate Tunes',

                /*
                 * Alternate Tunes share the physical
                 * h/... route with Classic Hymns.
                 *
                 * Examples:
                 *
                 * h/10b
                 * h/12b
                 */
                'index_code' =>
                    'h',

                'index_url' =>
                    'https://www.hymnal.net/en/song-index/h/',

                'primary_route' =>
                    'h',

                'language' =>
                    'english',

                /*
                 * Only suffixed Hymnal numbers belong
                 * to this logical collection.
                 */
                'discovery_number_pattern' =>
                    '/^[0-9]+[A-Za-z]+$/',

                /*
                 * Explicitly-known provider identities.
                 * Never generate a suffix sequence.
                 */
                'known_numbers' => [
                    '10b',
                    '12b',
                ],

                /*
                 * Identify the existing Classic
                 * canonical family, then stop for
                 * musician Tune review.
                 */
                'match_strategy' =>
                    'alternate_tune',

                'canonical_book_source' =>
                    'songbase',

                'canonical_book_source_id' =>
                    '2',

                'canonical_book_name' =>
                    'Hymnal',
            ],

            'new_tunes' => [
                'code' =>
                    'new_tunes',

                'label' =>
                    'New Tunes',

                'index_code' =>
                    'nt',

                'index_url' =>
                    'https://www.hymnal.net/en/song-index/nt',

                'primary_route' =>
                    'nt',

                'language' =>
                    'english',

                /*
                 * A New Tune belongs to the existing
                 * canonical Hymn family.
                 *
                 * It must never create a duplicate
                 * canonical Hymn automatically.
                 */
                'match_strategy' =>
                    'new_tune',

                'canonical_book_source' =>
                    'songbase',

                'canonical_book_source_id' =>
                    '2',

                'canonical_book_name' =>
                    'Hymnal',
            ],

            'new_songs' => [
                'code' =>
                    'new_songs',

                'label' =>
                    'New Songs',

                'index_code' =>
                    'ns',

                'index_url' =>
                    'https://www.hymnal.net/en/song-index/ns',

                'primary_route' =>
                    'ns',

                'language' =>
                    'english',

                /*
                 * New Songs may use more than one
                 * physical route (for example ns and
                 * lb). Discovery learns those routes.
                 */
                'match_strategy' =>
                    'title',
            ],

            'children' => [
                'code' =>
                    'children',

                'label' =>
                    "Children's Songs",

                'index_code' =>
                    'c',

                'index_url' =>
                    'https://www.hymnal.net/en/song-index/c',

                'primary_route' =>
                    'c',

                'language' =>
                    'english',

                'match_strategy' =>
                    'title',
            ],
        ];
    }

    public static function section(
        string $section
    ): array {
        $section =
            strtolower(
                trim($section)
            );

        $config =
            self::sections()[$section]
            ?? null;

        if (! $config) {
            throw new InvalidArgumentException(
                'Unsupported Hymnal.net section: '
                . $section
            );
        }

        return $config;
    }

    /*
     * Existing safe sequential catalog.
     *
     * Keep this separate from index discovery. We do
     * not invent sequential ranges for New Tunes,
     * New Songs, Children, or hidden route families.
     */
    public static function all(): array
    {
        return [
            'h' => [
                'code' =>
                    'h',

                'section_code' =>
                    'classic',

                'label' =>
                    'Classic Hymns',

                'language' =>
                    'english',

                'min_number' =>
                    1,

                /*
                 * Known-good sequential boundary.
                 * Sparse/discovered Classic IDs are
                 * synchronized separately.
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
                'Unsupported sequential Hymnal.net '
                . 'collection: '
                . $collection
                . '. Use Hymnal.net discovery for '
                . 'multi-collection imports.'
            );
        }

        return $config;
    }

    /*
     * Physical routes are discovered from Hymnal.net.
     *
     * Do not require every possible route code to be
     * present in all(). This is what allows routes such
     * as lb to be imported when the New Songs index
     * exposes them.
     */
    public static function url(
        string $collection,
        int|string $number
    ): string {
        $collection =
            strtolower(
                trim($collection)
            );

        $number =
            trim(
                (string) $number
            );

        if (
            ! preg_match(
                '/^[a-z0-9_-]+$/',
                $collection
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid Hymnal.net route code.'
            );
        }

        if (
            ! preg_match(
                '/^[A-Za-z0-9._-]+$/',
                $number
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid Hymnal.net hymn number.'
            );
        }

        return
            'https://www.hymnal.net/en/hymn/'
            . $collection
            . '/'
            . $number;
    }
}
