<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shepherding_contacts', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('person_id');

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();

            $table->date('contact_date')
                ->index();

            $table->string('outcome', 50)
                ->default('Completed')
                ->index();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['person_id', 'contact_date'],
                'shepherding_contacts_person_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shepherding_contacts');
    }
};
