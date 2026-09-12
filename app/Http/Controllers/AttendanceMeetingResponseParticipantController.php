<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMeetingResponse;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceSession;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceMeetingResponseParticipantController extends Controller
{
    public function store(
        Request $request,
        AttendanceMeetingResponse $response
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        if (
            $response->respondent_type
            !== AttendanceMeetingResponse::RESPONDENT_PERSON
            || ! $response->person_id
        ) {
            return back()->withErrors([
                'meeting_response_participant' =>
                    'This pre-listed entry must be linked to the People Database before it can be added to Attendance Participants.',
            ]);
        }

        $data = $request->validate([
            'participant_response_id' => [
                'required',
                'integer',
            ],

            'participant_scope' => [
                'required',
                'in:this_meeting,onward',
            ],
        ]);

        if (
            (int) $data['participant_response_id']
            !== (int) $response->id
        ) {
            abort(404);
        }

        $session =
            AttendanceSession::query()
                ->findOrFail(
                    $response->attendance_session_id
                );

        $sessionDate =
            $session->session_date
                ->copy()
                ->startOfDay();

        $scope =
            $data['participant_scope'];

        $desiredStart =
            $sessionDate->toDateString();

        $desiredEnd =
            $scope === 'this_meeting'
                ? $sessionDate->toDateString()
                : null;

        $participant =
            AttendanceParticipant::query()
                ->where(
                    'attendance_sheet_id',
                    $session->attendance_sheet_id
                )
                ->where(
                    'person_id',
                    $response->person_id
                )
                ->first();

        /*
         * No Participant row yet.
         */
        if (! $participant) {
            $participant =
                AttendanceParticipant::query()
                    ->create([
                        'attendance_sheet_id' =>
                            $session->attendance_sheet_id,

                        'person_id' =>
                            $response->person_id,

                        'starts_on' =>
                            $desiredStart,

                        'ends_on' =>
                            $desiredEnd,

                        'is_active' =>
                            true,
                    ]);

            $this->logEnrollment(
                response: $response,
                session: $session,
                participant: $participant,
                scope: $scope,
                action: 'created',
            );

            return $this->successResponse(
                $response,
                $scope
            );
        }

        /*
         * attendance_participants currently has one row
         * per Person + Sheet.
         *
         * Therefore these two buttons explicitly set the
         * scope of that existing row.
         *
         * This Meeting Only:
         *     selected date -> selected date
         *
         * From This Meeting Onward:
         *     selected date -> no end date
         *
         * If the row was previously removed/inactive,
         * reactivate that same row.
         */
        $oldValues = [
            'starts_on' =>
                $participant->starts_on
                    ?->format('Y-m-d'),

            'ends_on' =>
                $participant->ends_on
                    ?->format('Y-m-d'),

            'is_active' =>
                (bool) $participant->is_active,
        ];

        $wasInactive =
            ! (bool) $participant->is_active;

        $participant->forceFill([
            'starts_on' =>
                $desiredStart,

            'ends_on' =>
                $desiredEnd,

            'is_active' =>
                true,
        ])->save();

        ActivityLogger::log(
            action:
                $wasInactive
                    ? 'attendance_meeting_response.participant_reactivated'
                    : 'attendance_meeting_response.participant_scope_updated',

            subject:
                $participant,

            description:
                $wasInactive
                    ? 'Reactivated Pre-listed Person Attendance Participant with the requested meeting scope.'
                    : 'Updated Pre-listed Person Attendance Participant to the requested meeting scope.',

            oldValues:
                $oldValues,

            newValues: [
                'attendance_sheet_id' =>
                    $session->attendance_sheet_id,

                'attendance_session_id' =>
                    $session->id,

                'meeting_response_id' =>
                    $response->id,

                'person_id' =>
                    $response->person_id,

                'starts_on' =>
                    $participant->starts_on
                        ?->format('Y-m-d'),

                'ends_on' =>
                    $participant->ends_on
                        ?->format('Y-m-d'),

                'is_active' =>
                    (bool) $participant->is_active,

                'scope' =>
                    $scope,

                'reactivated' =>
                    $wasInactive,
            ],
        );

        return $this->successResponse(
            $response,
            $scope
        );
    }


    private function logEnrollment(
        AttendanceMeetingResponse $response,
        AttendanceSession $session,
        AttendanceParticipant $participant,
        string $scope,
        string $action
    ): void {
        ActivityLogger::log(
            action:
                'attendance_meeting_response.participant_'
                . $action,

            subject:
                $participant,

            description:
                'Added Pre-listed Person to Attendance Participants.',

            newValues: [
                'attendance_sheet_id' =>
                    $session->attendance_sheet_id,

                'attendance_session_id' =>
                    $session->id,

                'meeting_response_id' =>
                    $response->id,

                'person_id' =>
                    $response->person_id,

                'starts_on' =>
                    $participant->starts_on
                        ?->format('Y-m-d'),

                'ends_on' =>
                    $participant->ends_on
                        ?->format('Y-m-d'),

                'scope' =>
                    $scope,
            ],
        );
    }


    private function successResponse(
        AttendanceMeetingResponse $response,
        string $scope
    ): RedirectResponse {
        return back()
            ->with(
                'meeting_response_participant_added',
                true
            )
            ->with(
                'meeting_response_participant_name',
                $response->respondent_name
            )
            ->with(
                'meeting_response_participant_scope',
                $scope
            );
    }
}
