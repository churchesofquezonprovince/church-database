<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Add the replacement index FIRST.
         *
         * MariaDB currently uses the old Sheet + Person unique
         * index to support the attendance_sheet_id foreign key.
         * It therefore cannot be dropped until another index
         * beginning with attendance_sheet_id exists.
         */
        Schema::table(
            'attendance_participants',
            function (Blueprint $table): void {
                $table->index(
                    [
                        'attendance_sheet_id',
                        'person_id',
                    ],
                    'att_part_sheet_person_idx'
                );
            }
        );

        /*
         * AttendanceParticipant represents a dated membership
         * period, so the same Person may legitimately have more
         * than one period on the same Attendance Sheet.
         */
        Schema::table(
            'attendance_participants',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'attendance_participants_'
                    . 'attendance_sheet_id_person_id_unique'
                );
            }
        );
    }

    public function down(): void
    {
        /*
         * Restore the unique index before removing the replacement
         * index so the attendance_sheet_id foreign key always has
         * a suitable supporting index.
         *
         * Rollback will intentionally fail if the database already
         * contains multiple periods for the same Sheet + Person,
         * because those rows cannot satisfy the old uniqueness rule.
         */
        Schema::table(
            'attendance_participants',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'attendance_sheet_id',
                        'person_id',
                    ],
                    'attendance_participants_'
                    . 'attendance_sheet_id_person_id_unique'
                );
            }
        );

        Schema::table(
            'attendance_participants',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'att_part_sheet_person_idx'
                );
            }
        );
    }
};
