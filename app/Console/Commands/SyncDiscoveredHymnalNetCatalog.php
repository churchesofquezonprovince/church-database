<?php

namespace App\Console\Commands;

use App\Services\HymnalNetCatalogSyncService;
use App\Support\HymnalNetCollectionCatalog;
use Illuminate\Console\Command;
use Throwable;

class SyncDiscoveredHymnalNetCatalog extends Command
{
    protected $signature =
        'hymnal-net:sync-discovered
        {section=all : classic, alternate_tunes, new_tunes, new_songs, children, or all}
        {--delay=500 : Delay between hymn requests in milliseconds}
        {--limit=0 : Maximum rows per section; 0 means all}
        {--resync : Include rows already fetched}';

    protected $description =
        'Fetch and match Hymnal.net rows discovered '
        . 'from the official song indexes';

    public function handle(
        HymnalNetCatalogSyncService $service
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

            $limit =
                max(
                    0,
                    (int)
                        $this->option(
                            'limit'
                        )
                );

            $resync =
                (bool)
                    $this->option(
                        'resync'
                    );

            foreach (
                $sections
                as $section
            ) {
                $config =
                    HymnalNetCollectionCatalog
                        ::section(
                            $section
                        );

                $this->newLine();

                $this->info(
                    'Synchronizing discovered '
                    . $config['label']
                    . ' entries...'
                );

                $result =
                    $service->syncDiscovered(
                        $section,
                        $delay,
                        $resync,
                        $limit
                    );

                $this->table(
                    [
                        'Result',
                        'Count',
                    ],
                    [
                        [
                            'Processed',
                            $result['processed'],
                        ],
                        [
                            'Fetched',
                            $result['fetched'],
                        ],
                        [
                            'Valid',
                            $result['valid'],
                        ],
                        [
                            'Linked',
                            $result['linked'],
                        ],
                        [
                            'Variant review',
                            $result[
                                'variant_review'
                            ],
                        ],
                        [
                            'Unmatched',
                            $result['unmatched'],
                        ],
                        [
                            'Ambiguous',
                            $result['ambiguous'],
                        ],
                        [
                            'Conflicts',
                            $result['conflicts'],
                        ],
                        [
                            'Invalid',
                            $result['invalid'],
                        ],
                        [
                            'Failed',
                            $result['failed'],
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
