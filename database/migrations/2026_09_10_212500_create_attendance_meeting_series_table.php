<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * A Meeting Series is the permanent public identity
         * of a recurring meeting.
         *
         * It deliberately outlives individual Attendance Sheets.
         *
         * Example:
         *
         * campus-meeting-sc-lucban
         *   ├── S.Y. 2026-2027 Semester 1 Sheet
         *   ├── S.Y. 2026-2027 Semester 2 Sheet
         *   ├── S.Y. 2027-2028 Semester 1 Sheet
         *   └── ...
         */
        Schema::create(
            'attendance_meeting_series',
            function (Blueprint $table): void {
                $table->increments('id');

                $table->string('name');

                /*
                 * Stable public identity.
                 *
                 * Once shared or printed as a QR code,
                 * changing this value should be deliberate.
                 */
                $table
                    ->string('public_slug')
                    ->unique();

                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->index();

                $table
                    ->text('remarks')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::table(
            'attendance_sheets',
            function (Blueprint $table): void {
                /*
                 * Attendance Sheet IDs and related attendance
                 * IDs use INT UNSIGNED, so keep this FK aligned.
                 */
                $table
                    ->unsignedInteger(
                        'attendance_meeting_series_id'
                    )
                    ->nullable()
                    ->after('id')
                    ->index();

                /*
                 * Removing/retiring a Meeting Series must never
                 * delete historical Attendance Sheets.
                 */
                $table
                    ->foreign(
                        'attendance_meeting_series_id',
                        'attendance_sheets_meeting_series_fk'
                    )
                    ->references('id')
                    ->on('attendance_meeting_series')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'attendance_sheets',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'attendance_sheets_meeting_series_fk'
                );

                $table->dropColumn(
                    'attendance_meeting_series_id'
                );
            }
        );

        Schema::dropIfExists(
            'attendance_meeting_series'
        );
    }
};
