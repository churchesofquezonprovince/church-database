<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_guests', function (Blueprint $t): void {
            $t->id(); $t->unsignedInteger('attendance_sheet_id');
            $t->string('source_type', 20); $t->unsignedBigInteger('source_id')->nullable();
            $t->string('name'); $t->string('locality', 150)->nullable();
            $t->unsignedInteger('linked_person_id')->nullable(); $t->timestamps();
            $t->unique(['attendance_sheet_id','source_type','source_id'], 'att_guest_source_unique');
            $t->foreign('attendance_sheet_id')->references('id')->on('attendance_sheets')->cascadeOnDelete();
            $t->foreign('linked_person_id')->references('id')->on('persons')->restrictOnDelete();
        });
        Schema::create('attendance_guest_responses', function (Blueprint $t): void {
            $t->unsignedBigInteger('attendance_meeting_response_id')->primary();
            $t->unsignedBigInteger('attendance_guest_id');
            $t->foreign('attendance_meeting_response_id','att_guest_response_fk')->references('id')->on('attendance_meeting_responses')->cascadeOnDelete();
            $t->foreign('attendance_guest_id')->references('id')->on('attendance_guests')->cascadeOnDelete();
        });
        Schema::create('attendance_guest_periods', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('attendance_guest_id');
            $t->date('starts_on'); $t->date('ends_on')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->foreign('attendance_guest_id')->references('id')->on('attendance_guests')->cascadeOnDelete();
        });
        Schema::create('attendance_guest_records', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('attendance_guest_id'); $t->unsignedInteger('attendance_session_id');
            $t->string('status',20); $t->unsignedBigInteger('marked_by_id')->nullable(); $t->timestamp('marked_at'); $t->timestamps();
            $t->unique(['attendance_guest_id','attendance_session_id'],'att_guest_record_unique');
            $t->foreign('attendance_guest_id')->references('id')->on('attendance_guests')->cascadeOnDelete();
            $t->foreign('attendance_session_id')->references('id')->on('attendance_sessions')->cascadeOnDelete();
            $t->foreign('marked_by_id')->references('id')->on('users')->nullOnDelete();
        });
        Schema::create('conference_guest_details', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('conference_event_id'); $t->unsignedBigInteger('attendance_guest_id');
            $t->unsignedBigInteger('conference_team_id')->nullable(); $t->string('event_role',30)->nullable(); $t->timestamps();
            $t->unique(['conference_event_id','attendance_guest_id'],'conf_guest_detail_unique');
            $t->foreign('conference_event_id')->references('id')->on('conference_events')->cascadeOnDelete();
            $t->foreign('attendance_guest_id')->references('id')->on('attendance_guests')->cascadeOnDelete();
            $t->foreign('conference_team_id')->references('id')->on('conference_teams')->nullOnDelete();
        });
        Schema::create('conference_attendee_invitations', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('conference_event_id');
            // Keys are validated against the event roster. Positive = Person, negative = Guest.
            $t->bigInteger('inviter_key'); $t->bigInteger('invitee_key'); $t->timestamps();
            $t->unique(['conference_event_id','invitee_key'],'conf_attendee_invitee_unique');
            $t->foreign('conference_event_id')->references('id')->on('conference_events')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        foreach (['conference_attendee_invitations','conference_guest_details','attendance_guest_records',
            'attendance_guest_periods','attendance_guest_responses','attendance_guests'] as $table) Schema::dropIfExists($table);
    }
};
