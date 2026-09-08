<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_gospel_contacts',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * shepherding_contacts.id = BIGINT UNSIGNED
                 */
                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                /*
                 * gospel_contacts.id = INT UNSIGNED
                 */
                $table->unsignedInteger(
                    'gospel_contact_id'
                );

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'scgc_record_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'gospel_contact_id',
                    'scgc_gospel_fk'
                )
                    ->references('id')
                    ->on('gospel_contacts')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'gospel_contact_id',
                    ],
                    'scgc_record_gospel_uq'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_gospel_contacts'
        );
    }
};
