<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->date('meeting_date');
            $table->text('attendees')->nullable();
            $table->text('agenda')->nullable();
            $table->text('decisions')->nullable();
            $table->text('follow_up_items')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_meeting_minutes');
    }
};
