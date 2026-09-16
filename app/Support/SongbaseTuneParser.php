<?php

namespace App\Support;

class SongbaseTuneParser
{
    public static function parse(
        ?string $lyrics
    ): array {
        if (blank($lyrics)) {
            return [];
        }

        /*
         * Songbase uses many level-3 tune-heading
         * conventions, including:
         *
         * ### Tune 1
         * ### Tune 2 (simple)
         * ### Original tune
         * ### New Tune & Lyrics
         * ### Alternate Classic tune
         * ### Tune of 782: ...
         *
         * Treat any level-3 heading containing the
         * standalone word "tune" as a tune section.
         *
         * The Songbase tune query parameter remains
         * zero-based according to section order.
         */
        preg_match_all(
            '/^###\\s*([^\\r\\n]*\\btune\\b[^\\r\\n]*)\\s*$/mi',
            $lyrics,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $headers =
            $matches[0]
            ?? [];

        $headings =
            $matches[1]
            ?? [];

        if ($headers === []) {
            return [];
        }

        $variants = [];

        foreach (
            $headers
            as $index => $header
        ) {
            $fullHeader =
                $header[0];

            $headerOffset =
                $header[1];

            $bodyStart =
                $headerOffset
                + strlen($fullHeader);

            $bodyEnd =
                isset($headers[$index + 1])
                    ? $headers[$index + 1][1]
                    : strlen($lyrics);

            $body =
                trim(
                    substr(
                        $lyrics,
                        $bodyStart,
                        $bodyEnd - $bodyStart
                    )
                );

            $sourceHeading =
                trim(
                    (string)
                        ($headings[$index][0]
                            ?? '')
                );

            /*
             * Explicit "Tune N" headings retain N.
             *
             * Semantic headings such as Original tune
             * and New tune receive their Tune number
             * from section order:
             *
             * Original tune -> Tune 1 -> ?tune=0
             * New tune      -> Tune 2 -> ?tune=1
             */
            $headingNumber =
                (string) ($index + 1);

            if (
                preg_match(
                    '/^Tune\\s+(.+)$/i',
                    $sourceHeading,
                    $headingMatch
                )
            ) {
                $explicitHeadingNumber =
                    trim(
                        (string)
                            ($headingMatch[1]
                                ?? '')
                    );

                if (
                    $explicitHeadingNumber
                    !== ''
                ) {
                    $headingNumber =
                        $explicitHeadingNumber;
                }
            }

            $variants[] = [
                'variant_index' =>
                    $index,

                'songbase_tune_parameter' =>
                    $index,

                'heading_number' =>
                    $headingNumber,

                'source_heading' =>
                    $sourceHeading,

                'label' =>
                    'Tune ' . $headingNumber,

                'lyrics' =>
                    $body !== ''
                        ? $body
                        : null,

                'lyrics_search' =>
                    HymnLyricsNormalizer::forSearch(
                        $body
                    ),
            ];
        }

        return $variants;
    }
}
