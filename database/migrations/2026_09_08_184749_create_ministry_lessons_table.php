<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministry_lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ministry_book_id')
                ->constrained('ministry_books')
                ->cascadeOnDelete();

            $table->string('code', 50);

            $table->string('title', 255)
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->unique(
                ['ministry_book_id', 'code'],
                'ministry_lessons_book_code_unique'
            );

            $table->index(
                ['ministry_book_id', 'sort_order'],
                'ministry_lessons_book_sort_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_lessons');
    }
};
