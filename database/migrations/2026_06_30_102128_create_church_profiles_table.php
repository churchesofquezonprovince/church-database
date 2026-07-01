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
        Schema::create('church_profiles', function (Blueprint $table) {

            $table->increments('id');

            $table->unsignedInteger('person_id');

            $table->string('category', 100);

            $table->date('baptism_date')->nullable();

            $table->unsignedInteger('shepherd_id')->nullable();

            $table->unsignedInteger('introduced_by_id')->nullable();

            $table->string('service', 100)->nullable();

            $table->string('status', 20)->default('Active');

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

            $table->foreign('shepherd_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();

            $table->foreign('introduced_by_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | One Church Profile per Person
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
        Schema::dropIfExists('church_profiles');
    }
};
