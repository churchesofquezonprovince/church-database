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

        $session = AttendanceSession::query()
            ->findOrFail(
                $response->attendance_session_id
            );

        $sessionDate =
            $session->session_date->copy()->startOfDay();

        $participant = AttendanceParticipant::query()
            ->where(
                'attendance_sheet_id',
                $session->attendance_sheet_id
            )
            ->where(
                'person_id',
                $response->person_id
            )
            ->first();

        $scope =
            $data['participant_scope'];

        /*
         * No existing participant:
         * straightforward creation.
         */
        if (! $participant) {
            $participant =
                AttendanceParticipant::query()->create([
                    'attendance_sheet_id' =>
                        $session->attendance_sheet_id,

                    'person_id' =>
                        $response->person_id,

                    'starts_on' =>
                        $sessionDate->toDateString(),

                    'ends_on' =>
                        $scope === 'this_meeting'
                            ? $sessionDate->toDateString()
                            : null,

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
         * Do not silently repurpose an inactive historical
         * participant row.
         */
        if (! $participant->is_active) {
            return back()->withErrors([
                'meeting_response_participant' =>
                    'This Person already has an inactive Attendance Participant record for this sheet. Please manage that participant record before adding them from the pre-listed entry.',
            ]);
        }

        $startsOn =
            $participant->starts_on
                ?->copy()
                ?->startOfDay();

        $endsOn =
            $participant->ends_on
                ?->copy()
                ?->startOfDay();

        $coversSession =
            (! $startsOn || $startsOn->lte($sessionDate))
            &&
            (! $endsOn || $endsOn->gte($sessionDate));

        /*
         * THIS MEETING ONLY
         *
         * If the existing participant already covers this date,
         * nothing needs to change.
         *
         * If their existing range is somewhere else, do not
         * overwrite it because AttendanceParticipant currently
         * stores only one date range per Person + Sheet.
         */
        if ($scope === 'this_meeting') {
            if ($coversSession) {
                return back()
                    ->with(
                        'meeting_response_participant_already',
                        true
                    )
                    ->with(
                        'meeting_response_participant_name',
                        $response->respondent_name
                    );
            }

            return back()->withErrors([
                'meeting_response_participant' =>
                    $response->respondent_name
                    . ' already has a different Attendance Participant date range for this sheet. The existing range was not overwritten.',
            ]);
        }

        /*
         * FROM THIS MEETING ONWARD
         *
         * Existing range entirely before this meeting:
         * we cannot represent both the historical range and the
         * new onward range without introducing a false gap/range,
         * so do not overwrite it automatically.
         */
        if (
            $endsOn
            && $endsOn->lt($sessionDate)
        ) {
            return back()->withErrors([
                'meeting_response_participant' =>
                    $response->respondent_name
                    . ' already has an earlier Attendance Participant date range for this sheet. The existing historical range was not overwritten.',
            ]);
        }

        $oldValues = [
            'starts_on' =>
                $participant->starts_on
                    ?->format('Y-m-d'),

            'ends_on' =>
                $participant->ends_on
                    ?->format('Y-m-d'),

            'is_active' =>
                $participant->is_active,
        ];

        /*
         * If their existing range begins after this meeting,
         * move the start backward to this meeting.
         *
         * If it already begins before this meeting, preserve
         * that earlier start.
         */
        if (
            $startsOn
            && $startsOn->gt($sessionDate)
        ) {
            $participant->starts_on =
                $sessionDate->toDateString();
        }

        /*
         * From this meeting onward means no end date.
         */
        $participant->ends_on = null;
        $participant->is_active = true;
        $participant->save();

        ActivityLogger::log(
            action:
                'attendance_meeting_response.participant_extended',

            subject:
                $participant,

            description:
                'Extended Pre-listed Person attendance participation from meeting onward.',

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

                'scope' =>
                    $scope,
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
