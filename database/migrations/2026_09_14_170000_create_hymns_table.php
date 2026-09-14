<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hymns', function (Blueprint $table): void {
            $table->id();

            $table->string('source', 50)
                ->default('manual')
                ->index();

            $table->string('source_id', 100)
                ->nullable();

            $table->string('title');
            $table->string('language', 100)
                ->nullable()
                ->index();

            $table->string('source_url', 2048)
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamp('last_synced_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['source', 'source_id'],
                'hymns_source_source_id_unique'
            );

            $table->index(
                ['language', 'title'],
                'hymns_language_title_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hymns');
    }
};
