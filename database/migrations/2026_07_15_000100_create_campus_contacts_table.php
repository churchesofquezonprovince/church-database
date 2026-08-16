<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_contacts', function (Blueprint $table): void {
            $table->increments('id');

            /*
             * Filled only after this Campus Contact is promoted
             * to the main People Database.
             */
            $table->unsignedInteger('person_id')->nullable();

            /*
             * Four core People fields.
             */
            $table->string('firstname', 100);
            $table->string('lastname', 100);
            $table->enum('sex', ['Male', 'Female']);
            $table->string('locality', 150);

            /*
             * Campus information.
             */
            $table->string('school_campus', 255)->nullable();
            $table->string('course_strand', 255)->nullable();
            $table->string('grade_level', 100)->nullable();

            /*
             * Contact information.
             */
            $table->string('contact_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('facebook_account', 255)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('person_id', 'campus_contacts_person_fk')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();

            /*
             * One Campus Contact should link to only one Person record.
             * MySQL allows multiple NULL values in this unique index.
             */
            $table->unique(
                'person_id',
                'campus_contacts_person_id_unique'
            );

            $table->index('school_campus');
            $table->index('locality');
            $table->index(['lastname', 'firstname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_contacts');
    }
};
