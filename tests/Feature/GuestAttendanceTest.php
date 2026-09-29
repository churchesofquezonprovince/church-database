<?php

namespace Tests\Feature;

use App\Models\{AttendanceMeetingResponse,User};
use App\Services\{ConferenceWorkspace as W,GuestAttendance as G};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;

class GuestAttendanceTest extends ConferenceWorkspaceTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users',function(Blueprint $t) { $t->id(); $t->string('name'); });
        DB::table('users')->insert(['id'=>1,'name'=>'Admin']);
        foreach (['campus_contacts','gospel_contacts'] as $table) Schema::create($table,function(Blueprint $t) {
            $t->increments('id'); $t->unsignedInteger('person_id')->nullable(); $t->string('firstname')->nullable(); $t->string('lastname')->nullable(); $t->string('locality')->nullable();
        });
        Schema::create('church_profiles',function(Blueprint $t) { $t->increments('id'); $t->unsignedInteger('person_id'); $t->string('category')->nullable(); });
        Schema::table('attendance_sheets',fn(Blueprint $t)=>$t->string('schedule_type')->default('recurring'));
        Schema::table('attendance_participants',fn(Blueprint $t)=>$t->timestamps());
        Schema::table('attendance_records',function(Blueprint $t) {
            $t->string('attendance_source')->nullable(); $t->unsignedBigInteger('marked_by_id')->nullable(); $t->timestamp('marked_at')->nullable(); $t->timestamps();
        });
        Schema::table('attendance_meeting_responses',function(Blueprint $t) {
            $t->string('respondent_name')->nullable(); $t->string('guest_name')->nullable(); $t->text('guest_profile')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_09_29_140000_create_guest_attendance_tables.php'))->up();
    }
    private function makeGuestResponse(string $name='Guest One',int $session=1): int
    {
        return DB::table('attendance_meeting_responses')->insertGetId(['attendance_session_id'=>$session,'respondent_type'=>'guest','guest_name'=>$name,'response'=>'yes']);
    }
    public function test_guest_enrollment_does_not_create_people_or_attendance(): void
    {
        $response=$this->makeGuestResponse(); $id=G::enroll(1,1,'this_meeting',$response,null);
        $this->assertSame(3,DB::table('persons')->count());
        $this->assertSame(0,DB::table('attendance_guest_records')->count());
        $this->assertSame([$id],G::activeIds(1,'2026-09-26')->all());
        $this->assertSame([],G::activeIds(1,'2026-09-27')->all());
    }
    public function test_reenrollment_is_idempotent_and_names_do_not_merge(): void
    {
        $r=$this->makeGuestResponse(); $a=G::enroll(1,1,'this_meeting',$r,null);
        $this->assertSame($a,G::enroll(1,1,'this_meeting',$r,null));
        $b=G::enroll(1,1,'this_meeting',$this->makeGuestResponse(),null);
        $this->assertNotSame($a,$b); $this->assertSame(2,DB::table('attendance_guest_periods')->count());
    }
    public function test_explicit_guest_reuse_across_dates_counts_once(): void
    {
        $a=G::enroll(1,1,'this_meeting',$this->makeGuestResponse(),null);
        $r=$this->makeGuestResponse('Guest One',2);
        $this->assertSame($a,G::enroll(1,2,'this_meeting',$r,$a));
        $data=W::data(W::create(1,[1,2],'Blending'));
        $this->assertSame(3,$data['totals']['roster']);
        $this->assertCount(2,$data['rows']->get(-$a)['responses']);
    }
    public function test_same_response_cannot_be_reassigned_to_another_guest(): void
    {
        $r=$this->makeGuestResponse(); G::enroll(1,1,'this_meeting',$r,null);
        $b=G::enroll(1,1,'this_meeting',null,null,'Second');
        $this->expectException(ValidationException::class); G::enroll(1,1,'this_meeting',$r,$b);
    }
    public function test_guest_checkin_updates_shared_reports_and_conference_counts(): void
    {
        $g=G::enroll(1,1,'onward',null,null,'Walk-in','Lucban'); G::mark(1,1,$g,'late'); G::mark(1,2,$g,'absent');
        $event=W::create(1,[1,2],'Conference');
        $this->assertSame(1,W::data($event)['totals']['attended']);
        $this->assertSame(0,W::data($event,2)['totals']['attended']);
        $r=G::reportRows(1,G::reportSessions(1))->sole();
        $this->assertSame(2,$r['expected']); $this->assertSame(1,$r['present']); $this->assertSame(1,$r['absent']);
        $count=G::meetingCounts(\App\Models\AttendanceSession::findOrFail(1)); $this->assertSame(1,$count['present']);
    }
    public function test_other_sheet_cannot_mark_or_reuse_guest(): void
    {
        $g=G::enroll(1,1,'this_meeting',null,null,'Walk-in');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class); G::mark(2,3,$g,'present');
    }
    public function test_no_meeting_blocks_guest_checkin(): void
    {
        $g=G::enroll(1,1,'this_meeting',null,null,'Walk-in'); DB::table('attendance_sessions')->where('id',1)->update(['is_no_meeting'=>true]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class); G::mark(1,1,$g,'present');
    }
    public function test_deactivating_period_preserves_historical_attendance(): void
    {
        $g=G::enroll(1,1,'onward',null,null,'Walk-in'); G::mark(1,1,$g,'present');
        G::endPeriod(1,DB::table('attendance_guest_periods')->value('id'));
        $this->assertSame([],G::activeIds(1,'2026-09-26')->all()); $this->assertSame(1,G::reportRows(1,G::reportSessions(1))->sole()['present']);
    }
    public function test_guest_teams_and_mixed_invitations_work_without_people_creation(): void
    {
        $g=G::enroll(1,1,'this_meeting',null,null,'Walk-in'); $e=W::create(1,[1],'Blending');
        $team=DB::table('conference_teams')->insertGetId(['conference_event_id'=>$e,'name'=>'Rose','color'=>'#cc0000']);
        W::savePerson($e,-$g,$team,'young_people'); W::invite($e,1,-$g);
        $data=W::data($e); $this->assertSame($team,(int)$data['rows']->get(-$g)['team_id']);
        $this->assertSame([-$g],$data['rows']->get(1)['invites']); $this->assertSame(3,DB::table('persons')->count());
    }
    public function test_link_preserves_guest_history_and_moves_counts_assignments_and_invites(): void
    {
        $r=$this->makeGuestResponse(); $g=G::enroll(1,1,'onward',$r,null); G::mark(1,1,$g,'present');
        $e=W::create(1,[1,2],'Blending'); $team=DB::table('conference_teams')->insertGetId(['conference_event_id'=>$e,'name'=>'Rose','color'=>'#cc0000']);
        W::savePerson($e,-$g,$team,'young_people'); W::invite($e,1,-$g);
        G::linkPerson(1,$g,3);
        $this->assertSame(1,DB::table('attendance_guest_records')->count());
        $this->assertSame(3,(int)DB::table('attendance_guests')->where('id',$g)->value('linked_person_id'));
        $this->assertSame(1,DB::table('attendance_records')->where('person_id',3)->count());
        $data=W::data($e); $this->assertFalse($data['rows']->has(-$g)); $this->assertSame(3,$data['totals']['roster']);
        $this->assertSame(1,$data['totals']['attended']); $this->assertSame($team,(int)$data['rows']->get(3)['team_id']);
        $this->assertSame([3],$data['rows']->get(1)['invites']);
    }
    public function test_link_conflicting_attendance_rolls_back_everything(): void
    {
        $g=G::enroll(1,1,'this_meeting',null,null,'Walk-in'); G::mark(1,1,$g,'present');
        DB::table('attendance_records')->insert(['attendance_session_id'=>1,'person_id'=>3,'status'=>'absent']);
        try { G::linkPerson(1,$g,3); $this->fail('Conflict should stop linking.'); } catch(ValidationException) {}
        $this->assertNull(DB::table('attendance_guests')->where('id',$g)->value('linked_person_id'));
        $this->assertSame('absent',DB::table('attendance_records')->where('person_id',3)->value('status'));
    }
    public function test_existing_response_promotion_cannot_bypass_guest_history(): void
    {
        $r=$this->makeGuestResponse(); G::enroll(1,1,'this_meeting',$r,null);
        $this->expectException(ValidationException::class); G::guardOldPromotion(AttendanceMeetingResponse::findOrFail($r));
    }
    public function test_livewire_guest_panel_renders_and_enrolls_without_creating_a_person(): void
    {
        \Livewire\Livewire::test(\App\Livewire\GuestAttendancePanel::class,['sheetId'=>1,'sessionId'=>1,'mode'=>'enroll'])
            ->set('name','A New Guest')->call('enroll')->assertHasNoErrors()->assertSee('A New Guest');
        $this->assertSame(3,DB::table('persons')->count());
    }
    public function test_viewer_cannot_enroll_guests(): void
    {
        $u=new User; $u->forceFill(['id'=>2,'role'=>'viewer']); $this->actingAs($u);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class); G::enroll(1,1,'this_meeting',null,null,'Guest');
    }
    public function test_conflicting_team_stops_person_link_without_changing_counts(): void
    {
        $g=G::enroll(1,1,'this_meeting',null,null,'Guest'); $e=W::create(1,[1],'Conference');
        $a=DB::table('conference_teams')->insertGetId(['conference_event_id'=>$e,'name'=>'A','color'=>'#000000']);
        $b=DB::table('conference_teams')->insertGetId(['conference_event_id'=>$e,'name'=>'B','color'=>'#ffffff']);
        W::savePerson($e,-$g,$a,'young_people'); W::savePerson($e,1,$b,'young_people');
        try { G::linkPerson(1,$g,1); $this->fail('Conflict should stop link.'); } catch(ValidationException) {}
        $this->assertNull(DB::table('attendance_guests')->where('id',$g)->value('linked_person_id'));
        $this->assertSame(3,W::data($e)['totals']['roster']);
    }
    public function test_deleting_team_unassigns_people_and_guests_without_removing_roles(): void
    {
        $guestId =
            G::enroll(
                1,
                1,
                'this_meeting',
                null,
                null,
                'Guest Team Member'
            );

        $eventId =
            W::create(
                1,
                [1],
                'Conference'
            );

        $teamId =
            DB::table(
                'conference_teams'
            )->insertGetId([
                'conference_event_id' =>
                    $eventId,

                'name' =>
                    'Temporary Team',

                'color' =>
                    '#cc0000',
            ]);

        W::savePerson(
            $eventId,
            1,
            $teamId,
            'serving_one'
        );

        W::savePerson(
            $eventId,
            -$guestId,
            $teamId,
            'young_people'
        );

        $this->assertSame(
            2,
            W::deleteTeam(
                $eventId,
                $teamId
            )
        );

        $this->assertFalse(
            DB::table(
                'conference_teams'
            )
                ->where(
                    'id',
                    $teamId
                )
                ->exists()
        );

        $person =
            DB::table(
                'conference_person_details'
            )
                ->where(
                    'conference_event_id',
                    $eventId
                )
                ->where(
                    'person_id',
                    1
                )
                ->first();

        $guest =
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

        $this->assertNull(
            $person->conference_team_id
        );

        $this->assertSame(
            'serving_one',
            $person->event_role
        );

        $this->assertNull(
            $guest->conference_team_id
        );

        $this->assertSame(
            'young_people',
            $guest->event_role
        );
    }


    public function test_link_that_would_create_self_invitation_is_rejected(): void
    {
        $g=G::enroll(1,1,'this_meeting',null,null,'Guest'); $e=W::create(1,[1],'Conference'); W::invite($e,1,-$g);
        $this->expectException(ValidationException::class); G::linkPerson(1,$g,1);
    }
    public function test_onward_is_not_allowed_for_one_time_sheets(): void
    {
        DB::table('attendance_sheets')->where('id',1)->update(['schedule_type'=>'one_time']);
        $this->expectException(ValidationException::class); G::enroll(1,1,'onward',null,null,'Guest');
    }

    public function test_existing_campus_and_gospel_identities_are_reused_across_dates(): void
    {
        foreach (['campus'=>'campus_contacts','gospel'=>'gospel_contacts'] as $type=>$table) {
            DB::table($table)->insert(['id'=>1,'firstname'=>'Contact','lastname'=>'One','locality'=>'Lucban']);
            $ids=[];
            foreach ([1,2] as $session) {
                $r=DB::table('attendance_meeting_responses')->insertGetId(['attendance_session_id'=>$session,'respondent_type'=>$type,
                    $type.'_contact_id'=>1,'respondent_name'=>$type.' Contact','response'=>'yes']);
                $ids[]=G::enroll(1,$session,'this_meeting',$r,null);
            }
            $this->assertSame($ids[0],$ids[1]);
        }
        $this->assertSame(2,DB::table('attendance_guests')->count()); $this->assertSame(3,DB::table('persons')->count());
    }
    public function test_custom_export_people_rows_deduplicate_enrollment_periods(): void
    {
        DB::table('attendance_participants')->insert(['attendance_sheet_id'=>1,'person_id'=>1,'starts_on'=>'2026-09-26','ends_on'=>'2026-09-26']);
        $rows=G::peopleReportRows(1,G::reportSessions(1));
        $this->assertCount(2,$rows); $this->assertSame(2,$rows->firstWhere('person.id',1)['expected']);
    }

}
