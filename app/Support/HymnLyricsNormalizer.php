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
}
