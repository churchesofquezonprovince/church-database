<?php

namespace App\Services;

use App\Models\HymnSource;
use App\Models\HymnVariant;
use App\Support\HymnLyricsNormalizer;
use App\Support\SongbaseTuneParser;
use RuntimeException;

class SongbaseHymnVariantSyncService
{
    public function syncAllLocal(): array
    {
        /*
         * Variant identity remains canonical structural
         * data, but lyrics now belong to provider
         * source rows.
         */
        HymnVariant::query()
            ->where(
                'source',
                HymnSource::PROVIDER_SONGBASE
            )
            ->where(
                'variant_type',
                'tune'
            )
            ->update([
                'is_active' => false,
            ]);

        /*
         * Read Songbase lyrics from its provider source,
         * not from hymns.lyrics.
         */
        $songbaseSources =
            HymnSource::query()
                ->with('hymn')
                ->where(
                    'provider',
                    HymnSource::PROVIDER_SONGBASE
                )
                ->whereNull(
                    'hymn_variant_id'
                )
                ->whereNotNull(
                    'lyrics'
                )
                /*
                 * Keep database selection broad and cheap:
                 * only load lyrics that contain both a
                 * Markdown level-3 marker and the word
                 * "tune" somewhere afterward.
                 *
                 * SongbaseTuneParser remains the final
                 * authority for deciding whether actual
                 * tune sections exist.
                 */
                ->whereRaw(
                    'LOWER(lyrics) LIKE ?',
                    [
                        '%###%tune%',
                    ]
                )
                ->get()
                ->filter(
                    fn (
                        HymnSource $source
                    ): bool =>
                        SongbaseTuneParser::parse(
                            (string)
                                $source->lyrics
                        ) !== []
                )
                ->values();

        $expectedExternalIds = [];

        $variantCount = 0;

        foreach (
            $songbaseSources
            as $songbaseSource
        ) {
            $hymn =
                $songbaseSource->hymn;

            if (! $hymn) {
                continue;
            }

            $sourceId =
                trim(
                    (string)
                        $songbaseSource
                            ->external_id
                );

            if ($sourceId === '') {
                continue;
            }

            $variants =
                SongbaseTuneParser::parse(
                    (string)
                        $songbaseSource
                            ->lyrics
                );

            foreach (
                $variants
                as $variantData
            ) {
                /*
                 * hymn_variants identifies Tune 1,
                 * Tune 2, revisions, etc.
                 *
                 * It intentionally does not receive
                 * provider lyric content anymore.
                 */
                /*
                 * Prefer the existing Songbase structural
                 * variant when it already exists.
                 */
                $variant =
                    HymnVariant::query()
                        ->where(
                            'hymn_id',
                            $hymn->id
                        )
                        ->where(
                            'source',
                            HymnSource
                                ::PROVIDER_SONGBASE
                        )
                        ->where(
                            'source_id',
                            $sourceId
                        )
                        ->where(
                            'variant_type',
                            'tune'
                        )
                        ->where(
                            'variant_index',
                            $variantData[
                                'variant_index'
                            ]
                        )
                        ->first();

                $promotedFromReview =
                    false;

                $reviewMetadata =
                    null;

                /*
                 * Phase 28T may already have established
                 * the canonical tune structure during a
                 * Hymnal.net New Tune review.
                 *
                 * If exactly one review-created manual
                 * Tune occupies this same structural
                 * index, promote that row into the real
                 * Songbase structural variant rather than
                 * creating Tune 1 / Tune 2 duplicates.
                 */
                if (! $variant) {
                    $reviewCandidates =
                        HymnVariant::query()
                            ->where(
                                'hymn_id',
                                $hymn->id
                            )
                            ->where(
                                'source',
                                'manual'
                            )
                            ->where(
                                'variant_type',
                                'tune'
                            )
                            ->where(
                                'variant_index',
                                $variantData[
                                    'variant_index'
                                ]
                            )
                            ->get()
                            ->filter(
                                function (
                                    HymnVariant $candidate
                                ): bool {
                                    return in_array(
                                        (
                                            $candidate
                                                ->metadata[
                                                    'created_by'
                                                ]
                                            ?? null
                                        ),
                                        [
                                            'hymnal_net_new_tune_review',
                                            'hymnal_net_alternate_tune_review',
                                        ],
                                        true
                                    );
                                }
                            )
                            ->values();

                    if (
                        $reviewCandidates->count()
                            > 1
                    ) {
                        throw new RuntimeException(
                            'Multiple review-created Tune '
                            . 'variants occupy Hymn #'
                            . $hymn->id
                            . ' Tune index '
                            . $variantData[
                                'variant_index'
                            ]
                            . '. Songbase reconciliation '
                            . 'was stopped.'
                        );
                    }

                    if (
                        $reviewCandidates->count()
                            === 1
                    ) {
                        $variant =
                            $reviewCandidates
                                ->first();

                        $reviewMetadata =
                            $variant->metadata;

                        $promotedFromReview =
                            true;
                    }
                }

                if (! $variant) {
                    $variant =
                        new HymnVariant();

                    $variant->hymn_id =
                        $hymn->id;
                }

                $metadata = [
                    'songbase_tune_parameter' =>
                        $variantData[
                            'songbase_tune_parameter'
                        ],

                    'heading_number' =>
                        $variantData[
                            'heading_number'
                        ],

                    'source_heading' =>
                        $variantData[
                            'source_heading'
                        ]
                        ?? null,
                ];

                if ($promotedFromReview) {
                    $metadata[
                        'promoted_from_review'
                    ] = true;

                    $metadata[
                        'review_metadata'
                    ] = $reviewMetadata;
                }

                $variant->fill([
                    'source' =>
                        HymnSource
                            ::PROVIDER_SONGBASE,

                    'source_id' =>
                        $sourceId,

                    'variant_type' =>
                        'tune',

                    'variant_index' =>
                        $variantData[
                            'variant_index'
                        ],

                    'label' =>
                        $variantData[
                            'label'
                        ],

                    'title_override' =>
                        null,

                    'metadata' =>
                        $metadata,

                    'sort_order' =>
                        $variantData[
                            'variant_index'
                        ],

                    'is_active' =>
                        true,
                ]);

                $variant->save();


                $externalId =
                    $sourceId
                    . ':tune:'
                    . $variantData[
                        'songbase_tune_parameter'
                    ];

                $expectedExternalIds[] =
                    $externalId;

                $variantLyrics =
                    $variantData[
                        'lyrics'
                    ];

                HymnSource::query()
                    ->updateOrCreate(
                        [
                            'hymn_id' =>
                                $hymn->id,

                            'provider' =>
                                HymnSource
                                    ::PROVIDER_SONGBASE,

                            'external_id' =>
                                $externalId,
                        ],
                        [
                            'hymn_variant_id' =>
                                $variant->id,

                            'source_type' =>
                                HymnSource::TYPE_CATALOG,

                            'source_url' =>
                                'https://songbase.life/'
                                . $sourceId
                                . '?tune='
                                . $variantData[
                                    'songbase_tune_parameter'
                                ],

                            'label' =>
                                'Songbase · '
                                . $variantData[
                                    'label'
                                ],

                            'lyrics' =>
                                $variantLyrics,

                            'lyrics_search' =>
                                HymnLyricsNormalizer
                                    ::forSearch(
                                        $variantLyrics
                                    ),

                            'first_line_search' =>
                                HymnLyricsNormalizer
                                    ::firstLineForSearch(
                                        $variantLyrics
                                    ),

                            'lyrics_format' =>
                                HymnSource
                                    ::LYRICS_FORMAT_CHORDED,

                            'lyrics_synced_at' =>
                                $songbaseSource
                                    ->lyrics_synced_at
                                ?? now(),

                            'metadata' => [
                                'variant_type' =>
                                    'tune',

                                'variant_index' =>
                                    $variantData[
                                        'variant_index'
                                    ],

                                'source_heading' =>
                                    $variantData[
                                        'source_heading'
                                    ]
                                    ?? null,
                            ],
                        ]
                    );

                $variantCount++;
            }
        }

        $staleSources =
            HymnSource::query()
                ->where(
                    'provider',
                    HymnSource::PROVIDER_SONGBASE
                )
                ->where(
                    'external_id',
                    'like',
                    '%:tune:%'
                );

        if ($expectedExternalIds === []) {
            $staleSources->delete();
        } else {
            $staleSources
                ->whereNotIn(
                    'external_id',
                    $expectedExternalIds
                )
                ->delete();
        }

        return [
            'hymns_with_tune_headers' =>
                $songbaseSources->count(),

            'variants' =>
                $variantCount,

            'multiple_tune_hymns' =>
                $songbaseSources
                    ->filter(
                        fn (
                            HymnSource $source
                        ): bool =>
                            count(
                                SongbaseTuneParser
                                    ::parse(
                                        (string)
                                            $source
                                                ->lyrics
                                    )
                            ) > 1
                    )
                    ->count(),
        ];
    }
}
