<?php

namespace App\Console\Commands;

use App\Services\SongbaseHymnSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncSongbaseHymns extends Command
{
    protected $signature =
        'hymns:sync-songbase';

    protected $description =
        'Synchronize the local Hymn Catalog with Songbase';

    public function handle(
        SongbaseHymnSyncService $sync
    ): int {
        $this->info(
            'Synchronizing Songbase Hymn Catalog...'
        );

        try {
            $result =
                $sync->fullSync();
        } catch (Throwable $e) {
            $this->error(
                'Songbase sync failed: '
                . $e->getMessage()
            );

            return self::FAILURE;
        }

        $this->newLine();

        $this->table(
            [
                'Item',
                'Count',
            ],
            [
                [
                    'Songs',
                    $result[
                        'songs_received'
                    ],
                ],
                [
                    'Skipped songs',
                    $result[
                        'songs_skipped'
                    ],
                ],
                [
                    'Books',
                    $result[
                        'books_received'
                    ],
                ],
                [
                    'Book entries',
                    $result[
                        'book_entries'
                    ],
                ],
                [
                    'Skipped entries',
                    $result[
                        'entries_skipped'
                    ],
                ],
            ]
        );

        $this->newLine();

        $this->info(
            'Songbase Hymn Catalog synchronized.'
        );

        return self::SUCCESS;
    }
}
