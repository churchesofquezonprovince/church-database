<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                /*
                 * Where this RSVP originally entered the system.
                 *
                 * Unlike respondent_type, this value does not
                 * change when Guest → Campus → Person.
                 */
                $table
                    ->string('original_source', 20)
                    ->default('person')
                    ->after('respondent_type')
                    ->index(
                        'meeting_responses_original_source_index'
                    );

                /*
                 * Optional information voluntarily supplied by
                 * a manually-entered Guest.
                 *
                 * This remains available even after promotion
                 * to Campus or People.
                 */
                $table
                    ->json('guest_profile')
                    ->nullable()
                    ->after('guest_name');
            }
        );

        /*
         * Existing Phase 26C.1 responses already have the
         * correct current source, so use that as their
         * historical original source.
         */
        DB::statement(
            '
            UPDATE attendance_meeting_responses
            SET original_source = respondent_type
            '
        );
    }

    public function down(): void
    {
        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'meeting_responses_original_source_index'
                );

                $table->dropColumn([
                    'original_source',
                    'guest_profile',
                ]);
            }
        );
    }
};
