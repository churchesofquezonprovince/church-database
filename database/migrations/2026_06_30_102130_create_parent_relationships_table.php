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
        Schema::create('parent_relationships', function (Blueprint $table) {

            $table->increments('id');

            $table->unsignedInteger('person_id');

            // If the parent already exists in the database
            $table->unsignedInteger('parent_id')->nullable();

            // Otherwise store the parent's name
            $table->string('parent_name', 255)->nullable();

            $table->enum('relationship', [
                'Father',
                'Mother',
                'Guardian',
            ]);

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

            $table->foreign('parent_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Helpful Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(['person_id', 'relationship']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_relationships');
    }
};
