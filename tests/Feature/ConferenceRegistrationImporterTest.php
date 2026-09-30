<?php

namespace Tests\Feature;

use App\Services\ConferenceRegistrationImporter as Importer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConferenceRegistrationImporterTest extends TestCase
{
    private string $payloadPath;

    private int $eventId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        if (
            DB::getDriverName() !== 'sqlite'
            || DB::connection()->getDatabaseName() !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Only isolated in-memory SQLite is permitted.'
            );
        }

        DB::statement(
            'PRAGMA foreign_keys = ON'
        );

        /*
         * ----------------------------------------------------
         * Minimal production-shaped schema required by
         * ConferenceRegistrationImporter.
         * ----------------------------------------------------
         */

        Schema::create(
            'persons',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->string('firstname')->nullable();
                $table->string('middlename')->nullable();
                $table->string('lastname')->nullable();
                $table->string('suffix')->nullable();
                $table->string('nickname')->nullable();
                $table->string('locality')->nullable();
            }
        );

        Schema::create(
            'attendance_sheets',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->string('title');
                $table->string('sheet_type');
            }
        );

        Schema::create(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger(
                    'attendance_sheet_id'
                );
                $table->date('session_date');
            }
        );

        Schema::create(
            'attendance_participants',
            function (Blueprint $table): void {
                $table->increments('id');

                $table->unsignedInteger(
                    'attendance_sheet_id'
                );

                $table->unsignedInteger(
                    'person_id'
                );

                $table->date(
                    'starts_on'
                )->nullable();

                $table->date(
                    'ends_on'
                )->nullable();

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'attendance_records',
            function (Blueprint $table): void {
                $table->increments('id');

                $table->unsignedInteger(
                    'attendance_session_id'
                );

                $table->unsignedInteger(
                    'person_id'
                );

                $table->string(
                    'status'
                )->nullable();
            }
        );

        Schema::create(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedInteger(
                    'attendance_session_id'
                );

                $table->string(
                    'respondent_type'
                );

                $table->unsignedInteger(
                    'person_id'
                )->nullable();

                $table->string(
                    'guest_name'
                )->nullable();

                $table->string(
                    'response'
                )->nullable();
            }
        );

        Schema::create(
            'attendance_guests',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedInteger(
                    'attendance_sheet_id'
                );

                $table->string(
                    'source_type',
                    20
                );

                $table->unsignedBigInteger(
                    'source_id'
                )->nullable();

                $table->string(
                    'name'
                );

                $table->string(
                    'locality',
                    150
                )->nullable();

                $table->unsignedInteger(
                    'linked_person_id'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'attendance_sheet_id',
                        'source_type',
                        'source_id',
                    ],
                    'att_guest_source_unique'
                );
            }
        );

        Schema::create(
            'attendance_guest_responses',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'attendance_meeting_response_id'
                );

                $table->unsignedBigInteger(
                    'attendance_guest_id'
                );

                $table->unique(
                    'attendance_meeting_response_id'
                );
            }
        );

        Schema::create(
            'attendance_guest_periods',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'attendance_guest_id'
                );

                $table->date(
                    'starts_on'
                );

                $table->date(
                    'ends_on'
                )->nullable();

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'attendance_guest_records',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'attendance_guest_id'
                );

                $table->unsignedInteger(
                    'attendance_session_id'
                );

                $table->string(
                    'status'
                )->nullable();
            }
        );

        Schema::create(
            'conference_events',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedInteger(
                    'attendance_sheet_id'
                );

                $table->string(
                    'activity_type',
                    100
                );

                $table->timestamps();
            }
        );

        Schema::create(
            'conference_sessions',
            function (Blueprint $table): void {
                $table->unsignedBigInteger(
                    'conference_event_id'
                );

                $table->unsignedInteger(
                    'attendance_session_id'
                );

                $table->primary([
                    'conference_event_id',
                    'attendance_session_id',
                ]);
            }
        );

        Schema::create(
            'conference_person_details',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'conference_event_id'
                );

                $table->unsignedInteger(
                    'person_id'
                );

                $table->unsignedBigInteger(
                    'conference_team_id'
                )->nullable();

                $table->string(
                    'event_role',
                    30
                )->nullable();

                $table->timestamps();

                $table->unique([
                    'conference_event_id',
                    'person_id',
                ]);
            }
        );

        Schema::create(
            'conference_guest_details',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'conference_event_id'
                );

                $table->unsignedBigInteger(
                    'attendance_guest_id'
                );

                $table->unsignedBigInteger(
                    'conference_team_id'
                )->nullable();

                $table->string(
                    'event_role',
                    30
                )->nullable();

                $table->timestamps();

                $table->unique([
                    'conference_event_id',
                    'attendance_guest_id',
                ]);
            }
        );

        Schema::create(
            'conference_participant_fields',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'conference_event_id'
                );

                $table->string(
                    'name',
                    100
                );

                $table->string(
                    'field_type',
                    30
                );

                $table->boolean(
                    'is_required'
                )->default(false);

                $table->text(
                    'options_json'
                )->nullable();

                $table->unsignedInteger(
                    'sort_order'
                )->default(0);

                $table->timestamps();

                $table->unique([
                    'conference_event_id',
                    'name',
                ]);
            }
        );

        Schema::create(
            'conference_participant_field_values',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'conference_participant_field_id'
                );

                $table->bigInteger(
                    'attendee_key'
                );

                $table->text(
                    'value_json'
                )->nullable();

                $table->timestamps();

                $table->unique([
                    'conference_participant_field_id',
                    'attendee_key',
                ]);
            }
        );

        /*
         * ----------------------------------------------------
         * Conference target
         * ----------------------------------------------------
         */

        DB::table(
            'attendance_sheets'
        )->insert([
            'id' => 1,
            'title' => 'Conference',
            'sheet_type' => 'custom',
        ]);

        DB::table(
            'attendance_sessions'
        )->insert([
            'id' => 1,
            'attendance_sheet_id' => 1,
            'session_date' => '2026-09-26',
        ]);

        DB::table(
            'conference_events'
        )->insert([
            'id' => $this->eventId,
            'attendance_sheet_id' => 1,
            'activity_type' => 'YP Blending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table(
            'conference_sessions'
        )->insert([
            'conference_event_id' =>
                $this->eventId,

            'attendance_session_id' =>
                1,
        ]);

        /*
         * ----------------------------------------------------
         * 47 existing People
         * ----------------------------------------------------
         */

        for (
            $id = 1;
            $id <= 47;
            $id++
        ) {
            DB::table(
                'persons'
            )->insert([
                'id' => $id,
                'firstname' =>
                    'Person ' . $id,
                'lastname' =>
                    'Test',
                'locality' =>
                    'Lucban',
            ]);
        }

        /*
         * Production starting shape:
         *
         * Person #1 and #2 already enrolled.
         */
        foreach ([1, 2] as $personId) {
            DB::table(
                'attendance_participants'
            )->insert([
                'attendance_sheet_id' =>
                    1,

                'person_id' =>
                    $personId,

                'starts_on' =>
                    '2026-09-26',

                'ends_on' =>
                    '2026-09-26',

                'is_active' =>
                    true,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
        }

        /*
         * Person #1 already has its Conference role.
         */
        DB::table(
            'conference_person_details'
        )->insert([
            'conference_event_id' =>
                $this->eventId,

            'person_id' =>
                1,

            'conference_team_id' =>
                null,

            'event_role' =>
                'young_people',

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        /*
         * ----------------------------------------------------
         * One existing submitted-form Guest
         * ----------------------------------------------------
         */

        DB::table(
            'attendance_meeting_responses'
        )->insert([
            'id' => 24,
            'attendance_session_id' => 1,
            'respondent_type' => 'guest',
            'guest_name' => 'Form Guest',
            'response' => 'yes',
        ]);

        DB::table(
            'attendance_guests'
        )->insert([
            'id' => 1,
            'attendance_sheet_id' => 1,
            'source_type' => 'guest',
            'source_id' => 24,
            'name' => 'Form Guest',
            'locality' => 'Gumaca',
            'linked_person_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table(
            'attendance_guest_responses'
        )->insert([
            'attendance_meeting_response_id' =>
                24,

            'attendance_guest_id' =>
                1,
        ]);

        DB::table(
            'attendance_guest_periods'
        )->insert([
            'attendance_guest_id' =>
                1,

            'starts_on' =>
                '2026-09-26',

            'ends_on' =>
                '2026-09-26',

            'is_active' =>
                true,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        /*
         * Existing dynamic field, matching production.
         */
        DB::table(
            'conference_participant_fields'
        )->insert([
            'id' => 1,
            'conference_event_id' =>
                $this->eventId,

            'name' =>
                'With Invite?',

            'field_type' =>
                'checkbox',

            'is_required' =>
                false,

            'options_json' =>
                null,

            'sort_order' =>
                1,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        /*
         * ----------------------------------------------------
         * Synthetic 157-row normalized payload
         *
         * 47 People
         *  1 existing form Guest
         * 75 new Young People Guests
         * 34 new Serving One Guests
         * ---------------------------
         * 157
         * ----------------------------------------------------
         */

        $rows = [];

        for (
            $id = 1;
            $id <= 47;
            $id++
        ) {
            $rows[] =
                $this->registrationRow(
                    name:
                        'Person '
                        . $id
                        . ' Test',

                    role:
                        'young_people',

                    sheetRow:
                        $id,

                    locality:
                        $id === 1
                            ? 'Catanauan'
                            : 'Lucban',

                    yearLevel:
                        $id === 1
                            ? 'High School'
                            : null,

                    withInvite:
                        $id === 1
                            ? true
                            : null,

                    idInCanva:
                        $id === 1
                            ? false
                            : null,
                );
        }

        $rows[] =
            $this->registrationRow(
                name:
                    'Form Guest',

                role:
                    'young_people',

                sheetRow:
                    48,

                locality:
                    'Gumaca',

                yearLevel:
                    'Grade 9',

                withInvite:
                    null,

                idInCanva:
                    true,
            );

        /*
         * 75 new Young People = rows 49–123.
         */
        for (
            $row = 49;
            $row <= 123;
            $row++
        ) {
            $rows[] =
                $this->registrationRow(
                    name:
                        'Young Guest '
                        . $row,

                    role:
                        'young_people',

                    sheetRow:
                        $row,

                    locality:
                        'Lucban',

                    yearLevel:
                        null,

                    withInvite:
                        null,

                    idInCanva:
                        null,
                );
        }

        /*
         * 34 Serving Ones use parallel source rows.
         */
        for (
            $row = 1;
            $row <= 34;
            $row++
        ) {
            $rows[] =
                $this->registrationRow(
                    name:
                        'Serving Guest '
                        . $row,

                    role:
                        'serving_one',

                    sheetRow:
                        $row,

                    locality:
                        'Lucena City',

                    yearLevel:
                        null,

                    withInvite:
                        null,

                    idInCanva:
                        null,
                );
        }

        if (count($rows) !== 157) {
            throw new \RuntimeException(
                'Test payload does not contain 157 rows.'
            );
        }

        $path =
            tempnam(
                sys_get_temp_dir(),
                'coqp-conference-reg-'
            );

        if ($path === false) {
            throw new \RuntimeException(
                'Could not create test payload.'
            );
        }

        $this->payloadPath =
            $path;

        file_put_contents(
            $this->payloadPath,
            json_encode(
                $rows,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_UNICODE
                | JSON_PRETTY_PRINT
            )
        );
    }

    protected function tearDown(): void
    {
        if (
            isset($this->payloadPath)
            && is_file(
                $this->payloadPath
            )
        ) {
            unlink(
                $this->payloadPath
            );
        }

        parent::tearDown();
    }

    public function test_registration_import_is_idempotent_and_never_marks_attendance(): void
    {
        $importer =
            app(
                Importer::class
            );

        /*
         * ----------------------------------------------------
         * PREVIEW
         * ----------------------------------------------------
         */

        $preview =
            $importer->preview(
                $this->eventId,
                '2026-09-26',
                $this->payloadPath
            );

        $this->assertSame(
            [],
            $preview['blockers']
        );

        $this->assertSame(
            47,
            $preview[
                'counts'
            ][
                'person'
            ]
        );

        $this->assertSame(
            1,
            $preview[
                'counts'
            ][
                'form_guest'
            ]
        );

        $this->assertSame(
            0,
            $preview[
                'counts'
            ][
                'registration_guest'
            ]
        );

        $this->assertSame(
            109,
            $preview[
                'counts'
            ][
                'new_guest'
            ]
        );

        $this->assertSame(
            157,
            $preview[
                'classified'
            ]
        );

        $this->assertSame(
            3,
            $preview[
                'already_enrolled'
            ]
        );

        $this->assertSame(
            154,
            $preview[
                'needs_enrollment'
            ]
        );

        $this->assertSame(
            1,
            $preview[
                'role_correct'
            ]
        );

        $this->assertSame(
            156,
            $preview[
                'role_missing'
            ]
        );

        $this->assertSame(
            0,
            $preview[
                'attendance_records'
            ]
        );

        /*
         * Preview is read-only.
         */
        $this->assertSame(
            0,
            DB::table(
                'attendance_guests'
            )
                ->where(
                    'source_type',
                    Importer::SOURCE_TYPE
                )
                ->count()
        );

        /*
         * ----------------------------------------------------
         * FIRST APPLY
         * ----------------------------------------------------
         */

        $peopleBefore =
            DB::table(
                'persons'
            )->count();

        $first =
            $importer->apply(
                $this->eventId,
                '2026-09-26',
                $this->payloadPath
            );

        /*
         * Never create People.
         */
        $this->assertSame(
            $peopleBefore,
            DB::table(
                'persons'
            )->count()
        );

        /*
         * 109 previously unmatched registrations.
         */
        $this->assertSame(
            109,
            DB::table(
                'attendance_guests'
            )
                ->where(
                    'source_type',
                    Importer::SOURCE_TYPE
                )
                ->count()
        );

        /*
         * 109 new + 1 existing form Guest.
         */
        $this->assertSame(
            110,
            DB::table(
                'attendance_guests'
            )->count()
        );

        /*
         * All 47 People enrolled for the date.
         */
        $this->assertSame(
            47,
            $this->activePeopleCount(
                '2026-09-26'
            )
        );

        /*
         * All 110 Guests enrolled for the date.
         */
        $this->assertSame(
            110,
            $this->activeGuestCount(
                '2026-09-26'
            )
        );

        $this->assertSame(
            157,
            $this->activePeopleCount(
                '2026-09-26'
            )
            +
            $this->activeGuestCount(
                '2026-09-26'
            )
        );

        /*
         * Conference source role totals.
         */
        $this->assertSame(
            123,
            $this->roleCount(
                'young_people'
            )
        );

        $this->assertSame(
            34,
            $this->roleCount(
                'serving_one'
            )
        );

        /*
         * Four unified Conference columns.
         */
        $this->assertSame(
            4,
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $this->eventId
                )
                ->count()
        );

        $this->assertSame(
            [
                'Registration Locality',
                'Year Level',
                'With Invite?',
                'IDs in Canva',
            ],
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $this->eventId
                )
                ->orderBy(
                    'sort_order'
                )
                ->orderBy(
                    'id'
                )
                ->pluck(
                    'name'
                )
                ->all()
        );

        /*
         * Canonical People locality remains unchanged.
         */
        $this->assertSame(
            'Lucban',
            DB::table(
                'persons'
            )
                ->where(
                    'id',
                    1
                )
                ->value(
                    'locality'
                )
        );

        /*
         * Conference Registration Locality preserves
         * the spreadsheet value separately.
         */
        $localityField =
            $this->fieldId(
                'Registration Locality'
            );

        $yearField =
            $this->fieldId(
                'Year Level'
            );

        $inviteField =
            $this->fieldId(
                'With Invite?'
            );

        $canvaField =
            $this->fieldId(
                'IDs in Canva'
            );

        $this->assertSame(
            'Catanauan',
            $this->fieldValue(
                $localityField,
                1
            )
        );

        /*
         * Preserve Google Sheet wording exactly.
         */
        $this->assertSame(
            'High School',
            $this->fieldValue(
                $yearField,
                1
            )
        );

        $this->assertTrue(
            $this->fieldValue(
                $inviteField,
                1
            )
        );

        $this->assertFalse(
            $this->fieldValue(
                $canvaField,
                1
            )
        );

        /*
         * Registration is not Attendance.
         */
        $this->assertSame(
            0,
            DB::table(
                'attendance_records'
            )->count()
        );

        $this->assertSame(
            0,
            DB::table(
                'attendance_guest_records'
            )->count()
        );

        $this->assertSame(
            0,
            $first[
                'attendance_records_before'
            ]
        );

        $this->assertSame(
            0,
            $first[
                'attendance_records_after'
            ]
        );

        /*
         * ----------------------------------------------------
         * SECOND APPLY — must be idempotent
         * ----------------------------------------------------
         */

        $beforeSecond =
            $this->databaseCounts();

        $second =
            $importer->apply(
                $this->eventId,
                '2026-09-26',
                $this->payloadPath
            );

        $this->assertSame(
            $beforeSecond,
            $this->databaseCounts()
        );

        $this->assertSame(
            0,
            $second[
                'counts'
            ][
                'new_guest'
            ]
        );

        $this->assertSame(
            109,
            $second[
                'counts'
            ][
                'registration_guest'
            ]
        );

        $this->assertSame(
            157,
            $second[
                'already_enrolled'
            ]
        );

        $this->assertSame(
            0,
            $second[
                'needs_enrollment'
            ]
        );

        $this->assertSame(
            157,
            $second[
                'role_correct'
            ]
        );

        $this->assertSame(
            0,
            $second[
                'role_missing'
            ]
        );

        $this->assertSame(
            [],
            $second[
                'blockers'
            ]
        );

        /*
         * Still no Attendance after two imports.
         */
        $this->assertSame(
            0,
            DB::table(
                'attendance_records'
            )->count()
        );

        $this->assertSame(
            0,
            DB::table(
                'attendance_guest_records'
            )->count()
        );
    }

    private function registrationRow(
        string $name,
        string $role,
        int $sheetRow,
        string $locality,
        ?string $yearLevel,
        ?bool $withInvite,
        ?bool $idInCanva,
    ): array {
        return [
            'name' => $name,
            'first' => null,
            'last' => null,
            'role' => $role,
            'sheet_row' => $sheetRow,
            'locality' => $locality,
            'attendance' => false,
            'with_invite' => $withInvite,
            'id_in_canva' => $idInCanva,
            'year_level' => $yearLevel,
            'extra' => '',
        ];
    }

    private function activePeopleCount(
        string $date
    ): int {
        return DB::table(
            'attendance_participants'
        )
            ->where(
                'attendance_sheet_id',
                1
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
            ->distinct()
            ->count(
                'person_id'
            );
    }

    private function activeGuestCount(
        string $date
    ): int {
        return DB::table(
            'attendance_guest_periods'
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
            ->distinct()
            ->count(
                'attendance_guest_id'
            );
    }

    private function roleCount(
        string $role
    ): int {
        return
            DB::table(
                'conference_person_details'
            )
                ->where(
                    'conference_event_id',
                    $this->eventId
                )
                ->where(
                    'event_role',
                    $role
                )
                ->count()
            +
            DB::table(
                'conference_guest_details'
            )
                ->where(
                    'conference_event_id',
                    $this->eventId
                )
                ->where(
                    'event_role',
                    $role
                )
                ->count();
    }

    private function fieldId(
        string $name
    ): int {
        return (int)
            DB::table(
                'conference_participant_fields'
            )
                ->where(
                    'conference_event_id',
                    $this->eventId
                )
                ->where(
                    'name',
                    $name
                )
                ->value(
                    'id'
                );
    }

    private function fieldValue(
        int $fieldId,
        int $attendeeKey
    ): mixed {
        $json =
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
                ->value(
                    'value_json'
                );

        return json_decode(
            (string) $json,
            true
        );
    }

    private function databaseCounts(): array
    {
        return [
            'people' =>
                DB::table(
                    'persons'
                )->count(),

            'guests' =>
                DB::table(
                    'attendance_guests'
                )->count(),

            'person_periods' =>
                DB::table(
                    'attendance_participants'
                )->count(),

            'guest_periods' =>
                DB::table(
                    'attendance_guest_periods'
                )->count(),

            'person_details' =>
                DB::table(
                    'conference_person_details'
                )->count(),

            'guest_details' =>
                DB::table(
                    'conference_guest_details'
                )->count(),

            'fields' =>
                DB::table(
                    'conference_participant_fields'
                )->count(),

            'field_values' =>
                DB::table(
                    'conference_participant_field_values'
                )->count(),

            'person_attendance' =>
                DB::table(
                    'attendance_records'
                )->count(),

            'guest_attendance' =>
                DB::table(
                    'attendance_guest_records'
                )->count(),
        ];
    }
}
