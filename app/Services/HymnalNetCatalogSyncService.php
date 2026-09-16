<?php

namespace App\Services;

use App\Models\Hymn;
use App\Models\HymnBook;
use App\Models\HymnBookEntry;
use App\Models\HymnalNetEntry;
use App\Models\HymnSource;
use App\Models\HymnVariant;
use App\Support\HymnalNetCollectionCatalog;
use App\Support\HymnalNetSource;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class HymnalNetCatalogSyncService
{
    public function sync(
        string $collection,
        int $from,
        int $to,
        int $delayMs = 500,
        bool $allowSparse = false
    ): array {
        $config =
            HymnalNetCollectionCatalog
                ::get(
                    $collection
                );

        $collection =
            $config['code'];

        $min =
            (int) $config[
                'min_number'
            ];

        $max =
            (int) $config[
                'max_number'
            ];

        if ($allowSparse) {
            /*
             * Sparse mode is ONLY for one explicitly
             * known/discovered Hymnal.net ID.
             *
             * It must never become a way to crawl the
             * broken sequence above the safe boundary.
             */
            if (
                $from !== $to
                || $from < $min
            ) {
                throw new RuntimeException(
                    'Sparse Hymnal.net synchronization '
                    . 'requires exactly one valid ID.'
                );
            }
        } elseif (
            $from < $min
            || $to > $max
            || $from > $to
        ) {
            throw new RuntimeException(
                sprintf(
                    '%s sequential synchronization '
                    . 'is limited to %d through %d. '
                    . 'Requested range: %d-%d.',
                    $config['label'],
                    $min,
                    $max,
                    $from,
                    $to
                )
            );
        }

        $book =
            HymnBook::query()
                ->where(
                    'source',
                    $config[
                        'canonical_book_source'
                    ]
                )
                ->where(
                    'source_id',
                    $config[
                        'canonical_book_source_id'
                    ]
                )
                ->first();

        if (! $book) {
            throw new RuntimeException(
                'Matching Songbase book was '
                . 'not found: '
                . $config[
                    'canonical_book_name'
                ]
            );
        }

        $result = [
            'collection' =>
                $collection,

            'from' =>
                $from,

            'to' =>
                $to,

            'fetched' =>
                0,

            'valid' =>
                0,

            'linked' =>
                0,

            'unmatched' =>
                0,

            'ambiguous' =>
                0,

            'conflicts' =>
                0,

            'invalid' =>
                0,

            'failed' =>
                0,
        ];

        for (
            $number = $from;
            $number <= $to;
            $number++
        ) {
            $url =
                HymnalNetCollectionCatalog::url(
                    $collection,
                    $number
                );

            $entry =
                HymnalNetEntry::query()
                    ->firstOrNew([
                        'collection_code' =>
                            $collection,

                        'number' =>
                            (string) $number,
                    ]);

            $entry->section_code =
                $config['section_code']
                ?? 'classic';

            $entry->source_url =
                $url;

            try {
                $response =
                    Http::accept(
                        'text/html'
                    )
                        ->withHeaders([
                            'User-Agent' =>
                                'CoQP-HymnalNet-Catalog/1.0',
                        ])
                        ->timeout(30)
                        ->retry(
                            2,
                            750
                        )
                        ->get(
                            $url
                        );

                $entry->http_status =
                    $response->status();

                $entry->last_fetched_at =
                    now();

                if (! $response->successful()) {
                    $entry->fetch_status =
                        'http_error';

                    $entry->validation_status =
                        'failed';

                    $entry->validation_note =
                        'HTTP '
                        . $response->status();

                    $entry->match_status =
                        'not_checked';

                    $entry->matched_hymn_id =
                        null;

                    $entry->save();

                    $result['failed']++;

                    continue;
                }

                $body =
                    $response->body();

                $title =
                    $this->extractTitle(
                        $body
                    );

                $entry->title =
                    $title;

                $validation =
                    $this->validatePage(
                        $collection,
                        $number,
                        $title,
                        $body
                    );

                $entry->fetch_status =
                    'fetched';

                $result['fetched']++;

                if (! $validation['valid']) {
                    $entry->validation_status =
                        'invalid';

                    $entry->validation_note =
                        $validation['note'];

                    $entry->match_status =
                        'not_checked';

                    $entry->matched_hymn_id =
                        null;

                    $entry->match_method =
                        null;

                    $entry->match_score =
                        null;

                    $entry->save();

                    $result['invalid']++;

                    continue;
                }

                $entry->validation_status =
                    'valid';

                $entry->validation_note =
                    $validation['note'];

                $result['valid']++;

                $matchResult =
                    $this->matchAndLink(
                        $entry,
                        $book
                    );

                $result[
                    $matchResult
                ]++;

                $entry->save();
            } catch (Throwable $e) {
                report($e);

                $entry->fetch_status =
                    'error';

                $entry->validation_status =
                    'failed';

                $entry->validation_note =
                    $e->getMessage();

                $entry->match_status =
                    'not_checked';

                $entry->last_fetched_at =
                    now();

                $entry->save();

                $result['failed']++;
            } finally {
                if (
                    $delayMs > 0
                    && $number < $to
                ) {
                    usleep(
                        $delayMs * 1000
                    );
                }
            }
        }

        return $result;
    }

    public function syncDiscovered(
        string $section,
        int $delayMs = 500,
        bool $resync = false,
        int $limit = 0
    ): array {
        $config =
            HymnalNetCollectionCatalog
                ::section(
                    $section
                );

        $section =
            $config['code'];

        $strategy =
            $config[
                'match_strategy'
            ];

        $book =
            $this->matchingBookForSection(
                $config
            );

        $query =
            HymnalNetEntry::query()
                ->where(
                    'section_code',
                    $section
                )
                ->orderBy(
                    'collection_code'
                )
                ->orderByRaw(
                    'CAST(number AS UNSIGNED)'
                )
                ->orderBy(
                    'number'
                );

        if (! $resync) {
            $query->where(
                'fetch_status',
                'pending'
            );
        }

        if ($limit > 0) {
            $query->limit(
                $limit
            );
        }

        $entries =
            $query->get();

        $result = [
            'section' =>
                $section,

            'processed' =>
                0,

            'fetched' =>
                0,

            'valid' =>
                0,

            'linked' =>
                0,

            'variant_review' =>
                0,

            'unmatched' =>
                0,

            'ambiguous' =>
                0,

            'conflicts' =>
                0,

            'invalid' =>
                0,

            'failed' =>
                0,
        ];

        foreach (
            $entries
            as $index => $entry
        ) {
            $result[
                'processed'
            ]++;

            try {
                $response =
                    Http::accept(
                        'text/html'
                    )
                        ->withHeaders([
                            'User-Agent' =>
                                'CoQP-HymnalNet-Catalog/1.0',
                        ])
                        ->timeout(30)
                        ->retry(
                            2,
                            750
                        )
                        ->get(
                            $entry->source_url
                        );

                $entry->http_status =
                    $response->status();

                $entry->last_fetched_at =
                    now();

                if (! $response->successful()) {
                    $entry->fetch_status =
                        'http_error';

                    $entry->validation_status =
                        'failed';

                    $entry->validation_note =
                        'HTTP '
                        . $response->status();

                    $entry->match_status =
                        'not_checked';

                    $entry->matched_hymn_id =
                        null;

                    $entry
                        ->matched_hymn_variant_id =
                        null;

                    $entry->save();

                    $result['failed']++;

                    continue;
                }

                $body =
                    $response->body();

                $entry->title =
                    $this->extractTitle(
                        $body
                    );

                $entry->fetch_status =
                    'fetched';

                $result['fetched']++;

                $validation =
                    $this->validatePage(
                        $entry
                            ->collection_code,
                        $entry->number,
                        $entry->title,
                        $body
                    );

                if (! $validation['valid']) {
                    $entry->validation_status =
                        'invalid';

                    $entry->validation_note =
                        $validation['note'];

                    $entry->match_status =
                        'not_checked';

                    $entry->matched_hymn_id =
                        null;

                    $entry
                        ->matched_hymn_variant_id =
                        null;

                    $entry->match_method =
                        null;

                    $entry->match_score =
                        null;

                    $entry->save();

                    $result['invalid']++;

                    continue;
                }

                $entry->validation_status =
                    'valid';

                $entry->validation_note =
                    $validation['note'];

                $result['valid']++;

                $matchResult =
                    $this->matchAndLink(
                        $entry,
                        $book,
                        $strategy
                    );

                if (
                    ! array_key_exists(
                        $matchResult,
                        $result
                    )
                ) {
                    throw new RuntimeException(
                        'Unknown Hymnal.net match '
                        . 'result: '
                        . $matchResult
                    );
                }

                $result[
                    $matchResult
                ]++;

                $entry->save();
            } catch (Throwable $e) {
                report($e);

                $entry->fetch_status =
                    'error';

                $entry->validation_status =
                    'failed';

                $entry->validation_note =
                    $e->getMessage();

                $entry->match_status =
                    'not_checked';

                $entry->last_fetched_at =
                    now();

                $entry->save();

                $result['failed']++;
            } finally {
                if (
                    $delayMs > 0
                    && $index
                        < $entries->count() - 1
                ) {
                    usleep(
                        $delayMs * 1000
                    );
                }
            }
        }

        return $result;
    }


    private function matchingBookForSection(
        array $config
    ): ?HymnBook {
        $source =
            $config[
                'canonical_book_source'
            ]
            ?? null;

        $sourceId =
            $config[
                'canonical_book_source_id'
            ]
            ?? null;

        if (
            blank($source)
            || blank($sourceId)
        ) {
            return null;
        }

        $book =
            HymnBook::query()
                ->where(
                    'source',
                    $source
                )
                ->where(
                    'source_id',
                    $sourceId
                )
                ->first();

        if (! $book) {
            throw new RuntimeException(
                'Matching Songbase book was '
                . 'not found: '
                . (
                    $config[
                        'canonical_book_name'
                    ]
                    ?? $sourceId
                )
            );
        }

        return $book;
    }


    private function extractTitle(
        string $html
    ): ?string {
        if (
            ! preg_match(
                '/<h1\b[^>]*>(.*?)<\/h1>/is',
                $html,
                $matches
            )
        ) {
            return null;
        }

        $title =
            html_entity_decode(
                strip_tags(
                    $matches[1]
                ),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

        $title =
            preg_replace(
                '/\s+/u',
                ' ',
                $title
            ) ?? $title;

        $title =
            trim($title);

        return
            $title !== ''
                ? $title
                : null;
    }

    private function validatePage(
        string $collection,
        int|string $number,
        ?string $title,
        string $html
    ): array {
        if (blank($title)) {
            return [
                'valid' => false,
                'note' =>
                    'No Hymnal.net page title '
                    . 'could be extracted.',
            ];
        }

        /*
         * Classic English Hymnal pages normally
         * expose an internal lead-sheet reference:
         *
         * h/1    -> e0001_p.svg
         * h/1360 -> e1360_p.svg
         *
         * This catches the known h/1361 corruption,
         * where the returned contents belong to a
         * different hymn.
         */
        if (
            $collection === 'h'
            && preg_match(
                '#/Hymns/Hymnal/svg/'
                . 'e0*([0-9]+)_p\.svg#i',
                $html,
                $matches
            )
        ) {
            $internalNumber =
                (int) $matches[1];

            if (
                $internalNumber
                !== (int) $number
            ) {
                return [
                    'valid' => false,
                    'note' =>
                        'Internal Hymnal.net hymn '
                        . 'number '
                        . $internalNumber
                        . ' does not match requested '
                        . $number
                        . '.',
                ];
            }

            return [
                'valid' => true,
                'note' =>
                    'Internal Hymnal.net number '
                    . 'verified.',
            ];
        }

        return [
            'valid' => true,
            'note' =>
                'Title found; no internal number '
                . 'marker was available.',
        ];
    }

    private function matchAndLink(
        HymnalNetEntry $entry,
        ?HymnBook $book,
        string $strategy = 'classic'
    ): string {
        $bookHymnIds =
            collect();

        $lookupNumber =
            (string) $entry->number;

        /*
         * Alternate Tunes retain their full provider
         * number (for example h/10b), but
         * canonical-family lookup uses only the
         * corresponding numeric Classic number (10).
         *
         * Suffixed New Tunes such as nt/350b follow
         * the same family-lookup rule: use 350 only
         * to identify the canonical Hymn family.
         *
         * This must NEVER choose a Tune variant.
         */
        $suffixedTuneNumberMatches = [];

        $hasSuffixedTuneNumber =
            preg_match(
                '/^([0-9]+)([A-Za-z]+)$/',
                $lookupNumber,
                $suffixedTuneNumberMatches
            ) === 1;

        if (
            $strategy === 'alternate_tune'
            && ! $hasSuffixedTuneNumber
        ) {
            throw new RuntimeException(
                'Alternate Tune number must contain '
                . 'a numeric Classic number followed '
                . 'by a letter suffix.'
            );
        }

        $usesClassicBaseNumber =
            $strategy === 'alternate_tune'
            || (
                $strategy === 'new_tune'
                && $hasSuffixedTuneNumber
            );

        if ($usesClassicBaseNumber) {
            $lookupNumber =
                $suffixedTuneNumberMatches[1];

            /*
             * Prefer Hymnal.net's already-linked
             * Classic counterpart as the
             * canonical-family anchor.
             */
            $classicEntry =
                HymnalNetEntry::query()
                    ->where(
                        'section_code',
                        'classic'
                    )
                    ->where(
                        'collection_code',
                        'h'
                    )
                    ->where(
                        'number',
                        $lookupNumber
                    )
                    ->where(
                        'match_status',
                        'linked'
                    )
                    ->whereNotNull(
                        'matched_hymn_id'
                    )
                    ->first();

            if ($classicEntry) {
                $bookHymnIds =
                    collect([
                        (int)
                            $classicEntry
                                ->matched_hymn_id,
                    ]);
            }
        }

        if (
            $strategy !== 'title'
            && $bookHymnIds->isEmpty()
        ) {
            if (! $book) {
                throw new RuntimeException(
                    'This Hymnal.net matching strategy '
                    . 'requires the canonical Hymnal '
                    . 'book.'
                );
            }

            $bookHymnIds =
                HymnBookEntry::query()
                    ->where(
                        'hymn_book_id',
                        $book->id
                    )
                    ->where(
                        'number',
                        $lookupNumber
                    )
                    ->pluck(
                        'hymn_id'
                    )
                    ->unique()
                    ->values();
        }

        $hymnId = null;
        $matchMethod = null;
        $matchScore = null;

        /*
         * Strongest case:
         * exactly one canonical Hymn occupies
         * this Songbase Hymnal number.
         */
        if ($bookHymnIds->count() === 1) {
            $hymnId =
                (int) $bookHymnIds->first();

            $matchMethod =
                match ($strategy) {
                    'new_tune' =>
                        'new_tune_classic_number',

                    'alternate_tune' =>
                        'alternate_tune_classic_number',

                    default =>
                        'songbase_book_number',
                };

            $matchScore =
                100;
        }

        /*
         * If a Hymnal number is duplicated in
         * Songbase, use the Hymnal.net title to
         * distinguish the candidates.
         *
         * Example:
         * Hymnal #1193 has two Songbase entries,
         * but only one has the same normalized
         * title as Hymnal.net.
         */
        if ($bookHymnIds->count() > 1) {
            $needle =
                $this->normalizeTitle(
                    $entry->title
                );

            $titleMatches =
                Hymn::query()
                    ->whereIn(
                        'id',
                        $bookHymnIds->all()
                    )
                    ->get([
                        'id',
                        'title',
                    ])
                    ->filter(
                        fn (Hymn $hymn): bool =>
                            $this->normalizeTitle(
                                $hymn->title
                            ) === $needle
                    )
                    ->values();

            if ($titleMatches->count() === 1) {
                $hymnId =
                    (int) $titleMatches
                        ->first()
                        ->id;

                $matchMethod =
                    match ($strategy) {
                        'new_tune' =>
                            'new_tune_classic_number_title',

                        'alternate_tune' =>
                            'alternate_tune_classic_number_title',

                        default =>
                            'songbase_book_number_title',
                    };

                $matchScore =
                    100;
            } else {
                $entry->matched_hymn_id =
                    null;

                $entry->matched_hymn_variant_id =
                    null;

                $entry->match_status =
                    'ambiguous';

                $entry->match_method =
                    'songbase_book_number';

                $entry->match_score =
                    null;

                return 'ambiguous';
            }
        }

        /*
         * No canonical Hymnal book-number match:
         * look throughout the canonical catalog
         * for ONE exact normalized title.
         *
         * Do not fuzzy-auto-link here.
         */
        if ($bookHymnIds->isEmpty()) {
            $needle =
                $this->normalizeTitle(
                    $entry->title
                );

            $titleMatches =
                Hymn::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->whereNotNull(
                        'title'
                    )
                    ->get([
                        'id',
                        'title',
                    ])
                    ->filter(
                        fn (Hymn $hymn): bool =>
                            $this->normalizeTitle(
                                $hymn->title
                            ) === $needle
                    )
                    ->values();

            if ($titleMatches->count() === 1) {
                $hymnId =
                    (int) $titleMatches
                        ->first()
                        ->id;

                $matchMethod =
                    'exact_normalized_title';

                $matchScore =
                    95;
            } elseif ($titleMatches->count() > 1) {
                $entry->matched_hymn_id =
                    null;

                $entry->match_status =
                    'ambiguous';

                $entry->match_method =
                    'exact_normalized_title';

                $entry->match_score =
                    null;

                return 'ambiguous';
            } else {
                $entry->matched_hymn_id =
                    null;

                $entry->matched_hymn_variant_id =
                    null;

                $entry->match_status =
                    'unmatched';

                $entry->match_method =
                    null;

                $entry->match_score =
                    null;

                return 'unmatched';
            }
        }

        if (! $hymnId) {
            $entry->matched_hymn_id =
                null;

            $entry->matched_hymn_variant_id =
                null;

            $entry->match_status =
                'unmatched';

            $entry->match_method =
                null;

            $entry->match_score =
                null;

            return 'unmatched';
        }

        /*
         * New Tunes are not new canonical Hymns.
         *
         * We identify the canonical family but stop
         * before assigning a source to a tune/version.
         * Central review must explicitly decide the
         * correct HymnVariant.
         */
        if (
            in_array(
                $strategy,
                [
                    'new_tune',
                    'alternate_tune',
                ],
                true
            )
        ) {
            /*
             * Preserve a later explicit human review.
             */
            if (
                $entry->match_status
                    === 'linked'
                && $entry->match_method
                    === 'manual_review'
                && (int)
                    $entry->matched_hymn_id
                    === $hymnId
            ) {
                return 'linked';
            }

            $entry->matched_hymn_id =
                $hymnId;

            $entry->matched_hymn_variant_id =
                null;

            $entry->match_status =
                'variant_review';

            $strategyPrefix =
                $strategy === 'alternate_tune'
                    ? 'alternate_tune_'
                    : 'new_tune_';

            $fallbackMethod =
                $strategy === 'alternate_tune'
                    ? 'alternate_tune_exact_title'
                    : 'new_tune_exact_title';

            $entry->match_method =
                str_starts_with(
                    (string) $matchMethod,
                    $strategyPrefix
                )
                    ? $matchMethod
                    : $fallbackMethod;

            $entry->match_score =
                $matchScore;

            return 'variant_review';
        }

        /*
         * Variant safety:
         *
         * - One active variant: safe to select.
         * - Multiple active variants: never guess.
         * - Preserve an existing manually-reviewed
         *   variant when the canonical Hymn did not
         *   change.
         */
        $matchedVariantId = null;

        if (
            (int) $entry->matched_hymn_id
                === $hymnId
            && $entry->matched_hymn_variant_id
        ) {
            $existingVariant =
                HymnVariant::query()
                    ->where(
                        'hymn_id',
                        $hymnId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->find(
                        $entry
                            ->matched_hymn_variant_id
                    );

            if ($existingVariant) {
                $matchedVariantId =
                    $existingVariant->id;
            }
        }

        if (! $matchedVariantId) {
            $variants =
                HymnVariant::query()
                    ->where(
                        'hymn_id',
                        $hymnId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->get([
                        'id',
                    ]);

            if ($variants->count() === 1) {
                $matchedVariantId =
                    (int) $variants
                        ->first()
                        ->id;
            }
        }

        /*
         * Do not downgrade an explicit manual review
         * merely because this entry was synchronized
         * again later.
         */
        if (
            $entry->match_method
                === 'manual_review'
            && (int) $entry->matched_hymn_id
                === $hymnId
        ) {
            $matchMethod =
                'manual_review';

            $matchScore =
                100;
        }

        $externalId =
            HymnalNetSource
                ::externalIdForUrl(
                    $entry->source_url
                );

        $existingElsewhere =
            HymnSource::query()
                ->where(
                    'provider',
                    HymnSource::PROVIDER_HYMNAL_NET
                )
                ->where(
                    'external_id',
                    $externalId
                )
                ->where(
                    'hymn_id',
                    '!=',
                    $hymnId
                )
                ->exists();

        if ($existingElsewhere) {
            $entry->matched_hymn_id =
                $hymnId;

            $entry->matched_hymn_variant_id =
                $matchedVariantId;

            $entry->match_status =
                'conflict';

            $entry->match_method =
                $matchMethod;

            $entry->match_score =
                $matchScore;

            return 'conflicts';
        }

        HymnSource::query()
            ->updateOrCreate(
                [
                    'hymn_id' =>
                        $hymnId,

                    'provider' =>
                        HymnSource
                            ::PROVIDER_HYMNAL_NET,

                    'external_id' =>
                        $externalId,
                ],
                [
                    'hymn_variant_id' =>
                        $matchedVariantId,

                    'source_type' =>
                        HymnSource::TYPE_CATALOG,

                    'source_url' =>
                        $entry->source_url,

                    'label' =>
                        'Hymnal.net',

                    'metadata' => [
                        'collection' =>
                            $entry
                                ->collection_code,

                        'section' =>
                            $entry
                                ->section_code,

                        'number' =>
                            $entry->number,

                        'title' =>
                            $entry->title,

                        'sync' =>
                            'hymnal_net_catalog',

                        'match_method' =>
                            $matchMethod,
                    ],
                ]
            );

        $entry->matched_hymn_id =
            $hymnId;

        $entry->matched_hymn_variant_id =
            $matchedVariantId;

        $entry->match_status =
            'linked';

        $entry->match_method =
            $matchMethod;

        $entry->match_score =
            $matchScore;

        return 'linked';
    }

    private function normalizeTitle(
        ?string $title
    ): string {
        $title =
            mb_strtolower(
                trim(
                    (string) $title
                )
            );

        $title =
            preg_replace(
                '/[^\p{L}\p{N}]+/u',
                ' ',
                $title
            ) ?? $title;

        $title =
            preg_replace(
                '/\s+/u',
                ' ',
                $title
            ) ?? $title;

        return trim($title);
    }

}
