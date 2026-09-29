<?php

namespace App\Livewire;

use App\Models\{AttendanceMeetingResponse, AttendanceSession, Person};
use App\Services\GuestAttendance as G;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GuestAttendancePanel extends Component
{
    #[Locked] public int $sheetId;
    #[Locked] public int $sessionId;
    #[Locked] public string $mode='enroll';
    public ?int $responseId=null;
    public ?int $existingId=null;
    public string $name='';
    public string $locality='';
    public string $scope='this_meeting';
    public string $search='';
    public ?int $linkGuestId=null;
    public ?int $linkPersonId=null;
    public string $personSearch='';
    public bool $showGrid=false;
    public function mount(int $sheetId,int $sessionId,string $mode='enroll'): void
    {
        G::meeting($sheetId,$sessionId); $this->sheetId=$sheetId; $this->sessionId=$sessionId;
        abort_unless(in_array($mode,['enroll','check'],true),404); $this->mode=$mode;
    }
    private function success(string $message): void { Notification::make()->title($message)->success()->send(); $this->dispatch('guest-attendance-updated'); }
    public function enroll(): void
    {
        abort_unless($this->mode==='enroll',403);
        G::enroll($this->sheetId,$this->sessionId,$this->scope,$this->responseId,$this->existingId,$this->name,$this->locality);
        $this->reset('responseId','existingId','name','locality'); $this->success('Attendee enrolled. Attendance has not been marked.');
    }
    public function mark(int $guestId,string $status,?int $sessionId=null): void
    {
        abort_unless($this->mode==='check',403);
        G::mark($this->sheetId,$sessionId ?? $this->sessionId,$guestId,$status);
        $this->success('Attendance saved');
    }
    public function deactivate(int $periodId): void
    {
        abort_unless($this->mode==='enroll',403); G::endPeriod($this->sheetId,$periodId); $this->success('Enrollment period deactivated. Attendance history kept.');
    }
    public function updatedPersonSearch(): void { $this->linkPersonId=null; }
    public function link(): void
    {
        abort_unless($this->mode==='enroll',403);
        $this->validate(['linkGuestId'=>'required|integer|min:1','linkPersonId'=>'required|integer|min:1']);
        G::linkPerson($this->sheetId,$this->linkGuestId,$this->linkPersonId);
        $this->reset('linkGuestId','linkPersonId','personSearch');
        $this->success('Linked to People. Attendance and assignments preserved. ');
    }
    public function render()
    {
        $session=G::meeting($this->sheetId,$this->sessionId); $sheet=$session->sheet;
        $guests=G::guests($this->sheetId); $allGuests=$guests;
        $active=G::activeIds($this->sheetId,$session->session_date->toDateString());
        if ($this->mode==='check' && !$this->showGrid) $guests=$guests->whereIn('id',$active);
        if (trim($this->search)!=='') $guests=$guests->filter(fn($g)=>str_contains(mb_strtolower($g->name),mb_strtolower(trim($this->search))));
        $sessions=$this->showGrid ? AttendanceSession::query()->where('attendance_sheet_id',$this->sheetId)->orderByDesc('session_date')->limit(14)->get()->reverse()->values() : collect([$session]);
        $records=G::records($this->sheetId,$sessions->pluck('id'))->keyBy(fn($r)=>$r->attendance_session_id.':'.$r->attendance_guest_id);
        $rosters=$sessions->mapWithKeys(fn($s)=>[$s->id=>G::activeIds($this->sheetId,$s->session_date->toDateString())]);
        $responses=AttendanceMeetingResponse::query()->where('attendance_session_id',$this->sessionId)->whereIn('respondent_type',['guest','campus','gospel'])
            ->orderBy('respondent_name')->get();
        $periods=DB::table('attendance_guest_periods')->whereIn('attendance_guest_id',$guests->keys())->where('is_active',true)->orderBy('starts_on')->get();
        $people=trim($this->personSearch)==='' ? collect() : Person::query()->where(fn($q)=>$q->where('firstname','like','%'.trim($this->personSearch).'%')->orWhere('lastname','like','%'.trim($this->personSearch).'%'))
            ->orderBy('lastname')->limit(30)->get();
        return view('livewire.guest-attendance-panel',compact('session','sheet','guests','allGuests','active','sessions','records','rosters','responses','periods','people'));
    }
}
