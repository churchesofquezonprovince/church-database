<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE attendance_meeting_responses
             MODIFY response VARCHAR(20) NULL'
        );
    }

    public function down(): void
    {
        DB::table('attendance_meeting_responses')
            ->whereNull('response')
            ->update([
                'response' => 'no',
            ]);

        DB::statement(
            'ALTER TABLE attendance_meeting_responses
             MODIFY response VARCHAR(20) NOT NULL'
        );
    }
};
