<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sheets', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('title');
            $table->string('sheet_type')->default('custom')->index();
            $table->string('locality')->nullable()->index();
            $table->unsignedTinyInteger('meeting_day')->nullable()->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('attendance_sheet_id');
            $table->date('session_date')->index();
            $table->string('title')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['attendance_sheet_id', 'session_date']);

            $table->foreign('attendance_sheet_id')
                ->references('id')
                ->on('attendance_sheets')
                ->cascadeOnDelete();
        });

        Schema::create('attendance_participants', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('attendance_sheet_id');
            $table->unsignedInteger('person_id');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['attendance_sheet_id', 'person_id']);

            $table->foreign('attendance_sheet_id')
                ->references('id')
                ->on('attendance_sheets')
                ->cascadeOnDelete();

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('attendance_session_id');
            $table->unsignedInteger('person_id');
            $table->string('status')->default('present')->index();
            $table->boolean('is_present')->default(true)->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('marked_by_id')->nullable();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'person_id']);

            $table->foreign('attendance_session_id')
                ->references('id')
                ->on('attendance_sessions')
                ->cascadeOnDelete();

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();

            $table->foreign('marked_by_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_participants');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('attendance_sheets');
    }
};
