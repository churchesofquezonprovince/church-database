<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_household_gospel_members',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * shepherding_contacts.id = BIGINT UNSIGNED
                 */
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
                 * gospel_contacts.id = INT UNSIGNED
                 */
                $table->unsignedInteger(
                    'gospel_contact_id'
                );

                /*
                 * true  = present
                 * false = Household member but not present
                 */
                $table->boolean(
                    'was_present'
                )->default(true);

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'schgm_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'household_id',
                    'schgm_household_fk'
                )
                    ->references('id')
                    ->on('households')
                    ->restrictOnDelete();

                $table->foreign(
                    'gospel_contact_id',
                    'schgm_gospel_fk'
                )
                    ->references('id')
                    ->on('gospel_contacts')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'gospel_contact_id',
                    ],
                    'schgm_contact_gospel_uq'
                );

                $table->index(
                    [
                        'shepherding_contact_id',
                        'household_id',
                        'was_present',
                    ],
                    'schgm_household_present_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_household_gospel_members'
        );
    }
};
