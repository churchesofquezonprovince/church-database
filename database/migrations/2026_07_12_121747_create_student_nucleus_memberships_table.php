<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_nucleus_memberships', function (Blueprint $table): void {
            $table->id();

            $table->unsignedInteger('person_id');

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();

            $table->text('spiritual_condition')->nullable();

            $table->timestamps();

            $table->unique('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_nucleus_memberships');
    }
};
