<?php

namespace App\Support;

final class MeetingFormDatabaseFieldRegistry
{
    public const REFERENCE_PROPOSAL_VALUE =
        '__not_listed__';

    /*
     * These fields may silently contribute toward the
     * public form's autofill threshold.
     *
     * They are NEVER identified to the participant as
     * verification fields.
     *
     * Names are intentionally excluded because public name
     * search already reveals them.
     *
     * Baptism / church-history fields are intentionally
     * excluded because they may themselves be information
     * the form is trying to protect.
     */
    public const AUTOFILL_MATCH_ELIGIBLE_FIELDS = [
        'birthdate',
        'locality',
        'school',
        'grade_level',
        'course_strand',
        'contact_number',
        'email',
    ];

    public static function definitions(): array
    {
        return [

            /*
             * ====================================================
             * PERSONAL INFORMATION
             * ====================================================
             */

            'firstname' => [
                'label' => 'First Name',
                'group' => 'Personal Information',
                'input' => 'text',
                'owner' => 'person',
                'sources' => [
                    'person',
                    'campus_contact',
                    'gospel_contact',
                ],
            ],

            'middlename' => [
                'label' => 'Middle Name',
                'group' => 'Personal Information',
                'input' => 'text',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],

            'lastname' => [
                'label' => 'Last Name',
                'group' => 'Personal Information',
                'input' => 'text',
                'owner' => 'person',
                'sources' => [
                    'person',
                    'campus_contact',
                    'gospel_contact',
                ],
            ],

            'suffix' => [
                'label' => 'Suffix',
                'group' => 'Personal Information',
                'input' => 'text',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],

            'nickname' => [
                'label' => 'Nickname',
                'group' => 'Personal Information',
                'input' => 'text',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],

            'sex' => [
                'label' => 'Sex',
                'group' => 'Personal Information',
                'input' => 'sex',
                'owner' => 'person',
                'sources' => [
                    'person',
                    'campus_contact',
                    'gospel_contact',
                ],
            ],

            'birthdate' => [
                'label' => 'Birthday',
                'group' => 'Personal Information',
                'input' => 'date',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],

            'birthplace' => [
                'label' => 'Birthplace',
                'group' => 'Personal Information',
                'input' => 'text',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],


            /*
             * ====================================================
             * CHURCH INFORMATION
             * ====================================================
             *
             * Locality belongs here administratively.
             *
             * The canonical Person value currently lives on
             * persons.locality_id, with Campus/Gospel values
             * available as fallbacks for unlinked contacts.
             */

            'locality' => [
                'label' => 'Locality',
                'group' => 'Church Information',
                'input' => 'locality',
                'owner' => 'person',
                'sources' => [
                    'person',
                    'campus_contact',
                    'gospel_contact',
                ],
                'allow_reference_proposal' => true,
            ],

            'baptism_date' => [
                'label' => 'Baptism Date',
                'group' => 'Church Information',
                'input' => 'date',
                'owner' => 'church_profile',
                'sources' => [
                    'church_profile',
                ],
                'requires_person' => true,
            ],

            'first_contact_date' => [
                'label' => 'First Contact Date',
                'group' => 'Church Information',
                'input' => 'date',
                'owner' => 'church_profile',
                'sources' => [
                    'church_profile',
                ],
                'requires_person' => true,
            ],

            'contact_origin' => [
                'label' => 'Contact Origin',
                'group' => 'Church Information',
                'input' => 'text',
                'owner' => 'church_profile',
                'sources' => [
                    'church_profile',
                ],
                'requires_person' => true,
            ],

            'contact_origin_details' => [
                'label' => 'Contact Origin Details',
                'group' => 'Church Information',
                'input' => 'textarea',
                'owner' => 'church_profile',
                'sources' => [
                    'church_profile',
                ],
                'requires_person' => true,
            ],

            'service' => [
                'label' => 'Service',
                'group' => 'Church Information',
                'input' => 'multi_value',
                'owner' => 'church_profile',
                'sources' => [
                    'church_profile',
                ],
                'requires_person' => true,
            ],

            'church_status' => [
                'label' => 'Church Status',
                'group' => 'Church Information',
                'input' => 'text',
                'owner' => 'church_profile',
                'sources' => [
                    'church_profile',
                ],
                'requires_person' => true,
            ],


            /*
             * ====================================================
             * CONTACT INFORMATION
             * ====================================================
             */

            'contact_number' => [
                'label' => 'Contact Number',
                'group' => 'Contact Information',
                'input' => 'tel',
                'owner' => 'person',
                'sources' => [
                    'person',
                    'campus_contact',
                    'gospel_contact',
                ],
            ],

            'email' => [
                'label' => 'Email Address',
                'group' => 'Contact Information',
                'input' => 'email',
                'owner' => 'person',
                'sources' => [
                    'person',
                    'campus_contact',
                    'gospel_contact',
                ],
            ],

            'facebook_account' => [
                'label' => 'Facebook Account',
                'group' => 'Contact Information',
                'input' => 'text',
                'owner' => 'person',
                'sources' => [
                    'person',
                    'campus_contact',
                    'gospel_contact',
                ],
            ],

            'permanent_address' => [
                'label' => 'Permanent Address',
                'group' => 'Contact Information',
                'input' => 'textarea',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],

            'home_address' => [
                'label' => 'Home Address',
                'group' => 'Contact Information',
                'input' => 'textarea',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],

            'emergency_contact_relationship' => [
                'label' => 'Emergency Contact Relationship',
                'group' => 'Contact Information',
                'input' => 'text',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],

            'emergency_contact_number' => [
                'label' => 'Emergency Contact Number',
                'group' => 'Contact Information',
                'input' => 'tel',
                'owner' => 'person',
                'sources' => [
                    'person',
                ],
            ],


            /*
             * ====================================================
             * EDUCATION / CAMPUS INFORMATION
             * ====================================================
             */

            'school' => [
                'label' => 'School / Campus',
                'group' => 'Education / Campus Information',
                'input' => 'school',
                'owner' => 'education_profile',
                'sources' => [
                    'education_profile',
                    'campus_contact',
                ],
                'allow_reference_proposal' => true,
            ],

            'grade_level' => [
                'label' => 'Grade Level',
                'group' => 'Education / Campus Information',
                'input' => 'grade_level',
                'owner' => 'education_profile',
                'sources' => [
                    'education_profile',
                    'campus_contact',
                ],
                'options' => [
                    'Pre-School' => 'Pre-School',
                    'Kinder I' => 'Kinder I',
                    'Kinder II' => 'Kinder II',
                    'Grade 1' => 'Grade 1',
                    'Grade 2' => 'Grade 2',
                    'Grade 3' => 'Grade 3',
                    'Grade 4' => 'Grade 4',
                    'Grade 5' => 'Grade 5',
                    'Grade 6' => 'Grade 6',
                    'Grade 7' => 'Grade 7',
                    'Grade 8' => 'Grade 8',
                    'Grade 9' => 'Grade 9',
                    'Grade 10' => 'Grade 10',
                    'Grade 11' => 'Grade 11',
                    'Grade 12' => 'Grade 12',
                    'College - Year 1' => 'College - Year 1',
                    'College - Year 2' => 'College - Year 2',
                    'College - Year 3' => 'College - Year 3',
                    'College - Year 4' => 'College - Year 4',
                    'College - Year 5' => 'College - Year 5',
                    'Graduated' => 'Graduated',
                    'Not Applicable' => 'Not Applicable',
                ],
            ],

            'course_strand' => [
                'label' => 'Course / Strand',
                'group' => 'Education / Campus Information',
                'input' => 'text',
                'owner' => 'education_profile',
                'sources' => [
                    'education_profile',
                    'campus_contact',
                ],
            ],

            'occupation' => [
                'label' => 'Occupation',
                'group' => 'Education / Campus Information',
                'input' => 'text',
                'owner' => 'education_profile',
                'sources' => [
                    'education_profile',
                ],
            ],

            'workplace' => [
                'label' => 'Workplace',
                'group' => 'Education / Campus Information',
                'input' => 'text',
                'owner' => 'education_profile',
                'sources' => [
                    'education_profile',
                ],
            ],


            /*
             * ====================================================
             * GOSPEL CONTACT INFORMATION
             * ====================================================
             */

            'gospel_address' => [
                'label' => 'Gospel Contact Address',
                'group' => 'Gospel Contact Information',
                'input' => 'textarea',
                'owner' => 'gospel_contact',
                'sources' => [
                    'gospel_contact',
                ],
            ],

            'contact_place' => [
                'label' => 'Gospel Contact Place',
                'group' => 'Gospel Contact Information',
                'input' => 'text',
                'owner' => 'gospel_contact',
                'sources' => [
                    'gospel_contact',
                ],
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(
            self::definitions()
        );
    }

    public static function autofillMatchEligibleKeys(): array
    {
        return self::AUTOFILL_MATCH_ELIGIBLE_FIELDS;
    }

    public static function isAutofillMatchEligible(
        ?string $field
    ): bool {
        return filled($field)
            && in_array(
                $field,
                self::AUTOFILL_MATCH_ELIGIBLE_FIELDS,
                true
            );
    }

    public static function definition(
        ?string $field
    ): ?array {
        if (blank($field)) {
            return null;
        }

        return self::definitions()[
            $field
        ] ?? null;
    }

    public static function label(
        ?string $field
    ): ?string {
        return self::definition(
            $field
        )['label'] ?? null;
    }

    public static function input(
        ?string $field
    ): ?string {
        return self::definition(
            $field
        )['input'] ?? null;
    }

    public static function allowsReferenceProposal(
        ?string $field
    ): bool {
        return (bool) (
            self::definition(
                $field
            )['allow_reference_proposal']
            ?? false
        );
    }

    public static function options(
        ?string $field
    ): array {
        $options =
            self::definition(
                $field
            )['options'] ?? [];

        return is_array($options)
            ? $options
            : [];
    }

    public static function owner(
        ?string $field
    ): ?string {
        return self::definition(
            $field
        )['owner'] ?? null;
    }

    public static function groupedOptions(): array
    {
        $groups = [];

        foreach (
            self::definitions()
            as $key => $definition
        ) {
            $group =
                $definition['group']
                ?? 'Other';

            $groups[$group][$key] =
                $definition['label'];
        }

        return $groups;
    }
}
