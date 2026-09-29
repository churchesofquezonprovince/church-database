<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'children_work_profiles',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * One Children's Work profile belongs to
                 * one canonical Person.
                 *
                 * Person IDs in this database use INT UNSIGNED.
                 */
                $table->unsignedInteger('person_id');

                /*
                 * Children's Work Locality.
                 *
                 * This is intentionally separate from
                 * persons.locality_id because a child may
                 * participate in Children's Work in a
                 * different locality from their main
                 * Person record.
                 *
                 * Localities use Laravel BIGINT IDs.
                 */
                $table->foreignId('locality_id')
                    ->constrained('localities')
                    ->restrictOnDelete();

                /*
                 * Whether this child is currently active
                 * in Children's Work.
                 */
                $table->boolean('is_active')
                    ->default(true)
                    ->index();

                /*
                 * Optional class / grouping label.
                 *
                 * Examples:
                 * - Toddlers
                 * - Kinder
                 * - Grade 1-2
                 * - Group A
                 */
                $table->string('group_name', 100)
                    ->nullable();

                /*
                 * Existing Person serving with / caring
                 * for this child.
                 *
                 * Nullable because a serving one may not
                 * have been assigned yet.
                 */
                $table->unsignedInteger('serving_one_id')
                    ->nullable();

                $table->text('notes')
                    ->nullable();

                $table->timestamps();

                /*
                 * Exactly one Children's Work profile
                 * per Person.
                 */
                $table->unique('person_id');

                $table->foreign('person_id')
                    ->references('id')
                    ->on('persons')
                    ->cascadeOnDelete();

                $table->foreign('serving_one_id')
                    ->references('id')
                    ->on('persons')
                    ->nullOnDelete();

                $table->index(
                    [
                        'locality_id',
                        'is_active',
                    ],
                    'children_work_locality_active_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'children_work_profiles'
        );
    }
};
