<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children_work_lessons', function (Blueprint $table): void {
            $table->id();
            $table->date('scheduled_on')->nullable()->index();
            $table->string('lesson_code')->nullable()->index();
            $table->text('lesson_title')->nullable();
            $table->text('lesson_url')->nullable();
            $table->text('suggested_hymn')->nullable();
            $table->text('suggested_hymn_url')->nullable();
            $table->text('memory_verse')->nullable();
            $table->text('story')->nullable();
            $table->text('presentation_slides')->nullable();
            $table->text('presentation_slides_url')->nullable();
            $table->text('activity')->nullable();
            $table->text('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('scheduled')->index();

            $table->string('source')->default('local')->index();
            $table->unsignedInteger('google_sheet_row_number')->nullable()->index();
            $table->string('google_sheet_row_hash')->nullable();
            $table->timestamp('google_sheet_synced_at')->nullable();
            $table->string('sync_status')->default('local')->index();
            $table->text('sync_error')->nullable();

            $table->timestamps();

            $table->index(['scheduled_on', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children_work_lessons');
    }
};
