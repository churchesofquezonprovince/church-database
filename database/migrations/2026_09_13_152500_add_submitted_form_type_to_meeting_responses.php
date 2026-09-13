<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'submitted_form_type',
                        30
                    )
                    ->nullable()
                    ->after('response')
                    ->index();
            }
        );

        /*
         * Every response predating Google Form-like support
         * was necessarily a Normal Meeting Form response.
         */
        DB::table(
            'attendance_meeting_responses'
        )->update([
            'submitted_form_type' =>
                'normal',
        ]);

        /*
         * Google Form-like responses can be identified from
         * the new architecture by either:
         *
         * - having custom form answers, or
         * - having NULL RSVP response.
         *
         * Before Google Form-like support, response was NOT
         * NULL, so this is safe for our existing data.
         */
        DB::table(
            'attendance_meeting_responses as responses'
        )
            ->where(function ($query): void {
                $query
                    ->whereNull(
                        'responses.response'
                    )
                    ->orWhereExists(
                        function ($answers): void {
                            $answers
                                ->selectRaw('1')
                                ->from(
                                    'attendance_meeting_form_answers as answers'
                                )
                                ->whereColumn(
                                    'answers.attendance_meeting_response_id',
                                    'responses.id'
                                );
                        }
                    );
            })
            ->update([
                'submitted_form_type' =>
                    'google_form',
            ]);

        DB::statement(
            'ALTER TABLE attendance_meeting_responses
             MODIFY submitted_form_type VARCHAR(30) NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::table(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'submitted_form_type',
                ]);

                $table->dropColumn(
                    'submitted_form_type'
                );
            }
        );
    }
};
