<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children_work_lessons', function (Blueprint $table): void {
            if (! Schema::hasColumn('children_work_lessons', 'google_sheet_smart_chip_fields')) {
                $table->json('google_sheet_smart_chip_fields')
                    ->nullable()
                    ->after('google_sheet_row_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('children_work_lessons', function (Blueprint $table): void {
            if (Schema::hasColumn('children_work_lessons', 'google_sheet_smart_chip_fields')) {
                $table->dropColumn('google_sheet_smart_chip_fields');
            }
        });
    }
};
