<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hymn_books', function (Blueprint $table): void {
            $table->id();

            $table->string('source', 50)
                ->default('manual')
                ->index();

            $table->string('source_id', 100)
                ->nullable();

            $table->string('name');
            $table->string('slug')
                ->nullable();

            $table->string('language', 100)
                ->nullable()
                ->index();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamp('last_synced_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['source', 'source_id'],
                'hymn_books_source_source_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hymn_books');
    }
};
