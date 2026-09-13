<?php

namespace App\Support;

use App\Models\AttendanceMeetingFormQuestion;
use App\Models\AttendanceMeetingProfileCorrection;
use App\Models\AttendanceMeetingResponse;

final class MeetingFormProfileCorrectionRecorder
{
    public function __construct(
        private readonly MeetingFormRespondentResolver $resolver,
        private readonly MeetingFormDatabaseFieldMatcher $matcher
    ) {
    }

    public function record(
        AttendanceMeetingResponse $response,
        ?array $identity,
        iterable $questions,
        array $rawAnswers
    ): void {
        foreach ($questions as $question) {
            if (
                ! $question instanceof
                    AttendanceMeetingFormQuestion
                || ! $question->isDatabaseField()
                || blank($question->database_field)
            ) {
                continue;
            }

            $field =
                (string)
                $question->database_field;

            /*
             * If corrections are disabled for this question,
             * its answer still belongs to the meeting response,
             * but it must never create a database-change request.
             */
            if (! $question->allow_correction) {
                $this->clearPending(
                    $response,
                    $field
                );

                continue;
            }

            $questionKey =
                (string) $question->id;

            if (
                ! array_key_exists(
                    $questionKey,
                    $rawAnswers
                )
            ) {
                continue;
            }

            $submittedValue =
                $rawAnswers[$questionKey];

            /*
             * Blank means "leave existing information alone".
             *
             * We deliberately do NOT interpret blank as a request
             * to erase an existing database value.
             */
            if ($this->valueIsBlank($submittedValue)) {
                $this->clearPending(
                    $response,
                    $field
                );

                continue;
            }

            $normalizedSubmitted =
                $this->matcher->normalizedValue(
                    $field,
                    $submittedValue
                );

            if ($normalizedSubmitted === null) {
                continue;
            }

            $resolved =
                $identity
                    ? $this->resolver->fieldValue(
                        $identity,
                        $field
                    )
                    : [
                        'has_existing_value' =>
                            false,

                        'value' =>
                            null,
                    ];

            $hasExisting =
                (bool) (
                    $resolved[
                        'has_existing_value'
                    ]
                    ?? false
                );

            $existingValue =
                $resolved['value']
                ?? null;

            if ($hasExisting) {
                $normalizedExisting =
                    $this->matcher->normalizedValue(
                        $field,
                        $existingValue
                    );

                /*
                 * Same value means there is no correction to
                 * review. It also cancels any still-pending
                 * request from an earlier re-submission.
                 */
                if (
                    $normalizedExisting !== null
                    && hash_equals(
                        $normalizedExisting,
                        $normalizedSubmitted
                    )
                ) {
                    $this->clearPending(
                        $response,
                        $field
                    );

                    continue;
                }

                $changeType =
                    AttendanceMeetingProfileCorrection
                        ::TYPE_CORRECTION;
            } else {
                $changeType =
                    AttendanceMeetingProfileCorrection
                        ::TYPE_PROFILE_COMPLETION;
            }

            $definition =
                MeetingFormDatabaseFieldRegistry::definition(
                    $field
                );

            $identityIds =
                $this->identityIds(
                    $identity
                );

            [
                $originalText,
                $originalJson,
            ] = $this->storageValue(
                $existingValue
            );

            [
                $proposedText,
                $proposedJson,
            ] = $this->storageValue(
                $submittedValue
            );

            $pending =
                AttendanceMeetingProfileCorrection::query()
                    ->where(
                        'attendance_meeting_response_id',
                        $response->id
                    )
                    ->where(
                        'database_field',
                        $field
                    )
                    ->where(
                        'status',
                        AttendanceMeetingProfileCorrection
                            ::STATUS_PENDING
                    )
                    ->latest('id')
                    ->first();

            if (! $pending) {
                $pending =
                    new AttendanceMeetingProfileCorrection();

                $pending->attendance_meeting_response_id =
                    $response->id;

                $pending->status =
                    AttendanceMeetingProfileCorrection
                        ::STATUS_PENDING;
            }

            $pending->forceFill([
                'attendance_meeting_form_question_id' =>
                    $question->id,

                'person_id' =>
                    $identityIds['person_id'],

                'campus_contact_id' =>
                    $identityIds['campus_contact_id'],

                'gospel_contact_id' =>
                    $identityIds['gospel_contact_id'],

                'database_field' =>
                    $field,

                'field_owner' =>
                    $definition['owner']
                    ?? null,

                'change_type' =>
                    $changeType,

                'original_value_text' =>
                    $originalText,

                'original_value_json' =>
                    $originalJson,

                'proposed_value_text' =>
                    $proposedText,

                'proposed_value_json' =>
                    $proposedJson,

                /*
                 * A re-submission updates the same pending
                 * request and clears any previous review metadata.
                 */
                'status' =>
                    AttendanceMeetingProfileCorrection
                        ::STATUS_PENDING,

                'reviewed_by_id' =>
                    null,

                'reviewed_at' =>
                    null,

                'review_note' =>
                    null,
            ])->save();
        }
    }

    private function clearPending(
        AttendanceMeetingResponse $response,
        string $field
    ): void {
        AttendanceMeetingProfileCorrection::query()
            ->where(
                'attendance_meeting_response_id',
                $response->id
            )
            ->where(
                'database_field',
                $field
            )
            ->where(
                'status',
                AttendanceMeetingProfileCorrection
                    ::STATUS_PENDING
            )
            ->delete();
    }

    private function identityIds(
        ?array $identity
    ): array {
        return [
            'person_id' =>
                $identity['person']?->id
                ?? null,

            'campus_contact_id' =>
                $identity['campus_contact']?->id
                ?? null,

            'gospel_contact_id' =>
                $identity['gospel_contact']?->id
                ?? null,
        ];
    }

    private function storageValue(
        mixed $value
    ): array {
        if (is_array($value)) {
            return [
                null,
                array_values($value),
            ];
        }

        if (
            $value === null
            || is_object($value)
        ) {
            return [
                null,
                null,
            ];
        }

        $value =
            trim(
                (string) $value
            );

        return [
            $value !== ''
                ? $value
                : null,

            null,
        ];
    }

    private function valueIsBlank(
        mixed $value
    ): bool {
        if (is_array($value)) {
            return collect($value)
                ->filter(
                    fn ($item): bool =>
                        filled($item)
                )
                ->isEmpty();
        }

        return blank($value);
    }
}
