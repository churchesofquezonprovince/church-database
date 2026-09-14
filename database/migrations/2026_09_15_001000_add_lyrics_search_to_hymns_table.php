<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'hymns',
            function (Blueprint $table): void {
                $table
                    ->longText('lyrics_search')
                    ->nullable()
                    ->after('lyrics');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'hymns',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'lyrics_search'
                );
            }
        );
    }
};
