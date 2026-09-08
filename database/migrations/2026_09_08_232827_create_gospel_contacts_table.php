<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'gospel_contacts',
            function (Blueprint $table): void {
                /*
                 * Keep the same ID architecture as:
                 * - persons
                 * - households
                 * - campus_contacts
                 *
                 * INT UNSIGNED
                 */
                $table->increments('id');

                /*
                 * Filled once this Gospel Contact is
                 * linked to the People Database.
                 *
                 * persons.id = INT UNSIGNED
                 */
                $table->unsignedInteger(
                    'person_id'
                )->nullable();

                $table->string(
                    'firstname',
                    100
                )->nullable();

                $table->string(
                    'lastname',
                    100
                )->nullable();

                $table->enum(
                    'sex',
                    [
                        'Male',
                        'Female',
                    ]
                )->nullable();

                /*
                 * Compatibility text synchronized from
                 * locality_id.
                 */
                $table->string(
                    'locality',
                    150
                )->nullable();

                /*
                 * localities.id = BIGINT UNSIGNED
                 */
                $table->foreignId(
                    'locality_id'
                )
                    ->nullable()
                    ->constrained(
                        'localities'
                    )
                    ->restrictOnDelete();

                $table->string(
                    'contact_number',
                    20
                )->nullable();

                $table->string(
                    'email'
                )->nullable();

                $table->string(
                    'facebook_account',
                    255
                )->nullable();

                /*
                 * Residence / known address.
                 */
                $table->string(
                    'address',
                    255
                )->nullable();

                /*
                 * Where the gospel contact is commonly
                 * met/contacted:
                 *
                 * marketplace, workplace, barangay,
                 * home, terminal, etc.
                 */
                $table->string(
                    'contact_place',
                    255
                )->nullable();

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamps();

                $table->foreign(
                    'person_id',
                    'gospel_contacts_person_fk'
                )
                    ->references('id')
                    ->on('persons')
                    ->nullOnDelete();

                /*
                 * A Gospel Contact may link to only one
                 * unique Person record.
                 *
                 * Multiple NULL values remain allowed.
                 */
                $table->unique(
                    'person_id',
                    'gospel_contacts_person_id_unique'
                );

                $table->index(
                    [
                        'lastname',
                        'firstname',
                    ],
                    'gospel_contacts_name_idx'
                );

                $table->index(
                    'locality',
                    'gospel_contacts_locality_idx'
                );

                $table->index(
                    'contact_place',
                    'gospel_contacts_place_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'gospel_contacts'
        );
    }
};
