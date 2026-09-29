<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conference_events', function (Blueprint $t): void {
            $t->id();
            $t->unsignedInteger('attendance_sheet_id')->unique();
            $t->string('activity_type', 100);
            $t->timestamps();
            $t->foreign('attendance_sheet_id')->references('id')->on('attendance_sheets')->cascadeOnDelete();
        });
        Schema::create('conference_sessions', function (Blueprint $t): void {
            $t->unsignedBigInteger('conference_event_id');
            $t->unsignedInteger('attendance_session_id');
            $t->primary(['conference_event_id', 'attendance_session_id'], 'conference_session_pair');
            $t->foreign('conference_event_id')->references('id')->on('conference_events')->cascadeOnDelete();
            $t->foreign('attendance_session_id')->references('id')->on('attendance_sessions')->cascadeOnDelete();
        });
        Schema::create('conference_teams', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('conference_event_id');
            $t->string('name', 100); $t->string('color', 7); $t->timestamps();
            $t->unique(['conference_event_id', 'name']);
            $t->foreign('conference_event_id')->references('id')->on('conference_events')->cascadeOnDelete();
        });
        Schema::create('conference_person_details', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('conference_event_id');
            $t->unsignedInteger('person_id');
            $t->unsignedBigInteger('conference_team_id')->nullable();
            $t->string('event_role', 30)->nullable(); $t->timestamps();
            $t->unique(['conference_event_id', 'person_id']);
            $t->foreign('conference_event_id')->references('id')->on('conference_events')->cascadeOnDelete();
            $t->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $t->foreign('conference_team_id')->references('id')->on('conference_teams')->nullOnDelete();
        });
        Schema::create('conference_invitations', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('conference_event_id');
            $t->unsignedInteger('inviter_person_id');
            $t->unsignedInteger('invitee_person_id'); $t->timestamps();
            $t->unique(['conference_event_id', 'invitee_person_id'], 'conference_invitee_unique');
            $t->foreign('conference_event_id')->references('id')->on('conference_events')->cascadeOnDelete();
            $t->foreign('inviter_person_id')->references('id')->on('persons')->cascadeOnDelete();
            $t->foreign('invitee_person_id')->references('id')->on('persons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['conference_invitations', 'conference_person_details', 'conference_teams', 'conference_sessions', 'conference_events'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
