<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'hymnal_net_entries',
            function (Blueprint $table): void {
                $table
                    ->foreignId(
                        'matched_hymn_variant_id'
                    )
                    ->nullable()
                    ->after('matched_hymn_id')
                    ->constrained('hymn_variants')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'hymnal_net_entries',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'matched_hymn_variant_id'
                );
            }
        );
    }
};
