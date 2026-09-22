<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'public_shepherding_submissions',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid('public_id')
                    ->unique();

                $table->string(
                    'submitted_by_name',
                    255
                );

                $table->string(
                    'submitted_by_contact',
                    255
                )->nullable();

                $table->date(
                    'contact_date'
                );

                $table->string(
                    'contact_time',
                    5
                )->nullable();

                $table->foreignId(
                    'locality_id'
                )
                    ->nullable()
                    ->constrained('localities')
                    ->nullOnDelete();

                $table->string(
                    'outcome',
                    100
                );

                /*
                 * Public users enter names as text.
                 *
                 * We intentionally do NOT expose the People,
                 * Household, Campus Contact, or Gospel Contact
                 * databases through the public page.
                 *
                 * Admin resolves these to canonical records
                 * during Shepherding Record review.
                 */
                $table->text(
                    'contact_targets_text'
                );

                $table->text(
                    'participant_names_text'
                )->nullable();

                $table->json(
                    'activity_type_ids'
                )->nullable();

                $table->json(
                    'ministry_lesson_ids'
                )->nullable();

                $table->json(
                    'bible_references'
                )->nullable();

                $table->string(
                    'morning_revival_text',
                    500
                )->nullable();

                $table->text(
                    'hymns_text'
                )->nullable();

                $table->text(
                    'notes'
                )->nullable();

                $table->string(
                    'status',
                    20
                )
                    ->default('pending')
                    ->index();

                $table->foreignId(
                    'reviewed_by_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'reviewed_at'
                )->nullable();

                $table->text(
                    'review_note'
                )->nullable();

                $table->foreignId(
                    'shepherding_contact_id'
                )
                    ->nullable()
                    ->constrained(
                        'shepherding_contacts'
                    )
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'contact_date',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'public_shepherding_submissions'
        );
    }
};
