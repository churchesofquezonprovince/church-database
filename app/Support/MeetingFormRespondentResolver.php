<?php

namespace App\Support;

use App\Models\AttendanceMeetingResponse;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Person;
use Carbon\CarbonInterface;

final class MeetingFormRespondentResolver
{
    public function resolve(
        string $type,
        int $id
    ): ?array {
        return match ($type) {
            AttendanceMeetingResponse::RESPONDENT_PERSON =>
                $this->resolvePerson($id),

            AttendanceMeetingResponse::RESPONDENT_CAMPUS =>
                $this->resolveCampus($id),

            AttendanceMeetingResponse::RESPONDENT_GOSPEL =>
                $this->resolveGospel($id),

            default =>
                null,
        };
    }

    public function fieldValue(
        array $identity,
        string $field
    ): array {
        $definition =
            MeetingFormDatabaseFieldRegistry::definition(
                $field
            );

        if (! $definition) {
            return $this->emptyField(
                $field,
                null
            );
        }

        $person =
            $identity['person']
            ?? null;

        $campus =
            $identity['campus_contact']
            ?? null;

        $gospel =
            $identity['gospel_contact']
            ?? null;

        $result =
            match ($field) {
                /*
                 * ---------------------------------------------
                 * PERSONAL INFORMATION
                 * ---------------------------------------------
                 */
                'firstname' =>
                    $this->firstAvailable([
                        [
                            $person?->firstname,
                            'People Database',
                        ],
                        [
                            $campus?->firstname,
                            'Campus Database',
                        ],
                        [
                            $gospel?->firstname,
                            'Gospel Contacts',
                        ],
                    ]),

                'middlename' =>
                    $this->from(
                        $person?->middlename,
                        'People Database'
                    ),

                'lastname' =>
                    $this->firstAvailable([
                        [
                            $person?->lastname,
                            'People Database',
                        ],
                        [
                            $campus?->lastname,
                            'Campus Database',
                        ],
                        [
                            $gospel?->lastname,
                            'Gospel Contacts',
                        ],
                    ]),

                'suffix' =>
                    $this->from(
                        $person?->suffix,
                        'People Database'
                    ),

                'nickname' =>
                    $this->from(
                        $person?->nickname,
                        'People Database'
                    ),

                'sex' =>
                    $this->firstAvailable([
                        [
                            $person?->sex,
                            'People Database',
                        ],
                        [
                            $campus?->sex,
                            'Campus Database',
                        ],
                        [
                            $gospel?->sex,
                            'Gospel Contacts',
                        ],
                    ]),

                'birthdate' =>
                    $this->from(
                        $person?->birthdate,
                        'People Database'
                    ),

                'birthplace' =>
                    $this->from(
                        $person?->birthplace,
                        'People Database'
                    ),

                /*
                 * ---------------------------------------------
                 * CHURCH INFORMATION
                 * ---------------------------------------------
                 */
                'locality' =>
                    $this->localityValue(
                        $person,
                        $campus,
                        $gospel
                    ),

                'baptism_date' =>
                    $this->from(
                        $person
                            ?->churchProfile
                            ?->baptism_date,
                        'Church Information'
                    ),

                'first_contact_date' =>
                    $this->from(
                        $person
                            ?->churchProfile
                            ?->first_contact_date,
                        'Church Information'
                    ),

                'contact_origin' =>
                    $this->from(
                        $person
                            ?->churchProfile
                            ?->contact_origin,
                        'Church Information'
                    ),

                'contact_origin_details' =>
                    $this->from(
                        $person
                            ?->churchProfile
                            ?->contact_origin_details,
                        'Church Information'
                    ),

                'service' =>
                    $this->from(
                        $person
                            ?->churchProfile
                            ?->service,
                        'Church Information'
                    ),

                'church_status' =>
                    $this->from(
                        $person
                            ?->churchProfile
                            ?->status,
                        'Church Information'
                    ),

                /*
                 * ---------------------------------------------
                 * CONTACT INFORMATION
                 * ---------------------------------------------
                 */
                'contact_number' =>
                    $this->firstAvailable([
                        [
                            $person?->contact_number,
                            'People Database',
                        ],
                        [
                            $campus?->contact_number,
                            'Campus Database',
                        ],
                        [
                            $gospel?->contact_number,
                            'Gospel Contacts',
                        ],
                    ]),

                'email' =>
                    $this->firstAvailable([
                        [
                            $person?->email,
                            'People Database',
                        ],
                        [
                            $campus?->email,
                            'Campus Database',
                        ],
                        [
                            $gospel?->email,
                            'Gospel Contacts',
                        ],
                    ]),

                'facebook_account' =>
                    $this->firstAvailable([
                        [
                            $person?->facebook_account,
                            'People Database',
                        ],
                        [
                            $campus?->facebook_account,
                            'Campus Database',
                        ],
                        [
                            $gospel?->facebook_account,
                            'Gospel Contacts',
                        ],
                    ]),

                'permanent_address' =>
                    $this->from(
                        $person?->permanent_address,
                        'People Database'
                    ),

                'home_address' =>
                    $this->firstAvailable([
                        [
                            $person?->home_address,
                            'People Database',
                        ],
                        [
                            $gospel?->address,
                            'Gospel Contacts',
                        ],
                    ]),

                'emergency_contact_relationship' =>
                    $this->from(
                        $person
                            ?->emergency_contact_relationship,
                        'People Database'
                    ),

                'emergency_contact_number' =>
                    $this->from(
                        $person
                            ?->emergency_contact_number,
                        'People Database'
                    ),

                /*
                 * ---------------------------------------------
                 * EDUCATION / CAMPUS INFORMATION
                 * ---------------------------------------------
                 */
                'school' =>
                    $this->schoolValue(
                        $person,
                        $campus
                    ),

                'grade_level' =>
                    $this->firstAvailable([
                        [
                            $person
                                ?->educationProfile
                                ?->grade_level,
                            'Education Profile',
                        ],
                        [
                            $campus?->grade_level,
                            'Campus Database',
                        ],
                    ]),

                'course_strand' =>
                    $this->firstAvailable([
                        [
                            $person
                                ?->educationProfile
                                ?->course_strand,
                            'Education Profile',
                        ],
                        [
                            $campus?->course_strand,
                            'Campus Database',
                        ],
                    ]),

                'occupation' =>
                    $this->from(
                        $person
                            ?->educationProfile
                            ?->occupation,
                        'Education Profile'
                    ),

                'workplace' =>
                    $this->from(
                        $person
                            ?->educationProfile
                            ?->workplace,
                        'Education Profile'
                    ),

                /*
                 * ---------------------------------------------
                 * GOSPEL CONTACT INFORMATION
                 * ---------------------------------------------
                 */
                'gospel_address' =>
                    $this->from(
                        $gospel?->address,
                        'Gospel Contacts'
                    ),

                'contact_place' =>
                    $this->from(
                        $gospel?->contact_place,
                        'Gospel Contacts'
                    ),

                default =>
                    [
                        'value' => null,
                        'display' => null,
                        'source' => null,
                    ],
            };

        return [
            'field' =>
                $field,

            'label' =>
                $definition['label']
                ?? $field,

            'input' =>
                $definition['input']
                ?? 'text',

            'owner' =>
                $definition['owner']
                ?? null,

            'requires_person' =>
                (bool) (
                    $definition['requires_person']
                    ?? false
                ),

            'has_person' =>
                $person instanceof Person,

            'has_existing_value' =>
                $this->hasValue(
                    $result['value']
                    ?? null
                ),

            'value' =>
                $result['value']
                ?? null,

            'display' =>
                $result['display']
                ?? null,

            'source' =>
                $result['source']
                ?? null,
        ];
    }

    public function fields(
        array $identity,
        iterable $fields
    ): array {
        $resolved = [];

        foreach ($fields as $field) {
            $resolved[$field] =
                $this->fieldValue(
                    $identity,
                    (string) $field
                );
        }

        return $resolved;
    }

    private function resolvePerson(
        int $id
    ): ?array {
        $person =
            Person::query()
                ->with([
                    'localityRecord',
                    'churchProfile',
                    'educationProfile.school',
                    'campusContact.school',
                    'campusContact.localityRecord',
                    'gospelContact.localityRecord',
                ])
                ->find($id);

        if (! $person) {
            return null;
        }

        return [
            'selected_type' =>
                AttendanceMeetingResponse::RESPONDENT_PERSON,

            'selected_id' =>
                (int) $person->id,

            'canonical_type' =>
                AttendanceMeetingResponse::RESPONDENT_PERSON,

            'canonical_id' =>
                (int) $person->id,

            'name' =>
                $person->display_name,

            'source_label' =>
                'People Database',

            'person' =>
                $person,

            'campus_contact' =>
                $person->campusContact,

            'gospel_contact' =>
                $person->gospelContact,
        ];
    }

    private function resolveCampus(
        int $id
    ): ?array {
        $contact =
            CampusContact::query()
                ->with([
                    'school',
                    'localityRecord',
                    'person.localityRecord',
                    'person.churchProfile',
                    'person.educationProfile.school',
                    'person.gospelContact.localityRecord',
                ])
                ->find($id);

        if (! $contact) {
            return null;
        }

        $person =
            $contact->person;

        return [
            'selected_type' =>
                AttendanceMeetingResponse::RESPONDENT_CAMPUS,

            'selected_id' =>
                (int) $contact->id,

            'canonical_type' =>
                $person
                    ? AttendanceMeetingResponse::RESPONDENT_PERSON
                    : AttendanceMeetingResponse::RESPONDENT_CAMPUS,

            'canonical_id' =>
                $person
                    ? (int) $person->id
                    : (int) $contact->id,

            'name' =>
                $person?->display_name
                ?? $contact->display_name,

            'source_label' =>
                'Campus Database',

            'person' =>
                $person,

            'campus_contact' =>
                $contact,

            'gospel_contact' =>
                $person?->gospelContact,
        ];
    }

    private function resolveGospel(
        int $id
    ): ?array {
        $contact =
            GospelContact::query()
                ->with([
                    'localityRecord',
                    'person.localityRecord',
                    'person.churchProfile',
                    'person.educationProfile.school',
                    'person.campusContact.school',
                    'person.campusContact.localityRecord',
                ])
                ->find($id);

        if (! $contact) {
            return null;
        }

        $person =
            $contact->person;

        return [
            'selected_type' =>
                AttendanceMeetingResponse::RESPONDENT_GOSPEL,

            'selected_id' =>
                (int) $contact->id,

            'canonical_type' =>
                $person
                    ? AttendanceMeetingResponse::RESPONDENT_PERSON
                    : AttendanceMeetingResponse::RESPONDENT_GOSPEL,

            'canonical_id' =>
                $person
                    ? (int) $person->id
                    : (int) $contact->id,

            'name' =>
                $person?->display_name
                ?? $contact->display_name,

            'source_label' =>
                'Gospel Contacts',

            'person' =>
                $person,

            'campus_contact' =>
                $person?->campusContact,

            'gospel_contact' =>
                $contact,
        ];
    }

    private function localityValue(
        ?Person $person,
        ?CampusContact $campus,
        ?GospelContact $gospel
    ): array {
        if (
            filled(
                $person?->locality_id
            )
        ) {
            return [
                'value' =>
                    (int) $person->locality_id,

                'display' =>
                    $person->localityRecord?->name
                    ?? $person->locality,

                'source' =>
                    'Church Information',
            ];
        }

        if (
            filled(
                $campus?->locality_id
            )
        ) {
            return [
                'value' =>
                    (int) $campus->locality_id,

                'display' =>
                    $campus->localityRecord?->name
                    ?? $campus->locality,

                'source' =>
                    'Campus Database',
            ];
        }

        if (
            filled(
                $gospel?->locality_id
            )
        ) {
            return [
                'value' =>
                    (int) $gospel->locality_id,

                'display' =>
                    $gospel->localityRecord?->name
                    ?? $gospel->locality,

                'source' =>
                    'Gospel Contacts',
            ];
        }

        return [
            'value' => null,
            'display' => null,
            'source' => null,
        ];
    }

    private function schoolValue(
        ?Person $person,
        ?CampusContact $campus
    ): array {
        $education =
            $person?->educationProfile;

        if (
            filled(
                $education?->school_id
            )
        ) {
            return [
                'value' =>
                    (int) $education->school_id,

                'display' =>
                    $education->school?->name,

                'source' =>
                    'Education Profile',
            ];
        }

        if (
            filled(
                $campus?->school_id
            )
        ) {
            return [
                'value' =>
                    (int) $campus->school_id,

                'display' =>
                    $campus->school?->name,

                'source' =>
                    'Campus Database',
            ];
        }

        return [
            'value' => null,
            'display' => null,
            'source' => null,
        ];
    }

    private function firstAvailable(
        array $candidates
    ): array {
        foreach ($candidates as $candidate) {
            [
                $value,
                $source,
            ] = $candidate;

            if ($this->hasValue($value)) {
                return $this->from(
                    $value,
                    $source
                );
            }
        }

        return [
            'value' => null,
            'display' => null,
            'source' => null,
        ];
    }

    private function from(
        mixed $value,
        string $source
    ): array {
        if (! $this->hasValue($value)) {
            return [
                'value' => null,
                'display' => null,
                'source' => null,
            ];
        }

        return [
            'value' =>
                $this->normalizedValue(
                    $value
                ),

            'display' =>
                $this->displayValue(
                    $value
                ),

            'source' =>
                $source,
        ];
    }

    private function normalizedValue(
        mixed $value
    ): mixed {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    private function displayValue(
        mixed $value
    ): ?string {
        if ($value instanceof CarbonInterface) {
            return $value->format(
                'F j, Y'
            );
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(
                'F j, Y'
            );
        }

        if (is_array($value)) {
            return collect($value)
                ->filter(
                    fn ($item): bool =>
                        filled($item)
                )
                ->implode(', ');
        }

        return filled($value)
            ? (string) $value
            : null;
    }

    private function hasValue(
        mixed $value
    ): bool {
        if (is_array($value)) {
            return collect($value)
                ->filter(
                    fn ($item): bool =>
                        filled($item)
                )
                ->isNotEmpty();
        }

        return filled($value);
    }

    private function emptyField(
        string $field,
        ?array $definition
    ): array {
        return [
            'field' =>
                $field,

            'label' =>
                $definition['label']
                ?? $field,

            'input' =>
                $definition['input']
                ?? 'text',

            'owner' =>
                $definition['owner']
                ?? null,

            'requires_person' =>
                (bool) (
                    $definition['requires_person']
                    ?? false
                ),

            'has_person' =>
                false,

            'has_existing_value' =>
                false,

            'value' =>
                null,

            'display' =>
                null,

            'source' =>
                null,
        ];
    }
}
