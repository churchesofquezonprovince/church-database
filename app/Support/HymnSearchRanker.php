<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class HymnSearchRanker
{
    public static function apply(
        Builder $query,
        string $search,
        bool $preferEnglish = false
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

        /*
         * Relevance:
         *
         * 10 exact title
         * 20 title starts with
         * 30 title contains
         * 40 exact first lyric line
         * 50 first lyric line starts with
         * 60 first lyric line contains
         * 70 remaining lyrics
         * 80 source / number / variant-only match
         */
        $query->orderByRaw(
            "CASE
                WHEN LOWER(TRIM(title)) = ?
                    THEN 10

                WHEN LOWER(title) LIKE ?
                    THEN 20

                WHEN LOWER(title) LIKE ?
                    THEN 30

                WHEN LOWER(
                    TRIM(first_line_search)
                ) = ?
                    THEN 40

                WHEN LOWER(first_line_search) LIKE ?
                    THEN 50

                WHEN LOWER(first_line_search) LIKE ?
                    THEN 60

                WHEN LOWER(lyrics_search) LIKE ?
                    THEN 70

                ELSE 80
            END",
            [
                $searchLower,
                $startsWith,
                $contains,
                $searchLower,
                $startsWith,
                $contains,
                $contains,
            ]
        );

        if ($preferEnglish) {
            $query->orderByRaw(
                "CASE
                    WHEN language = 'english'
                        THEN 0
                    ELSE 1
                END"
            );
        }

        return $query->orderBy('title');
    }
}
