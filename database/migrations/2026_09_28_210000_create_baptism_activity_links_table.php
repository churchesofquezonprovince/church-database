<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baptism_activity_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('person_id')->unique();
            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->unsignedInteger('attendance_session_id');
            $table->foreign('attendance_session_id')->references('id')->on('attendance_sessions')->cascadeOnDelete();
            $table->date('baptism_date')->index();
            $table->foreignId('confirmed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baptism_activity_links');
    }
};
