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

        preg_match_all(
            '/^###\s*Tune\s+([^\r\n]+)\s*$/mi',
            $lyrics,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $headers =
            $matches[0]
            ?? [];

        $numbers =
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

            $headingNumber =
                trim(
                    (string)
                        ($numbers[$index][0]
                            ?? ($index + 1))
                );

            $variants[] = [
                'variant_index' =>
                    $index,

                'songbase_tune_parameter' =>
                    $index,

                'heading_number' =>
                    $headingNumber,

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
