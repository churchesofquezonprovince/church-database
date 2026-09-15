<?php

namespace App\Services;

use App\Models\Hymn;
use App\Models\HymnSource;
use App\Models\HymnVariant;
use App\Support\SongbaseTuneParser;

class SongbaseHymnVariantSyncService
{
    public function syncAllLocal(): array
    {
        HymnVariant::query()
            ->where(
                'source',
                'songbase'
            )
            ->where(
                'variant_type',
                'tune'
            )
            ->update([
                'is_active' => false,
            ]);

        $hymns =
            Hymn::query()
                ->where(
                    'source',
                    'songbase'
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

        foreach ($hymns as $hymn) {
            $variants =
                SongbaseTuneParser::parse(
                    $hymn->lyrics
                );

            foreach ($variants as $variantData) {
                $variant =
                    HymnVariant::query()
                        ->updateOrCreate(
                            [
                                'hymn_id' =>
                                    $hymn->id,

                                'source' =>
                                    'songbase',

                                'source_id' =>
                                    $hymn->source_id,

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

                                'lyrics' =>
                                    $variantData[
                                        'lyrics'
                                    ],

                                'lyrics_search' =>
                                    $variantData[
                                        'lyrics_search'
                                    ],

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
                    $hymn->source_id
                    . ':tune:'
                    . $variantData[
                        'songbase_tune_parameter'
                    ];

                $expectedExternalIds[] =
                    $externalId;

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
                                . $hymn->source_id
                                . '?tune='
                                . $variantData[
                                    'songbase_tune_parameter'
                                ],

                            'label' =>
                                'Songbase · '
                                . $variantData[
                                    'label'
                                ],

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
                $hymns->count(),

            'variants' =>
                $variantCount,

            'multiple_tune_hymns' =>
                $hymns
                    ->filter(
                        fn (Hymn $hymn): bool =>
                            count(
                                SongbaseTuneParser::parse(
                                    $hymn->lyrics
                                )
                            ) > 1
                    )
                    ->count(),
        ];
    }
}
