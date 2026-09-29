<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ConferenceWorkspace as W;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class ConferenceWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Only isolated in-memory SQLite is permitted.');
        }
        Schema::create('persons', function (Blueprint $t) {
            $t->increments('id'); foreach (['firstname','middlename','lastname','suffix','locality'] as $c) $t->string($c)->nullable();
        });
        Schema::create('attendance_sheets', function (Blueprint $t) {
            $t->increments('id'); $t->string('title'); $t->string('sheet_type'); $t->boolean('is_active'); $t->string('meeting_form_type')->default('google_form');
        });
        Schema::create('attendance_sessions', function (Blueprint $t) {
            $t->increments('id'); $t->unsignedInteger('attendance_sheet_id'); $t->date('session_date'); $t->boolean('is_no_meeting')->default(false);
        });
        Schema::create('attendance_participants', function (Blueprint $t) {
            $t->increments('id'); $t->unsignedInteger('attendance_sheet_id'); $t->unsignedInteger('person_id');
            $t->date('starts_on')->nullable(); $t->date('ends_on')->nullable(); $t->boolean('is_active')->default(true);
        });
        Schema::create('attendance_records', function (Blueprint $t) {
            $t->increments('id'); $t->unsignedInteger('attendance_session_id'); $t->unsignedInteger('person_id'); $t->string('status'); $t->boolean('is_present')->default(false);
        });
        Schema::create('attendance_meeting_responses', function (Blueprint $t) {
            $t->id(); $t->unsignedInteger('attendance_session_id'); $t->string('respondent_type'); $t->unsignedInteger('person_id')->nullable();
            $t->unsignedInteger('campus_contact_id')->nullable(); $t->unsignedInteger('gospel_contact_id')->nullable();
            $t->string('response')->nullable(); $t->timestamp('responded_at')->nullable();
        });
        Schema::create('attendance_meeting_form_questions', function (Blueprint $t) {
            $t->id(); $t->unsignedInteger('attendance_sheet_id'); $t->string('question_type'); $t->string('question_text'); $t->text('options')->nullable();
        });
        Schema::create('attendance_meeting_form_answers', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('attendance_meeting_response_id'); $t->unsignedBigInteger('attendance_meeting_form_question_id');
            $t->text('answer_text')->nullable(); $t->text('answer_json')->nullable();
        });
        (require database_path('migrations/2026_09_29_130000_create_conference_workspace_tables.php'))->up();
        $user = new User; $user->forceFill(['id'=>1,'role'=>'admin','name'=>'Test']); $this->actingAs($user);
        DB::table('attendance_sheets')->insert([
            ['id'=>1,'title'=>'Conference','sheet_type'=>'custom','is_active'=>true],
            ['id'=>2,'title'=>'Other','sheet_type'=>'custom','is_active'=>true],
        ]);
        DB::table('attendance_sessions')->insert([
            ['id'=>1,'attendance_sheet_id'=>1,'session_date'=>'2026-09-26'],
            ['id'=>2,'attendance_sheet_id'=>1,'session_date'=>'2026-09-27'],
            ['id'=>3,'attendance_sheet_id'=>2,'session_date'=>'2026-09-26'],
        ]);
        foreach ([1,2,3] as $id) DB::table('persons')->insert(['id'=>$id,'firstname'=>'Person '.$id,'lastname'=>'Test','locality'=>'Lucban']);
        foreach ([1,2] as $id) DB::table('attendance_participants')->insert(['attendance_sheet_id'=>1,'person_id'=>$id]);
    }

    public function test_conference_reuses_sheet_and_rejects_foreign_dates(): void
    {
        $id = W::create(1,[1,2],'YP Blending');
        $this->assertSame(2,W::data($id)['sessions']->count());
        $this->assertSame(2,DB::table('attendance_participants')->count());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        W::create(2,[1],'Conference');
    }
    public function test_repeated_roster_periods_and_days_do_not_inflate_people(): void
    {
        DB::table('attendance_participants')->insert(['attendance_sheet_id'=>1,'person_id'=>1]);
        DB::table('attendance_participants')->insert(['attendance_sheet_id'=>1,'person_id'=>3,'starts_on'=>'2026-10-01']);
        $data = W::data(W::create(1,[1,2],'Conference'));
        $this->assertSame(2,$data['totals']['roster']);
        $this->assertCount(2,$data['rows']);
    }
    public function test_attendance_status_wins_over_legacy_boolean(): void
    {
        DB::table('attendance_records')->insert([
            ['attendance_session_id'=>1,'person_id'=>1,'status'=>'absent','is_present'=>true],
            ['attendance_session_id'=>2,'person_id'=>2,'status'=>'late','is_present'=>false],
        ]);
        $id=W::create(1,[1,2],'Conference');
        $this->assertSame(1,W::data($id)['totals']['attended']);
        $this->assertSame(0,W::data($id,1)['totals']['attended']);
    }
    public function test_responses_do_not_enroll_or_mark_attendance(): void
    {
        DB::table('attendance_meeting_responses')->insert([
            ['attendance_session_id'=>1,'respondent_type'=>'guest','person_id'=>null,'response'=>'yes'],
            ['attendance_session_id'=>1,'respondent_type'=>'person','person_id'=>3,'response'=>'yes'],
        ]);
        $data=W::data(W::create(1,[1],'Conference'));
        $this->assertSame(2,$data['totals']['review']);
        $this->assertSame(2,$data['totals']['roster']);
        $this->assertSame(0,$data['totals']['attended']);
        $this->assertSame(2,DB::table('attendance_participants')->count());
    }
    public function test_checkbox_filters_use_exact_options_and_preserve_unanswered(): void
    {
        $answer=new \App\Models\AttendanceMeetingFormAnswer(['attendance_meeting_form_question_id'=>1,'answer_json'=>['Overnight','LTM']]);
        $response=new \App\Models\AttendanceMeetingResponse;
        $response->setRelation('formAnswers',collect([$answer]));
        $this->assertTrue(W::matchesAnswer(collect([$response]),1,'Overnight'));
        $this->assertFalse(W::matchesAnswer(collect([$response]),1,'Night'));
        $this->assertFalse(W::matchesAnswer(collect([$response]),1,'__unanswered'));
        $this->assertTrue(W::matchesAnswer(collect([$response]),2,'__unanswered'));
    }
    public function test_assignments_and_invitations_do_not_change_attendance(): void
    {
        $id=W::create(1,[1],'Conference');
        $team=DB::table('conference_teams')->insertGetId(['conference_event_id'=>$id,'name'=>'Rose','color'=>'#cc0000']);
        W::savePerson($id,1,$team,'serving_one'); W::invite($id,1,2);
        $data=W::data($id);
        $this->assertSame('serving_one',$data['rows']->get(1)['role']);
        $this->assertSame([2],array_map('intval',$data['rows']->get(1)['invites']));
        $this->assertSame(0,DB::table('attendance_records')->count());
        $this->assertSame(2,DB::table('attendance_participants')->count());
    }
    public function test_team_from_another_conference_is_rejected(): void
    {
        $id=W::create(1,[1],'Conference'); $other=W::create(2,[3],'Conference');
        $team=DB::table('conference_teams')->insertGetId(['conference_event_id'=>$other,'name'=>'Other','color'=>'#cc0000']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        W::savePerson($id,1,$team,'young_person');
    }
    public function test_self_invitation_is_rejected(): void
    {
        $id=W::create(1,[1],'Conference');
        $this->expectException(\Illuminate\Validation\ValidationException::class); W::invite($id,1,1);
    }
    public function test_duplicate_conference_is_rejected(): void
    {
        W::create(1,[1],'Conference');
        $this->expectException(\Illuminate\Validation\ValidationException::class); W::create(1,[2],'Conference');
    }
    public function test_viewer_cannot_read_or_write_conferences(): void
    {
        $user=new User; $user->forceFill(['id'=>2,'role'=>'viewer']); $this->actingAs($user);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class); W::create(1,[1],'Conference');
    }
}
