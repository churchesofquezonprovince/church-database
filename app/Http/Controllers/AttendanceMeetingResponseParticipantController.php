<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMeetingResponse;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if ($response->respondent_type !== AttendanceMeetingResponse::RESPONDENT_PERSON || !$response->person_id) {
            $data=$request->validate(['participant_response_id'=>'required|integer','participant_scope'=>'required|in:this_meeting,onward','participant_guest_id'=>'nullable|integer|min:1']);
            abort_unless((int)$data['participant_response_id']===(int)$response->id,404);
            $session=AttendanceSession::findOrFail($response->attendance_session_id);
            \App\Services\GuestAttendance::enroll((int)$session->attendance_sheet_id,(int)$session->id,$data['participant_scope'],(int)$response->id,isset($data['participant_guest_id']) ? (int)$data['participant_guest_id'] : null);
            \Filament\Notifications\Notification::make()->title('Guest/contact enrolled; attendance not marked')->success()->send();
            return back();
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

        $sheet =
            AttendanceSheet::query()
                ->findOrFail(
                    $session->attendance_sheet_id
                );

        if (
            $scope === 'onward'
            && $sheet->schedule_type
                === AttendanceSheet::SCHEDULE_ONE_TIME
        ) {
            return back()->withErrors([
                'meeting_response_participant' =>
                    'From This Meeting Onward is not available for a One-time Attendance Sheet.',
            ]);
        }

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


    public function bulkStore(
        Request $request
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $data = $request->validate([
            'attendance_session_id' => [
                'required',
                'integer',
                'exists:attendance_sessions,id',
            ],

            'participant_scope' => [
                'required',
                'in:this_meeting,onward',
            ],

            'response_ids' => [
                'required',
                'array',
                'min:2',
            ],

            'response_ids.*' => [
                'required',
                'integer',
                'exists:attendance_meeting_responses,id',
            ],
        ]);

        $session =
            AttendanceSession::query()
                ->findOrFail(
                    (int) $data['attendance_session_id']
                );

        $sheet =
            AttendanceSheet::query()
                ->findOrFail(
                    $session->attendance_sheet_id
                );

        $scope =
            $data['participant_scope'];

        if (
            $scope === 'onward'
            && $sheet->schedule_type
                === AttendanceSheet::SCHEDULE_ONE_TIME
        ) {
            return back()->withErrors([
                'meeting_response_participant' =>
                    'From This Meeting Onward is not available for a One-time Attendance Sheet.',
            ]);
        }

        /*
         * Bulk Participant actions apply only to YES responses
         * already linked to the People Database.
         *
         * Guest/Campus entries still require identity review
         * before they can become Attendance Participants.
         */
        $responses =
            AttendanceMeetingResponse::query()
                ->whereIn(
                    'id',
                    collect(
                        $data['response_ids']
                    )
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        )
                        ->unique()
                        ->values()
                        ->all()
                )
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->where(
                    'response',
                    'yes'
                )
                ->where(
                    'respondent_type',
                    AttendanceMeetingResponse::RESPONDENT_PERSON
                )
                ->whereNotNull(
                    'person_id'
                )
                ->orderBy('id')
                ->get()
                ->unique('person_id')
                ->values();

        if ($responses->count() < 2) {
            return back()->withErrors([
                'meeting_response_participant' =>
                    'At least two Participant Review responses are required for this bulk action.',
            ]);
        }

        $sessionDate =
            $session->session_date
                ->copy()
                ->startOfDay();

        $desiredStart =
            $sessionDate->toDateString();

        $desiredEnd =
            $scope === 'this_meeting'
                ? $sessionDate->toDateString()
                : null;

        $personIds = [];

        DB::transaction(
            function () use (
                $responses,
                $session,
                $desiredStart,
                $desiredEnd,
                &$personIds,
            ): void {
                foreach ($responses as $response) {
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
                    } else {
                        /*
                         * Same rule as the individual buttons:
                         * explicitly set the requested scope and
                         * reactivate an inactive row.
                         */
                        $participant->forceFill([
                            'starts_on' =>
                                $desiredStart,

                            'ends_on' =>
                                $desiredEnd,

                            'is_active' =>
                                true,
                        ])->save();
                    }

                    $personIds[] =
                        (int) $response->person_id;
                }
            }
        );

        ActivityLogger::log(
            action:
                'attendance_meeting_response.participants_bulk_updated',

            subject:
                $session,

            description:
                'Applied a bulk Attendance Participant scope to linked YES Pre-listed Responses.',

            newValues: [
                'attendance_sheet_id' =>
                    $session->attendance_sheet_id,

                'attendance_session_id' =>
                    $session->id,

                'participant_scope' =>
                    $scope,

                'participant_count' =>
                    count($personIds),

                'person_ids' =>
                    $personIds,
            ],
        );

        return back()
            ->with(
                'meeting_response_participants_bulk_added',
                true
            )
            ->with(
                'meeting_response_participants_bulk_count',
                count($personIds)
            )
            ->with(
                'meeting_response_participants_bulk_scope',
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
