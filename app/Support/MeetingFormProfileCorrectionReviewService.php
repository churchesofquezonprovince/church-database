<?php

namespace App\Support;

use App\Models\AttendanceMeetingProfileCorrection;
use App\Models\AttendanceMeetingResponse;
use App\Models\ChurchProfile;
use App\Models\EducationProfile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MeetingFormProfileCorrectionReviewService
{
    public function __construct(
        private readonly MeetingFormRespondentResolver $resolver,
        private readonly MeetingFormDatabaseFieldMatcher $matcher,
    ) {
    }

    public function approve(
        AttendanceMeetingProfileCorrection $change,
        ?int $reviewerId
    ): AttendanceMeetingProfileCorrection {
        return DB::transaction(
            function () use (
                $change,
                $reviewerId,
            ): AttendanceMeetingProfileCorrection {
                $change =
                    AttendanceMeetingProfileCorrection::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $change->id
                        );

                if (
                    $change->status
                    !== AttendanceMeetingProfileCorrection::STATUS_PENDING
                ) {
                    throw new RuntimeException(
                        'This database change has already been reviewed.'
                    );
                }

                $identity =
                    $this->identityFor(
                        $change
                    );

                if (! $identity) {
                    throw new RuntimeException(
                        'Link this response to a database identity before approving this change.'
                    );
                }

                $field =
                    $change->database_field;

                $current =
                    $this->resolver->fieldValue(
                        $identity,
                        $field
                    );

                $currentValue =
                    $current['value']
                    ?? null;

                $originalValue =
                    $change->original_value_json
                    ?? $change->original_value_text;

                $proposedValue =
                    $change->proposed_value_json
                    ?? $change->proposed_value_text;

                /*
                 * If another administrator changed the canonical
                 * value after this request was submitted, do not
                 * silently overwrite that newer information.
                 *
                 * If canonical already equals the proposal, this
                 * approval becomes a safe no-op.
                 */
                if (
                    ! $this->valuesEqual(
                        $field,
                        $currentValue,
                        $proposedValue
                    )
                    &&
                    ! $this->valuesEqual(
                        $field,
                        $currentValue,
                        $originalValue
                    )
                ) {
                    throw new RuntimeException(
                        'The database value changed after this request was submitted. Review the current value before approving.'
                    );
                }

                if (
                    ! $this->valuesEqual(
                        $field,
                        $currentValue,
                        $proposedValue
                    )
                ) {
                    $this->applyProposedValue(
                        $change,
                        $proposedValue
                    );
                }

                $change->forceFill([
                    'status' =>
                        AttendanceMeetingProfileCorrection::STATUS_APPROVED,

                    'reviewed_by_id' =>
                        $reviewerId,

                    'reviewed_at' =>
                        now(),

                    'review_note' =>
                        null,
                ])->save();

                $this->logReviewActivity(
                    change: $change,
                    action:
                        'meeting_form.database_change.approved',
                    description:
                        'Approved a meeting-form database change for '
                        . MeetingFormDatabaseFieldRegistry::label(
                            $field
                        )
                        . '.',
                    canonicalBefore:
                        $currentValue,
                    proposedValue:
                        $proposedValue,
                    reviewerId:
                        $reviewerId,
                    approved:
                        true,
                );

                return $change->fresh();
            }
        );
    }


    public function reject(
        AttendanceMeetingProfileCorrection $change,
        ?int $reviewerId
    ): AttendanceMeetingProfileCorrection {
        return DB::transaction(
            function () use (
                $change,
                $reviewerId,
            ): AttendanceMeetingProfileCorrection {
                $change =
                    AttendanceMeetingProfileCorrection::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $change->id
                        );

                if (
                    $change->status
                    !== AttendanceMeetingProfileCorrection::STATUS_PENDING
                ) {
                    throw new RuntimeException(
                        'This database change has already been reviewed.'
                    );
                }

                $identity =
                    $this->identityFor(
                        $change
                    );

                $currentValue =
                    $identity
                        ? (
                            $this->resolver->fieldValue(
                                $identity,
                                $change->database_field
                            )['value']
                            ?? null
                        )
                        : (
                            $change->original_value_json
                            ?? $change->original_value_text
                        );

                $proposedValue =
                    $change->proposed_value_json
                    ?? $change->proposed_value_text;

                $change->forceFill([
                    'status' =>
                        AttendanceMeetingProfileCorrection::STATUS_REJECTED,

                    'reviewed_by_id' =>
                        $reviewerId,

                    'reviewed_at' =>
                        now(),

                    'review_note' =>
                        null,
                ])->save();

                $this->logReviewActivity(
                    change: $change,
                    action:
                        'meeting_form.database_change.rejected',
                    description:
                        'Rejected a meeting-form database change for '
                        . MeetingFormDatabaseFieldRegistry::label(
                            $change->database_field
                        )
                        . '. Canonical data was left unchanged.',
                    canonicalBefore:
                        $currentValue,
                    proposedValue:
                        $proposedValue,
                    reviewerId:
                        $reviewerId,
                    approved:
                        false,
                );

                return $change->fresh();
            }
        );
    }


    private function logReviewActivity(
        AttendanceMeetingProfileCorrection $change,
        string $action,
        string $description,
        mixed $canonicalBefore,
        mixed $proposedValue,
        ?int $reviewerId,
        bool $approved
    ): void {
        $change->loadMissing([
            'response.session.sheet',
            'person',
            'campusContact',
            'gospelContact',
        ]);

        $response =
            $change->response;

        $session =
            $response?->session;

        $sheet =
            $session?->sheet;

        /*
         * Prefer the actual database identity as Activity Log
         * subject so Activity Logs show the person's/contact's
         * human-readable name.
         */
        $subject =
            $change->person
            ?? $change->campusContact
            ?? $change->gospelContact;

        $fieldLabel =
            MeetingFormDatabaseFieldRegistry::label(
                $change->database_field
            );

        ActivityLogger::log(
            action:
                $action,

            subject:
                $subject,

            description:
                $description,

            oldValues: [
                'correction_id' =>
                    $change->id,

                'meeting_response_id' =>
                    $change->attendance_meeting_response_id,

                'question_id' =>
                    $change->attendance_meeting_form_question_id,

                'attendance_session_id' =>
                    $response?->attendance_session_id,

                'attendance_sheet_id' =>
                    $sheet?->id,

                'attendance_sheet_title' =>
                    $sheet?->title,

                'database_field' =>
                    $change->database_field,

                'field_label' =>
                    $fieldLabel,

                'field_owner' =>
                    $change->field_owner,

                'change_type' =>
                    $change->change_type,

                'canonical_value' =>
                    $canonicalBefore,
            ],

            newValues: [
                'proposed_value' =>
                    $proposedValue,

                'canonical_value_after' =>
                    $approved
                        ? $proposedValue
                        : $canonicalBefore,

                'review_status' =>
                    $approved
                        ? AttendanceMeetingProfileCorrection::STATUS_APPROVED
                        : AttendanceMeetingProfileCorrection::STATUS_REJECTED,

                'reviewed_by_id' =>
                    $reviewerId,
            ],
        );
    }


    private function identityFor(
        AttendanceMeetingProfileCorrection $change
    ): ?array {
        if ($change->person_id) {
            return $this->resolver->resolve(
                AttendanceMeetingResponse::RESPONDENT_PERSON,
                (int) $change->person_id
            );
        }

        if ($change->campus_contact_id) {
            return $this->resolver->resolve(
                AttendanceMeetingResponse::RESPONDENT_CAMPUS,
                (int) $change->campus_contact_id
            );
        }

        if ($change->gospel_contact_id) {
            return $this->resolver->resolve(
                AttendanceMeetingResponse::RESPONDENT_GOSPEL,
                (int) $change->gospel_contact_id
            );
        }

        return null;
    }


    private function valuesEqual(
        string $field,
        mixed $left,
        mixed $right
    ): bool {
        if (
            is_array($left)
            || is_array($right)
        ) {
            return $this->normalizedArray(
                $left
            ) === $this->normalizedArray(
                $right
            );
        }

        $leftNormalized =
            $this->matcher->normalizedValue(
                $field,
                $left
            );

        $rightNormalized =
            $this->matcher->normalizedValue(
                $field,
                $right
            );

        if (
            $leftNormalized === null
            || $rightNormalized === null
        ) {
            return $leftNormalized
                === $rightNormalized;
        }

        return hash_equals(
            $leftNormalized,
            $rightNormalized
        );
    }


    private function normalizedArray(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            return trim(
                (string) $value
            );
        }

        return json_encode(
            array_values(
                $value
            ),
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        ) ?: null;
    }


    private function applyProposedValue(
        AttendanceMeetingProfileCorrection $change,
        mixed $value
    ): void {
        $owner =
            $change->field_owner
            ?: MeetingFormDatabaseFieldRegistry::owner(
                $change->database_field
            );

        match ($owner) {
            'person' =>
                $this->applyPersonValue(
                    $change,
                    $value
                ),

            'church_profile' =>
                $this->applyChurchProfileValue(
                    $change,
                    $value
                ),

            'education_profile' =>
                $this->applyEducationProfileValue(
                    $change,
                    $value
                ),

            'gospel_contact' =>
                $this->applyGospelContactValue(
                    $change,
                    $value
                ),

            default =>
                throw new RuntimeException(
                    'This database field does not have a supported approval target.'
                ),
        };
    }


    private function applyPersonValue(
        AttendanceMeetingProfileCorrection $change,
        mixed $value
    ): void {
        /*
         * Linked identities update the canonical Person.
         */
        if ($change->person_id) {
            $person =
                $change->person()
                    ->first();

            if (! $person) {
                throw new RuntimeException(
                    'The linked People Database record no longer exists.'
                );
            }

            $attribute =
                match ($change->database_field) {
                    'firstname' =>
                        'firstname',

                    'middlename' =>
                        'middlename',

                    'lastname' =>
                        'lastname',

                    'suffix' =>
                        'suffix',

                    'nickname' =>
                        'nickname',

                    'sex' =>
                        'sex',

                    'birthdate' =>
                        'birthdate',

                    'birthplace' =>
                        'birthplace',

                    'locality' =>
                        'locality_id',

                    'contact_number' =>
                        'contact_number',

                    'email' =>
                        'email',

                    'facebook_account' =>
                        'facebook_account',

                    'permanent_address' =>
                        'permanent_address',

                    'home_address' =>
                        'home_address',

                    'emergency_contact_relationship' =>
                        'emergency_contact_relationship',

                    'emergency_contact_number' =>
                        'emergency_contact_number',

                    default =>
                        throw new RuntimeException(
                            'This People Database field is not supported for approval.'
                        ),
                };

            $person->setAttribute(
                $attribute,
                $this->storageValue(
                    $change->database_field,
                    $value
                )
            );

            $person->save();

            return;
        }

        /*
         * An unlinked Campus/Gospel record may still own
         * shared fields directly. Do not create a Person.
         */
        if ($change->campus_contact_id) {
            $contact =
                $change->campusContact()
                    ->first();

            if (! $contact) {
                throw new RuntimeException(
                    'The Campus Database record no longer exists.'
                );
            }

            $attribute =
                match ($change->database_field) {
                    'firstname' =>
                        'firstname',

                    'lastname' =>
                        'lastname',

                    'sex' =>
                        'sex',

                    'locality' =>
                        'locality_id',

                    'contact_number' =>
                        'contact_number',

                    'email' =>
                        'email',

                    'facebook_account' =>
                        'facebook_account',

                    default =>
                        throw new RuntimeException(
                            'Link this Campus record to People Database before approving this field.'
                        ),
                };

            $contact->setAttribute(
                $attribute,
                $this->storageValue(
                    $change->database_field,
                    $value
                )
            );

            $contact->save();

            return;
        }

        if ($change->gospel_contact_id) {
            $contact =
                $change->gospelContact()
                    ->first();

            if (! $contact) {
                throw new RuntimeException(
                    'The Gospel Contact no longer exists.'
                );
            }

            $attribute =
                match ($change->database_field) {
                    'firstname' =>
                        'firstname',

                    'lastname' =>
                        'lastname',

                    'sex' =>
                        'sex',

                    'locality' =>
                        'locality_id',

                    'contact_number' =>
                        'contact_number',

                    'email' =>
                        'email',

                    'facebook_account' =>
                        'facebook_account',

                    default =>
                        throw new RuntimeException(
                            'Link this Gospel Contact to People Database before approving this field.'
                        ),
                };

            $contact->setAttribute(
                $attribute,
                $this->storageValue(
                    $change->database_field,
                    $value
                )
            );

            $contact->save();

            return;
        }

        throw new RuntimeException(
            'This request does not have a database identity to update.'
        );
    }


    private function applyChurchProfileValue(
        AttendanceMeetingProfileCorrection $change,
        mixed $value
    ): void {
        if (! $change->person_id) {
            throw new RuntimeException(
                'Link this response to People Database before approving Church Information.'
            );
        }

        $profile =
            ChurchProfile::query()
                ->firstOrNew([
                    'person_id' =>
                        $change->person_id,
                ]);

        $attribute =
            match ($change->database_field) {
                'baptism_date' =>
                    'baptism_date',

                'first_contact_date' =>
                    'first_contact_date',

                'contact_origin' =>
                    'contact_origin',

                'contact_origin_details' =>
                    'contact_origin_details',

                'service' =>
                    'service',

                'church_status' =>
                    'status',

                default =>
                    throw new RuntimeException(
                        'This Church Information field is not supported for approval.'
                    ),
            };

        $profile->setAttribute(
            $attribute,
            $value
        );

        $profile->save();
    }


    private function applyEducationProfileValue(
        AttendanceMeetingProfileCorrection $change,
        mixed $value
    ): void {
        if ($change->person_id) {
            $profile =
                EducationProfile::query()
                    ->firstOrNew([
                        'person_id' =>
                            $change->person_id,
                    ]);

            $attribute =
                match ($change->database_field) {
                    'school' =>
                        'school_id',

                    'grade_level' =>
                        'grade_level',

                    'course_strand' =>
                        'course_strand',

                    'occupation' =>
                        'occupation',

                    'workplace' =>
                        'workplace',

                    default =>
                        throw new RuntimeException(
                            'This Education field is not supported for approval.'
                        ),
                };

            $profile->setAttribute(
                $attribute,
                $this->storageValue(
                    $change->database_field,
                    $value
                )
            );

            $profile->save();

            return;
        }

        /*
         * Unlinked Campus records may own the campus-specific
         * subset directly.
         */
        if ($change->campus_contact_id) {
            $contact =
                $change->campusContact()
                    ->first();

            if (! $contact) {
                throw new RuntimeException(
                    'The Campus Database record no longer exists.'
                );
            }

            $attribute =
                match ($change->database_field) {
                    'school' =>
                        'school_id',

                    'grade_level' =>
                        'grade_level',

                    'course_strand' =>
                        'course_strand',

                    default =>
                        throw new RuntimeException(
                            'Link this Campus record to People Database before approving this Education field.'
                        ),
                };

            $contact->setAttribute(
                $attribute,
                $this->storageValue(
                    $change->database_field,
                    $value
                )
            );

            $contact->save();

            return;
        }

        throw new RuntimeException(
            'Link this response to People Database before approving this Education field.'
        );
    }


    private function applyGospelContactValue(
        AttendanceMeetingProfileCorrection $change,
        mixed $value
    ): void {
        $contact =
            $change->gospelContact()
                ->first();

        if (! $contact) {
            throw new RuntimeException(
                'The Gospel Contact record is required for this field.'
            );
        }

        $attribute =
            match ($change->database_field) {
                'gospel_address' =>
                    'address',

                'contact_place' =>
                    'contact_place',

                default =>
                    throw new RuntimeException(
                        'This Gospel Contact field is not supported for approval.'
                    ),
            };

        $contact->setAttribute(
            $attribute,
            $value
        );

        $contact->save();
    }


    private function storageValue(
        string $field,
        mixed $value
    ): mixed {
        return match ($field) {
            'locality',
            'school' =>
                filled($value)
                    ? (int) $value
                    : null,

            default =>
                $value,
        };
    }
}
