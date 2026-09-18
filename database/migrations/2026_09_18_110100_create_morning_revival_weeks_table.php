<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'morning_revival_weeks',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'morning_revival_publication_id'
                );

                $table->unsignedTinyInteger(
                    'week_number'
                );

                $table->string(
                    'title',
                    1000
                );

                /*
                 * Day 1 / Monday of this week.
                 */
                $table->date(
                    'start_date'
                );

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();

                $table->foreign(
                    'morning_revival_publication_id',
                    'mr_week_publication_fk'
                )
                    ->references('id')
                    ->on(
                        'morning_revival_publications'
                    )
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'morning_revival_publication_id',
                        'week_number',
                    ],
                    'mr_week_publication_number_unique'
                );

                $table->index(
                    [
                        'start_date',
                        'is_active',
                    ],
                    'mr_week_date_active_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'morning_revival_weeks'
        );
    }
};
