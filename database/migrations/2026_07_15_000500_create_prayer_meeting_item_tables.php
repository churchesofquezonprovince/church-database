<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prayer_meeting_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('attendance_sheet_id')
                ->nullable();

            $table->foreign('attendance_sheet_id')
                ->references('id')
                ->on('attendance_sheets')
                ->nullOnDelete();
            $table->string('locality', 150)->nullable()->index();
            $table->string('title', 255)->default('Prayer Meeting Items');
            $table->date('meeting_date')->nullable();
            $table->timestamps();

            $table->unique('locality');
        });

        Schema::create('prayer_meeting_item_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prayer_meeting_item_id')
                ->constrained('prayer_meeting_items')
                ->cascadeOnDelete();

            $table->string('line_type', 30)->default('letter');
            $table->string('marker', 20)->nullable();
            $table->longText('content');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(
                ['prayer_meeting_item_id', 'sort_order'],
                'pmi_lines_item_sort_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_meeting_item_lines');
        Schema::dropIfExists('prayer_meeting_items');
    }
};
