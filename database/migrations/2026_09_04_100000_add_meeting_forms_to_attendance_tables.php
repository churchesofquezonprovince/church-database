<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * -------------------------------------------------------
         * Attendance Sheet
         * -------------------------------------------------------
         *
         * disabled
         * normal
         *
         * google_form can be added later without changing
         * the database structure because this is a string.
         */
        Schema::table('attendance_sheets', function (Blueprint $table): void {
            $table
                ->string('meeting_form_type', 50)
                ->default('disabled')
                ->after('is_one_time')
                ->index();
        });

        /*
         * -------------------------------------------------------
         * Attendance Session
         * -------------------------------------------------------
         *
         * Every meeting date can have its own public URL:
         *
         * /meeting/8-15-26-churchmeeting
         */
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table
                ->string('public_slug')
                ->nullable()
                ->after('title')
                ->unique();
        });

        /*
         * -------------------------------------------------------
         * Public Meeting Responses
         * -------------------------------------------------------
         *
         * RSVP information is deliberately separate from
         * attendance_records.
         *
         * Saying "Yes, I'll attend" is NOT the same as being
         * physically marked present.
         */
        Schema::create('attendance_meeting_responses', function (Blueprint $table): void {
            $table->id();

            $table->unsignedInteger('attendance_session_id');
            $table->unsignedInteger('person_id');

            $table
                ->string('response', 20)
                ->index();

            $table
                ->timestamp('responded_at')
                ->nullable();

            $table->timestamps();

            /*
             * One response per person per meeting.
             *
             * Re-submitting the form later will update the
             * existing response instead of creating duplicates.
             */
$table->unique(
    [
        'attendance_session_id',
        'person_id',
    ],
    'meeting_response_session_person_unique'
);

            $table
                ->foreign('attendance_session_id')
                ->references('id')
                ->on('attendance_sessions')
                ->cascadeOnDelete();

            $table
                ->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_meeting_responses'
        );

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropUnique([
                'public_slug',
            ]);

            $table->dropColumn(
                'public_slug'
            );
        });

        Schema::table('attendance_sheets', function (Blueprint $table): void {
            $table->dropColumn(
                'meeting_form_type'
            );
        });
    }
};
