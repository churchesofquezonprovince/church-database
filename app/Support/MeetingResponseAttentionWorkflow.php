<?php

namespace App\Support;

use App\Models\{AttendanceMeetingResponse, AttendanceParticipant, AttendanceSession};
use Illuminate\Support\Collection;

class MeetingResponseAttentionWorkflow
{
    // Shared with Attendance Sheets so the bell and page use exactly the same rules.
    public static function statuses(Collection $responses, AttendanceSession $session): Collection
    {
    $sessionDate =
        $session->session_date->format('Y-m-d');

    $personIds = $responses
        ->filter(
            fn (AttendanceMeetingResponse $response): bool =>
                $response->respondent_type
                    === AttendanceMeetingResponse::RESPONDENT_PERSON
                &&
                filled($response->person_id)
        )
        ->pluck('person_id')
        ->map(fn ($id): int => (int) $id)
        ->unique()
        ->values();

    $participants =
        AttendanceParticipant::query()
            ->where(
                'attendance_sheet_id',
                $session->attendance_sheet_id,
            )
            ->whereIn(
                'person_id',
                $personIds
            )
            ->get()
            ->groupBy(
                fn (
                    AttendanceParticipant $participant
                ): int =>
                    (int) $participant->person_id
            );

    return $responses->mapWithKeys(
        function (
            AttendanceMeetingResponse $response
        ) use (
            $participants,
            $sessionDate,
        ): array {
            /*
             * Guest / Campus has not yet reached a canonical Person.
             *
             * This remains an identity-review action even if the
             * response itself is NO.
             */
            if (
                $response->respondent_type
                    !== AttendanceMeetingResponse::RESPONDENT_PERSON
                ||
                blank($response->person_id)
            ) {
                return [
                    $response->id => [
                        'key' => 'needs_identity_review',
                        'label' => 'Needs Identity Review',
                        'needs_action' => true,
                        'detail' => null,
                    ],
                ];
            }

            /*
             * A canonical Person who answered NO does not need to
             * be enrolled from the pre-listed response.
             */
            if (
                $response->response
                === AttendanceMeetingResponse::RESPONSE_NO
            ) {
                return [
                    $response->id => [
                        'key' => 'no_participant_needed',
                        'label' => 'No Participant Needed',
                        'needs_action' => false,
                        'detail' => null,
                    ],
                ];
            }

            $personParticipants =
                $participants->get(
                    (int) $response->person_id,
                    collect()
                );

            $coversDate =
                function (
                    AttendanceParticipant $participant
                ) use (
                    $sessionDate
                ): bool {
                    $startsOn =
                        $participant
                            ->starts_on
                            ?->format('Y-m-d');

                    $endsOn =
                        $participant
                            ->ends_on
                            ?->format('Y-m-d');

                    return
                        (
                            blank($startsOn)
                            ||
                            $startsOn <= $sessionDate
                        )
                        &&
                        (
                            blank($endsOn)
                            ||
                            $endsOn >= $sessionDate
                        );
                };

            $activeParticipant =
                $personParticipants
                    ->first(
                        fn (
                            AttendanceParticipant $participant
                        ): bool =>
                            $participant->is_active
                            &&
                            $coversDate(
                                $participant
                            )
                    );

            if ($activeParticipant) {
                return [
                    $response->id => [
                        'key' => 'participant_covered',
                        'label' => 'Participant Covered',
                        'needs_action' => false,
                        'detail' => null,
                    ],
                ];
            }

            $inactiveParticipant =
                $personParticipants
                    ->first(
                        fn (
                            AttendanceParticipant $participant
                        ): bool =>
                            ! $participant->is_active
                            &&
                            $coversDate(
                                $participant
                            )
                    );

            if ($inactiveParticipant) {
                return [
                    $response->id => [
                        'key' => 'participant_review',
                        'label' => 'Participant Review',
                        'needs_action' => true,
                        'detail' =>
                            'The participant period for this meeting is inactive.',
                    ],
                ];
            }

            if ($personParticipants->isNotEmpty()) {
                return [
                    $response->id => [
                        'key' => 'participant_review',
                        'label' => 'Participant Review',
                        'needs_action' => true,
                        'detail' =>
                            'Existing participant periods do not cover this meeting.',
                    ],
                ];
            }

            return [
                $response->id => [
                    'key' => 'ready_for_participant',
                    'label' => 'Ready for Participant',
                    'needs_action' => true,
                    'detail' => null,
                ],
            ];
        }
    );
}
}
