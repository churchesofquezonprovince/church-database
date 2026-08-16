<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campus_work_terms', function (Blueprint $table): void {
            $table->boolean('is_archived')
                ->default(false)
                ->after('is_active');

            $table->index(
                'is_archived',
                'campus_work_terms_is_archived_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('campus_work_terms', function (Blueprint $table): void {
            $table->dropIndex('campus_work_terms_is_archived_index');
            $table->dropColumn('is_archived');
        });
    }
};
