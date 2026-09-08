<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('children_work_lessons', function (Blueprint $table): void {
        $table->text('story_url')
            ->nullable()
            ->after('story');
    });
}

public function down(): void
{
    Schema::table('children_work_lessons', function (Blueprint $table): void {
        $table->dropColumn('story_url');
    });
}

};
