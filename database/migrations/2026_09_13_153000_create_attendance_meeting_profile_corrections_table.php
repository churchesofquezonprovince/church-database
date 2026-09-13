<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'attendance_meeting_profile_corrections',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'attendance_meeting_response_id'
                );

                $table->unsignedBigInteger(
                    'attendance_meeting_form_question_id'
                );

                $table
                    ->unsignedInteger('person_id')
                    ->nullable();

                $table
                    ->unsignedInteger('campus_contact_id')
                    ->nullable();

                $table
                    ->unsignedInteger('gospel_contact_id')
                    ->nullable();

                $table
                    ->string('database_field', 100);

                $table
                    ->string('field_owner', 50)
                    ->nullable();

                $table
                    ->string('change_type', 30)
                    ->index();

                $table
                    ->longText('original_value_text')
                    ->nullable();

                $table
                    ->json('original_value_json')
                    ->nullable();

                $table
                    ->longText('proposed_value_text')
                    ->nullable();

                $table
                    ->json('proposed_value_json')
                    ->nullable();

                $table
                    ->string('status', 20)
                    ->default('pending')
                    ->index();

                $table
                    ->unsignedBigInteger('reviewed_by_id')
                    ->nullable();

                $table
                    ->timestamp('reviewed_at')
                    ->nullable();

                $table
                    ->text('review_note')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'attendance_meeting_response_id',
                        'database_field',
                        'status',
                    ],
                    'meeting_profile_change_response_field_status'
                );

                $table->index(
                    [
                        'person_id',
                        'status',
                    ],
                    'meeting_profile_change_person_status'
                );

                $table->index(
                    [
                        'campus_contact_id',
                        'status',
                    ],
                    'meeting_profile_change_campus_status'
                );

                $table->index(
                    [
                        'gospel_contact_id',
                        'status',
                    ],
                    'meeting_profile_change_gospel_status'
                );

                $table
                    ->foreign(
                        'attendance_meeting_response_id',
                        'meeting_profile_change_response_fk'
                    )
                    ->references('id')
                    ->on('attendance_meeting_responses')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'attendance_meeting_form_question_id',
                        'meeting_profile_change_question_fk'
                    )
                    ->references('id')
                    ->on('attendance_meeting_form_questions')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'person_id',
                        'meeting_profile_change_person_fk'
                    )
                    ->references('id')
                    ->on('persons')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'campus_contact_id',
                        'meeting_profile_change_campus_fk'
                    )
                    ->references('id')
                    ->on('campus_contacts')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'gospel_contact_id',
                        'meeting_profile_change_gospel_fk'
                    )
                    ->references('id')
                    ->on('gospel_contacts')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_meeting_profile_corrections'
        );
    }
};
