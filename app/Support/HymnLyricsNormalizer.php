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

        /*
         * Remove chord markers such as:
         *
         * [D]
         * [Em]
         * [F#m7]
         * [Bb]
         * [G/B]
         * [Dsus4]
         * [Cadd9]
         *
         * But preserve labels such as:
         *
         * [Chorus]
         * [Verse 1]
         */
        $text =
            preg_replace(
                '/\[(?:[A-G](?:#|b)?'
                . '(?:m|maj|min|dim|aug|sus|add)?'
                . '\d*'
                . '(?:\/[A-G](?:#|b)?)?'
                . ')\]/i',
                ' ',
                $lyrics
            )
            ?? $lyrics;

        /*
         * Normalize line breaks, tabs, and repeated
         * spaces so phrase searches work across them.
         */
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

            if ($line === '') {
                continue;
            }

            /*
             * Song metadata such as:
             *
             * # Rev. 22:16-17
             * # Capo 2
             *
             * is not part of the first lyric line.
             */
            if (str_starts_with($line, '#')) {
                continue;
            }

            /*
             * Skip standalone section labels such as:
             *
             * [Verse 1]
             * [Chorus]
             */
            if (
                preg_match(
                    '/^\[[^\]]+\]\s*$/u',
                    $line
                ) === 1
            ) {
                continue;
            }

            /*
             * Remove inline chord markers.
             *
             * Removing them with an empty string is
             * intentional so an[G]gel becomes angel,
             * not "an gel".
             */
            $line =
                preg_replace(
                    '/\[(?:(?:'
                    . '[A-G](?:#|b)?'
                    . '(?:m|maj|min|dim|aug|sus|add)?'
                    . '\d*'
                    . '(?:\/[A-G](?:#|b)?)?'
                    . ')'
                    . '(?:-(?:'
                    . '[A-G](?:#|b)?'
                    . '(?:m|maj|min|dim|aug|sus|add)?'
                    . '\d*'
                    . '(?:\/[A-G](?:#|b)?)?'
                    . '))*'
                    . ')\]/i',
                    '',
                    $line
                )
                ?? $line;

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
                return $line;
            }
        }

        return null;
    }

}
