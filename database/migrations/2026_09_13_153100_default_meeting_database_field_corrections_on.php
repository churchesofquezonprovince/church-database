<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Change the default only.
         *
         * Existing question rows keep their current
         * allow_correction value.
         */
        DB::statement(
            'ALTER TABLE attendance_meeting_form_questions
             MODIFY allow_correction TINYINT(1)
             NOT NULL DEFAULT 1'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE attendance_meeting_form_questions
             MODIFY allow_correction TINYINT(1)
             NOT NULL DEFAULT 0'
        );
    }
};
