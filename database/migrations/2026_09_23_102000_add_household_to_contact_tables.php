<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'campus_contacts',
            function (Blueprint $table): void {
                /*
                 * households.id uses INT UNSIGNED.
                 *
                 * This belongs to an unlinked Campus
                 * Contact. When the contact becomes a
                 * Person, household membership will be
                 * transferred to the Person record.
                 */
                $table->unsignedInteger(
                    'household_id'
                )
                    ->nullable()
                    ->after('person_id');

                $table->foreign(
                    'household_id'
                )
                    ->references('id')
                    ->on('households')
                    ->nullOnDelete();
            }
        );

        Schema::table(
            'gospel_contacts',
            function (Blueprint $table): void {
                /*
                 * Same rule as Campus Contacts:
                 * direct Household membership is for
                 * contacts not yet represented by a
                 * Person record.
                 */
                $table->unsignedInteger(
                    'household_id'
                )
                    ->nullable()
                    ->after('person_id');

                $table->foreign(
                    'household_id'
                )
                    ->references('id')
                    ->on('households')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'campus_contacts',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'household_id',
                ]);

                $table->dropColumn(
                    'household_id'
                );
            }
        );

        Schema::table(
            'gospel_contacts',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'household_id',
                ]);

                $table->dropColumn(
                    'household_id'
                );
            }
        );
    }
};
