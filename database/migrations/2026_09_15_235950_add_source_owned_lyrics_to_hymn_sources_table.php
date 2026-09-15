<?php

use App\Support\HymnLyricsNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * MySQL can commit ALTER TABLE statements even
         * when a later statement in the same migration
         * fails.
         *
         * Therefore every column addition is guarded so
         * this migration can safely be rerun after a
         * partially-completed attempt.
         */
        if (! Schema::hasColumn(
            'hymn_sources',
            'lyrics'
        )) {
            Schema::table(
                'hymn_sources',
                function (Blueprint $table): void {
                    $table
                        ->longText('lyrics')
                        ->nullable()
                        ->after('label');
                }
            );
        }

        if (! Schema::hasColumn(
            'hymn_sources',
            'lyrics_search'
        )) {
            Schema::table(
                'hymn_sources',
                function (Blueprint $table): void {
                    $table
                        ->longText('lyrics_search')
                        ->nullable()
                        ->after('lyrics');
                }
            );
        }

        if (! Schema::hasColumn(
            'hymn_sources',
            'first_line_search'
        )) {
            Schema::table(
                'hymn_sources',
                function (Blueprint $table): void {
                    $table
                        ->text('first_line_search')
                        ->nullable()
                        ->after('lyrics_search');
                }
            );
        }

        if (! Schema::hasColumn(
            'hymn_sources',
            'lyrics_format'
        )) {
            Schema::table(
                'hymn_sources',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'lyrics_format',
                            30
                        )
                        ->nullable()
                        ->after(
                            'first_line_search'
                        );
                }
            );
        }

        if (! Schema::hasColumn(
            'hymn_sources',
            'lyrics_synced_at'
        )) {
            Schema::table(
                'hymn_sources',
                function (Blueprint $table): void {
                    $table
                        ->timestamp(
                            'lyrics_synced_at'
                        )
                        ->nullable()
                        ->after('lyrics_format');
                }
            );
        }

        /*
         * PASS 1
         *
         * Preserve existing hymn-level Songbase lyrics.
         *
         * Do NOT use upsert() here. An upsert keyed only
         * by hymn_sources.id is still compiled as an
         * INSERT ... ON DUPLICATE KEY UPDATE, which means
         * MySQL requires all non-null insert columns such
         * as hymn_id.
         *
         * These are existing rows, so normal UPDATEs are
         * the correct operation.
         */
        DB::table('hymn_sources')
            ->join(
                'hymns',
                'hymns.id',
                '=',
                'hymn_sources.hymn_id'
            )
            ->select([
                'hymn_sources.id as source_row_id',
                'hymns.lyrics',
                'hymns.last_synced_at',
                'hymn_sources.updated_at as source_updated_at',
            ])
            ->where(
                'hymn_sources.provider',
                'songbase'
            )
            ->whereNull(
                'hymn_sources.hymn_variant_id'
            )
            ->whereNotNull(
                'hymns.lyrics'
            )
            ->chunkById(
                250,
                function ($rows): void {
                    DB::transaction(
                        function () use ($rows): void {
                            foreach ($rows as $row) {
                                $lyrics =
                                    (string)
                                        $row->lyrics;

                                DB::table(
                                    'hymn_sources'
                                )
                                    ->where(
                                        'id',
                                        (int)
                                            $row
                                                ->source_row_id
                                    )
                                    ->update([
                                        'lyrics' =>
                                            $lyrics,

                                        'lyrics_search' =>
                                            HymnLyricsNormalizer
                                                ::forSearch(
                                                    $lyrics
                                                ),

                                        'first_line_search' =>
                                            HymnLyricsNormalizer
                                                ::firstLineForSearch(
                                                    $lyrics
                                                ),

                                        'lyrics_format' =>
                                            'chorded',

                                        'lyrics_synced_at' =>
                                            $row
                                                ->last_synced_at
                                            ?? $row
                                                ->source_updated_at,
                                    ]);
                            }
                        }
                    );
                },
                'hymn_sources.id',
                'source_row_id'
            );

        /*
         * PASS 2
         *
         * Preserve Songbase Tune / variant lyrics in
         * their corresponding provider source rows.
         */
        DB::table('hymn_sources')
            ->join(
                'hymn_variants',
                'hymn_variants.id',
                '=',
                'hymn_sources.hymn_variant_id'
            )
            ->select([
                'hymn_sources.id as source_row_id',
                'hymn_variants.lyrics',
                'hymn_sources.updated_at as source_updated_at',
            ])
            ->where(
                'hymn_sources.provider',
                'songbase'
            )
            ->whereNotNull(
                'hymn_sources.hymn_variant_id'
            )
            ->whereNotNull(
                'hymn_variants.lyrics'
            )
            ->chunkById(
                250,
                function ($rows): void {
                    DB::transaction(
                        function () use ($rows): void {
                            foreach ($rows as $row) {
                                $lyrics =
                                    (string)
                                        $row->lyrics;

                                DB::table(
                                    'hymn_sources'
                                )
                                    ->where(
                                        'id',
                                        (int)
                                            $row
                                                ->source_row_id
                                    )
                                    ->update([
                                        'lyrics' =>
                                            $lyrics,

                                        'lyrics_search' =>
                                            HymnLyricsNormalizer
                                                ::forSearch(
                                                    $lyrics
                                                ),

                                        'first_line_search' =>
                                            HymnLyricsNormalizer
                                                ::firstLineForSearch(
                                                    $lyrics
                                                ),

                                        'lyrics_format' =>
                                            'chorded',

                                        'lyrics_synced_at' =>
                                            $row
                                                ->source_updated_at,
                                    ]);
                            }
                        }
                    );
                },
                'hymn_sources.id',
                'source_row_id'
            );
    }

    public function down(): void
    {
        $columns = [];

        foreach ([
            'lyrics',
            'lyrics_search',
            'first_line_search',
            'lyrics_format',
            'lyrics_synced_at',
        ] as $column) {
            if (
                Schema::hasColumn(
                    'hymn_sources',
                    $column
                )
            ) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            Schema::table(
                'hymn_sources',
                function (
                    Blueprint $table
                ) use ($columns): void {
                    $table->dropColumn(
                        $columns
                    );
                }
            );
        }
    }
};
