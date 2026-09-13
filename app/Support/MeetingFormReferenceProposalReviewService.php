<?php

namespace App\Support;

use App\Models\AttendanceMeetingReferenceProposal;
use App\Models\EducationProfile;
use App\Models\Locality;
use App\Models\Province;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MeetingFormReferenceProposalReviewService
{
    public function approve(
        AttendanceMeetingReferenceProposal $proposal,
        ?int $reviewerId,
        ?int $resolvedProvinceId = null
    ): AttendanceMeetingReferenceProposal {
        return DB::transaction(
            function () use (
                $proposal,
                $reviewerId,
                $resolvedProvinceId
            ): AttendanceMeetingReferenceProposal {
                $proposal =
                    AttendanceMeetingReferenceProposal::query()
                        ->lockForUpdate()
                        ->findOrFail($proposal->id);

                $this->ensurePending($proposal);

                $province =
                    $this->resolveProvince(
                        $proposal,
                        $resolvedProvinceId
                    );

                $reference =
                    match ($proposal->database_field) {
                        'school' =>
                            $this->resolveSchool(
                                $proposal,
                                $province
                            ),

                        'locality' =>
                            $this->resolveLocality(
                                $proposal,
                                $province
                            ),

                        default =>
                            throw new RuntimeException(
                                'This reference proposal type is not supported.'
                            ),
                    };

                $this->assignReference(
                    $proposal,
                    $reference
                );

                /*
                 * Keep the originally submitted Province
                 * untouched for provenance.
                 *
                 * The resolved Province is represented by
                 * the approved School / Locality reference.
                 */
                $proposal->forceFill([
                    'status' =>
                        AttendanceMeetingReferenceProposal
                            ::STATUS_APPROVED,

                    'resolved_reference_type' =>
                        $proposal->database_field,

                    'resolved_reference_id' =>
                        $reference->id,

                    'reviewed_by_id' =>
                        $reviewerId,

                    'reviewed_at' =>
                        now(),

                    'review_note' =>
                        null,
                ])->save();

                $this->logReview(
                    proposal: $proposal,
                    approved: true,
                    reference: $reference,
                    province: $province,
                    reviewerId: $reviewerId,
                );

                return $proposal->fresh();
            }
        );
    }


    public function reject(
        AttendanceMeetingReferenceProposal $proposal,
        ?int $reviewerId
    ): AttendanceMeetingReferenceProposal {
        return DB::transaction(
            function () use (
                $proposal,
                $reviewerId
            ): AttendanceMeetingReferenceProposal {
                $proposal =
                    AttendanceMeetingReferenceProposal::query()
                        ->lockForUpdate()
                        ->findOrFail($proposal->id);

                $this->ensurePending($proposal);

                $proposal->forceFill([
                    'status' =>
                        AttendanceMeetingReferenceProposal
                            ::STATUS_REJECTED,

                    'resolved_reference_type' =>
                        null,

                    'resolved_reference_id' =>
                        null,

                    'reviewed_by_id' =>
                        $reviewerId,

                    'reviewed_at' =>
                        now(),

                    'review_note' =>
                        null,
                ])->save();

                $this->logReview(
                    proposal: $proposal,
                    approved: false,
                    reference: null,
                    province:
                        $proposal->proposedProvince,
                    reviewerId: $reviewerId,
                );

                return $proposal->fresh();
            }
        );
    }


    private function ensurePending(
        AttendanceMeetingReferenceProposal $proposal
    ): void {
        if (
            $proposal->status
            !== AttendanceMeetingReferenceProposal
                ::STATUS_PENDING
        ) {
            throw new RuntimeException(
                'This reference proposal has already been reviewed.'
            );
        }
    }


    private function resolveProvince(
        AttendanceMeetingReferenceProposal $proposal,
        ?int $resolvedProvinceId
    ): Province {
        $provinceId =
            $resolvedProvinceId
            ?? $proposal->proposed_province_id;

        if (! $provinceId) {
            throw new RuntimeException(
                'Select an existing Province before approving this proposal.'
            );
        }

        $province =
            Province::query()
                ->whereKey($provinceId)
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $province) {
            throw new RuntimeException(
                'Select an active existing Province before approving this proposal.'
            );
        }

        return $province;
    }


    private function resolveSchool(
        AttendanceMeetingReferenceProposal $proposal,
        Province $province
    ): School {
        $name =
            trim(
                (string)
                $proposal->proposed_label
            );

        $cityMunicipality =
            filled(
                $proposal
                    ->proposed_city_municipality
            )
                ? trim(
                    (string)
                    $proposal
                        ->proposed_city_municipality
                )
                : null;

        if ($name === '') {
            throw new RuntimeException(
                'The proposed School name is empty.'
            );
        }

        /*
         * Match the School Setup duplicate rule:
         *
         * name + province + city/municipality
         */
        $school =
            School::query()
                ->where(
                    'name',
                    $name
                )
                ->where(
                    'province_id',
                    $province->id
                )
                ->when(
                    $cityMunicipality === null,
                    fn ($query) =>
                        $query->whereNull(
                            'city_municipality'
                        ),
                    fn ($query) =>
                        $query->where(
                            'city_municipality',
                            $cityMunicipality
                        )
                )
                ->first();

        if ($school) {
            if (! $school->is_active) {
                $school->forceFill([
                    'is_active' => true,
                ])->save();
            }

            return $school;
        }

        return School::query()
            ->create([
                'name' =>
                    $name,

                'short_name' =>
                    null,

                'province_id' =>
                    $province->id,

                'city_municipality' =>
                    $cityMunicipality,

                'is_active' =>
                    true,
            ]);
    }


    private function resolveLocality(
        AttendanceMeetingReferenceProposal $proposal,
        Province $province
    ): Locality {
        $name =
            trim(
                (string)
                $proposal->proposed_label
            );

        if ($name === '') {
            throw new RuntimeException(
                'The proposed Locality name is empty.'
            );
        }

        /*
         * Localities are unique by:
         *
         * province + name
         */
        $locality =
            Locality::query()
                ->where(
                    'province_id',
                    $province->id
                )
                ->where(
                    'name',
                    $name
                )
                ->first();

        if ($locality) {
            if (! $locality->is_active) {
                $locality->forceFill([
                    'is_active' => true,
                ])->save();
            }

            return $locality;
        }

        return Locality::query()
            ->create([
                'province_id' =>
                    $province->id,

                'name' =>
                    $name,

                'is_active' =>
                    true,
            ]);
    }


    private function assignReference(
        AttendanceMeetingReferenceProposal $proposal,
        School|Locality $reference
    ): void {
        if ($proposal->database_field === 'locality') {
            $this->assignLocality(
                $proposal,
                $reference
            );

            return;
        }

        if ($proposal->database_field === 'school') {
            $this->assignSchool(
                $proposal,
                $reference
            );

            return;
        }

        throw new RuntimeException(
            'Unsupported reference assignment.'
        );
    }


    private function assignLocality(
        AttendanceMeetingReferenceProposal $proposal,
        Locality $locality
    ): void {
        /*
         * Guest response:
         * create/resolve the reference only.
         */
        if (
            ! $proposal->person_id
            && ! $proposal->campus_contact_id
            && ! $proposal->gospel_contact_id
        ) {
            return;
        }

        if ($proposal->person_id) {
            $person =
                $proposal->person()
                    ->first();

            if (! $person) {
                throw new RuntimeException(
                    'The People Database record no longer exists.'
                );
            }

            $person->forceFill([
                'locality_id' =>
                    $locality->id,
            ])->save();

            return;
        }

        if ($proposal->campus_contact_id) {
            $contact =
                $proposal->campusContact()
                    ->first();

            if (! $contact) {
                throw new RuntimeException(
                    'The Campus Contact no longer exists.'
                );
            }

            $contact->forceFill([
                'locality_id' =>
                    $locality->id,
            ])->save();

            return;
        }

        if ($proposal->gospel_contact_id) {
            $contact =
                $proposal->gospelContact()
                    ->first();

            if (! $contact) {
                throw new RuntimeException(
                    'The Gospel Contact no longer exists.'
                );
            }

            $contact->forceFill([
                'locality_id' =>
                    $locality->id,
            ])->save();
        }
    }


    private function assignSchool(
        AttendanceMeetingReferenceProposal $proposal,
        School $school
    ): void {
        /*
         * Guest response:
         * create/resolve the reference only.
         */
        if (
            ! $proposal->person_id
            && ! $proposal->campus_contact_id
            && ! $proposal->gospel_contact_id
        ) {
            return;
        }

        if ($proposal->person_id) {
            $profile =
                EducationProfile::query()
                    ->firstOrNew([
                        'person_id' =>
                            $proposal->person_id,
                    ]);

            $profile->school_id =
                $school->id;

            $profile->save();

            return;
        }

        if ($proposal->campus_contact_id) {
            $contact =
                $proposal->campusContact()
                    ->first();

            if (! $contact) {
                throw new RuntimeException(
                    'The Campus Contact no longer exists.'
                );
            }

            $contact->forceFill([
                'school_id' =>
                    $school->id,
            ])->save();

            return;
        }

        /*
         * Gospel Contacts do not currently own school_id.
         *
         * Resolve/create the School reference, but do not
         * manufacture a People or Campus record.
         */
    }


    private function logReview(
        AttendanceMeetingReferenceProposal $proposal,
        bool $approved,
        School|Locality|null $reference,
        ?Province $province,
        ?int $reviewerId
    ): void {
        $proposal->loadMissing([
            'response.session.sheet',
            'person',
            'campusContact',
            'gospelContact',
        ]);

        $subject =
            $proposal->person
            ?? $proposal->campusContact
            ?? $proposal->gospelContact
            ?? $proposal->response;

        ActivityLogger::log(
            action: $approved
                ? 'meeting_form.reference_proposal.approved'
                : 'meeting_form.reference_proposal.rejected',

            subject:
                $subject,

            description: $approved
                ? 'Approved a meeting-form reference proposal for '
                    . $proposal->fieldLabel()
                    . '.'
                : 'Rejected a meeting-form reference proposal for '
                    . $proposal->fieldLabel()
                    . '.',

            oldValues: [
                'proposal_id' =>
                    $proposal->id,

                'database_field' =>
                    $proposal->database_field,

                'proposed_label' =>
                    $proposal->proposed_label,

                'submitted_province' =>
                    $proposal->proposed_province_name,

                'city_municipality' =>
                    $proposal
                        ->proposed_city_municipality,

                'status' =>
                    AttendanceMeetingReferenceProposal
                        ::STATUS_PENDING,
            ],

            newValues: [
                'status' =>
                    $approved
                        ? AttendanceMeetingReferenceProposal
                            ::STATUS_APPROVED
                        : AttendanceMeetingReferenceProposal
                            ::STATUS_REJECTED,

                'resolved_reference_type' =>
                    $approved
                        ? $proposal->database_field
                        : null,

                'resolved_reference_id' =>
                    $reference?->id,

                'resolved_reference_name' =>
                    $reference?->name,

                'resolved_province' =>
                    $province?->name,

                'reviewed_by_id' =>
                    $reviewerId,
            ],
        );
    }
}
