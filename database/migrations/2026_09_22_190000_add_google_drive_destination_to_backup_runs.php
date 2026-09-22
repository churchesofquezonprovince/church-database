<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('backup_runs', function (Blueprint $table): void {
            $table->json('google_drive_destination')->nullable();
        });
    }
    public function down(): void {
        Schema::table('backup_runs', function (Blueprint $table): void {
            $table->dropColumn('google_drive_destination');
        });
    }
};
