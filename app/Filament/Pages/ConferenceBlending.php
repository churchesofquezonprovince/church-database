<?php

namespace App\Filament\Pages;

use App\Models\{AttendanceSheet, AttendanceSession};
use App\Services\ConferenceWorkspace as Workspace;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

class ConferenceBlending extends Page
{
    protected string $view = 'filament.pages.conference-blending';
    #[Url] public ?int $eventId = null;
    #[Url] public ?int $sessionId = null;
    public ?int $setupSheetId = null;
    public array $setupSessionIds = [];
    public string $activityType = 'YP Blending';
    public string $search = '';
    public string $localityFilter = '';
    public string $attendanceFilter = '';
    public string $teamFilter = '';
    public string $roleFilter = '';
    public string $responseFilter = '';
    public ?int $questionFilter = null;
    public string $answerFilter = '';
    public int $pageNumber = 1;
    public ?int $editPersonId = null;
    public ?int $personTeamId = null;
    public string $personRole = '';
    public ?int $teamEditId = null;
    public string $teamName = '';
    public string $teamColor = '#3b82f6';
    public ?int $inviterId = null;
    public ?int $inviteeId = null;
    public ?int $detailPersonId = null;

    public static function getNavigationGroup(): ?string { return 'Attendance'; }
    public static function getNavigationSort(): ?int { return 45; }
    public static function getNavigationLabel(): string { return 'Conference & Blending'; }
    public static function getNavigationIcon(): ?string { return 'heroicon-o-user-group'; }
    public function getTitle(): string { return 'Conference & Blending'; }
    public static function canAccess(): bool { return auth()->user()?->canManageRecords() ?? false; }
    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }
    public function mount(): void { Workspace::authorize(); }
    public function updatedSetupSheetId(): void { $this->setupSessionIds = []; }
    public function updatedEventId(): void
    {
        $this->reset('sessionId', 'search', 'localityFilter', 'attendanceFilter', 'teamFilter', 'roleFilter', 'responseFilter', 'questionFilter', 'answerFilter', 'editPersonId', 'teamEditId', 'teamName', 'inviterId', 'inviteeId', 'detailPersonId', 'pageNumber');
        $this->resetValidation();
    }
    public function updatedSessionId(): void { $this->reset('pageNumber', 'detailPersonId', 'editPersonId'); }
    public function updatedQuestionFilter(): void { $this->answerFilter = ''; $this->pageNumber = 1; }
    public function updated(string $property): void
    {
        if ($property === 'search' || str_ends_with($property, 'Filter')) $this->pageNumber = 1;
    }
    private function currentId(): int { Workspace::authorize(); abort_unless($this->eventId, 404); return $this->eventId; }
    private function success(string $title): void { Notification::make()->title($title)->success()->send(); }
    public function createConference(): void
    {
        Workspace::authorize();
        $this->validate(['setupSheetId' => 'required|integer', 'setupSessionIds' => 'required|array|min:1',
            'setupSessionIds.*' => 'integer', 'activityType' => 'required|string|max:100']);
        $id = Workspace::create($this->setupSheetId, $this->setupSessionIds, $this->activityType);
        $this->updatedEventId(); $this->eventId = $id;
        $this->success('Conference linked to the attendance sheet');
    }
    public function editPerson(int $id): void
    {
        $row = Workspace::data($this->currentId())['rows']->get($id) ?? abort(404);
        $this->editPersonId = $id; $this->personTeamId = $row['team_id']; $this->personRole = $row['role'];
    }
    public function savePerson(): void
    {
        abort_unless($this->editPersonId, 422);
        Workspace::savePerson($this->currentId(), $this->editPersonId, $this->personTeamId, $this->personRole);
        $this->editPersonId = null; $this->success('Team and event role saved');
    }
    public function editTeam(int $id): void
    {
        $team = DB::table('conference_teams')->where('conference_event_id', $this->currentId())->where('id', $id)->first() ?? abort(404);
        $this->teamEditId = $id; $this->teamName = $team->name; $this->teamColor = $team->color;
    }
    public function cancelTeam(): void { $this->reset('teamEditId', 'teamName', 'teamColor'); }
    public function saveTeam(): void
    {
        $eventId = $this->currentId(); Workspace::event($eventId);
        $this->teamName = trim($this->teamName);
        $this->validate(['teamName' => 'required|string|max:100', 'teamColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']]);
        DB::transaction(function () use ($eventId): void {
            DB::table('conference_events')->where('id', $eventId)->lockForUpdate()->first();
            $duplicate = DB::table('conference_teams')->where('conference_event_id', $eventId)->where('name', $this->teamName);
            if ($this->teamEditId) $duplicate->where('id', '!=', $this->teamEditId);
            if ($duplicate->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['teamName' => 'A team with this name already exists.']);
            $values = ['name' => $this->teamName, 'color' => strtolower($this->teamColor), 'updated_at' => now()];
            if ($this->teamEditId) {
                $query = DB::table('conference_teams')->where('conference_event_id', $eventId)->where('id', $this->teamEditId);
                abort_unless($query->exists(), 404); $query->update($values);
            } else {
                DB::table('conference_teams')->insert($values + ['conference_event_id' => $eventId, 'created_at' => now()]);
            }
        });
        $this->cancelTeam(); $this->success('Team saved');
    }
    public function saveInvitation(): void
    {
        $this->validate(['inviterId' => 'required|integer', 'inviteeId' => 'required|integer']);
        Workspace::invite($this->currentId(), $this->inviterId, $this->inviteeId);
        $this->success('Invitation link saved');
    }
    public function removeInvitation(int $id): void
    {
        \App\Services\GuestConference::remove($this->currentId(), $id);
        $this->success('Invitation link removed');
    }
    public function showPerson(int $id): void
    {
        abort_unless(Workspace::data($this->currentId())['rows']->has($id), 404);
        $this->detailPersonId = $id;
    }
    public function closePerson(): void { $this->detailPersonId = null; }
    public function changePage(int $page): void { Workspace::authorize(); $this->pageNumber = max(1, $page); }

    protected function getViewData(): array
    {
        Workspace::authorize();
        $events = DB::table('conference_events as e')->join('attendance_sheets as s', 's.id', '=', 'e.attendance_sheet_id')
            ->orderByDesc('e.id')->get(['e.id', 'e.activity_type', 's.title']);
        $sheets = AttendanceSheet::query()->where('sheet_type', 'custom')->where('is_active', true)
            ->whereNotIn('id', DB::table('conference_events')->select('attendance_sheet_id'))->orderByDesc('start_date')->get();
        $setupSessions = $sheets->contains('id', $this->setupSheetId)
            ? AttendanceSession::query()->where('attendance_sheet_id', $this->setupSheetId)->where('is_no_meeting', false)->orderBy('session_date')->get() : collect();
        $data = $this->eventId ? Workspace::data($this->eventId, $this->sessionId) : null;
        $all = $data ? ($this->sessionId ? Workspace::data($this->eventId) : $data) : null;
        $questions = $data ? $data['sheet']->meetingFormQuestions()->whereIn('question_type', ['checkboxes', 'dropdown', 'multiple_choice'])->get() : collect();
        $question = $questions->firstWhere('id', $this->questionFilter);
        $answerOptions = collect($question?->options ?? [])->filter(fn ($v) => is_scalar($v))->map(fn ($v) => (string) $v);
        if ($question && $data) {
            $answerOptions = $answerOptions->merge($data['responses']->flatMap(fn ($r) => $r->formAnswers->where('attendance_meeting_form_question_id', $question->id)
                ->flatMap(fn ($a) => Workspace::answerValues($a))))->unique()->values();
        }
        $rows = $data ? $data['rows'] : collect();
        $localities = $rows->pluck('locality')->unique()->sort()->values();
        $rows = $rows->filter(function ($row) use ($question): bool {
            return ($this->search === '' || str_contains(mb_strtolower($row['name']), mb_strtolower(trim($this->search))))
                && ($this->localityFilter === '' || $row['locality'] === $this->localityFilter)
                && ($this->roleFilter === '' || ($this->roleFilter === 'unassigned' ? $row['role'] === '' : $row['role'] === $this->roleFilter))
                && ($this->teamFilter === '' || ($this->teamFilter === 'unassigned' ? ! $row['team_id'] : (string) $row['team_id'] === $this->teamFilter))
                && ($this->attendanceFilter === '' || match ($this->attendanceFilter) {
                    'attended' => $row['attended'], 'not_attended' => ! $row['attended'], 'unmarked' => ! $row['recorded'], 'roster' => $row['roster'], default => false,
                })
                && (! $question || $this->answerFilter === '' || Workspace::matchesAnswer($row['responses'], $question->id, $this->answerFilter));
        });
        $rowCount = $rows->count(); $lastPage = max(1, (int) ceil($rowCount / 30));
        $currentPage = min(max(1, $this->pageNumber), $lastPage);
        $rows = $rows->slice(($currentPage - 1) * 30, 30);
        $responses = $data ? $data['responses']->filter(function ($r) use ($data, $question): bool {
            return ($this->responseFilter === '' || ($this->responseFilter === 'review'
                ? ($data['workflow']->get($r->id)['needs_action'] ?? false) : $r->response === $this->responseFilter))
                && (! $question || $this->answerFilter === '' || Workspace::matchesAnswer(collect([$r]), $question->id, $this->answerFilter));
        }) : collect();
        return compact('events', 'sheets', 'setupSessions', 'data', 'all', 'questions', 'answerOptions', 'localities', 'rows', 'rowCount', 'currentPage', 'lastPage', 'responses');
    }
}
