<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                /*
                 * Gospel Contacts use INT UNSIGNED IDs,
                 * matching gospel_contacts.id.
                 */
                $table
                    ->unsignedInteger('gospel_contact_id')
                    ->nullable()
                    ->after('campus_contact_id');

                $table
                    ->foreign(
                        'gospel_contact_id',
                        'meeting_responses_gospel_fk'
                    )
                    ->references('id')
                    ->on('gospel_contacts')
                    ->nullOnDelete();

                /*
                 * One Gospel Contact response per meeting.
                 *
                 * MySQL permits multiple NULL values.
                 */
                $table->unique(
                    [
                        'attendance_session_id',
                        'gospel_contact_id',
                    ],
                    'meeting_response_session_gospel_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'meeting_responses_gospel_fk'
                );

                $table->dropUnique(
                    'meeting_response_session_gospel_unique'
                );

                $table->dropColumn(
                    'gospel_contact_id'
                );
            }
        );
    }
};
