<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            if (! Schema::hasColumn('schedules', 'google_sync_status')) {
                $table->string('google_sync_status')->default('pending')->index();
            }

            if (! Schema::hasColumn('schedules', 'google_sync_error')) {
                $table->text('google_sync_error')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            if (Schema::hasColumn('schedules', 'google_sync_error')) {
                $table->dropColumn('google_sync_error');
            }

            if (Schema::hasColumn('schedules', 'google_sync_status')) {
                $table->dropColumn('google_sync_status');
            }
        });
    }
};
