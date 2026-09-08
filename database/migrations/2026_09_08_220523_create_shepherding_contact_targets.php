<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_people',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                /*
                 * persons.id uses increments()
                 * = INT UNSIGNED.
                 */
                $table->unsignedInteger(
                    'person_id'
                );

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'scpeople_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'person_id',
                    'scpeople_person_fk'
                )
                    ->references('id')
                    ->on('persons')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'person_id',
                    ],
                    'scpeople_contact_person_uq'
                );
            }
        );

        Schema::create(
            'shepherding_contact_households',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                /*
                 * households.id uses increments()
                 * = INT UNSIGNED.
                 */
                $table->unsignedInteger(
                    'household_id'
                );

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'schouse_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'household_id',
                    'schouse_household_fk'
                )
                    ->references('id')
                    ->on('households')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'household_id',
                    ],
                    'schouse_contact_household_uq'
                );
            }
        );

        /*
         * Preserve every existing single-Person contact.
         */
        DB::table('shepherding_contacts')
            ->whereNotNull('person_id')
            ->orderBy('id')
            ->chunkById(
                200,
                function ($contacts): void {
                    $now = now();

                    $rows = $contacts
                        ->map(
                            fn ($contact): array => [
                                'shepherding_contact_id' =>
                                    $contact->id,
                                'person_id' =>
                                    $contact->person_id,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]
                        )
                        ->all();

                    if ($rows !== []) {
                        DB::table(
                            'shepherding_contact_people'
                        )->insertOrIgnore($rows);
                    }
                }
            );

        /*
         * person_id is replaced by the target pivots.
         * Remove the old single-target field only after
         * its data has been copied successfully.
         */
        Schema::table(
            'shepherding_contacts',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'person_id',
                ]);

                $table->dropColumn(
                    'person_id'
                );
            }
        );
    }

    public function down(): void
    {
        /*
         * Rollback compatibility:
         * restore a nullable legacy Person pointer
         * using the first contacted Person, if any.
         */
        Schema::table(
            'shepherding_contacts',
            function (Blueprint $table): void {
                $table->unsignedInteger(
                    'person_id'
                )
                    ->nullable()
                    ->after('id');

                $table->foreign(
                    'person_id',
                    'sc_legacy_person_fk'
                )
                    ->references('id')
                    ->on('persons')
                    ->nullOnDelete();
            }
        );

        DB::table('shepherding_contacts')
            ->orderBy('id')
            ->chunkById(
                200,
                function ($contacts): void {
                    foreach ($contacts as $contact) {
                        $personId = DB::table(
                            'shepherding_contact_people'
                        )
                            ->where(
                                'shepherding_contact_id',
                                $contact->id
                            )
                            ->orderBy('id')
                            ->value('person_id');

                        if ($personId) {
                            DB::table(
                                'shepherding_contacts'
                            )
                                ->where(
                                    'id',
                                    $contact->id
                                )
                                ->update([
                                    'person_id' =>
                                        $personId,
                                ]);
                        }
                    }
                }
            );

        Schema::dropIfExists(
            'shepherding_contact_households'
        );

        Schema::dropIfExists(
            'shepherding_contact_people'
        );
    }
};
