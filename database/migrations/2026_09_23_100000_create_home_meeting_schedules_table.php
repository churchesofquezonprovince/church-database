<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'home_meeting_schedules',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * The schedule belongs to a real Locality.
                 *
                 * The current schedule is for Lucban, but this
                 * keeps the feature reusable for other Localities.
                 */
                $table->foreignId('locality_id')
                    ->constrained('localities')
                    ->restrictOnDelete();

                /*
                 * These are nullable because several entries in
                 * the existing Lucban schedule are not yet linked
                 * to Household / Person records.
                 */
                /*
                 * Legacy Household and Person primary keys use
                 * INT UNSIGNED rather than Laravel's newer
                 * BIGINT UNSIGNED foreignId convention.
                 */
                $table->unsignedInteger('household_id')
                    ->nullable();

                $table->foreign(
                    'household_id'
                )
                    ->references('id')
                    ->on('households')
                    ->nullOnDelete();

                $table->unsignedInteger(
                    'contact_person_id'
                )
                    ->nullable();

                $table->foreign(
                    'contact_person_id'
                )
                    ->references('id')
                    ->on('persons')
                    ->nullOnDelete();

                /*
                 * Preserve the actual schedule label even before
                 * a database Person or Household has been linked.
                 *
                 * Examples:
                 *   Kavin Arabe
                 *   Pavino Family
                 *   Claudette Raton & Chariz
                 */
                $table->string('display_name');

                /*
                 * Examples:
                 *   Katipunan at Burol
                 *   Urban Phase 2 at Burol
                 *   Burol
                 *   Umban at Burol
                 */
                $table->string('area_name')
                    ->nullable();

                /*
                 * Carbon weekday convention:
                 *
                 * 0 = Lord's Day / Sunday
                 * 1 = Monday
                 * 2 = Tuesday
                 * 3 = Wednesday
                 * 4 = Thursday
                 * 5 = Friday
                 * 6 = Saturday
                 */
                $table->unsignedTinyInteger(
                    'day_of_week'
                );

                $table->time('meeting_time');

                $table->boolean('is_active')
                    ->default(true)
                    ->index();

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->text('notes')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'locality_id',
                        'day_of_week',
                        'meeting_time',
                    ],
                    'home_meeting_locality_day_time_idx'
                );

                $table->index(
                    [
                        'locality_id',
                        'sort_order',
                    ],
                    'home_meeting_locality_sort_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'home_meeting_schedules'
        );
    }
};
