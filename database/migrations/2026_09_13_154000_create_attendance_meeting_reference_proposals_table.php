<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'attendance_meeting_reference_proposals',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'attendance_meeting_response_id'
                    )
                    ->constrained(
                        'attendance_meeting_responses'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'attendance_meeting_form_question_id'
                    )
                    ->constrained(
                        'attendance_meeting_form_questions'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreignId('person_id')
                    ->nullable()
                    ->constrained('people')
                    ->nullOnDelete();

                $table
                    ->foreignId('campus_contact_id')
                    ->nullable()
                    ->constrained('campus_contacts')
                    ->nullOnDelete();

                $table
                    ->foreignId('gospel_contact_id')
                    ->nullable()
                    ->constrained('gospel_contacts')
                    ->nullOnDelete();

                $table->string(
                    'database_field',
                    100
                );

                $table->string(
                    'proposed_label',
                    255
                );

                $table
                    ->foreignId(
                        'proposed_province_id'
                    )
                    ->nullable()
                    ->constrained('provinces')
                    ->nullOnDelete();

                /*
                 * Keep a snapshot even if the Province is
                 * later renamed or removed before review.
                 */
                $table
                    ->string(
                        'proposed_province_name',
                        150
                    )
                    ->nullable();

                $table
                    ->string(
                        'proposed_city_municipality',
                        150
                    )
                    ->nullable();

                $table
                    ->string('status', 30)
                    ->default('pending');

                $table
                    ->string(
                        'resolved_reference_type',
                        50
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'resolved_reference_id'
                    )
                    ->nullable();

                $table
                    ->foreignId('reviewed_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp('reviewed_at')
                    ->nullable();

                $table
                    ->text('review_note')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'attendance_meeting_response_id',
                    'status',
                ]);

                $table->index([
                    'database_field',
                    'status',
                ]);

                $table->index([
                    'proposed_province_id',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_meeting_reference_proposals'
        );
    }
};
