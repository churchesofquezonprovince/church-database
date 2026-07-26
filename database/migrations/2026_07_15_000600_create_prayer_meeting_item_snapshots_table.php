<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prayer_meeting_item_snapshots', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('prayer_meeting_item_id')
                ->constrained('prayer_meeting_items')
                ->cascadeOnDelete();

            $table->string('locality', 150)->nullable();
            $table->string('title', 255)->default('Prayer Meeting Items');
            $table->date('meeting_date')->nullable();
            $table->string('meeting_schedule_snapshot', 100)->nullable();
            $table->longText('content_json');

            $table->string('created_by_name', 255)->nullable();

            $table->timestamps();

            $table->index('prayer_meeting_item_id', 'pmi_snapshots_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_meeting_item_snapshots');
    }
};
