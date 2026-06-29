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
    Schema::create('person_spouses', function (Blueprint $table) {

        $table->id();

$table->unsignedInteger('person_id');
$table->unsignedInteger('spouse_id');

$table->foreign('person_id')
    ->references('id')
    ->on('persons')
    ->cascadeOnDelete();

$table->foreign('spouse_id')
    ->references('id')
    ->on('persons')
    ->cascadeOnDelete();


/*
        $table->foreignId('person_id')
            ->constrained('persons')
            ->cascadeOnDelete();

        $table->foreignId('spouse_id')
            ->constrained('persons')
            ->cascadeOnDelete();
*/

        $table->date('marriage_date')->nullable();

        $table->timestamps();

        $table->unique(['person_id', 'spouse_id']);
    });
}


/*
// Old
    public function up(): void
    {
        Schema::create('person_spouses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
*/


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_spouses');
    }
};
