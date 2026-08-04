<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children_work_lessons', function (Blueprint $table): void {
            if (! Schema::hasColumn('children_work_lessons', 'activity_url')) {
                $table->text('activity_url')->nullable()->after('activity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('children_work_lessons', function (Blueprint $table): void {
            if (Schema::hasColumn('children_work_lessons', 'activity_url')) {
                $table->dropColumn('activity_url');
            }
        });
    }
};
