<?php

namespace App\Support;

class HymnLyricsNormalizer
{
    public static function forSearch(
        ?string $lyrics
    ): ?string {
        if (blank($lyrics)) {
            return null;
        }

        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $lyrics
        );

        $searchableLines = [];

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);

            if (
                self::isStructuralLine(
                    $line
                )
            ) {
                continue;
            }

            /*
             * Strip musical chord markers while
             * preserving ordinary bracketed labels.
             *
             * Examples removed:
             *
             * [D]
             * [F#m7]
             * [G/B]
             * [Cadd9]
             * [G-G7]
             * [D - D7]
             * [F C]
             * [C-Am]
             * [(A-E)]
             *
             * Chords are removed without inserting a
             * space because Songbase frequently places
             * them inside words:
             *
             *   an[G]gel -> angel
             *   lov[G]ely -> lovely
             */
            $line =
                self::stripChordMarkers(
                    $line
                );

            /*
             * Songbase also uses [] as an empty chord /
             * alignment marker.
             */
            $line = str_replace(
                '[]',
                '',
                $line
            );

            $line =
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $line
                )
                ?? $line;

            $line = trim($line);

            if ($line !== '') {
                $searchableLines[] =
                    $line;
            }
        }

        if ($searchableLines === []) {
            return null;
        }

        $text =
            implode(
                ' ',
                $searchableLines
            );

        $text =
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            )
            ?? $text;

        $text = trim($text);

        return self::searchText(
            $text
        );
    }

    public static function firstLineForSearch(
        ?string $lyrics
    ): ?string {
        if (blank($lyrics)) {
            return null;
        }

        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $lyrics
        );

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);

            if (
                self::isStructuralLine(
                    $line
                )
            ) {
                continue;
            }

            $line =
                self::stripChordMarkers(
                    $line
                );

            $line = str_replace(
                '[]',
                '',
                $line
            );

            $line =
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $line
                )
                ?? $line;

            $line = trim($line);

            /*
             * A line that consisted only of chords can
             * become empty after chord removal.
             */
            if ($line === '') {
                continue;
            }

            return self::searchText(
                $line
            );
        }

        return null;
    }

    /*
     * Normalize arbitrary lyric-search text.
     *
     * Search is:
     * - case-insensitive
     * - apostrophe-insensitive
     * - punctuation-insensitive
     *
     * Examples:
     *
     * don't   -> dont
     * don’t   -> dont
     * O Lord, I love You
     *          -> o lord i love you
     */
    public static function searchText(
        ?string $text
    ): ?string {
        if (blank($text)) {
            return null;
        }

        $text =
            mb_strtolower(
                trim(
                    (string) $text
                )
            );

        /*
         * Remove apostrophes entirely so words remain
         * joined:
         *
         * Lord's -> lords
         * don't  -> dont
         * don’t  -> dont
         */
        $text =
            preg_replace(
                "/['’‘ʼ＇]+/u",
                '',
                $text
            )
            ?? $text;

        /*
         * All remaining punctuation and symbols become
         * spaces. This makes commas, periods, quotes,
         * dashes, parentheses, etc. optional in search.
         */
        $text =
            preg_replace(
                '/[^\p{L}\p{N}]+/u',
                ' ',
                $text
            )
            ?? $text;

        $text =
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            )
            ?? $text;

        $text = trim($text);

        return $text !== ''
            ? $text
            : null;
    }

    public static function containsPattern(
        ?string $text
    ): string {
        $normalized =
            self::searchText(
                $text
            );

        if ($normalized !== null) {
            return '%' . $normalized . '%';
        }

        /*
         * Avoid turning punctuation-only searches into
         * %% and therefore matching every lyric.
         */
        $fallback =
            trim(
                trim(
                    (string) $text
                ),
                '%'
            );

        return '%' . $fallback . '%';
    }

    private static function isStructuralLine(
        string $line
    ): bool {
        $line = trim($line);

        if ($line === '') {
            return true;
        }

        /*
         * Songbase comments / metadata:
         *
         * # Capo 2
         * # Rev. 22:16-17
         * ### Tune 1
         * ### Original Tune
         * # Part 1
         * #(Repeat)
         */
        if (
            str_starts_with(
                $line,
                '#'
            )
        ) {
            return true;
        }

        /*
         * Stanza numbers by themselves:
         *
         * 1
         * 2
         * 3.
         * 4)
         */
        if (
            preg_match(
                '/^\d+\s*[.)]?\s*$/u',
                $line
            ) === 1
        ) {
            return true;
        }

        /*
         * Standalone bracketed structural labels:
         *
         * [Verse 1]
         * [Chorus]
         * [Bridge]
         *
         * Pure chord-only bracket lines are harmless
         * here too; they contain no lyric text.
         */
        if (
            preg_match(
                '/^\[[^\]]+\]\s*$/u',
                $line
            ) === 1
            && self::stripChordMarkers(
                $line
            ) === ''
        ) {
            return true;
        }

        if (
            preg_match(
                '/^\[(?:'
                . 'verse(?:\s+\d+)?'
                . '|stanza(?:\s+\d+)?'
                . '|chorus(?:\s+\d+)?'
                . '|refrain(?:\s+\d+)?'
                . '|bridge(?:\s+\d+)?'
                . '|pre[- ]?chorus'
                . '|intro'
                . '|outro'
                . '|interlude'
                . '|ending'
                . '|end'
                . '|part(?:\s+\d+)?'
                . '|tune(?:\s+\d+)?'
                . ')\]\s*$/iu',
                $line
            ) === 1
        ) {
            return true;
        }

        /*
         * Common Songbase plain-text directions:
         *
         * Capo 3
         * Verse 1
         * Chorus
         * Chorus 2:
         * Part 1:
         * Tune 1
         * Original Tune
         * New Tune
         * Interlude
         * Ending:
         * End:
         */
        if (
            preg_match(
                '/^(?:'
                . 'capo(?:\s+\d+)?'
                . '|verse(?:\s+\d+)?'
                . '|stanza(?:\s+\d+)?'
                . '|chorus(?:\s+\d+)?'
                . '|refrain(?:\s+\d+)?'
                . '|bridge(?:\s+\d+)?'
                . '|pre[- ]?chorus'
                . '|intro'
                . '|outro'
                . '|interlude'
                . '|ending'
                . '|end'
                . '|part(?:\s+\d+)?'
                . '|tune(?:\s+\d+)?'
                . '|original\s+tune'
                . '|new\s+tune'
                . ')\s*:?\s*$/iu',
                $line
            ) === 1
        ) {
            return true;
        }

        /*
         * Repetition instructions which are not lyrics:
         *
         * Repeat
         * (Repeat)
         * Repeat twice
         */
        if (
            preg_match(
                '/^\(?\s*repeat'
                . '(?:\s+[^)]*)?'
                . '\s*\)?\s*$/iu',
                $line
            ) === 1
        ) {
            return true;
        }

        return false;
    }

    private static function stripChordMarkers(
        string $text
    ): string {
        /*
         * One chord token:
         *
         * D
         * F#m7
         * Bb
         * G/B
         * Dsus4
         * Cadd9
         * Asus2
         */
        $chord =
            '[A-G]'
            . '(?:#|b)?'
            . '(?:'
            . 'm'
            . '|maj'
            . '|min'
            . '|dim'
            . '|aug'
            . '|sus'
            . '|add'
            . ')?'
            . '\d*'
            . '(?:'
            . '\/[A-G](?:#|b)?'
            . ')?';

        /*
         * One or multiple chords inside brackets.
         *
         * Handles:
         *
         * [D]
         * [G-G7]
         * [D - D7]
         * [F C]
         * [C-Am]
         * [D-G-D]
         * [(A-E)]
         */
        $pattern =
            '/\['
            . '\s*'
            . '[-–—]?'
            . '\s*'
            . '\(?'
            . '\s*'
            . '(?:'
            . $chord
            . ')'
            . '(?:'
            . '\s*'
            . '(?:[-–—,+]|\s)'
            . '\s*'
            . '(?:'
            . $chord
            . ')'
            . ')*'
            . '\s*'
            . '\)?'
            . '\s*'
            . '\]'
            . '/iu';

        return
            preg_replace(
                $pattern,
                '',
                $text
            )
            ?? $text;
    }
}
