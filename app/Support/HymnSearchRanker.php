<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class HymnSearchRanker
{
    public static function apply(
        Builder $query,
        string $search,
        bool $preferEnglish = false,
        ?string $sourceProvider = null
    ): Builder {
        $search =
            preg_replace(
                '/\s+/u',
                ' ',
                trim($search)
            )
            ?? trim($search);

        if ($search === '') {
            return $query;
        }

        $searchLower =
            mb_strtolower($search);

        $startsWith =
            $searchLower . '%';

        $contains =
            '%' . $searchLower . '%';

        $lyricsSearch =
            HymnLyricsNormalizer::searchText(
                $search
            )
            ?? $searchLower;

        $lyricsStartsWith =
            $lyricsSearch . '%';

        $lyricsContains =
            '%' . $lyricsSearch . '%';

        /*
         * Lyrics are provider-owned.
         *
         * Relevance:
         *
         * 10 exact canonical title
         * 20 canonical title starts with
         * 30 canonical title contains
         * 40 exact first lyric line from a source
         * 50 source first line starts with
         * 60 source first line contains
         * 70 source lyrics contain phrase
         * 80 source ID / number / variant-only match
         *
         * When $sourceProvider is supplied, only that
         * provider participates in lyric ranking.
         *
         * This is important for Songbase Setup, whose
         * search must remain Songbase-exclusive.
         */
        $providerSql =
            $sourceProvider !== null
                ? ' AND hs.provider = ?'
                : '';

        $bindings = [
            $searchLower,
            $startsWith,
            $contains,
        ];

        $sourceBindings =
            function (
                string $value
            ) use (
                $sourceProvider
            ): array {
                $bindings = [];

                if ($sourceProvider !== null) {
                    $bindings[] =
                        $sourceProvider;
                }

                $bindings[] =
                    $value;

                return $bindings;
            };

        array_push(
            $bindings,
            ...$sourceBindings(
                $lyricsSearch
            ),
            ...$sourceBindings(
                $lyricsStartsWith
            ),
            ...$sourceBindings(
                $lyricsContains
            ),
            ...$sourceBindings(
                $lyricsContains
            )
        );

        $query->orderByRaw(
            "CASE
                WHEN LOWER(
                    TRIM(hymns.title)
                ) = ?
                    THEN 10

                WHEN LOWER(
                    hymns.title
                ) LIKE ?
                    THEN 20

                WHEN LOWER(
                    hymns.title
                ) LIKE ?
                    THEN 30

                WHEN EXISTS (
                    SELECT 1
                    FROM hymn_sources hs
                    WHERE
                        hs.hymn_id = hymns.id
                        {$providerSql}
                        AND LOWER(
                            TRIM(
                                hs.first_line_search
                            )
                        ) = ?
                )
                    THEN 40

                WHEN EXISTS (
                    SELECT 1
                    FROM hymn_sources hs
                    WHERE
                        hs.hymn_id = hymns.id
                        {$providerSql}
                        AND LOWER(
                            hs.first_line_search
                        ) LIKE ?
                )
                    THEN 50

                WHEN EXISTS (
                    SELECT 1
                    FROM hymn_sources hs
                    WHERE
                        hs.hymn_id = hymns.id
                        {$providerSql}
                        AND LOWER(
                            hs.first_line_search
                        ) LIKE ?
                )
                    THEN 60

                WHEN EXISTS (
                    SELECT 1
                    FROM hymn_sources hs
                    WHERE
                        hs.hymn_id = hymns.id
                        {$providerSql}
                        AND LOWER(
                            hs.lyrics_search
                        ) LIKE ?
                )
                    THEN 70

                ELSE 80
            END",
            $bindings
        );

        if ($preferEnglish) {
            $query->orderByRaw(
                "CASE
                    WHEN hymns.language = 'english'
                        THEN 0
                    ELSE 1
                END"
            );
        }

        return $query->orderBy(
            'hymns.title'
        );
    }
}
