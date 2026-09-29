<?php

namespace App\Services;

use App\Models\{AttendanceMeetingResponse, AttendanceParticipant, AttendanceRecord, AttendanceSession, AttendanceSheet, Person};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GuestAttendance
{
    public static function authorize(): void { abort_unless(auth()->user()?->canManageRecords(), 403); }
    private static function fail(string $message): never { throw ValidationException::withMessages(['guestAttendance'=>$message]); }
    public static function sheet(int $id, bool $write = false): AttendanceSheet
    {
        self::authorize();
        $sheet = AttendanceSheet::query()->where('sheet_type','custom')->findOrFail($id);
        if ($write) abort_unless($sheet->is_active, 403);
        return $sheet;
    }
    public static function meeting(int $sheetId, int $sessionId, bool $write = false): AttendanceSession
    {
        self::sheet($sheetId, $write);
        $session = AttendanceSession::query()->where('attendance_sheet_id',$sheetId)->findOrFail($sessionId);
        if ($write) abort_if($session->is_no_meeting, 403, 'This date is marked No Meeting.');
        return $session;
    }
    public static function guests(int $sheetId): Collection
    {
        self::sheet($sheetId);
        return DB::table('attendance_guests')->where('attendance_sheet_id',$sheetId)->whereNull('linked_person_id')->orderBy('name')->get()->keyBy('id');
    }
    public static function activeIds(int $sheetId, string $date): Collection
    {
        return DB::table('attendance_guest_periods as p')->join('attendance_guests as g','g.id','=','p.attendance_guest_id')
            ->where('g.attendance_sheet_id',$sheetId)->whereNull('g.linked_person_id')->where('p.is_active',true)
            ->where('p.starts_on','<=',$date)->where(fn ($q)=>$q->whereNull('p.ends_on')->orWhere('p.ends_on','>=',$date))
            ->distinct()->pluck('g.id')->map(fn ($id)=>(int)$id);
    }
    public static function records(int $sheetId, Collection $sessionIds): Collection
    {
        return DB::table('attendance_guest_records as r')->join('attendance_guests as g','g.id','=','r.attendance_guest_id')
            ->where('g.attendance_sheet_id',$sheetId)->whereNull('g.linked_person_id')
            ->whereIn('r.attendance_session_id',$sessionIds)->get(['r.*']);
    }
    public static function enroll(int $sheetId, int $sessionId, string $scope, ?int $responseId, ?int $existingId, string $name = '', string $locality = ''): int
    {
        return DB::transaction(function () use ($sheetId,$sessionId,$scope,$responseId,$existingId,$name,$locality): int {
            self::authorize();
            DB::table('attendance_sheets')->where('id',$sheetId)->lockForUpdate()->first();
            $sheet=self::sheet($sheetId,true); $session=self::meeting($sheetId,$sessionId,true);
            if (!in_array($scope,['this_meeting','onward'],true)) self::fail('Choose an enrollment period.');
            if ($scope==='onward' && $sheet->schedule_type==='one_time') self::fail('Use This Meeting for a one-time sheet.');
            $guest=$existingId ? self::guests($sheetId)->get($existingId) : null;
            if ($existingId && !$guest) abort(404);
            $type='manual'; $source=null; $response=null;
            if ($responseId) {
                $response=AttendanceMeetingResponse::query()->where('attendance_session_id',$sessionId)->findOrFail($responseId);
                if ($response->respondent_type==='person' || $response->person_id) self::fail('This response is already linked to People. Use its existing participant action.');
                $type=$response->respondent_type;
                if (!in_array($type,['guest','campus','gospel'],true)) self::fail('Unsupported response identity.');
                $source=match($type) {'campus'=>$response->campus_contact_id,'gospel'=>$response->gospel_contact_id,default=>$response->id};
                if (!$source) self::fail('The response has no usable source identity.');
                $mapped=DB::table('attendance_guest_responses')->where('attendance_meeting_response_id',$responseId)->value('attendance_guest_id');
                $known=DB::table('attendance_guests')->where('attendance_sheet_id',$sheetId)->where('source_type',$type)->where('source_id',$source)->first();
                if ($mapped) $known=DB::table('attendance_guests')->find($mapped);
                if ($known && $known->linked_person_id) self::fail('This attendee is already linked to People.');
                if ($guest && $known && (int)$guest->id!==(int)$known->id) self::fail('This response already belongs to another attendee.');
                $guest ??= $known;
                $contact=match($type) {'campus'=>$response->campusContact,'gospel'=>$response->gospelContact,default=>null};
                if ($contact?->person_id) self::fail('This contact is linked to People. Review the response identity first.');
                $profile=$response->guest_profile ?? [];
                $name=trim((string)($response->respondent_name ?: $response->guest_name ?: $contact?->display_name));
                $locality=trim((string)($contact?->locality ?: ($profile['locality'] ?? '')));
            }
            if (!$guest) {
                $name=trim($name); $locality=trim($locality);
                if ($name==='' || mb_strlen($name)>255 || mb_strlen($locality)>150) self::fail('Enter a name up to 255 characters and locality up to 150 characters.');
                $id=DB::table('attendance_guests')->insertGetId(['attendance_sheet_id'=>$sheetId,'source_type'=>$type,'source_id'=>$source,
                    'name'=>$name,'locality'=>$locality ?: null,'created_at'=>now(),'updated_at'=>now()]);
                $guest=DB::table('attendance_guests')->find($id);
            }
            if ($response) DB::table('attendance_guest_responses')->updateOrInsert(['attendance_meeting_response_id'=>$response->id],['attendance_guest_id'=>$guest->id]);
            $start=$session->session_date->toDateString(); $end=$scope==='this_meeting' ? $start : null;
            $covered=DB::table('attendance_guest_periods')->where('attendance_guest_id',$guest->id)->where('is_active',true)
                ->where('starts_on','<=',$start)->when($end===null,fn($q)=>$q->whereNull('ends_on'),fn($q)=>$q->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',$end)))->exists();
            if (!$covered) DB::table('attendance_guest_periods')->insert(['attendance_guest_id'=>$guest->id,'starts_on'=>$start,'ends_on'=>$end,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            return (int)$guest->id;
        });
    }
    public static function mark(int $sheetId,int $sessionId,int $guestId,string $status): void
    {
        DB::transaction(function () use ($sheetId,$sessionId,$guestId,$status): void {
            self::authorize();
            DB::table('attendance_sheets')->where('id',$sheetId)->lockForUpdate()->first();
            $session=self::meeting($sheetId,$sessionId,true);
            if (!in_array($status,['present','late','absent','excused'],true)) self::fail('Choose a valid attendance status.');
            abort_unless(self::activeIds($sheetId,$session->session_date->toDateString())->contains($guestId),403);
            DB::table('attendance_guest_records')->updateOrInsert(['attendance_guest_id'=>$guestId,'attendance_session_id'=>$sessionId],
                ['status'=>$status,'marked_by_id'=>auth()->id(),'marked_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        });
    }
    public static function endPeriod(int $sheetId,int $periodId): void
    {
        DB::transaction(function() use($sheetId,$periodId): void {
            self::authorize(); DB::table('attendance_sheets')->where('id',$sheetId)->lockForUpdate()->first(); self::sheet($sheetId,true);
            $row=DB::table('attendance_guest_periods as p')->join('attendance_guests as g','g.id','=','p.attendance_guest_id')
                ->where('g.attendance_sheet_id',$sheetId)->whereNull('g.linked_person_id')->where('p.id',$periodId)->first(['p.id']) ?? abort(404);
            DB::table('attendance_guest_periods')->where('id',$row->id)->update(['is_active'=>false,'updated_at'=>now()]);
        });
    }
    public static function reportSessions(int $sheetId,?string $from=null,?string $to=null,?int $day=null): Collection
    {
        return AttendanceSession::query()->where('attendance_sheet_id',$sheetId)->where('is_no_meeting',false)
            ->when($from,fn($q)=>$q->whereDate('session_date','>=',$from))->when($to,fn($q)=>$q->whereDate('session_date','<=',$to))
            ->orderBy('session_date')->get()->filter(fn($s)=>$day===null || $s->session_date->dayOfWeek===$day);
    }
    public static function reportRows(int $sheetId,Collection $sessions): Collection
    {
        $guests=self::guests($sheetId); $records=self::records($sheetId,$sessions->pluck('id'));
        $active=$sessions->mapWithKeys(fn($s)=>[$s->id=>self::activeIds($sheetId,$s->session_date->toDateString())]);
        return $guests->map(function($g) use($sessions,$records,$active) {
            $own=$records->where('attendance_guest_id',$g->id);
            $expected=$sessions->filter(fn($s)=>$active->get($s->id)->contains((int)$g->id))->count();
            if (!$expected && $own->isEmpty()) return null;
            $present=$own->whereIn('status',['present','late'])->count(); $absent=$own->whereIn('status',['absent','excused'])->count();
            $marked=$present+$absent;
            return ['guest_id'=>(int)$g->id,'participant'=>null,'person'=>(object)['display_name'=>$g->name.' ('.ucfirst($g->source_type).')','locality'=>$g->locality,'churchProfile'=>null],
                'expected'=>$expected,'present'=>$present,'absent'=>$absent,'marked'=>$marked,'prophesied'=>0,'unmarked'=>max(0,$expected-$marked),
                'rate'=>$marked ? round(100*$present/$marked,1) : 0];
        })->filter()->values();
    }
    public static function peopleReportRows(int $sheetId,Collection $sessions,?string $category=null): Collection
    {
        self::sheet($sheetId);
        $periods=AttendanceParticipant::query()->where('attendance_sheet_id',$sheetId)->get();
        $records=AttendanceRecord::query()->whereIn('attendance_session_id',$sessions->pluck('id'))->get();
        $ids=collect($periods->pluck('person_id')->all())->merge($records->pluck('person_id')->all())->unique();
        $people=Person::query()->with('churchProfile')->whereIn('id',$ids)
            ->when(filled($category),fn($q)=>$q->whereHas('churchProfile',fn($q)=>$q->where('category',$category)))->get();
        return $people->map(function($p) use($periods,$records,$sessions) {
            $ownPeriods=$periods->where('person_id',$p->id); $ownRecords=$records->where('person_id',$p->id);
            $expected=$sessions->filter(function($s) use($ownPeriods) {
                $date=$s->session_date->toDateString();
                return $ownPeriods->contains(fn($period)=>$period->is_active && (!$period->starts_on || $period->starts_on->toDateString()<=$date) && (!$period->ends_on || $period->ends_on->toDateString()>=$date));
            })->count();
            if (!$expected && $ownRecords->isEmpty()) return null;
            $present=$ownRecords->whereIn('status',['present','late'])->count(); $absent=$ownRecords->whereIn('status',['absent','excused'])->count(); $marked=$present+$absent;
            return ['participant'=>$ownPeriods->first(),'person'=>$p,'expected'=>$expected,'present'=>$present,'absent'=>$absent,'marked'=>$marked,
                'prophesied'=>0,'unmarked'=>max(0,$expected-$marked),'rate'=>$marked ? round(100*$present/$marked,1) : 0];
        })->filter()->values();
    }
    public static function meetingCounts(AttendanceSession $s): array
    {
        if ($s->is_no_meeting) return ['expected'=>0,'present'=>0,'absent'=>0,'marked'=>0,'unmarked'=>0];
        $expected=self::activeIds($s->attendance_sheet_id,$s->session_date->toDateString())->count();
        $records=self::records($s->attendance_sheet_id,collect([$s->id]));
        $present=$records->whereIn('status',['present','late'])->count(); $absent=$records->whereIn('status',['absent','excused'])->count();
        return ['expected'=>$expected,'present'=>$present,'absent'=>$absent,'marked'=>$records->count(),'unmarked'=>max(0,$expected-$records->count())];
    }
    public static function addMeetingCounts(array $row): array
    {
        $s=$row['session']; if ($s->sheet->sheet_type!=='custom') return $row;
        $extra=self::meetingCounts($s);
        foreach (['expected','active_participants','present','absent','marked','unmarked'] as $key) {
            if (array_key_exists($key,$row)) $row[$key]+=$extra[$key==='active_participants' ? 'expected' : $key];
        }
        $denominator=$row['expected'] ?? $row['active_participants'] ?? 0;
        $row['rate']=$denominator ? round(100*$row['present']/$denominator,1) : 0;
        return $row;
    }
    public static function guardOldPromotion(AttendanceMeetingResponse $response): void
    {
        self::authorize();
        if (!\Illuminate\Support\Facades\Schema::hasTable('attendance_guests')) return;
        if (DB::table('attendance_guest_responses as r')->join('attendance_guests as g','g.id','=','r.attendance_guest_id')
            ->where('r.attendance_meeting_response_id',$response->id)->whereNull('g.linked_person_id')->exists()) {
            self::fail('This response has guest attendance. Use Link to People in Enrolled Attendees (All enrollment history) so its attendance and team assignments are preserved.');
        }
    }
    public static function linkPerson(int $sheetId,int $guestId,int $personId): void
    {
        DB::transaction(function () use($sheetId,$guestId,$personId): void {
            self::authorize(); DB::table('attendance_sheets')->where('id',$sheetId)->lockForUpdate()->first(); self::sheet($sheetId,true);
            $g=self::guests($sheetId)->get($guestId) ?? abort(404);
            Person::query()->findOrFail($personId);
            $records=DB::table('attendance_guest_records')->where('attendance_guest_id',$guestId)->get();
            foreach ($records as $r) {
                $existing=AttendanceRecord::query()->where('attendance_session_id',$r->attendance_session_id)->where('person_id',$personId)->first();
                if ($existing && $existing->status!==$r->status) self::fail('Attendance conflicts on session #'.$r->attendance_session_id.'. Review both attendance records before linking.');
            }
            GuestConference::checkLink($sheetId,$guestId,$personId);
            foreach (DB::table('attendance_guest_periods')->where('attendance_guest_id',$guestId)->where('is_active',true)->get() as $p) {
                AttendanceParticipant::query()->firstOrCreate(['attendance_sheet_id'=>$sheetId,'person_id'=>$personId,'starts_on'=>$p->starts_on,'ends_on'=>$p->ends_on,'is_active'=>true]);
            }
            foreach ($records as $r) AttendanceRecord::query()->firstOrCreate(['attendance_session_id'=>$r->attendance_session_id,'person_id'=>$personId],
                ['status'=>$r->status,'is_present'=>in_array($r->status,['present','late'],true),'attendance_source'=>'manual','marked_by_id'=>$r->marked_by_id,'marked_at'=>$r->marked_at]);
            GuestConference::applyLink($sheetId,$guestId,$personId);
            // Explicit user selection is the identity decision; never match names automatically.
            $responseIds=DB::table('attendance_guest_responses')->where('attendance_guest_id',$guestId)->pluck('attendance_meeting_response_id');
            AttendanceMeetingResponse::query()->whereIn('id',$responseIds)->update(['respondent_type'=>'person','person_id'=>$personId]);
            DB::table('attendance_guests')->where('id',$guestId)->update(['linked_person_id'=>$personId,'updated_at'=>now()]);
        });
    }
}
