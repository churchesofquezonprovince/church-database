<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sheets', function (Blueprint $table): void {
            $table->string('schedule_type', 32)
                ->default('recurring')
                ->after('is_one_time')
                ->index();

            $table->time('end_time')
                ->nullable()
                ->after('meeting_time');
        });

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->time('session_end_time')
                ->nullable()
                ->after('session_time');
        });

        DB::table('attendance_sheets')
            ->where('is_one_time', true)
            ->update([
                'schedule_type' => 'one_time',
            ]);

        DB::table('attendance_sheets')
            ->where('is_one_time', false)
            ->update([
                'schedule_type' => 'recurring',
            ]);
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropColumn('session_end_time');
        });

        Schema::table('attendance_sheets', function (Blueprint $table): void {
            $table->dropIndex(['schedule_type']);

            $table->dropColumn([
                'schedule_type',
                'end_time',
            ]);
        });
    }
};
