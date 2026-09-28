<?php

namespace App\Services;

use App\Models\Locality;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ScheduleLocalityResolver
{
    /**
     * Resolve Google Calendar metadata/location into one of the
     * active configured COQP Localities.
     *
     * This method does NOT modify the database.
     */
    public function resolve(
        ?string $description,
        ?string $location
    ): array {
        $localities = $this->activeLocalities();

        /*
         * -------------------------------------------------------
         * Priority 1: explicit metadata
         *
         * Locality: Lucban
         * -------------------------------------------------------
         */
        $metadataLocality =
            $this->descriptionMetadataValue(
                $description,
                'Locality'
            );

        if (filled($metadataLocality)) {
            $match = $this->bestMatch(
                $metadataLocality,
                $localities,
                true
            );

            if ($match) {
                $match['source'] =
                    $match['match_type'] === 'exact'
                        ? 'google_metadata_exact'
                        : 'google_metadata_fuzzy';

                $match['input'] =
                    $metadataLocality;

                return $match;
            }
        }

        /*
         * -------------------------------------------------------
         * Priority 2: Google Calendar Location field
         * -------------------------------------------------------
         */
        if (filled($location)) {
            $match = $this->bestMatch(
                $location,
                $localities,
                false
            );

            if ($match) {
                $match['source'] =
                    match ($match['match_type']) {
                        'exact' =>
                            'google_location_exact',

                        'contained' =>
                            'google_location_contained',

                        default =>
                            'google_location_fuzzy',
                    };

                $match['input'] =
                    trim((string) $location);

                return $match;
            }
        }

        return [
            'locality_id' => null,
            'locality' => null,
            'source' => 'unresolved',
            'match_type' => null,
            'confidence' => 0.0,
            'input' =>
                $metadataLocality
                ?: (
                    filled($location)
                        ? trim((string) $location)
                        : null
                ),
        ];
    }

    private function activeLocalities(): Collection
    {
        return Locality::query()
            ->with('province')
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'province_id',
            ]);
    }

    private function descriptionMetadataValue(
        ?string $description,
        string $key
    ): ?string {
        if (blank($description)) {
            return null;
        }

        $pattern =
            '/(?:^|\R)\s*'
            . preg_quote($key, '/')
            . '\s*:\s*(.+?)\s*(?:\R|$)/iu';

        if (
            ! preg_match(
                $pattern,
                (string) $description,
                $matches
            )
        ) {
            return null;
        }

        $value = trim(
            (string) ($matches[1] ?? '')
        );

        return $value !== ''
            ? $value
            : null;
    }

    private function bestMatch(
        string $input,
        Collection $localities,
        bool $metadata
    ): ?array {
        $normalizedInput =
            $this->normalize($input);

        if ($normalizedInput === '') {
            return null;
        }

        /*
         * First compare useful pieces of a Google Location,
         * for example:
         *
         *   Church Meeting Hall, Lucban, Quezon
         *
         * rather than comparing only the entire string.
         */
        $pieces = $this->inputPieces($input);

        /*
         * -------------------------------------------------------
         * Exact match
         * -------------------------------------------------------
         */
        foreach ($pieces as $pieceIndex => $piece) {
            foreach ($localities as $locality) {
                foreach (
                    $this->localityForms(
                        $locality->name
                    )
                    as $form
                ) {
                    if ($piece !== $form) {
                        continue;
                    }

                    /*
                     * A later piece that is also a Province name
                     * is weaker than an actual Locality piece.
                     */
                    if (
                        ! $metadata
                        && $pieceIndex > 0
                        && $this->isProvinceName(
                            $piece,
                            $localities
                        )
                    ) {
                        continue;
                    }

                    return $this->result(
                        $locality,
                        'exact',
                        1.0
                    );
                }
            }
        }

        /*
         * -------------------------------------------------------
         * Containment
         *
         * "Church in Lucban"
         * "Lucban Church Meeting Hall"
         * -------------------------------------------------------
         */
        $contained = [];

        foreach ($localities as $locality) {
            foreach (
                $this->localityForms(
                    $locality->name
                )
                as $form
            ) {
                if (
                    mb_strlen($form) < 4
                    || ! $this->containsPhrase(
                        $normalizedInput,
                        $form
                    )
                ) {
                    continue;
                }

                $contained[] = [
                    'locality' => $locality,
                    'length' => mb_strlen($form),
                ];
            }
        }

        if ($contained !== []) {
            usort(
                $contained,
                fn (array $a, array $b): int =>
                    $b['length']
                    <=>
                    $a['length']
            );

            /*
             * Longest contained Locality wins.
             *
             * This prevents a shorter Locality name from beating
             * a more specific one.
             */
            return $this->result(
                $contained[0]['locality'],
                'contained',
                0.97
            );
        }

        /*
         * -------------------------------------------------------
         * Fuzzy spelling match
         *
         * Example:
         *   Lucbn -> Lucban
         *
         * High threshold + second-place margin are deliberately
         * required so we do not silently guess ambiguous names.
         * -------------------------------------------------------
         */
        $scores = [];

        foreach ($localities as $locality) {
            $bestScore = 0.0;

            foreach (
                $this->localityForms(
                    $locality->name
                )
                as $form
            ) {
                if (mb_strlen($form) < 4) {
                    continue;
                }

                foreach ($pieces as $piece) {
                    $score =
                        $this->similarity(
                            $piece,
                            $form
                        );

                    $bestScore = max(
                        $bestScore,
                        $score
                    );
                }
            }

            if ($bestScore > 0) {
                $scores[] = [
                    'locality' => $locality,
                    'score' => $bestScore,
                ];
            }
        }

        usort(
            $scores,
            fn (array $a, array $b): int =>
                $b['score']
                <=>
                $a['score']
        );

        $best = $scores[0] ?? null;
        $second = $scores[1] ?? null;

        if (! $best) {
            return null;
        }

        $minimumConfidence =
            $metadata
                ? 0.88
                : 0.86;

        $minimumMargin = 0.08;

        if (
            $best['score']
                < $minimumConfidence
        ) {
            return null;
        }

        if (
            $second
            && (
                $best['score']
                - $second['score']
            ) < $minimumMargin
        ) {
            return null;
        }

        return $this->result(
            $best['locality'],
            'fuzzy',
            round(
                $best['score'],
                4
            )
        );
    }

    private function localityForms(
        string $name
    ): array {
        $normalized =
            $this->normalize($name);

        $forms = [
            $normalized,
        ];

        /*
         * Allow:
         *
         *   Lucena City -> Lucena
         *
         * but fuzzy/ambiguity protection still applies.
         */
        if (
            str_ends_with(
                $normalized,
                ' city'
            )
        ) {
            $forms[] = trim(
                substr(
                    $normalized,
                    0,
                    -5
                )
            );
        }

        return array_values(
            array_unique(
                array_filter($forms)
            )
        );
    }

    private function inputPieces(
        string $input
    ): array {
        $pieces = collect(
            preg_split(
                '/[,;|\/()\[\]\-]+/u',
                $input
            ) ?: []
        )
            ->map(
                fn ($piece): string =>
                    $this->normalize(
                        (string) $piece
                    )
            )
            ->filter()
            ->values();

        $full =
            $this->normalize($input);

        if ($full !== '') {
            $pieces->push($full);
        }

        /*
         * Add short contiguous word groups so venue text such as
         * "Church Meeting Hall Lucbn Quezon" can still identify
         * the misspelled Locality.
         */
        $words = preg_split(
            '/\s+/u',
            $full
        ) ?: [];

        foreach ($words as $word) {
            $word = trim($word);

            if (mb_strlen($word) >= 4) {
                $pieces->push($word);
            }
        }

        return $pieces
            ->unique()
            ->values()
            ->all();
    }

    private function normalize(
        ?string $value
    ): string {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return '';
        }

        $value = Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches(
                '/[^a-z0-9]+/',
                ' '
            )
            ->replaceMatches(
                '/\s+/',
                ' '
            )
            ->trim()
            ->toString();

        /*
         * Common Google Location wording.
         */
        $value = preg_replace(
            '/^(city|municipality)\s+of\s+/i',
            '',
            $value
        ) ?? $value;

        return trim($value);
    }

    private function containsPhrase(
        string $haystack,
        string $needle
    ): bool {
        return str_contains(
            ' ' . $haystack . ' ',
            ' ' . $needle . ' '
        );
    }

    private function similarity(
        string $a,
        string $b
    ): float {
        if (
            $a === ''
            || $b === ''
        ) {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        $maxLength = max(
            strlen($a),
            strlen($b)
        );

        if ($maxLength === 0) {
            return 1.0;
        }

        return max(
            0.0,
            1.0
            - (
                levenshtein($a, $b)
                / $maxLength
            )
        );
    }

    private function isProvinceName(
        string $value,
        Collection $localities
    ): bool {
        return $localities
            ->pluck('province.name')
            ->filter()
            ->map(
                fn ($name): string =>
                    $this->normalize(
                        (string) $name
                    )
            )
            ->contains($value);
    }

    private function result(
        Locality $locality,
        string $matchType,
        float $confidence
    ): array {
        return [
            'locality_id' =>
                (int) $locality->id,

            'locality' =>
                $locality->name,

            'source' =>
                null,

            'match_type' =>
                $matchType,

            'confidence' =>
                $confidence,

            'input' =>
                null,
        ];
    }
}
