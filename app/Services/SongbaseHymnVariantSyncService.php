<?php

namespace App\Services;

use App\Models\HymnSource;
use App\Models\HymnVariant;
use App\Support\HymnLyricsNormalizer;
use App\Support\SongbaseTuneParser;

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
                ->where(
                    'lyrics',
                    'like',
                    '%### Tune%'
                )
                ->get();

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
                $variant =
                    HymnVariant::query()
                        ->updateOrCreate(
                            [
                                'hymn_id' =>
                                    $hymn->id,

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
                            ],
                            [
                                'label' =>
                                    $variantData[
                                        'label'
                                    ],

                                'title_override' =>
                                    null,

                                'metadata' => [
                                    'songbase_tune_parameter' =>
                                        $variantData[
                                            'songbase_tune_parameter'
                                        ],

                                    'heading_number' =>
                                        $variantData[
                                            'heading_number'
                                        ],
                                ],

                                'sort_order' =>
                                    $variantData[
                                        'variant_index'
                                    ],

                                'is_active' =>
                                    true,
                            ]
                        );

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
