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
                 * attendance_sheets.id is legacy INT UNSIGNED.
                 *
                 * This is the parent/series-level Attendance link.
                 * attendance_session_id remains the exact
                 * occurrence-level link.
                 */
                $table
                    ->unsignedInteger(
                        'attendance_sheet_id'
                    )
                    ->nullable()
                    ->after(
                        'attendance_session_id'
                    );

                /*
                 * One Attendance Sheet belongs to at most
                 * one Campus Activity / Campus schedule.
                 */
                $table->unique(
                    'attendance_sheet_id',
                    'campus_work_activities_attendance_sheet_unique'
                );

                $table
                    ->foreign(
                        'attendance_sheet_id',
                        'campus_work_activities_attendance_sheet_fk'
                    )
                    ->references('id')
                    ->on('attendance_sheets')
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
                    'campus_work_activities_attendance_sheet_fk'
                );

                $table->dropUnique(
                    'campus_work_activities_attendance_sheet_unique'
                );

                $table->dropColumn(
                    'attendance_sheet_id'
                );
            }
        );
    }
};
