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
        Schema::create('persons', function (Blueprint $table) {

            $table->increments('id');

            // Basic Information
            $table->string('firstname', 100);
            $table->string('middlename', 100)->nullable();
            $table->string('lastname', 100);
            $table->string('suffix', 20)->nullable();
            $table->enum('sex', ['Male', 'Female'])->nullable();

            $table->string('nickname', 100)->nullable();

            $table->date('birthdate')->nullable();
            $table->string('birthplace', 255)->nullable();

            // Household
            $table->unsignedInteger('household_id')->nullable();

            // Marriage
            $table->unsignedInteger('spouse_id')->nullable();

            // Addresses
            $table->string('locality', 150)->nullable();
            $table->string('permanent_address', 255)->nullable();
            $table->string('home_address', 255)->nullable();

            // Location
            $table->string('geocoordinates', 255)->nullable();

            // Contact
            $table->string('email')->nullable();
            $table->string('contact_number', 20)->nullable();

            // Emergency Contact
            $table->unsignedInteger('emergency_contact_id')->nullable();
            $table->string('emergency_contact_relationship', 50)->nullable();
            $table->string('emergency_contact_number', 20)->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('household_id')
                ->references('id')
                ->on('households')
                ->nullOnDelete();

            $table->foreign('spouse_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();

            $table->foreign('emergency_contact_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
