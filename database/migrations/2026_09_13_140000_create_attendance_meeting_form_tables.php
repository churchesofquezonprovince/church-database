<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'attendance_meeting_form_questions',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedInteger(
                    'attendance_sheet_id'
                );

                $table
                    ->string('question_type', 50)
                    ->index();

                $table->text('question_text');

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->json('options')
                    ->nullable();

                $table
                    ->boolean('is_required')
                    ->default(false);

                $table
                    ->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamps();

                $table->index(
                    [
                        'attendance_sheet_id',
                        'sort_order',
                    ],
                    'meeting_form_questions_sheet_sort_index'
                );

                $table
                    ->foreign('attendance_sheet_id')
                    ->references('id')
                    ->on('attendance_sheets')
                    ->cascadeOnDelete();
            }
        );

        Schema::create(
            'attendance_meeting_form_answers',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'attendance_meeting_response_id'
                );

                $table->unsignedBigInteger(
                    'attendance_meeting_form_question_id'
                );

                $table
                    ->longText('answer_text')
                    ->nullable();

                $table
                    ->json('answer_json')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'attendance_meeting_response_id',
                        'attendance_meeting_form_question_id',
                    ],
                    'meeting_form_answer_response_question_unique'
                );

                $table
                    ->foreign(
                        'attendance_meeting_response_id',
                        'meeting_form_answer_response_fk'
                    )
                    ->references('id')
                    ->on('attendance_meeting_responses')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'attendance_meeting_form_question_id',
                        'meeting_form_answer_question_fk'
                    )
                    ->references('id')
                    ->on('attendance_meeting_form_questions')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_meeting_form_answers'
        );

        Schema::dropIfExists(
            'attendance_meeting_form_questions'
        );
    }
};
