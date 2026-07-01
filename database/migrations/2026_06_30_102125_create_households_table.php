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
        Schema::create('households', function (Blueprint $table) {
            $table->increments('id');

            $table->string('household_name', 150)->nullable();

            // Foreign key added later to avoid circular dependency
            $table->unsignedInteger('household_head_id')->nullable();

            $table->string('address', 255)->nullable();
            $table->string('locality', 150)->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
