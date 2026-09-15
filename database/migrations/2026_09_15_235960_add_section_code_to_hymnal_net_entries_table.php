<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'hymnal_net_entries',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'section_code',
                        64
                    )
                    ->nullable()
                    ->after(
                        'collection_code'
                    )
                    ->index();
            }
        );

        /*
         * Existing Hymnal.net catalog rows are the
         * Classic Hymns collection.
         */
        DB::table(
            'hymnal_net_entries'
        )
            ->where(
                'collection_code',
                'h'
            )
            ->whereNull(
                'section_code'
            )
            ->update([
                'section_code' =>
                    'classic',
            ]);
    }

    public function down(): void
    {
        Schema::table(
            'hymnal_net_entries',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'section_code',
                ]);

                $table->dropColumn(
                    'section_code'
                );
            }
        );
    }
};
