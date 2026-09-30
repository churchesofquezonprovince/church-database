<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ConferenceRegistrationImporter
{
    public const SOURCE_TYPE = 'conference_reg';

    private const FIELD_TYPES = [
        'Registration Locality' => 'text',
        'Year Level' => 'text',
        'With Invite?' => 'checkbox',
        'IDs in Canva' => 'checkbox',
    ];

    public function preview(
        int $eventId,
        string $date,
        string $path
    ): array {
        $rows = $this->loadPayload($path);

        return $this->buildPlan(
            $eventId,
            $date,
            $rows
        );
    }

    public function apply(
        int $eventId,
        string $date,
        string $path
    ): array {
        $rows = $this->loadPayload($path);

        return DB::transaction(
            function () use (
                $eventId,
                $date,
                $rows
            ): array {
                DB::table('conference_events')
                    ->where('id', $eventId)
                    ->lockForUpdate()
                    ->first();

                $plan = $this->buildPlan(
                    $eventId,
                    $date,
                    $rows
                );

                if ($plan['blockers'] !== []) {
                    throw new RuntimeException(
                        "Import has blocking conflicts:\n- "
                        . implode(
                            "\n- ",
                            $plan['blockers']
                        )
                    );
                }

                $beforeAttendance =
                    $this->attendanceRecordCount(
                        $plan['session_id']
                    );

                $fields =
                    $this->ensureFields(
                        $eventId
                    );

                foreach (
                    $plan['rows']
                    as $planned
                ) {
                    $identity =
                        $this->materializeIdentity(
                            $plan['sheet_id'],
                            $planned
                        );

                    $attendeeKey =
                        $identity['attendee_key'];

                    if (
                        $identity['kind']
                        === 'person'
                    ) {
                        $this->ensurePersonEnrollment(
                            $plan['sheet_id'],
                            $identity['person_id'],
                            $date
                        );

                        $this->ensurePersonRole(
                            $eventId,
                            $identity['person_id'],
                            $planned['role']
                        );
                    } else {
                        $this->ensureGuestEnrollment(
                            $identity['guest_id'],
                            $date
                        );

                        $this->ensureGuestRole(
                            $eventId,
                            $identity['guest_id'],
                            $planned['role']
                        );
                    }

                    $this->saveFieldValue(
                        $fields['Registration Locality'],
                        $attendeeKey,
                        $planned['registration_locality']
                    );

                    $this->saveFieldValue(
                        $fields['Year Level'],
                        $attendeeKey,
                        $planned['year_level']
                    );

                    $this->saveFieldValue(
                        $fields['With Invite?'],
                        $attendeeKey,
                        $planned['with_invite']
                    );

                    $this->saveFieldValue(
                        $fields['IDs in Canva'],
                        $attendeeKey,
                        $planned['id_in_canva']
                    );
                }

                $afterAttendance =
                    $this->attendanceRecordCount(
                        $plan['session_id']
                    );

                if (
                    $beforeAttendance
                    !== $afterAttendance
                ) {
                    throw new RuntimeException(
                        'Attendance records changed during '
                        . 'registration import. Rolling back.'
                    );
                }

                $result =
                    $this->buildPlan(
                        $eventId,
                        $date,
                        $rows
                    );

                $result['attendance_records_before'] =
                    $beforeAttendance;

                $result['attendance_records_after'] =
                    $afterAttendance;

                return $result;
            }
        );
    }

    private function loadPayload(
        string $path
    ): array {
        if (! is_file($path)) {
            throw new RuntimeException(
                "Registration payload not found: {$path}"
            );
        }

        $rows =
            json_decode(
                file_get_contents($path),
                true,
                flags: JSON_THROW_ON_ERROR
            );

        if (
            ! is_array($rows)
            || count($rows) !== 157
        ) {
            throw new RuntimeException(
                'Expected exactly 157 normalized registrations.'
            );
        }

        return $rows;
    }

    private function buildPlan(
        int $eventId,
        string $date,
        array $rows
    ): array {
        $event =
            DB::table('conference_events')
                ->where('id', $eventId)
                ->first();

        if (! $event) {
            throw new RuntimeException(
                "Conference event #{$eventId} not found."
            );
        }

        $sheetId =
            (int) $event->attendance_sheet_id;

        $session =
            DB::table('attendance_sessions as s')
                ->join(
                    'conference_sessions as cs',
                    'cs.attendance_session_id',
                    '=',
                    's.id'
                )
                ->where(
                    'cs.conference_event_id',
                    $eventId
                )
                ->where(
                    's.attendance_sheet_id',
                    $sheetId
                )
                ->whereDate(
                    's.session_date',
                    $date
                )
                ->select('s.*')
                ->first();

        if (! $session) {
            throw new RuntimeException(
                "Conference date {$date} was not found "
                . "for event #{$eventId}."
            );
        }

        $people =
            Person::query()
                ->get([
                    'id',
                    'firstname',
                    'middlename',
                    'lastname',
                    'suffix',
                    'nickname',
                    'locality',
                ]);

        $aliases = [];

        foreach ($people as $person) {
            foreach (
                $this->personAliases(
                    $person
                )
                as $alias
            ) {
                $aliases[
                    $this->normalize($alias)
                ][
                    (int) $person->id
                ] = $person;
            }
        }

        $formGuests = [];

        foreach (
            DB::table(
                'attendance_guest_responses as map'
            )
                ->join(
                    'attendance_meeting_responses as response',
                    'response.id',
                    '=',
                    'map.attendance_meeting_response_id'
                )
                ->join(
                    'attendance_sessions as session',
                    'session.id',
                    '=',
                    'response.attendance_session_id'
                )
                ->join(
                    'attendance_guests as guest',
                    'guest.id',
                    '=',
                    'map.attendance_guest_id'
                )
                ->where(
                    'session.attendance_sheet_id',
                    $sheetId
                )
                ->whereNull(
                    'guest.linked_person_id'
                )
                ->get([
                    'guest.id',
                    'guest.name',
                    'guest.locality',
                    'response.id as response_id',
                ])
            as $guest
        ) {
            $formGuests[
                $this->normalize(
                    $guest->name
                )
            ][] = $guest;
        }

        $registrationGuests =
            DB::table('attendance_guests')
                ->where(
                    'attendance_sheet_id',
                    $sheetId
                )
                ->where(
                    'source_type',
                    self::SOURCE_TYPE
                )
                ->get()
                ->keyBy(
                    fn ($guest): int =>
                        (int) $guest->source_id
                );

        $fields =
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->get();

        $fieldByName =
            $fields->keyBy(
                fn ($field): string =>
                    mb_strtolower(
                        trim($field->name)
                    )
            );

        $blockers = [];

        foreach (
            self::FIELD_TYPES
            as $name => $type
        ) {
            $existing =
                $fieldByName->get(
                    mb_strtolower($name)
                );

            if (
                $existing
                && $existing->field_type !== $type
            ) {
                $blockers[] =
                    "{$name} exists as "
                    . "{$existing->field_type}; "
                    . "expected {$type}.";
            }
        }

        $fieldValues = [];

        if ($fields->isNotEmpty()) {
            foreach (
                DB::table(
                    'conference_participant_field_values'
                )
                    ->whereIn(
                        'conference_participant_field_id',
                        $fields->pluck('id')
                    )
                    ->get()
                as $value
            ) {
                $fieldValues[
                    (int) $value
                        ->conference_participant_field_id
                    . ':'
                    . (int) $value->attendee_key
                ] =
                    json_decode(
                        $value->value_json,
                        true
                    );
            }
        }

        $counts = [
            'person' => 0,
            'form_guest' => 0,
            'registration_guest' => 0,
            'new_guest' => 0,
        ];

        $alreadyEnrolled = 0;
        $needsEnrollment = 0;
        $roleCorrect = 0;
        $roleMissing = 0;

        $localityDifferences = [];
        $plannedRows = [];

        foreach ($rows as $row) {
            $name =
                trim(
                    (string) (
                        $row['name']
                        ?? ''
                    )
                );

            if ($name === '') {
                $blockers[] =
                    'Registration contains an empty name.';

                continue;
            }

            $role =
                (string) (
                    $row['role']
                    ?? ''
                );

            if (
                ! in_array(
                    $role,
                    [
                        'young_people',
                        'serving_one',
                    ],
                    true
                )
            ) {
                $blockers[] =
                    "{$name} has invalid role {$role}.";

                continue;
            }

            $normalized =
                $this->normalize(
                    $name
                );

            $personCandidates =
                $aliases[$normalized]
                ?? [];

            $identity = null;

            if (
                count($personCandidates) === 1
            ) {
                $person =
                    array_values(
                        $personCandidates
                    )[0];

                $identity = [
                    'kind' => 'person',
                    'person_id' =>
                        (int) $person->id,
                    'guest_id' => null,
                    'attendee_key' =>
                        (int) $person->id,
                    'source_id' =>
                        $this->sourceId($row),
                ];

                $counts['person']++;

                $registrationLocality =
                    trim(
                        (string) (
                            $row['locality']
                            ?? ''
                        )
                    );

                if (
                    $registrationLocality !== ''
                    && filled($person->locality)
                    && $this->normalize(
                        $registrationLocality
                    )
                        !==
                        $this->normalize(
                            $person->locality
                        )
                ) {
                    $localityDifferences[] = [
                        'name' => $name,
                        'registration' =>
                            $registrationLocality,
                        'database' =>
                            (string) $person->locality,
                    ];
                }
            } elseif (
                count($personCandidates) > 1
            ) {
                $blockers[] =
                    "{$name} matches multiple People.";

                continue;
            }

            if ($identity === null) {
                $guestCandidates =
                    $formGuests[$normalized]
                    ?? [];

                if (
                    count($guestCandidates) === 1
                ) {
                    $guest =
                        $guestCandidates[0];

                    $identity = [
                        'kind' => 'guest',
                        'person_id' => null,
                        'guest_id' =>
                            (int) $guest->id,
                        'attendee_key' =>
                            -1 * (int) $guest->id,
                        'source_id' =>
                            $this->sourceId($row),
                    ];

                    $counts['form_guest']++;
                } elseif (
                    count($guestCandidates) > 1
                ) {
                    $blockers[] =
                        "{$name} matches multiple form Guests.";

                    continue;
                }
            }

            $sourceId =
                $this->sourceId($row);

            if ($identity === null) {
                $existing =
                    $registrationGuests->get(
                        $sourceId
                    );

                if ($existing) {
                    if (
                        $this->normalize(
                            $existing->name
                        )
                        !== $normalized
                    ) {
                        $blockers[] =
                            "conference_reg {$sourceId} "
                            . "belongs to {$existing->name}, "
                            . "not {$name}.";

                        continue;
                    }

                    if (
                        $existing->linked_person_id
                    ) {
                        $identity = [
                            'kind' => 'person',
                            'person_id' =>
                                (int)
                                $existing
                                    ->linked_person_id,
                            'guest_id' =>
                                (int) $existing->id,
                            'attendee_key' =>
                                (int)
                                $existing
                                    ->linked_person_id,
                            'source_id' =>
                                $sourceId,
                        ];
                    } else {
                        $identity = [
                            'kind' => 'guest',
                            'person_id' => null,
                            'guest_id' =>
                                (int) $existing->id,
                            'attendee_key' =>
                                -1
                                * (int) $existing->id,
                            'source_id' =>
                                $sourceId,
                        ];
                    }

                    $counts[
                        'registration_guest'
                    ]++;
                }
            }

            if ($identity === null) {
                $identity = [
                    'kind' => 'new_guest',
                    'person_id' => null,
                    'guest_id' => null,
                    'attendee_key' => null,
                    'source_id' => $sourceId,
                ];

                $counts['new_guest']++;
            }

            $enrolled =
                $this->isEnrolled(
                    $sheetId,
                    $identity,
                    $date
                );

            if ($enrolled) {
                $alreadyEnrolled++;
            } else {
                $needsEnrollment++;
            }

            $currentRole =
                $this->currentRole(
                    $eventId,
                    $identity
                );

            if ($currentRole === null) {
                $roleMissing++;
            } elseif (
                $currentRole === $role
            ) {
                $roleCorrect++;
            } else {
                $blockers[] =
                    "{$name} already has role "
                    . "{$currentRole}; import wants "
                    . "{$role}.";
            }

            $desiredValues = [
                'Registration Locality' =>
                    $this->nullIfBlank(
                        $row['locality']
                        ?? null
                    ),

                'Year Level' =>
                    $this->nullIfBlank(
                        $row['year_level']
                        ?? null
                    ),

                'With Invite?' =>
                    $row['with_invite']
                    ?? null,

                'IDs in Canva' =>
                    $row['id_in_canva']
                    ?? null,
            ];

            if (
                $identity['attendee_key']
                !== null
            ) {
                foreach (
                    $desiredValues
                    as $fieldName => $desired
                ) {
                    $field =
                        $fieldByName->get(
                            mb_strtolower(
                                $fieldName
                            )
                        );

                    if (! $field) {
                        continue;
                    }

                    $key =
                        (int) $field->id
                        . ':'
                        . $identity[
                            'attendee_key'
                        ];

                    if (
                        array_key_exists(
                            $key,
                            $fieldValues
                        )
                        && $fieldValues[$key]
                            !== $desired
                    ) {
                        $blockers[] =
                            "{$name} / {$fieldName} "
                            . 'already differs from import.';
                    }
                }
            }

            $plannedRows[] = [
                'name' => $name,
                'identity' => $identity,
                'role' => $role,
                'registration_locality' =>
                    $desiredValues[
                        'Registration Locality'
                    ],
                'year_level' =>
                    $desiredValues[
                        'Year Level'
                    ],
                'with_invite' =>
                    $desiredValues[
                        'With Invite?'
                    ],
                'id_in_canva' =>
                    $desiredValues[
                        'IDs in Canva'
                    ],
            ];
        }

        $sourceAttendanceTrue =
            collect($rows)
                ->whereStrict(
                    'attendance',
                    true
                )
                ->count();

        $sourceAttendanceFalse =
            collect($rows)
                ->whereStrict(
                    'attendance',
                    false
                )
                ->count();

        return [
            'event_id' => $eventId,
            'sheet_id' => $sheetId,
            'session_id' =>
                (int) $session->id,
            'date' => $date,

            'counts' => $counts,

            'classified' =>
                count($plannedRows),

            'already_enrolled' =>
                $alreadyEnrolled,

            'needs_enrollment' =>
                $needsEnrollment,

            'role_correct' =>
                $roleCorrect,

            'role_missing' =>
                $roleMissing,

            'locality_differences' =>
                $localityDifferences,

            'fields' =>
                collect(
                    self::FIELD_TYPES
                )
                    ->map(
                        function (
                            string $type,
                            string $name
                        ) use (
                            $fieldByName
                        ): array {
                            $existing =
                                $fieldByName->get(
                                    mb_strtolower(
                                        $name
                                    )
                                );

                            return [
                                'name' => $name,
                                'type' => $type,
                                'exists' =>
                                    (bool) $existing,
                            ];
                        }
                    )
                    ->values()
                    ->all(),

            'source_attendance_true' =>
                $sourceAttendanceTrue,

            'source_attendance_false' =>
                $sourceAttendanceFalse,

            'attendance_records' =>
                $this->attendanceRecordCount(
                    (int) $session->id
                ),

            'blockers' =>
                array_values(
                    array_unique(
                        $blockers
                    )
                ),

            'rows' =>
                $plannedRows,
        ];
    }

    private function personAliases(
        object $person
    ): array {
        $aliases = [];

        $first =
            trim(
                (string) $person->firstname
            );

        $middle =
            trim(
                (string) $person->middlename
            );

        $last =
            trim(
                (string) $person->lastname
            );

        $nickname =
            trim(
                (string) $person->nickname
            );

        if (
            $first !== ''
            && $last !== ''
        ) {
            $aliases[] =
                "{$first} {$last}";
        }

        if (
            $first !== ''
            && $middle !== ''
            && $last !== ''
        ) {
            $aliases[] =
                "{$first} {$middle} {$last}";
        }

        if (
            $nickname !== ''
            && $last !== ''
        ) {
            $aliases[] =
                "{$nickname} {$last}";
        }

        return array_unique($aliases);
    }

    private function normalize(
        ?string $value
    ): string {
        $value =
            mb_strtolower(
                trim(
                    (string) $value
                )
            );

        $value =
            str_replace(
                [
                    '’',
                    "'",
                    '.',
                    ',',
                    '-',
                    '_',
                ],
                ' ',
                $value
            );

        return trim(
            (string) preg_replace(
                '/\s+/u',
                ' ',
                $value
            )
        );
    }

    private function sourceId(
        array $row
    ): int {
        $sheetRow =
            (int) (
                $row['sheet_row']
                ?? 0
            );

        if ($sheetRow < 1) {
            throw new RuntimeException(
                'Registration row has no sheet_row.'
            );
        }

        return match (
            $row['role']
            ?? null
        ) {
            'young_people' =>
                1000000 + $sheetRow,

            'serving_one' =>
                2000000 + $sheetRow,

            default =>
                throw new RuntimeException(
                    'Unexpected registration role.'
                ),
        };
    }

    private function isEnrolled(
        int $sheetId,
        array $identity,
        string $date
    ): bool {
        if (
            $identity['kind']
            === 'new_guest'
        ) {
            return false;
        }

        if (
            $identity['kind']
            === 'person'
        ) {
            return DB::table(
                'attendance_participants'
            )
                ->where(
                    'attendance_sheet_id',
                    $sheetId
                )
                ->where(
                    'person_id',
                    $identity['person_id']
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    function ($query) use (
                        $date
                    ): void {
                        $query
                            ->whereNull(
                                'starts_on'
                            )
                            ->orWhere(
                                'starts_on',
                                '<=',
                                $date
                            );
                    }
                )
                ->where(
                    function ($query) use (
                        $date
                    ): void {
                        $query
                            ->whereNull(
                                'ends_on'
                            )
                            ->orWhere(
                                'ends_on',
                                '>=',
                                $date
                            );
                    }
                )
                ->exists();
        }

        return DB::table(
            'attendance_guest_periods'
        )
            ->where(
                'attendance_guest_id',
                $identity['guest_id']
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'starts_on',
                '<=',
                $date
            )
            ->where(
                function ($query) use (
                    $date
                ): void {
                    $query
                        ->whereNull(
                            'ends_on'
                        )
                        ->orWhere(
                            'ends_on',
                            '>=',
                            $date
                        );
                }
            )
            ->exists();
    }

    private function currentRole(
        int $eventId,
        array $identity
    ): ?string {
        if (
            $identity['kind']
            === 'new_guest'
        ) {
            return null;
        }

        if (
            $identity['kind']
            === 'person'
        ) {
            $value =
                DB::table(
                    'conference_person_details'
                )
                    ->where(
                        'conference_event_id',
                        $eventId
                    )
                    ->where(
                        'person_id',
                        $identity['person_id']
                    )
                    ->value(
                        'event_role'
                    );

            return filled($value)
                ? (string) $value
                : null;
        }

        $value =
            DB::table(
                'conference_guest_details'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->where(
                    'attendance_guest_id',
                    $identity['guest_id']
                )
                ->value(
                    'event_role'
                );

        return filled($value)
            ? (string) $value
            : null;
    }

    private function materializeIdentity(
        int $sheetId,
        array $planned
    ): array {
        $identity =
            $planned['identity'];

        if (
            $identity['kind']
            !== 'new_guest'
        ) {
            return $identity;
        }

        $guestId =
            DB::table(
                'attendance_guests'
            )
                ->insertGetId([
                    'attendance_sheet_id' =>
                        $sheetId,

                    'source_type' =>
                        self::SOURCE_TYPE,

                    'source_id' =>
                        $identity[
                            'source_id'
                        ],

                    'name' =>
                        $planned['name'],

                    'locality' =>
                        $planned[
                            'registration_locality'
                        ],

                    'linked_person_id' =>
                        null,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

        return [
            'kind' => 'guest',
            'person_id' => null,
            'guest_id' => (int) $guestId,
            'attendee_key' =>
                -1 * (int) $guestId,
            'source_id' =>
                $identity['source_id'],
        ];
    }

    private function ensurePersonEnrollment(
        int $sheetId,
        int $personId,
        string $date
    ): void {
        if (
            $this->isEnrolled(
                $sheetId,
                [
                    'kind' => 'person',
                    'person_id' =>
                        $personId,
                    'guest_id' => null,
                ],
                $date
            )
        ) {
            return;
        }

        DB::table(
            'attendance_participants'
        )
            ->insert([
                'attendance_sheet_id' =>
                    $sheetId,

                'person_id' =>
                    $personId,

                'starts_on' =>
                    $date,

                'ends_on' =>
                    $date,

                'is_active' =>
                    true,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
    }

    private function ensureGuestEnrollment(
        int $guestId,
        string $date
    ): void {
        if (
            DB::table(
                'attendance_guest_periods'
            )
                ->where(
                    'attendance_guest_id',
                    $guestId
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'starts_on',
                    '<=',
                    $date
                )
                ->where(
                    function ($query) use (
                        $date
                    ): void {
                        $query
                            ->whereNull(
                                'ends_on'
                            )
                            ->orWhere(
                                'ends_on',
                                '>=',
                                $date
                            );
                    }
                )
                ->exists()
        ) {
            return;
        }

        DB::table(
            'attendance_guest_periods'
        )
            ->insert([
                'attendance_guest_id' =>
                    $guestId,

                'starts_on' =>
                    $date,

                'ends_on' =>
                    $date,

                'is_active' =>
                    true,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
    }

    private function ensurePersonRole(
        int $eventId,
        int $personId,
        string $role
    ): void {
        $existing =
            DB::table(
                'conference_person_details'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->where(
                    'person_id',
                    $personId
                )
                ->first();

        if ($existing) {
            if (
                blank($existing->event_role)
            ) {
                DB::table(
                    'conference_person_details'
                )
                    ->where(
                        'id',
                        $existing->id
                    )
                    ->update([
                        'event_role' =>
                            $role,

                        'updated_at' =>
                            now(),
                    ]);
            }

            return;
        }

        DB::table(
            'conference_person_details'
        )
            ->insert([
                'conference_event_id' =>
                    $eventId,

                'person_id' =>
                    $personId,

                'conference_team_id' =>
                    null,

                'event_role' =>
                    $role,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
    }

    private function ensureGuestRole(
        int $eventId,
        int $guestId,
        string $role
    ): void {
        $existing =
            DB::table(
                'conference_guest_details'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->where(
                    'attendance_guest_id',
                    $guestId
                )
                ->first();

        if ($existing) {
            if (
                blank($existing->event_role)
            ) {
                DB::table(
                    'conference_guest_details'
                )
                    ->where(
                        'id',
                        $existing->id
                    )
                    ->update([
                        'event_role' =>
                            $role,

                        'updated_at' =>
                            now(),
                    ]);
            }

            return;
        }

        DB::table(
            'conference_guest_details'
        )
            ->insert([
                'conference_event_id' =>
                    $eventId,

                'attendance_guest_id' =>
                    $guestId,

                'conference_team_id' =>
                    null,

                'event_role' =>
                    $role,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
    }

    private function ensureFields(
        int $eventId
    ): array {
        $existing =
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->get();

        $byName =
            $existing->keyBy(
                fn ($field): string =>
                    mb_strtolower(
                        trim($field->name)
                    )
            );

        $nextOrder =
            ((int) $existing->max(
                'sort_order'
            )) + 1;

        foreach (
            self::FIELD_TYPES
            as $name => $type
        ) {
            $key =
                mb_strtolower($name);

            if ($byName->has($key)) {
                continue;
            }

            $id =
                DB::table(
                    'conference_participant_fields'
                )
                    ->insertGetId([
                        'conference_event_id' =>
                            $eventId,

                        'name' =>
                            $name,

                        'field_type' =>
                            $type,

                        'is_required' =>
                            false,

                        'options_json' =>
                            null,

                        'sort_order' =>
                            $nextOrder++,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]);

            $byName->put(
                $key,
                DB::table(
                    'conference_participant_fields'
                )
                    ->where(
                        'id',
                        $id
                    )
                    ->first()
            );
        }

        /*
         * Keep the imported Google-Sheet-style columns
         * together in their intended order.
         *
         * Existing values are untouched. This only updates
         * participant-column display order.
         */
        $sortOrder = 1;

        foreach (
            array_keys(
                self::FIELD_TYPES
            )
            as $name
        ) {
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->where(
                    'name',
                    $name
                )
                ->update([
                    'sort_order' =>
                        $sortOrder++,

                    'updated_at' =>
                        now(),
                ]);
        }

        /*
         * Keep the imported Google-Sheet-style columns
         * together in their intended order.
         *
         * Existing values are untouched. This only updates
         * participant-column display order.
         */
        $sortOrder = 1;

        foreach (
            array_keys(
                self::FIELD_TYPES
            )
            as $name
        ) {
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->where(
                    'name',
                    $name
                )
                ->update([
                    'sort_order' =>
                        $sortOrder++,

                    'updated_at' =>
                        now(),
                ]);
        }

        /*
         * Keep the imported Google-Sheet-style columns
         * together in their intended order.
         *
         * Existing values are untouched. This only updates
         * participant-column display order.
         */
        $sortOrder = 1;

        foreach (
            array_keys(
                self::FIELD_TYPES
            )
            as $name
        ) {
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->where(
                    'name',
                    $name
                )
                ->update([
                    'sort_order' =>
                        $sortOrder++,

                    'updated_at' =>
                        now(),
                ]);
        }

        return collect(
            array_keys(
                self::FIELD_TYPES
            )
        )
            ->mapWithKeys(
                fn (
                    string $name
                ): array => [
                    $name =>
                        (int) $byName[
                            mb_strtolower(
                                $name
                            )
                        ]->id,
                ]
            )
            ->all();
    }

    private function saveFieldValue(
        int $fieldId,
        int $attendeeKey,
        mixed $value
    ): void {
        if (
            $value === null
            || $value === ''
        ) {
            DB::table(
                'conference_participant_field_values'
            )
                ->where(
                    'conference_participant_field_id',
                    $fieldId
                )
                ->where(
                    'attendee_key',
                    $attendeeKey
                )
                ->delete();

            return;
        }

        DB::table(
            'conference_participant_field_values'
        )
            ->updateOrInsert(
                [
                    'conference_participant_field_id' =>
                        $fieldId,

                    'attendee_key' =>
                        $attendeeKey,
                ],
                [
                    'value_json' =>
                        json_encode(
                            $value,
                            JSON_THROW_ON_ERROR
                            | JSON_UNESCAPED_UNICODE
                        ),

                    'updated_at' =>
                        now(),

                    'created_at' =>
                        now(),
                ]
            );
    }

    private function attendanceRecordCount(
        int $sessionId
    ): int {
        return
            DB::table(
                'attendance_records'
            )
                ->where(
                    'attendance_session_id',
                    $sessionId
                )
                ->count()
            +
            DB::table(
                'attendance_guest_records'
            )
                ->where(
                    'attendance_session_id',
                    $sessionId
                )
                ->count();
    }

    private function nullIfBlank(
        mixed $value
    ): ?string {
        $value =
            trim(
                (string) (
                    $value
                    ?? ''
                )
            );

        return $value === ''
            ? null
            : $value;
    }
}
