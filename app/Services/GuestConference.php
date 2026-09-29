<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GuestConference
{
    public static function extend(array $data): array
    {
        $eventId=(int)$data['event']->id; $sheetId=(int)$data['sheet']->id;
        $guests=GuestAttendance::guests($sheetId); $sessions=$data['scope'];
        $records=GuestAttendance::records($sheetId,$sessions->pluck('id'));
        $roster=collect(); foreach ($sessions as $s) $roster=$roster->merge(GuestAttendance::activeIds($sheetId,$s->session_date->toDateString()));
        $roster=$roster->unique();
        $maps=DB::table('attendance_guest_responses')->whereIn('attendance_meeting_response_id',$data['responses']->pluck('id'))->get();
        $details=DB::table('conference_guest_details')->where('conference_event_id',$eventId)->get()->keyBy('attendance_guest_id');
        foreach ($guests as $g) {
            $own=$records->where('attendance_guest_id',$g->id);
            $responses=$data['responses']->whereIn('id',$maps->where('attendance_guest_id',$g->id)->pluck('attendance_meeting_response_id'))->values();
            if (!$roster->contains((int)$g->id) && $own->isEmpty() && $responses->isEmpty()) continue;
            $detail=$details->get($g->id); $key=-(int)$g->id;
            // Negative UI keys have a separate namespace and are never written as Person IDs.
            $data['rows']->put($key,['id'=>$key,'name'=>$g->name.' ('.ucfirst($g->source_type).')','locality'=>$g->locality ?: 'No locality',
                'roster'=>$roster->contains((int)$g->id),'attended'=>$own->whereIn('status',['present','late'])->isNotEmpty(),'recorded'=>$own->isNotEmpty(),
                'role'=>$detail?->event_role ?? '', 'team_id'=>$detail?->conference_team_id,'team'=>$data['teams']->get($detail?->conference_team_id),
                'responses'=>$responses,'invites'=>[]]);
        }
        $invites=self::invitations($eventId);
        $data['invitations']=$invites;
        $data['rows']=$data['rows']->map(function($row) use($invites) {
            $row['invites']=$invites->where('inviter_person_id',$row['id'])->pluck('invitee_person_id')->map(fn($v)=>(int)$v)->all(); return $row;
        })->sortBy('name',SORT_NATURAL|SORT_FLAG_CASE);
        $data['totals']['roster']=$data['rows']->where('roster',true)->count();
        $data['totals']['attended']=$data['rows']->where('attended',true)->count();
        return $data;
    }
    public static function invitations(int $eventId)
    {
        $old=DB::table('conference_invitations')->where('conference_event_id',$eventId)->get();
        $new=DB::table('conference_attendee_invitations')->where('conference_event_id',$eventId)->get()->map(fn($r)=>(object)[
            'id'=>-(int)$r->id,'inviter_person_id'=>(int)$r->inviter_key,'invitee_person_id'=>(int)$r->invitee_key]);
        return collect($old->all())->concat($new);
    }
    public static function save(int $eventId,int $key,?int $teamId,string $role): void
    {
        ConferenceWorkspace::event($eventId);
        DB::transaction(function() use($eventId,$key,$teamId,$role): void {
            DB::table('conference_events')->where('id',$eventId)->lockForUpdate()->first();
            $data=ConferenceWorkspace::data($eventId);
            abort_unless($key<0 && $data['rows']->has($key),404);
            if ($teamId && !$data['teams']->has($teamId)) abort(422);
            abort_unless(in_array($role,['','young_people','serving_one','other'],true),422);
            DB::table('conference_guest_details')->updateOrInsert(['conference_event_id'=>$eventId,'attendance_guest_id'=>-$key],
                ['conference_team_id'=>$teamId,'event_role'=>$role ?: null,'updated_at'=>now()]);
        });
    }
    public static function invite(int $eventId,int $inviter,int $invitee): void
    {
        ConferenceWorkspace::event($eventId);
        DB::transaction(function() use($eventId,$inviter,$invitee) {
            DB::table('conference_events')->where('id',$eventId)->lockForUpdate()->first();
            $data=ConferenceWorkspace::data($eventId);
            if ($inviter===$invitee || !$data['rows']->has($inviter) || !$data['rows']->has($invitee)) {
                throw ValidationException::withMessages(['inviteeId'=>'Choose two different attendees in this conference.']);
            }
            // All new edits use the mixed-identity table, including Person-to-Person invitations.
            if ($invitee>0) DB::table('conference_invitations')->where('conference_event_id',$eventId)->where('invitee_person_id',$invitee)->delete();
            DB::table('conference_attendee_invitations')->updateOrInsert(['conference_event_id'=>$eventId,'invitee_key'=>$invitee],['inviter_key'=>$inviter,'updated_at'=>now()]);
        });
    }
    public static function remove(int $eventId,int $id): void
    {
        ConferenceWorkspace::event($eventId);
        DB::table($id<0 ? 'conference_attendee_invitations' : 'conference_invitations')->where('conference_event_id',$eventId)->where('id',abs($id))->delete();
    }
    public static function checkLink(int $sheetId,int $guestId,int $personId): void
    {
        foreach (DB::table('conference_events')->where('attendance_sheet_id',$sheetId)->get() as $event) {
            DB::table('conference_events')->where('id',$event->id)->lockForUpdate()->first();
            $g=DB::table('conference_guest_details')->where('conference_event_id',$event->id)->where('attendance_guest_id',$guestId)->first();
            $p=DB::table('conference_person_details')->where('conference_event_id',$event->id)->where('person_id',$personId)->first();
            if ($g && $p && (($g->conference_team_id && $p->conference_team_id && $g->conference_team_id!=$p->conference_team_id)
                || ($g->event_role && $p->event_role && $g->event_role!==$p->event_role))) {
                throw ValidationException::withMessages(['guestAttendance'=>'Team or event role conflicts. Review the assignments before linking.']);
            }
            $seen=[];
            foreach (self::invitations($event->id) as $i) {
                $from=(int)$i->inviter_person_id; $to=(int)$i->invitee_person_id;
                if ($from===-$guestId) $from=$personId; if ($to===-$guestId) $to=$personId;
                if ($from===$to || (isset($seen[$to]) && $seen[$to]!==$from)) throw ValidationException::withMessages(['guestAttendance'=>'Invitation links conflict. Review or remove the conflicting invitation before linking.']);
                $seen[$to]=$from;
            }
        }
    }
    public static function applyLink(int $sheetId,int $guestId,int $personId): void
    {
        foreach (DB::table('conference_events')->where('attendance_sheet_id',$sheetId)->get() as $event) {
            $g=DB::table('conference_guest_details')->where('conference_event_id',$event->id)->where('attendance_guest_id',$guestId)->first();
            $p=DB::table('conference_person_details')->where('conference_event_id',$event->id)->where('person_id',$personId)->first();
            if ($g) DB::table('conference_person_details')->updateOrInsert(['conference_event_id'=>$event->id,'person_id'=>$personId],
                ['conference_team_id'=>$p?->conference_team_id ?: $g->conference_team_id,'event_role'=>$p?->event_role ?: $g->event_role,'updated_at'=>now()]);
            $links=self::invitations($event->id)->map(fn($i)=>[
                'from'=>(int)$i->inviter_person_id===-$guestId ? $personId : (int)$i->inviter_person_id,
                'to'=>(int)$i->invitee_person_id===-$guestId ? $personId : (int)$i->invitee_person_id]);
            DB::table('conference_invitations')->where('conference_event_id',$event->id)->delete();
            DB::table('conference_attendee_invitations')->where('conference_event_id',$event->id)->delete();
            foreach ($links->unique('to') as $link) DB::table('conference_attendee_invitations')->insert([
                'conference_event_id'=>$event->id,'inviter_key'=>$link['from'],'invitee_key'=>$link['to'],'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}
