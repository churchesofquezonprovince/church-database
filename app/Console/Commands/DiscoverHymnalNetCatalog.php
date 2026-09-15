<?php

namespace App\Console\Commands;

use App\Services\HymnalNetCatalogDiscoveryService;
use App\Support\HymnalNetCollectionCatalog;
use Illuminate\Console\Command;
use Throwable;

class DiscoverHymnalNetCatalog extends Command
{
    protected $signature =
        'hymnal-net:discover
        {section=all : classic, new_tunes, new_songs, children, or all}
        {--delay=250 : Delay between index requests in milliseconds}';

    protected $description =
        'Discover Hymnal.net songs and physical route '
        . 'families from the official song indexes';

    public function handle(
        HymnalNetCatalogDiscoveryService $service
    ): int {
        try {
            $requested =
                strtolower(
                    trim(
                        (string)
                            $this->argument(
                                'section'
                            )
                    )
                );

            $sections =
                $requested === 'all'
                    ? array_keys(
                        HymnalNetCollectionCatalog
                            ::sections()
                    )
                    : [
                        $requested,
                    ];

            $delay =
                max(
                    0,
                    (int)
                        $this->option(
                            'delay'
                        )
                );

            foreach (
                $sections
                as $section
            ) {
                $result =
                    $service->discover(
                        $section,
                        $delay
                    );

                $this->newLine();

                $this->info(
                    $result['label']
                );

                $this->table(
                    [
                        'Metric',
                        'Value',
                    ],
                    [
                        [
                            'Index pages',
                            $result[
                                'index_pages'
                            ],
                        ],
                        [
                            'Songs discovered',
                            $result[
                                'discovered'
                            ],
                        ],
                        [
                            'New rows',
                            $result[
                                'created'
                            ],
                        ],
                        [
                            'Existing rows',
                            $result[
                                'existing'
                            ],
                        ],
                        [
                            'Physical routes',
                            implode(
                                ', ',
                                $result[
                                    'routes'
                                ]
                            ),
                        ],
                    ]
                );
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
