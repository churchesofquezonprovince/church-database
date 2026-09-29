<?php

namespace Tests\Feature;

use App\Services\{AttendeeEnrollment as E, GuestAttendance as G};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendeeEnrollmentTest extends GuestAttendanceTest
{
    private function contactRow(string $type, int $id = 1, ?int $person = null): void
    {
        DB::table($type.'_contacts')->insert(['id'=>$id, 'firstname'=>'Contact', 'lastname'=>$type,
            'locality'=>'Lucban', 'person_id'=>$person]);
    }

    public function test_direct_campus_and_gospel_enrollment_needs_no_response_or_person(): void
    {
        foreach (['campus', 'gospel'] as $type) {
            $this->contactRow($type);
            E::add(1, 1, $type, 1, 'this_meeting');
            E::add(1, 1, $type, 1, 'this_meeting');
        }
        $this->assertSame(2, DB::table('attendance_guests')->count());
        $this->assertSame(2, DB::table('attendance_guest_periods')->count());
        $this->assertSame(3, DB::table('persons')->count());
        $this->assertSame(0, DB::table('attendance_meeting_responses')->count());
        $this->assertSame(0, DB::table('attendance_guest_records')->count());
        $this->assertSame(4, E::rows(1,1)->count());
    }

    public function test_direct_contact_and_later_response_reuse_same_identity(): void
    {
        $this->contactRow('campus'); E::add(1,1,'campus',1,'this_meeting');
        $id = (int) DB::table('attendance_guests')->value('id');
        $r = DB::table('attendance_meeting_responses')->insertGetId(['attendance_session_id'=>1,
            'respondent_type'=>'campus','campus_contact_id'=>1,'response'=>'yes']);
        $this->assertSame($id, G::enroll(1,1,'this_meeting',$r,null));
        $this->assertSame(1, DB::table('attendance_guests')->count());
        $this->assertSame(1, DB::table('attendance_guest_periods')->count());
    }

    public function test_direct_contact_reuses_response_explicitly_matched_to_walkin(): void
    {
        $this->contactRow('campus');
        $guest = G::enroll(1,1,'this_meeting',null,null,'Walk-in');
        $r = DB::table('attendance_meeting_responses')->insertGetId(['attendance_session_id'=>1,
            'respondent_type'=>'campus','campus_contact_id'=>1,'response'=>'yes']);
        G::enroll(1,1,'this_meeting',$r,$guest);
        E::add(1,1,'campus',1,'this_meeting');
        $this->assertSame(1, DB::table('attendance_guests')->count());
    }

    public function test_linked_contact_enrolls_its_person_once(): void
    {
        $this->contactRow('campus',1,3);
        E::add(1,1,'campus',1,'this_meeting'); E::add(1,1,'person',3,'this_meeting');
        $this->assertSame(0, DB::table('attendance_guests')->count());
        $this->assertSame(1, DB::table('attendance_participants')->where('person_id',3)->count());
        $this->assertSame(3, E::rows(1,1)->count());
    }

    public function test_existing_contact_history_requires_explicit_link_before_person_enrollment(): void
    {
        $this->contactRow('campus'); E::add(1,1,'campus',1,'this_meeting');
        DB::table('campus_contacts')->where('id',1)->update(['person_id'=>3]);
        try { E::add(1,1,'person',3,'this_meeting'); $this->fail('Expected identity review'); }
        catch (ValidationException $e) { $this->assertStringContainsString('Link to People', $e->getMessage()); }
        $this->assertSame(0, DB::table('attendance_participants')->where('person_id',3)->count());
        $this->assertSame(1, DB::table('attendance_guests')->count());
    }

    public function test_unified_rows_filter_dates_and_deduplicate_people_periods(): void
    {
        DB::table('attendance_participants')->insert(['attendance_sheet_id'=>1,'person_id'=>1,'is_active'=>true]);
        G::enroll(1,2,'this_meeting',null,null,'Tomorrow');
        $this->assertSame(2, E::rows(1,1)->count());
        $this->assertSame(3, E::rows(1,1,true)->count());
        $this->assertSame(3, E::rows(1,2)->count());
    }

    public function test_direct_enrollment_rejects_foreign_session(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        E::add(1,3,'person',3,'this_meeting');
    }

    public function test_unified_panel_renders_contact_choices_and_all_sources(): void
    {
        $this->contactRow('campus'); E::add(1,1,'campus',1,'this_meeting');
        \Livewire\Livewire::test(\App\Livewire\AttendeeEnrollmentPanel::class,
            ['sheetId'=>1,'sessionId'=>1,'mode'=>'enroll'])
            ->assertSee('Enrolled Attendees')->assertSee('Campus Contact')
            ->set('addSource','campus')->set('identitySearch','Contact')
            ->assertSee('campus, Contact')->set('identityId',1)->call('addAttendee')->assertHasNoErrors();
        $this->assertSame(1, DB::table('attendance_guests')->count());
    }

    public function test_viewer_cannot_directly_enroll_contacts(): void
    {
        auth()->user()->role = 'viewer';
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        E::add(1,1,'campus',1,'this_meeting');
    }
}
