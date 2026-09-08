<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_household_members',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                /*
                 * households.id = INT UNSIGNED
                 */
                $table->unsignedInteger(
                    'household_id'
                );

                /*
                 * persons.id = INT UNSIGNED
                 */
                $table->unsignedInteger(
                    'person_id'
                );

                /*
                 * true  = present / actually contacted
                 * false = household member but not present
                 */
                $table->boolean(
                    'was_present'
                )->default(true);

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'schm_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'household_id',
                    'schm_household_fk'
                )
                    ->references('id')
                    ->on('households')
                    ->restrictOnDelete();

                $table->foreign(
                    'person_id',
                    'schm_person_fk'
                )
                    ->references('id')
                    ->on('persons')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'person_id',
                    ],
                    'schm_contact_person_uq'
                );

                $table->index(
                    [
                        'shepherding_contact_id',
                        'household_id',
                        'was_present',
                    ],
                    'schm_contact_household_present_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_household_members'
        );
    }
};
