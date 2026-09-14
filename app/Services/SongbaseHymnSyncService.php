<?php

namespace App\Services;

use App\Support\HymnLyricsNormalizer;
use App\Models\Hymn;
use App\Models\HymnBook;
use App\Models\HymnSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SongbaseHymnSyncService
{
    private const SOURCE = 'songbase';

    private const API_URL =
        'https://songbase.life/api/v2/app_data';

    public function fullSync(): array
    {
        $response = Http::acceptJson()
            ->withHeaders([
                'User-Agent' =>
                    'CoQP-Hymn-Catalog/1.0',
            ])
            ->timeout(120)
            ->retry(3, 1000)
            ->get(self::API_URL)
            ->throw();

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException(
                'Songbase returned an invalid response.'
            );
        }

        $songs = $data['songs'] ?? [];
        $books = $data['books'] ?? [];

        if (
            ! is_array($songs)
            || $songs === []
        ) {
            throw new RuntimeException(
                'Songbase returned no songs. Sync cancelled.'
            );
        }

        if (! is_array($books)) {
            throw new RuntimeException(
                'Songbase returned invalid book data.'
            );
        }

        $reportedSongCount =
            isset($data['songCount'])
                ? (int) $data['songCount']
                : null;

        if (
            $reportedSongCount !== null
            && $reportedSongCount
                !== count($songs)
        ) {
            throw new RuntimeException(
                'Songbase songCount does not match '
                . 'the returned song list.'
            );
        }

        $syncedAt = now();

        $songRows = [];
        $songLanguageBySourceId = [];
        $skippedSongs = 0;

        foreach ($songs as $song) {
            $sourceId =
                trim(
                    (string) ($song['id'] ?? '')
                );

            $title =
                trim(
                    (string) ($song['title'] ?? '')
                );

            if (
                $sourceId === ''
                || $title === ''
            ) {
                $skippedSongs++;

                continue;
            }

            $language =
                trim(
                    (string) ($song['lang'] ?? '')
                );

            $language =
                $language !== ''
                    ? $language
                    : null;

            $songLanguageBySourceId[
                $sourceId
            ] = $language;

            $songRows[] = [
                'source' =>
                    self::SOURCE,

                'source_id' =>
                    $sourceId,

                'title' =>
                    $title,

                'language' =>
                    $language,

                'lyrics' =>
                    filled(
                        $song['lyrics'] ?? null
                    )
                        ? (string) $song['lyrics']
                        : null,

                'lyrics_search' =>
                    HymnLyricsNormalizer::forSearch(
                        filled(
                            $song['lyrics'] ?? null
                        )
                            ? (string) $song['lyrics']
                            : null
                    ),

                'source_url' =>
                    'https://songbase.life/'
                    . $sourceId,

                'is_active' =>
                    true,

                'last_synced_at' =>
                    $syncedAt,

                'created_at' =>
                    $syncedAt,

                'updated_at' =>
                    $syncedAt,
            ];
        }

        $result = DB::transaction(
            function () use (
                $songRows,
                $books,
                $songLanguageBySourceId,
                $syncedAt,
                $skippedSongs
            ): array {
                /*
                 * Anything missing from a full Songbase
                 * refresh becomes archived rather than
                 * deleted so historical references survive.
                 */
                Hymn::query()
                    ->where(
                        'source',
                        self::SOURCE
                    )
                    ->update([
                        'is_active' => false,
                    ]);

                foreach (
                    array_chunk(
                        $songRows,
                        500
                    ) as $chunk
                ) {
                    DB::table('hymns')
                        ->upsert(
                            $chunk,
                            [
                                'source',
                                'source_id',
                            ],
                            [
                                'title',
                                'language',
                                'lyrics',
                                'lyrics_search',
                                'source_url',
                                'is_active',
                                'last_synced_at',
                                'updated_at',
                            ]
                        );
                }

                $songIds =
                    Hymn::query()
                        ->where(
                            'source',
                            self::SOURCE
                        )
                        ->pluck(
                            'id',
                            'source_id'
                        )
                        ->all();

                /*
                 * Keep the canonical multi-source table
                 * synchronized with every Songbase song.
                 *
                 * Existing Songbase sources are updated,
                 * while newly-added Songbase songs receive
                 * their hymn_sources row automatically.
                 */
                $sourceRows = [];

                foreach ($songRows as $songRow) {
                    $sourceId =
                        (string) $songRow[
                            'source_id'
                        ];

                    $hymnId =
                        $songIds[
                            $sourceId
                        ]
                        ?? null;

                    if (! $hymnId) {
                        continue;
                    }

                    $sourceRows[] = [
                        'hymn_id' =>
                            $hymnId,

                        'provider' =>
                            HymnSource::PROVIDER_SONGBASE,

                        'source_type' =>
                            HymnSource::TYPE_CATALOG,

                        'external_id' =>
                            $sourceId,

                        'source_url' =>
                            $songRow[
                                'source_url'
                            ],

                        'label' =>
                            'Songbase',

                        'metadata' =>
                            null,

                        'created_at' =>
                            $syncedAt,

                        'updated_at' =>
                            $syncedAt,
                    ];
                }

                foreach (
                    array_chunk(
                        $sourceRows,
                        500
                    ) as $chunk
                ) {
                    DB::table(
                        'hymn_sources'
                    )->upsert(
                        $chunk,
                        [
                            'hymn_id',
                            'provider',
                            'external_id',
                        ],
                        [
                            'source_type',
                            'source_url',
                            'label',
                            'updated_at',
                        ]
                    );
                }

                HymnBook::query()
                    ->where(
                        'source',
                        self::SOURCE
                    )
                    ->update([
                        'is_active' => false,
                    ]);

                $bookRows = [];

                foreach ($books as $book) {
                    $sourceId =
                        trim(
                            (string)
                                ($book['id'] ?? '')
                        );

                    $name =
                        trim(
                            (string)
                                ($book['name'] ?? '')
                        );

                    if (
                        $sourceId === ''
                        || $name === ''
                    ) {
                        continue;
                    }

                    $bookRows[] = [
                        'source' =>
                            self::SOURCE,

                        'source_id' =>
                            $sourceId,

                        'name' =>
                            $name,

                        'slug' =>
                            filled(
                                $book['slug']
                                    ?? null
                            )
                                ? trim(
                                    (string)
                                        $book['slug']
                                )
                                : null,

                        'language' =>
                            $this
                                ->bookLanguage(
                                    $book,
                                    $songLanguageBySourceId
                                ),

                        'is_active' =>
                            true,

                        'last_synced_at' =>
                            $syncedAt,

                        'created_at' =>
                            $syncedAt,

                        'updated_at' =>
                            $syncedAt,
                    ];
                }

                foreach (
                    array_chunk(
                        $bookRows,
                        100
                    ) as $chunk
                ) {
                    DB::table('hymn_books')
                        ->upsert(
                            $chunk,
                            [
                                'source',
                                'source_id',
                            ],
                            [
                                'name',
                                'slug',
                                'language',
                                'is_active',
                                'last_synced_at',
                                'updated_at',
                            ]
                        );
                }

                $bookIds =
                    HymnBook::query()
                        ->where(
                            'source',
                            self::SOURCE
                        )
                        ->pluck(
                            'id',
                            'source_id'
                        )
                        ->all();

                /*
                 * Songbase book membership is rebuilt
                 * from the authoritative full payload.
                 */
                DB::table(
                    'hymn_book_entries'
                )
                    ->whereIn(
                        'hymn_book_id',
                        array_values(
                            $bookIds
                        )
                    )
                    ->delete();

                $entryRows = [];
                $skippedEntries = 0;

                foreach ($books as $book) {
                    $bookSourceId =
                        trim(
                            (string)
                                ($book['id'] ?? '')
                        );

                    $bookId =
                        $bookIds[
                            $bookSourceId
                        ] ?? null;

                    if (! $bookId) {
                        continue;
                    }

                    $entries =
                        $book['songs'] ?? [];

                    if (
                        ! is_array($entries)
                    ) {
                        continue;
                    }

                    foreach (
                        $entries
                        as $songSourceId
                        => $number
                    ) {
                        $songSourceId =
                            trim(
                                (string)
                                    $songSourceId
                            );

                        $hymnId =
                            $songIds[
                                $songSourceId
                            ] ?? null;

                        if (! $hymnId) {
                            $skippedEntries++;

                            continue;
                        }

                        $entryRows[] = [
                            'hymn_book_id' =>
                                $bookId,

                            'hymn_id' =>
                                $hymnId,

                            'number' =>
                                (string)
                                    $number,

                            'created_at' =>
                                $syncedAt,

                            'updated_at' =>
                                $syncedAt,
                        ];
                    }
                }

                foreach (
                    array_chunk(
                        $entryRows,
                        500
                    ) as $chunk
                ) {
                    DB::table(
                        'hymn_book_entries'
                    )->insert($chunk);
                }

                return [
                    'songs_received' =>
                        count($songRows),

                    'songs_skipped' =>
                        $skippedSongs,

                    'books_received' =>
                        count($bookRows),

                    'book_entries' =>
                        count($entryRows),

                    'entries_skipped' =>
                        $skippedEntries,
                ];
            }
        );

        return $result;
    }

    private function bookLanguage(
        array $book,
        array $songLanguageBySourceId
    ): ?string {
        $languages = [];

        foreach (
            array_keys(
                $book['songs'] ?? []
            )
            as $songSourceId
        ) {
            $songSourceId =
                trim(
                    (string) $songSourceId
                );

            $language =
                $songLanguageBySourceId[
                    $songSourceId
                ] ?? null;

            if (filled($language)) {
                $languages[
                    (string) $language
                ] = true;
            }
        }

        $languages =
            array_keys($languages);

        return count($languages) === 1
            ? $languages[0]
            : null;
    }
}
