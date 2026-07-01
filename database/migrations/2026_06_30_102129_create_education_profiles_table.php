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
        Schema::create('education_profiles', function (Blueprint $table) {

            $table->increments('id');

            $table->unsignedInteger('person_id');

            $table->string('grade_level', 50)->nullable();

            $table->string('course_strand', 100)->nullable();

            $table->string('occupation', 100)->nullable();

            $table->string('school_workplace', 255)->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | One Education Profile per Person
            |--------------------------------------------------------------------------
            */

            $table->unique('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('education_profiles');
    }
};
