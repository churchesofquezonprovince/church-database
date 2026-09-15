<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'hymn_sources',
            function (Blueprint $table): void {
                $table
                    ->foreignId('hymn_variant_id')
                    ->nullable()
                    ->after('hymn_id')
                    ->constrained('hymn_variants')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'hymn_sources',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'hymn_variant_id'
                );
            }
        );
    }
};
