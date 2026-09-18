<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'morning_revival_publications',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Example:
                 * 2026 International Memorial Day
                 * Blending Conference
                 */
                $table->string(
                    'source_title',
                    255
                );

                /*
                 * Example:
                 * The Great Need for a New Revival
                 */
                $table->string(
                    'general_subject',
                    500
                );

                /*
                 * Day 1 of Week 1.
                 *
                 * Morning Revival days run Monday
                 * through Saturday. Lord's Day is
                 * intentionally not represented.
                 */
                $table->date(
                    'start_date'
                );

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();

                $table->index([
                    'start_date',
                    'is_active',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'morning_revival_publications'
        );
    }
};
