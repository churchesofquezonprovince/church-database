<?php

namespace Tests\Feature;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceParticipantPeriodsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * These regression tests exercise the post-29J attendance
         * model only.
         *
         * The application has historical MySQL-specific migrations,
         * so do not use RefreshDatabase here. Build the small
         * post-29J schema required by these tests directly in the
         * already-guarded SQLite :memory: database.
         */
        DB::statement('PRAGMA foreign_keys = ON');

        Schema::create(
            'persons',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->string('firstname');
                $table->string('lastname');
                $table->timestamps();
            }
        );

        Schema::create(
            'attendance_sheets',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->string('title');
                $table->string('sheet_type')
                    ->default(AttendanceSheet::TYPE_CUSTOM);
                $table->string('schedule_type')
                    ->default(AttendanceSheet::SCHEDULE_RECURRING);
                $table->boolean('is_one_time')
                    ->default(false);
                $table->boolean('is_active')
                    ->default(true);
                $table->timestamps();
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
                $table->string('title')->nullable();
                $table->timestamps();

                $table->unique([
                    'attendance_sheet_id',
                    'session_date',
                ]);

                $table->foreign(
                    'attendance_sheet_id'
                )
                    ->references('id')
                    ->on('attendance_sheets')
                    ->cascadeOnDelete();
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

                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();

                /*
                 * Post-29J:
                 * deliberately NOT unique on
                 * attendance_sheet_id + person_id.
                 */
                $table->index(
                    [
                        'attendance_sheet_id',
                        'person_id',
                    ],
                    'att_part_sheet_person_idx'
                );

                $table->foreign(
                    'attendance_sheet_id'
                )
                    ->references('id')
                    ->on('attendance_sheets')
                    ->cascadeOnDelete();

                $table->foreign('person_id')
                    ->references('id')
                    ->on('persons')
                    ->cascadeOnDelete();
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

                $table->string('status')
                    ->default(
                        AttendanceRecord::STATUS_PRESENT
                    );

                $table->boolean('is_present')
                    ->default(true);

                $table->string('attendance_source')
                    ->nullable();

                $table->timestamps();

                $table->unique([
                    'attendance_session_id',
                    'person_id',
                ]);

                $table->foreign(
                    'attendance_session_id'
                )
                    ->references('id')
                    ->on('attendance_sessions')
                    ->cascadeOnDelete();

                $table->foreign('person_id')
                    ->references('id')
                    ->on('persons')
                    ->cascadeOnDelete();
            }
        );
    }

    private function createPerson(
        string $firstname = 'Test',
        string $lastname = 'Person'
    ): int {
        return (int) DB::table('persons')
            ->insertGetId([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function createSheet(): AttendanceSheet
    {
        return AttendanceSheet::query()->create([
            'title' =>
                'Participant Period Regression Test',

            'sheet_type' =>
                AttendanceSheet::TYPE_CUSTOM,

            'schedule_type' =>
                AttendanceSheet::SCHEDULE_MANUAL,

            'is_active' => true,
        ]);
    }

    public function test_same_person_can_have_multiple_dated_periods_on_same_sheet(): void
    {
        $sheet = $this->createSheet();
        $personId = $this->createPerson();

        AttendanceParticipant::query()->create([
            'attendance_sheet_id' => $sheet->id,
            'person_id' => $personId,
            'starts_on' => '2026-09-02',
            'ends_on' => '2026-09-02',
            'is_active' => true,
        ]);

        AttendanceParticipant::query()->create([
            'attendance_sheet_id' => $sheet->id,
            'person_id' => $personId,
            'starts_on' => '2026-09-09',
            'ends_on' => '2026-09-09',
            'is_active' => true,
        ]);

        $this->assertSame(
            2,
            AttendanceParticipant::query()
                ->where(
                    'attendance_sheet_id',
                    $sheet->id
                )
                ->where(
                    'person_id',
                    $personId
                )
                ->count()
        );
    }

    public function test_active_on_returns_only_periods_covering_the_date(): void
    {
        $sheet = $this->createSheet();
        $personId = $this->createPerson();

        $earlyPeriod =
            AttendanceParticipant::query()->create([
                'attendance_sheet_id' => $sheet->id,
                'person_id' => $personId,
                'starts_on' => '2026-09-01',
                'ends_on' => '2026-09-03',
                'is_active' => true,
            ]);

        $laterPeriod =
            AttendanceParticipant::query()->create([
                'attendance_sheet_id' => $sheet->id,
                'person_id' => $personId,
                'starts_on' => '2026-09-09',
                'ends_on' => null,
                'is_active' => true,
            ]);

        AttendanceParticipant::query()->create([
            'attendance_sheet_id' => $sheet->id,
            'person_id' => $personId,
            'starts_on' => '2026-09-02',
            'ends_on' => '2026-09-02',
            'is_active' => false,
        ]);

        $this->assertSame(
            [$earlyPeriod->id],
            AttendanceParticipant::query()
                ->where(
                    'attendance_sheet_id',
                    $sheet->id
                )
                ->activeOn('2026-09-02')
                ->pluck('id')
                ->all()
        );

        $this->assertSame(
            [$laterPeriod->id],
            AttendanceParticipant::query()
                ->where(
                    'attendance_sheet_id',
                    $sheet->id
                )
                ->activeOn('2026-09-10')
                ->pluck('id')
                ->all()
        );
    }

    public function test_participant_count_counts_distinct_people_not_period_rows(): void
    {
        $sheet = $this->createSheet();

        $personA = $this->createPerson(
            'Person',
            'Alpha'
        );

        $personB = $this->createPerson(
            'Person',
            'Beta'
        );

        foreach (
            [
                '2026-09-02',
                '2026-09-09',
            ] as $date
        ) {
            AttendanceParticipant::query()->create([
                'attendance_sheet_id' =>
                    $sheet->id,

                'person_id' =>
                    $personA,

                'starts_on' =>
                    $date,

                'ends_on' =>
                    $date,

                'is_active' =>
                    true,
            ]);
        }

        AttendanceParticipant::query()->create([
            'attendance_sheet_id' =>
                $sheet->id,

            'person_id' =>
                $personB,

            'starts_on' =>
                '2026-09-09',

            'ends_on' =>
                '2026-09-09',

            'is_active' =>
                true,
        ]);

        $sheetWithCount =
            AttendanceSheet::query()
                ->withDistinctParticipantCount()
                ->findOrFail($sheet->id);

        $this->assertSame(
            2,
            (int) $sheetWithCount
                ->participants_count
        );
    }

    public function test_attendance_record_survives_participant_period_deletion(): void
    {
        $sheet = $this->createSheet();
        $personId = $this->createPerson();

        $session =
            AttendanceSession::query()->create([
                'attendance_sheet_id' =>
                    $sheet->id,

                'session_date' =>
                    '2026-09-09',

                'title' =>
                    'September 9 Meeting',
            ]);

        $participant =
            AttendanceParticipant::query()->create([
                'attendance_sheet_id' =>
                    $sheet->id,

                'person_id' =>
                    $personId,

                'starts_on' =>
                    '2026-09-09',

                'ends_on' =>
                    '2026-09-09',

                'is_active' =>
                    true,
            ]);

        $record =
            AttendanceRecord::query()->create([
                'attendance_session_id' =>
                    $session->id,

                'person_id' =>
                    $personId,

                'status' =>
                    AttendanceRecord::STATUS_PRESENT,

                'is_present' =>
                    true,

                'attendance_source' =>
                    AttendanceRecord::SOURCE_MANUAL,
            ]);

        $participant->delete();

        $this->assertDatabaseMissing(
            'attendance_participants',
            [
                'id' => $participant->id,
            ]
        );

        $this->assertDatabaseHas(
            'attendance_records',
            [
                'id' => $record->id,

                'attendance_session_id' =>
                    $session->id,

                'person_id' =>
                    $personId,

                'is_present' =>
                    true,
            ]
        );
    }
}
