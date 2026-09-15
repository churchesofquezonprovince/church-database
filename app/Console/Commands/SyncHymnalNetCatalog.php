<?php

namespace App\Console\Commands;

use App\Services\HymnalNetCatalogSyncService;
use App\Support\HymnalNetCollectionCatalog;
use Illuminate\Console\Command;
use Throwable;

class SyncHymnalNetCatalog extends Command
{
    protected $signature =
        'hymnal-net:sync
        {collection=h : Hymnal.net collection code}
        {--from=1 : First hymn number}
        {--to= : Last hymn number}
        {--delay=500 : Delay between requests in milliseconds}
        {--sparse : Sync one explicitly known ID outside the safe sequential range}';

    protected $description =
        'Synchronize Hymnal.net catalog metadata '
        . 'and match it to canonical CoQP Hymns';

    public function handle(
        HymnalNetCatalogSyncService $service
    ): int {
        try {
            $collection =
                strtolower(
                    trim(
                        (string)
                            $this->argument(
                                'collection'
                            )
                    )
                );

            $config =
                HymnalNetCollectionCatalog::get(
                    $collection
                );

            $from =
                (int) $this->option(
                    'from'
                );

            $toOption =
                $this->option(
                    'to'
                );

            $to =
                filled($toOption)
                    ? (int) $toOption
                    : (int) $config[
                        'max_number'
                    ];

            $delay =
                max(
                    0,
                    (int) $this->option(
                        'delay'
                    )
                );

            $sparse =
                (bool) $this->option(
                    'sparse'
                );

            $this->info(
                sprintf(
                    'Synchronizing Hymnal.net %s %d-%d...',
                    $collection,
                    $from,
                    $to
                )
            );

            $this->line(
                'Metadata only: title, number, '
                . 'URL, and matching information.'
            );

            $result =
                $service->sync(
                    $collection,
                    $from,
                    $to,
                    $delay,
                    $sparse
                );

            $this->newLine();

            $this->table(
                [
                    'Result',
                    'Count',
                ],
                [
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
