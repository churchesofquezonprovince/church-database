<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'hymn_book_entries',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('hymn_book_id')
                    ->constrained('hymn_books')
                    ->cascadeOnDelete();

                $table->foreignId('hymn_id')
                    ->constrained('hymns')
                    ->cascadeOnDelete();

                $table->string('number', 50);

                $table->timestamps();

                /*
                 * Songbase can assign the same displayed
                 * hymn number to more than one song in a
                 * book, so book + number is not unique.
                 */
                $table->unique(
                    ['hymn_book_id', 'hymn_id'],
                    'hymn_book_entries_book_hymn_unique'
                );

                $table->index(
                    ['hymn_book_id', 'number'],
                    'hymn_book_entries_book_number_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('hymn_book_entries');
    }
};
