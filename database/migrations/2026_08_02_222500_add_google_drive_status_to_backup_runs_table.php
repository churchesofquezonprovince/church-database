<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('backup_runs', 'google_drive_status')) {
                $table->string('google_drive_status')->default('disabled')->index()->after('external_size_bytes');
            }

            if (! Schema::hasColumn('backup_runs', 'google_drive_file_id')) {
                $table->string('google_drive_file_id')->nullable()->after('google_drive_status');
            }

            if (! Schema::hasColumn('backup_runs', 'google_drive_path')) {
                $table->string('google_drive_path')->nullable()->after('google_drive_file_id');
            }

            if (! Schema::hasColumn('backup_runs', 'google_drive_uploaded_at')) {
                $table->timestamp('google_drive_uploaded_at')->nullable()->after('google_drive_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('backup_runs', function (Blueprint $table): void {
            foreach ([
                'google_drive_status',
                'google_drive_file_id',
                'google_drive_path',
                'google_drive_uploaded_at',
            ] as $column) {
                if (Schema::hasColumn('backup_runs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
