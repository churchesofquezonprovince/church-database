<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_campus_contacts',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                /*
                 * campus_contacts.id uses increments()
                 * = INT UNSIGNED.
                 */
                $table->unsignedInteger(
                    'campus_contact_id'
                );

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'sccc_record_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'campus_contact_id',
                    'sccc_campus_contact_fk'
                )
                    ->references('id')
                    ->on('campus_contacts')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'campus_contact_id',
                    ],
                    'sccc_record_campus_uq'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_campus_contacts'
        );
    }
};
