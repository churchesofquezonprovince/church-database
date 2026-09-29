<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'conference_participant_fields',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'conference_event_id'
                );

                $table->string(
                    'name',
                    100
                );

                $table->string(
                    'field_type',
                    30
                );

                $table->boolean(
                    'is_required'
                )->default(false);

                /*
                 * Used by dropdown fields.
                 *
                 * Stored as JSON text rather than fixed
                 * schema so future field types can reuse it.
                 */
                $table->text(
                    'options_json'
                )->nullable();

                $table->unsignedInteger(
                    'sort_order'
                )->default(0);

                $table->timestamps();

                $table->unique(
                    [
                        'conference_event_id',
                        'name',
                    ],
                    'conference_field_name_unique'
                );

                $table->index(
                    [
                        'conference_event_id',
                        'sort_order',
                    ],
                    'conference_field_order_idx'
                );

                $table->foreign(
                    'conference_event_id'
                )
                    ->references('id')
                    ->on('conference_events')
                    ->cascadeOnDelete();
            }
        );


        Schema::create(
            'conference_participant_field_values',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'conference_participant_field_id'
                );

                /*
                 * Same attendee-key convention already used
                 * by Conference & Blending:
                 *
                 * positive = Person ID
                 * negative = attendance_guest ID
                 *
                 * This deliberately avoids duplicating
                 * identity columns for every custom field.
                 */
                $table->bigInteger(
                    'attendee_key'
                );

                /*
                 * NULL means:
                 *   unknown / not entered
                 *
                 * JSON allows:
                 *   checkbox -> true / false
                 *   text     -> string
                 *   number   -> number
                 *   dropdown -> string
                 *   date     -> YYYY-MM-DD string
                 *
                 * and leaves room for future field types.
                 */
                $table->text(
                    'value_json'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'conference_participant_field_id',
                        'attendee_key',
                    ],
                    'conference_field_attendee_unique'
                );

                $table->foreign(
                    'conference_participant_field_id',
                    'conference_field_value_field_fk'
                )
                    ->references('id')
                    ->on('conference_participant_fields')
                    ->cascadeOnDelete();
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'conference_participant_field_values'
        );

        Schema::dropIfExists(
            'conference_participant_fields'
        );
    }
};
