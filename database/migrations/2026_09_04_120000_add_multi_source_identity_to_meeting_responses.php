<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * person_id was originally required and cascaded
         * on delete.
         *
         * Multi-source RSVP responses need it nullable.
         * Preserve RSVP history if a Person is later deleted.
         */
        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'person_id',
                ]);
            }
        );

        DB::statement(
            'ALTER TABLE attendance_meeting_responses
             MODIFY person_id INT UNSIGNED NULL'
        );

        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table
                    ->string('respondent_type', 20)
                    ->default('person')
                    ->after('attendance_session_id')
                    ->index('meeting_responses_type_index');

                $table
                    ->unsignedInteger('campus_contact_id')
                    ->nullable()
                    ->after('person_id');

                $table
                    ->string('guest_name')
                    ->nullable()
                    ->after('campus_contact_id');

                /*
                 * Snapshot of the displayed name at the time
                 * the response was submitted.
                 */
                $table
                    ->string('respondent_name')
                    ->nullable()
                    ->after('guest_name');

                $table
                    ->foreign(
                        'person_id',
                        'meeting_responses_person_fk'
                    )
                    ->references('id')
                    ->on('persons')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'campus_contact_id',
                        'meeting_responses_campus_fk'
                    )
                    ->references('id')
                    ->on('campus_contacts')
                    ->nullOnDelete();

                /*
                 * One Campus Contact response per meeting.
                 *
                 * MySQL permits multiple NULL values here.
                 */
                $table->unique(
                    [
                        'attendance_session_id',
                        'campus_contact_id',
                    ],
                    'meeting_response_session_campus_unique'
                );
            }
        );

        /*
         * Backfill names for any Phase 26C Person responses
         * that already exist.
         */
        DB::statement(
            "
            UPDATE attendance_meeting_responses AS responses
            INNER JOIN persons
                ON persons.id = responses.person_id
            SET
                responses.respondent_type = 'person',
                responses.respondent_name = TRIM(
                    CONCAT_WS(
                        ' ',
                        CASE
                            WHEN persons.lastname IS NULL
                                OR persons.lastname = ''
                            THEN NULL
                            ELSE CONCAT(persons.lastname, ',')
                        END,
                        persons.firstname,
                        persons.middlename,
                        persons.suffix
                    )
                )
            WHERE responses.person_id IS NOT NULL
            "
        );
    }

    public function down(): void
    {
        /*
         * Old schema cannot represent Campus/Guest responses.
         */
        DB::table('attendance_meeting_responses')
            ->whereNull('person_id')
            ->delete();

        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'meeting_responses_campus_fk'
                );

                $table->dropForeign(
                    'meeting_responses_person_fk'
                );

                $table->dropUnique(
                    'meeting_response_session_campus_unique'
                );

                $table->dropIndex(
                    'meeting_responses_type_index'
                );

                $table->dropColumn([
                    'respondent_type',
                    'campus_contact_id',
                    'guest_name',
                    'respondent_name',
                ]);
            }
        );

        DB::statement(
            'ALTER TABLE attendance_meeting_responses
             MODIFY person_id INT UNSIGNED NOT NULL'
        );

        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table
                    ->foreign('person_id')
                    ->references('id')
                    ->on('persons')
                    ->cascadeOnDelete();
            }
        );
    }
};
