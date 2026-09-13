<?php

namespace App\Support;

use App\Models\AttendanceMeetingReferenceProposal;
use App\Models\AttendanceMeetingResponse;
use Illuminate\Support\Collection;

class MeetingFormReferenceProposalRecorder
{
    public function record(
        AttendanceMeetingResponse $response,
        Collection $questions,
        array $proposals
    ): void {
        foreach ($questions as $question) {
            $field =
                (string)
                $question->database_field;

            if (
                ! $question->isDatabaseField()
                || ! MeetingFormDatabaseFieldRegistry
                    ::allowsReferenceProposal(
                        $field
                    )
            ) {
                continue;
            }

            $proposal =
                $proposals[
                    (int) $question->id
                ]
                ?? null;

            if (! is_array($proposal)) {
                $this->clearPending(
                    $response,
                    (int) $question->id
                );

                continue;
            }

            $label =
                trim(
                    (string)
                    ($proposal['label'] ?? '')
                );

            if ($label === '') {
                $this->clearPending(
                    $response,
                    (int) $question->id
                );

                continue;
            }

            $pending =
                AttendanceMeetingReferenceProposal::query()
                    ->where(
                        'attendance_meeting_response_id',
                        $response->id
                    )
                    ->where(
                        'attendance_meeting_form_question_id',
                        $question->id
                    )
                    ->where(
                        'status',
                        AttendanceMeetingReferenceProposal
                            ::STATUS_PENDING
                    )
                    ->latest('id')
                    ->first();

            if (! $pending) {
                $pending =
                    new AttendanceMeetingReferenceProposal();
            }

            $pending->fill([
                'attendance_meeting_response_id' =>
                    $response->id,

                'attendance_meeting_form_question_id' =>
                    $question->id,

                'person_id' =>
                    $response->person_id,

                'campus_contact_id' =>
                    $response->campus_contact_id,

                'gospel_contact_id' =>
                    $response->gospel_contact_id,

                'database_field' =>
                    $field,

                'proposed_label' =>
                    $label,

                'proposed_province_id' =>
                    $proposal['province_id']
                    ?? null,

                'proposed_province_name' =>
                    $proposal['province_name']
                    ?? null,

                'proposed_city_municipality' =>
                    $proposal['city_municipality']
                    ?? null,

                'status' =>
                    AttendanceMeetingReferenceProposal
                        ::STATUS_PENDING,

                'reviewed_by_id' =>
                    null,

                'reviewed_at' =>
                    null,

                'review_note' =>
                    null,
            ]);

            $pending->save();
        }
    }

    private function clearPending(
        AttendanceMeetingResponse $response,
        int $questionId
    ): void {
        AttendanceMeetingReferenceProposal::query()
            ->where(
                'attendance_meeting_response_id',
                $response->id
            )
            ->where(
                'attendance_meeting_form_question_id',
                $questionId
            )
            ->where(
                'status',
                AttendanceMeetingReferenceProposal
                    ::STATUS_PENDING
            )
            ->delete();
    }
}
