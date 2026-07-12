<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_work_activities', function (Blueprint $table): void {
            $table->increments('id');

            $table->unsignedInteger('campus_work_term_id')->nullable();

            $table->string('activity_type', 100);
            $table->string('other_activity_name', 150)->nullable();
            $table->string('title', 255)->nullable();

            $table->date('activity_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('school_campus', 255)->nullable();
            $table->string('venue', 255)->nullable();
            $table->string('locality', 150)->nullable();

            $table->text('description')->nullable();

            $table->timestamps();

            $table->foreign(
                'campus_work_term_id',
                'campus_work_activities_term_fk'
            )
                ->references('id')
                ->on('campus_work_terms')
                ->nullOnDelete();

            $table->index('activity_date');
            $table->index('activity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_work_activities');
    }
};
