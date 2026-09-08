<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'campus_work_activities',
            function (Blueprint $table): void {
                /*
                 * attendance_sessions.id is legacy INT UNSIGNED,
                 * so this must deliberately NOT use foreignId().
                 *
                 * One Campus Activity may generate at most one
                 * Attendance Session.
                 */
                $table
                    ->unsignedInteger(
                        'attendance_session_id'
                    )
                    ->nullable()
                    ->after('locality_id');

                $table->unique(
                    'attendance_session_id',
                    'campus_work_activities_attendance_session_unique'
                );

                $table
                    ->foreign(
                        'attendance_session_id',
                        'campus_work_activities_attendance_session_fk'
                    )
                    ->references('id')
                    ->on('attendance_sessions')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'campus_work_activities',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'campus_work_activities_attendance_session_fk'
                );

                $table->dropUnique(
                    'campus_work_activities_attendance_session_unique'
                );

                $table->dropColumn(
                    'attendance_session_id'
                );
            }
        );
    }
};
