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
        Schema::table(
            'hymns',
            function (Blueprint $table): void {
                $table
                    ->text('first_line_search')
                    ->nullable()
                    ->after('lyrics_search');
            }
        );

        DB::table('hymns')
            ->select([
                'id',
                'lyrics',
            ])
            ->whereNotNull('lyrics')
            ->chunkById(
                250,
                function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('hymns')
                            ->where(
                                'id',
                                $row->id
                            )
                            ->update([
                                'first_line_search' =>
                                    HymnLyricsNormalizer
                                        ::firstLineForSearch(
                                            $row->lyrics
                                        ),
                            ]);
                    }
                }
            );
    }

    public function down(): void
    {
        Schema::table(
            'hymns',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'first_line_search'
                );
            }
        );
    }
};
