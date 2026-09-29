<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ConferenceParticipantFields
{
    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_TEXT = 'text';

    public const TYPE_NUMBER = 'number';

    public const TYPE_SELECT = 'select';

    public const TYPE_DATE = 'date';


    public static function types(): array
    {
        return [
            self::TYPE_CHECKBOX =>
                'Checkbox',

            self::TYPE_TEXT =>
                'Text',

            self::TYPE_NUMBER =>
                'Number',

            self::TYPE_SELECT =>
                'Dropdown',

            self::TYPE_DATE =>
                'Date',
        ];
    }


    public static function fields(
        int $eventId
    ): Collection {
        ConferenceWorkspace::event(
            $eventId
        );

        return DB::table(
            'conference_participant_fields'
        )
            ->where(
                'conference_event_id',
                $eventId
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(
                function (object $field): object {
                    $field->options =
                        self::decodeOptions(
                            $field->options_json
                        );

                    return $field;
                }
            )
            ->keyBy('id');
    }


    public static function create(
        int $eventId,
        string $name,
        string $type,
        bool $required = false,
        array $options = []
    ): int {
        ConferenceWorkspace::authorize();

        ConferenceWorkspace::event(
            $eventId
        );

        $name = trim($name);

        self::validateDefinition(
            $name,
            $type,
            $options
        );

        return DB::transaction(
            function () use (
                $eventId,
                $name,
                $type,
                $required,
                $options
            ): int {
                DB::table(
                    'conference_events'
                )
                    ->where(
                        'id',
                        $eventId
                    )
                    ->lockForUpdate()
                    ->first();

                $duplicate =
                    DB::table(
                        'conference_participant_fields'
                    )
                        ->where(
                            'conference_event_id',
                            $eventId
                        )
                        ->whereRaw(
                            'LOWER(name) = ?',
                            [
                                mb_strtolower(
                                    $name
                                ),
                            ]
                        )
                        ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'fieldName' =>
                            'A participant column with this '
                            . 'name already exists.',
                    ]);
                }

                $nextOrder =
                    (
                        (int) DB::table(
                            'conference_participant_fields'
                        )
                            ->where(
                                'conference_event_id',
                                $eventId
                            )
                            ->max(
                                'sort_order'
                            )
                    ) + 1;

                return DB::table(
                    'conference_participant_fields'
                )->insertGetId([
                    'conference_event_id' =>
                        $eventId,

                    'name' =>
                        $name,

                    'field_type' =>
                        $type,

                    'is_required' =>
                        $required,

                    'options_json' =>
                        $options === []
                            ? null
                            : json_encode(
                                array_values(
                                    $options
                                ),
                                JSON_UNESCAPED_UNICODE
                                | JSON_THROW_ON_ERROR
                            ),

                    'sort_order' =>
                        $nextOrder,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);
            }
        );
    }


    public static function update(
        int $eventId,
        int $fieldId,
        string $name,
        string $type,
        bool $required = false,
        array $options = []
    ): void {
        ConferenceWorkspace::authorize();

        ConferenceWorkspace::event(
            $eventId
        );

        $name = trim($name);

        self::validateDefinition(
            $name,
            $type,
            $options
        );

        DB::transaction(
            function () use (
                $eventId,
                $fieldId,
                $name,
                $type,
                $required,
                $options
            ): void {
                DB::table(
                    'conference_events'
                )
                    ->where(
                        'id',
                        $eventId
                    )
                    ->lockForUpdate()
                    ->first();

                $field =
                    DB::table(
                        'conference_participant_fields'
                    )
                        ->where(
                            'conference_event_id',
                            $eventId
                        )
                        ->where(
                            'id',
                            $fieldId
                        )
                        ->first();

                abort_unless(
                    $field,
                    404
                );

                $duplicate =
                    DB::table(
                        'conference_participant_fields'
                    )
                        ->where(
                            'conference_event_id',
                            $eventId
                        )
                        ->where(
                            'id',
                            '!=',
                            $fieldId
                        )
                        ->whereRaw(
                            'LOWER(name) = ?',
                            [
                                mb_strtolower(
                                    $name
                                ),
                            ]
                        )
                        ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'fieldName' =>
                            'A participant column with this '
                            . 'name already exists.',
                    ]);
                }

                /*
                 * Do not silently reinterpret already-entered
                 * values when the field type changes.
                 */
                if (
                    $field->field_type !== $type
                    && DB::table(
                        'conference_participant_field_values'
                    )
                        ->where(
                            'conference_participant_field_id',
                            $fieldId
                        )
                        ->whereNotNull(
                            'value_json'
                        )
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'fieldType' =>
                            'Clear the existing values before '
                            . 'changing this column type.',
                    ]);
                }

                DB::table(
                    'conference_participant_fields'
                )
                    ->where(
                        'id',
                        $fieldId
                    )
                    ->update([
                        'name' =>
                            $name,

                        'field_type' =>
                            $type,

                        'is_required' =>
                            $required,

                        'options_json' =>
                            $options === []
                                ? null
                                : json_encode(
                                    array_values(
                                        $options
                                    ),
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_THROW_ON_ERROR
                                ),

                        'updated_at' =>
                            now(),
                    ]);
            }
        );
    }


    public static function delete(
        int $eventId,
        int $fieldId
    ): void {
        ConferenceWorkspace::authorize();

        ConferenceWorkspace::event(
            $eventId
        );

        DB::table(
            'conference_participant_fields'
        )
            ->where(
                'conference_event_id',
                $eventId
            )
            ->where(
                'id',
                $fieldId
            )
            ->delete();
    }


    public static function values(
        int $eventId
    ): Collection {
        $fieldIds =
            self::fields(
                $eventId
            )
                ->keys();

        if ($fieldIds->isEmpty()) {
            return collect();
        }

        return DB::table(
            'conference_participant_field_values'
        )
            ->whereIn(
                'conference_participant_field_id',
                $fieldIds
            )
            ->get()
            ->mapWithKeys(
                fn (object $row): array => [
                    self::valueKey(
                        (int) $row
                            ->conference_participant_field_id,
                        (int) $row->attendee_key
                    ) =>
                        self::decodeValue(
                            $row->value_json
                        ),
                ]
            );
    }


    public static function value(
        int $eventId,
        int $fieldId,
        int $attendeeKey
    ): mixed {
        $field =
            self::field(
                $eventId,
                $fieldId
            );

        self::attendee(
            $eventId,
            $attendeeKey
        );

        $raw =
            DB::table(
                'conference_participant_field_values'
            )
                ->where(
                    'conference_participant_field_id',
                    $field->id
                )
                ->where(
                    'attendee_key',
                    $attendeeKey
                )
                ->value(
                    'value_json'
                );

        return self::decodeValue(
            $raw
        );
    }


    public static function saveValue(
        int $eventId,
        int $fieldId,
        int $attendeeKey,
        mixed $value
    ): void {
        ConferenceWorkspace::authorize();

        $field =
            self::field(
                $eventId,
                $fieldId
            );

        self::attendee(
            $eventId,
            $attendeeKey
        );

        $value =
            self::normalizeValue(
                $field,
                $value
            );

        if (
            $field->is_required
            && $value === null
        ) {
            throw ValidationException::withMessages([
                'participantFieldValue' =>
                    $field->name
                    . ' is required.',
            ]);
        }

        if ($value === null) {
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
        )->updateOrInsert(
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
                        JSON_UNESCAPED_UNICODE
                        | JSON_THROW_ON_ERROR
                    ),

                'updated_at' =>
                    now(),
            ]
        );
    }


    public static function assertAttendeeMergeable(
        int $eventId,
        int $fromAttendeeKey,
        int $toAttendeeKey
    ): void {
        ConferenceWorkspace::authorize();

        $fieldIds =
            self::fields(
                $eventId
            )->keys();

        if ($fieldIds->isEmpty()) {
            return;
        }

        $rows =
            DB::table(
                'conference_participant_field_values'
            )
                ->whereIn(
                    'conference_participant_field_id',
                    $fieldIds
                )
                ->whereIn(
                    'attendee_key',
                    [
                        $fromAttendeeKey,
                        $toAttendeeKey,
                    ]
                )
                ->get()
                ->groupBy(
                    'conference_participant_field_id'
                );

        foreach ($rows as $fieldId => $values) {
            $from =
                $values->firstWhere(
                    'attendee_key',
                    $fromAttendeeKey
                );

            $to =
                $values->firstWhere(
                    'attendee_key',
                    $toAttendeeKey
                );

            if (
                ! $from
                || ! $to
            ) {
                continue;
            }

            if (
                self::decodeValue(
                    $from->value_json
                )
                !==
                self::decodeValue(
                    $to->value_json
                )
            ) {
                $field =
                    self::field(
                        $eventId,
                        (int) $fieldId
                    );

                throw ValidationException::withMessages([
                    'guestAttendance' =>
                        'The conference column "'
                        . $field->name
                        . '" has conflicting values. '
                        . 'Review the values before '
                        . 'linking these identities.',
                ]);
            }
        }
    }


    public static function mergeAttendee(
        int $eventId,
        int $fromAttendeeKey,
        int $toAttendeeKey
    ): void {
        ConferenceWorkspace::authorize();

        self::assertAttendeeMergeable(
            $eventId,
            $fromAttendeeKey,
            $toAttendeeKey
        );

        $fieldIds =
            self::fields(
                $eventId
            )->keys();

        if ($fieldIds->isEmpty()) {
            return;
        }

        DB::transaction(
            function () use (
                $fieldIds,
                $fromAttendeeKey,
                $toAttendeeKey
            ): void {
                $sourceRows =
                    DB::table(
                        'conference_participant_field_values'
                    )
                        ->whereIn(
                            'conference_participant_field_id',
                            $fieldIds
                        )
                        ->where(
                            'attendee_key',
                            $fromAttendeeKey
                        )
                        ->get();

                foreach ($sourceRows as $source) {
                    $target =
                        DB::table(
                            'conference_participant_field_values'
                        )
                            ->where(
                                'conference_participant_field_id',
                                $source
                                    ->conference_participant_field_id
                            )
                            ->where(
                                'attendee_key',
                                $toAttendeeKey
                            )
                            ->first();

                    if ($target) {
                        /*
                         * assertAttendeeMergeable() already
                         * proved that the values are equal.
                         */
                        DB::table(
                            'conference_participant_field_values'
                        )
                            ->where(
                                'id',
                                $source->id
                            )
                            ->delete();

                        continue;
                    }

                    DB::table(
                        'conference_participant_field_values'
                    )
                        ->where(
                            'id',
                            $source->id
                        )
                        ->update([
                            'attendee_key' =>
                                $toAttendeeKey,

                            'updated_at' =>
                                now(),
                        ]);
                }
            }
        );
    }


    public static function valueKey(
        int $fieldId,
        int $attendeeKey
    ): string {
        return $fieldId
            . ':'
            . $attendeeKey;
    }


    private static function field(
        int $eventId,
        int $fieldId
    ): object {
        ConferenceWorkspace::event(
            $eventId
        );

        $field =
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->where(
                    'id',
                    $fieldId
                )
                ->first();

        abort_unless(
            $field,
            404
        );

        $field->options =
            self::decodeOptions(
                $field->options_json
            );

        return $field;
    }


    private static function attendee(
        int $eventId,
        int $attendeeKey
    ): array {
        if ($attendeeKey === 0) {
            abort(404);
        }

        $row =
            ConferenceWorkspace::data(
                $eventId
            )['rows']
                ->get(
                    $attendeeKey
                );

        abort_unless(
            $row,
            404
        );

        return $row;
    }


    private static function validateDefinition(
        string $name,
        string $type,
        array $options
    ): void {
        if (
            $name === ''
            || mb_strlen($name) > 100
        ) {
            throw ValidationException::withMessages([
                'fieldName' =>
                    'Enter a participant column name '
                    . 'up to 100 characters.',
            ]);
        }

        if (
            ! array_key_exists(
                $type,
                self::types()
            )
        ) {
            throw ValidationException::withMessages([
                'fieldType' =>
                    'Choose a valid participant column type.',
            ]);
        }

        if (
            $type === self::TYPE_SELECT
            && $options === []
        ) {
            throw ValidationException::withMessages([
                'fieldOptions' =>
                    'Add at least one dropdown option.',
            ]);
        }

        if (
            $type !== self::TYPE_SELECT
            && $options !== []
        ) {
            throw ValidationException::withMessages([
                'fieldOptions' =>
                    'Options are only used by dropdown columns.',
            ]);
        }

        if (
            collect($options)
                ->contains(
                    fn ($option): bool =>
                        ! is_string($option)
                        || trim($option) === ''
                        || mb_strlen(
                            trim($option)
                        ) > 100
                )
        ) {
            throw ValidationException::withMessages([
                'fieldOptions' =>
                    'Dropdown options must be non-empty '
                    . 'and at most 100 characters.',
            ]);
        }

        $normalized =
            collect($options)
                ->map(
                    fn (string $option): string =>
                        mb_strtolower(
                            trim($option)
                        )
                );

        if (
            $normalized->unique()->count()
            !== $normalized->count()
        ) {
            throw ValidationException::withMessages([
                'fieldOptions' =>
                    'Dropdown options must be unique.',
            ]);
        }
    }


    private static function normalizeValue(
        object $field,
        mixed $value
    ): mixed {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return match (
            $field->field_type
        ) {
            self::TYPE_CHECKBOX =>
                self::checkboxValue(
                    $value
                ),

            self::TYPE_TEXT =>
                self::textValue(
                    $value
                ),

            self::TYPE_NUMBER =>
                self::numberValue(
                    $value
                ),

            self::TYPE_SELECT =>
                self::selectValue(
                    $field,
                    $value
                ),

            self::TYPE_DATE =>
                self::dateValue(
                    $value
                ),

            default =>
                throw ValidationException::withMessages([
                    'participantFieldValue' =>
                        'Unknown participant column type.',
                ]),
        };
    }


    private static function checkboxValue(
        mixed $value
    ): bool {
        if (
            $value === true
            || $value === 1
            || $value === '1'
            || $value === 'true'
        ) {
            return true;
        }

        if (
            $value === false
            || $value === 0
            || $value === '0'
            || $value === 'false'
        ) {
            return false;
        }

        throw ValidationException::withMessages([
            'participantFieldValue' =>
                'Checkbox values must be Yes, No, or blank.',
        ]);
    }


    private static function textValue(
        mixed $value
    ): string {
        if (! is_scalar($value)) {
            throw ValidationException::withMessages([
                'participantFieldValue' =>
                    'Enter a text value.',
            ]);
        }

        $value = trim(
            (string) $value
        );

        if (
            mb_strlen($value) > 500
        ) {
            throw ValidationException::withMessages([
                'participantFieldValue' =>
                    'Text values may be at most '
                    . '500 characters.',
            ]);
        }

        return $value;
    }


    private static function numberValue(
        mixed $value
    ): int|float {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                'participantFieldValue' =>
                    'Enter a valid number.',
            ]);
        }

        return str_contains(
            (string) $value,
            '.'
        )
            ? (float) $value
            : (int) $value;
    }


    private static function selectValue(
        object $field,
        mixed $value
    ): string {
        $value = trim(
            (string) $value
        );

        if (
            ! in_array(
                $value,
                $field->options,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'participantFieldValue' =>
                    'Choose one of the configured '
                    . 'dropdown options.',
            ]);
        }

        return $value;
    }


    private static function dateValue(
        mixed $value
    ): string {
        $value = trim(
            (string) $value
        );

        $date =
            \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        if (
            ! $date
            || $date->format('Y-m-d')
                !== $value
        ) {
            throw ValidationException::withMessages([
                'participantFieldValue' =>
                    'Enter a valid date.',
            ]);
        }

        return $value;
    }


    private static function decodeOptions(
        ?string $value
    ): array {
        if (
            $value === null
            || $value === ''
        ) {
            return [];
        }

        $decoded =
            json_decode(
                $value,
                true
            );

        return is_array($decoded)
            ? array_values($decoded)
            : [];
    }


    private static function decodeValue(
        ?string $value
    ): mixed {
        if ($value === null) {
            return null;
        }

        return json_decode(
            $value,
            true
        );
    }
}
