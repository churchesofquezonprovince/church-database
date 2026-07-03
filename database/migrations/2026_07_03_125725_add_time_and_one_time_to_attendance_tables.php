<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sheets', function (Blueprint $table): void {
            $table->time('meeting_time')->nullable()->after('meeting_day');
            $table->boolean('is_one_time')->default(false)->after('meeting_time')->index();
        });

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->time('session_time')->nullable()->after('session_date');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropColumn('session_time');
        });

        Schema::table('attendance_sheets', function (Blueprint $table): void {
            $table->dropColumn(['meeting_time', 'is_one_time']);
        });
    }
};
