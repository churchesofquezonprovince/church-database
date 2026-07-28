<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_work_student_centers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('school_campus', 255)->nullable();
            $table->string('locality', 150)->nullable();
            $table->string('place', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('school_campus');
            $table->index('locality');
        });

        Schema::create('campus_work_student_center_members', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('campus_work_student_center_id');
            $table->unsignedInteger('campus_contact_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('campus_work_student_center_id', 'cwsc_members_center_fk')
                ->references('id')
                ->on('campus_work_student_centers')
                ->cascadeOnDelete();

            $table->foreign('campus_contact_id', 'cwsc_members_contact_fk')
                ->references('id')
                ->on('campus_contacts')
                ->cascadeOnDelete();

            $table->unique(
                ['campus_work_student_center_id', 'campus_contact_id'],
                'cwsc_members_center_contact_unique'
            );

            $table->index('campus_contact_id', 'cwsc_members_contact_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_work_student_center_members');
        Schema::dropIfExists('campus_work_student_centers');
    }
};
