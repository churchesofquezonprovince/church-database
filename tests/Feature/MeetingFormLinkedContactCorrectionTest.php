<?php

namespace Tests\Feature;

use App\Models\AttendanceMeetingProfileCorrection;
use App\Models\Person;
use App\Support\MeetingFormProfileCorrectionReviewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class MeetingFormLinkedContactCorrectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * These regression tests exercise the meeting-form
         * database correction review flow only.
         *
         * Historical application migrations contain
         * MySQL-specific SQL, so do not use RefreshDatabase.
         * Build only the schema required by this feature inside
         * the guarded SQLite :memory: testing database.
         */
        DB::statement('PRAGMA foreign_keys = ON');

        Schema::create(
            'localities',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
                $table->timestamps();
            }
        );

        Schema::create(
            'schools',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
                $table->timestamps();
            }
        );

        Schema::create(
            'persons',
            function (Blueprint $table): void {
                $table->increments('id');

                $table->string('firstname', 100);
                $table->string('middlename', 100)
                    ->nullable();
                $table->string('lastname', 100);
                $table->string('suffix', 50)
                    ->nullable();

                $table->string('sex', 20)
                    ->nullable();

                $table->string('nickname', 100)
                    ->nullable();

                $table->date('birthdate')
                    ->nullable();

                $table->string('birthplace')
                    ->nullable();

                $table->unsignedInteger('household_id')
                    ->nullable();

                $table->unsignedInteger('spouse_id')
                    ->nullable();

                $table->string('locality')
                    ->nullable();

                $table->unsignedInteger('locality_id')
                    ->nullable();

                $table->text('permanent_address')
                    ->nullable();

                $table->text('home_address')
                    ->nullable();

                $table->string('email')
                    ->nullable();

                $table->string('facebook_account')
                    ->nullable();

                $table->string('contact_number', 20)
                    ->nullable();

                $table->unsignedInteger(
                    'emergency_contact_id'
                )->nullable();

                $table->string(
                    'emergency_contact_relationship'
                )->nullable();

                $table->string(
                    'emergency_contact_number',
                    20
                )->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'church_profiles',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedInteger('person_id')
                    ->unique();

                $table->string('category')
                    ->nullable();

                $table->string('status')
                    ->nullable();

                $table->date('baptism_date')
                    ->nullable();

                /*
                 * ChurchProfile keeps these derived baptism
                 * components alongside baptism_date.
                 */
                $table->unsignedTinyInteger(
                    'baptism_month'
                )->nullable();

                $table->unsignedTinyInteger(
                    'baptism_day'
                )->nullable();

                $table->date('first_contact_date')
                    ->nullable();

                $table->string('contact_origin')
                    ->nullable();

                $table->text('contact_origin_details')
                    ->nullable();

                $table->string('service')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'education_profiles',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedInteger('person_id')
                    ->unique();

                $table->unsignedInteger('school_id')
                    ->nullable();

                $table->string('grade_level')
                    ->nullable();

                $table->string('course_strand')
                    ->nullable();

                $table->string('occupation')
                    ->nullable();

                $table->string('workplace')
                    ->nullable();

                $table->string('school_workplace')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'campus_contacts',
            function (Blueprint $table): void {
                $table->increments('id');

                $table->unsignedInteger('person_id')
                    ->nullable();

                $table->string('firstname', 100);
                $table->string('lastname', 100);
                $table->string('sex', 20);

                $table->string('locality')
                    ->nullable();

                $table->unsignedInteger('locality_id')
                    ->nullable();

                $table->unsignedInteger('school_id')
                    ->nullable();

                $table->string('school_campus')
                    ->nullable();

                $table->string('course_strand')
                    ->nullable();

                $table->string('grade_level')
                    ->nullable();

                $table->string('contact_number', 20)
                    ->nullable();

                $table->string('email')
                    ->nullable();

                $table->string('facebook_account')
                    ->nullable();

                $table->text('notes')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'gospel_contacts',
            function (Blueprint $table): void {
                $table->increments('id');

                $table->unsignedInteger('person_id')
                    ->nullable();

                $table->string('firstname', 100);
                $table->string('lastname', 100);
                $table->string('sex', 20);

                $table->string('locality')
                    ->nullable();

                $table->unsignedInteger('locality_id')
                    ->nullable();

                $table->string('contact_number', 20)
                    ->nullable();

                $table->string('email')
                    ->nullable();

                $table->string('facebook_account')
                    ->nullable();

                $table->text('address')
                    ->nullable();

                $table->string('contact_place')
                    ->nullable();

                $table->text('notes')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'attendance_sheets',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->string('title');
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

                $table->string('title')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'attendance_meeting_responses',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedInteger(
                    'attendance_session_id'
                );

                $table->string('respondent_type', 20);

                $table->string('original_source', 20)
                    ->nullable();

                $table->unsignedInteger('person_id')
                    ->nullable();

                $table->unsignedInteger('campus_contact_id')
                    ->nullable();

                $table->unsignedInteger('gospel_contact_id')
                    ->nullable();

                $table->string('guest_name')
                    ->nullable();

                $table->text('guest_profile')
                    ->nullable();

                $table->string('respondent_name')
                    ->nullable();

                $table->string('response', 20)
                    ->nullable();

                $table->string('submitted_form_type', 30)
                    ->nullable();

                $table->timestamp('responded_at')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'attendance_meeting_form_questions',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedInteger(
                    'attendance_sheet_id'
                );

                $table->string('question_type', 50);

                $table->string('database_field', 100)
                    ->nullable();

                $table->text('question_text');

                $table->text('description')
                    ->nullable();

                $table->text('options')
                    ->nullable();

                $table->boolean('is_required')
                    ->default(false);

                $table->boolean('allow_correction')
                    ->default(true);

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamps();
            }
        );

        Schema::create(
            'attendance_meeting_profile_corrections',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'attendance_meeting_response_id'
                );

                $table->unsignedBigInteger(
                    'attendance_meeting_form_question_id'
                );

                $table->unsignedInteger('person_id')
                    ->nullable();

                $table->unsignedInteger('campus_contact_id')
                    ->nullable();

                $table->unsignedInteger('gospel_contact_id')
                    ->nullable();

                $table->string('database_field', 100);

                $table->string('field_owner', 50)
                    ->nullable();

                $table->string('change_type', 30);

                $table->longText('original_value_text')
                    ->nullable();

                $table->text('original_value_json')
                    ->nullable();

                $table->longText('proposed_value_text')
                    ->nullable();

                $table->text('proposed_value_json')
                    ->nullable();

                $table->string('status', 20)
                    ->default('pending');

                $table->unsignedBigInteger('reviewed_by_id')
                    ->nullable();

                $table->timestamp('reviewed_at')
                    ->nullable();

                $table->text('review_note')
                    ->nullable();

                $table->timestamps();
            }
        );
    }


    public function test_pending_campus_person_field_becomes_approvable_after_linking(): void
    {
        $personId =
            $this->createPerson();

        $campusContactId =
            DB::table('campus_contacts')
                ->insertGetId([
                    'person_id' => null,
                    'firstname' => 'Campus',
                    'lastname' => 'Contact',
                    'sex' => 'Male',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        $correctionId =
            $this->createBirthdateCorrection(
                respondentType: 'campus',
                campusContactId:
                    $campusContactId,
                gospelContactId:
                    null,
                proposedBirthdate:
                    '2005-06-14',
            );

        /*
         * The correction already exists when this contact
         * becomes linked to People Database.
         *
         * Use the query builder here deliberately: the behavior
         * under test is correction review, not Campus linking.
         */
        DB::table('campus_contacts')
            ->where('id', $campusContactId)
            ->update([
                'person_id' => $personId,
                'updated_at' => now(),
            ]);

        $service =
            app(
                MeetingFormProfileCorrectionReviewService::class
            );

        $service->approve(
            AttendanceMeetingProfileCorrection::query()
                ->findOrFail($correctionId),
            null
        );

        $person =
            Person::query()
                ->findOrFail($personId);

        $this->assertSame(
            '2005-06-14',
            $person->birthdate?->format('Y-m-d')
        );

        $correction =
            AttendanceMeetingProfileCorrection::query()
                ->findOrFail($correctionId);

        $this->assertSame(
            AttendanceMeetingProfileCorrection::STATUS_APPROVED,
            $correction->status
        );

        $this->assertSame(
            $personId,
            (int) $correction->person_id
        );

        /*
         * Preserve the original Campus source identity.
         */
        $this->assertSame(
            $campusContactId,
            (int) $correction->campus_contact_id
        );
    }


    public function test_pending_gospel_person_field_becomes_approvable_after_linking(): void
    {
        $personId =
            $this->createPerson();

        $gospelContactId =
            DB::table('gospel_contacts')
                ->insertGetId([
                    'person_id' => null,
                    'firstname' => 'Gospel',
                    'lastname' => 'Contact',
                    'sex' => 'Female',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        $correctionId =
            $this->createBirthdateCorrection(
                respondentType: 'gospel',
                campusContactId:
                    null,
                gospelContactId:
                    $gospelContactId,
                proposedBirthdate:
                    '2003-11-21',
            );

        /*
         * Simulate the contact being promoted/linked after the
         * pending correction had already been recorded.
         */
        DB::table('gospel_contacts')
            ->where('id', $gospelContactId)
            ->update([
                'person_id' => $personId,
                'updated_at' => now(),
            ]);

        $service =
            app(
                MeetingFormProfileCorrectionReviewService::class
            );

        $service->approve(
            AttendanceMeetingProfileCorrection::query()
                ->findOrFail($correctionId),
            null
        );

        $person =
            Person::query()
                ->findOrFail($personId);

        $this->assertSame(
            '2003-11-21',
            $person->birthdate?->format('Y-m-d')
        );

        $correction =
            AttendanceMeetingProfileCorrection::query()
                ->findOrFail($correctionId);

        $this->assertSame(
            AttendanceMeetingProfileCorrection::STATUS_APPROVED,
            $correction->status
        );

        $this->assertSame(
            $personId,
            (int) $correction->person_id
        );

        /*
         * Preserve the original Gospel source identity.
         */
        $this->assertSame(
            $gospelContactId,
            (int) $correction->gospel_contact_id
        );
    }


    public function test_linked_person_newer_value_still_blocks_stale_pending_correction(): void
    {
        /*
         * This Person already has a newer canonical value by the
         * time the old Campus correction is reviewed.
         */
        $personId =
            $this->createPerson(
                '2004-01-01'
            );

        $campusContactId =
            DB::table('campus_contacts')
                ->insertGetId([
                    'person_id' => null,
                    'firstname' => 'Conflict',
                    'lastname' => 'Contact',
                    'sex' => 'Male',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        /*
         * At submission time this Campus Contact had no Person,
         * so the original Person-only Birthdate value was blank.
         */
        $correctionId =
            $this->createBirthdateCorrection(
                respondentType: 'campus',
                campusContactId:
                    $campusContactId,
                gospelContactId:
                    null,
                proposedBirthdate:
                    '2005-06-14',
            );

        DB::table('campus_contacts')
            ->where('id', $campusContactId)
            ->update([
                'person_id' => $personId,
                'updated_at' => now(),
            ]);

        $service =
            app(
                MeetingFormProfileCorrectionReviewService::class
            );

        try {
            $service->approve(
                AttendanceMeetingProfileCorrection::query()
                    ->findOrFail($correctionId),
                null
            );

            $this->fail(
                'Expected the stale correction to be blocked.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'The database value changed after this request was submitted. Review the current value before approving.',
                $exception->getMessage()
            );
        }

        /*
         * The newer canonical Person value must never be
         * overwritten by the stale proposal.
         */
        $person =
            Person::query()
                ->findOrFail($personId);

        $this->assertSame(
            '2004-01-01',
            $person->birthdate?->format('Y-m-d')
        );

        $correction =
            AttendanceMeetingProfileCorrection::query()
                ->findOrFail($correctionId);

        $this->assertSame(
            AttendanceMeetingProfileCorrection::STATUS_PENDING,
            $correction->status
        );
    }


    private function createPerson(
        ?string $birthdate = null
    ): int {
        return (int) DB::table('persons')
            ->insertGetId([
                'firstname' => 'Linked',
                'middlename' => null,
                'lastname' => 'Person',
                'suffix' => null,
                'sex' => 'Male',
                'nickname' => null,
                'birthdate' => $birthdate,
                'birthplace' => null,
                'household_id' => null,
                'spouse_id' => null,
                'locality' => null,
                'locality_id' => null,
                'permanent_address' => null,
                'home_address' => null,
                'email' => null,
                'facebook_account' => null,
                'contact_number' => null,
                'emergency_contact_id' => null,
                'emergency_contact_relationship' => null,
                'emergency_contact_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }


    private function createBirthdateCorrection(
        string $respondentType,
        ?int $campusContactId,
        ?int $gospelContactId,
        string $proposedBirthdate
    ): int {
        $sheetId =
            (int) DB::table('attendance_sheets')
                ->insertGetId([
                    'title' =>
                        'Linked Contact Correction Test',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        $sessionId =
            (int) DB::table('attendance_sessions')
                ->insertGetId([
                    'attendance_sheet_id' =>
                        $sheetId,
                    'session_date' =>
                        '2026-09-20',
                    'title' =>
                        'Regression Test Meeting',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        $questionId =
            (int) DB::table(
                'attendance_meeting_form_questions'
            )->insertGetId([
                'attendance_sheet_id' =>
                    $sheetId,

                'question_type' =>
                    'database_field',

                'database_field' =>
                    'birthdate',

                'question_text' =>
                    'Birthdate',

                'description' =>
                    null,

                'options' =>
                    null,

                'is_required' =>
                    false,

                'allow_correction' =>
                    true,

                'sort_order' =>
                    1,

                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $respondentName =
            $respondentType === 'campus'
                ? 'Contact, Campus'
                : 'Contact, Gospel';

        $responseId =
            (int) DB::table(
                'attendance_meeting_responses'
            )->insertGetId([
                'attendance_session_id' =>
                    $sessionId,

                'respondent_type' =>
                    $respondentType,

                'original_source' =>
                    $respondentType,

                'person_id' =>
                    null,

                'campus_contact_id' =>
                    $campusContactId,

                'gospel_contact_id' =>
                    $gospelContactId,

                'guest_name' =>
                    null,

                'guest_profile' =>
                    null,

                'respondent_name' =>
                    $respondentName,

                'response' =>
                    null,

                'submitted_form_type' =>
                    'google_form',

                'responded_at' =>
                    now(),

                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return (int) DB::table(
            'attendance_meeting_profile_corrections'
        )->insertGetId([
            'attendance_meeting_response_id' =>
                $responseId,

            'attendance_meeting_form_question_id' =>
                $questionId,

            /*
             * This is the exact historical state that caused
             * the bug: no Person was linked yet.
             */
            'person_id' =>
                null,

            'campus_contact_id' =>
                $campusContactId,

            'gospel_contact_id' =>
                $gospelContactId,

            'database_field' =>
                'birthdate',

            'field_owner' =>
                'person',

            'change_type' =>
                'profile_completion',

            'original_value_text' =>
                null,

            'original_value_json' =>
                null,

            'proposed_value_text' =>
                $proposedBirthdate,

            'proposed_value_json' =>
                null,

            'status' =>
                AttendanceMeetingProfileCorrection::STATUS_PENDING,

            'reviewed_by_id' =>
                null,

            'reviewed_at' =>
                null,

            'review_note' =>
                null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
