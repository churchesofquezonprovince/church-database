<?php

namespace App\Services;

use App\Models\{AttendanceMeetingResponse, AttendanceParticipant, AttendanceRecord, AttendanceSession, AttendanceSheet};
use App\Support\MeetingResponseAttentionWorkflow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConferenceWorkspace
{
    public static function authorize(): void
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);
    }

    public static function event(int $id): object
    {
        self::authorize();
        return DB::table('conference_events')->where('id', $id)->first() ?? abort(404);
    }

    public static function create(int $sheetId, array $sessionIds, string $type): int
    {
        self::authorize();
        $sheet = AttendanceSheet::query()->where('sheet_type', 'custom')->where('is_active', true)->findOrFail($sheetId);
        $ids = array_values(array_unique(array_map('intval', $sessionIds)));
        $type = trim($type);
        if ($ids === [] || $type === '' || mb_strlen($type) > 100
            || $sheet->sessions()->whereIn('id', $ids)->where('is_no_meeting', false)->count() !== count($ids)) {
            throw ValidationException::withMessages(['setupSessionIds' => 'Choose meeting dates from this attendance sheet and enter an activity type.']);
        }
        return DB::transaction(function () use ($sheetId, $ids, $type): int {
            // Serialize event creation for the same sheet.
            DB::table('attendance_sheets')->where('id', $sheetId)->lockForUpdate()->first();
            if (DB::table('conference_events')->where('attendance_sheet_id', $sheetId)->exists()) {
                throw ValidationException::withMessages(['setupSheetId' => 'This sheet already has a conference. Open it from the list.']);
            }
            $id = DB::table('conference_events')->insertGetId([
                'attendance_sheet_id' => $sheetId, 'activity_type' => $type,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($ids as $sessionId) {
                DB::table('conference_sessions')->insert(['conference_event_id' => $id, 'attendance_session_id' => $sessionId]);
            }
            return $id;
        });
    }

    public static function sessions(object $event): Collection
    {
        return AttendanceSession::query()->where('attendance_sheet_id', $event->attendance_sheet_id)
            ->whereIn('id', DB::table('conference_sessions')->where('conference_event_id', $event->id)->select('attendance_session_id'))
            ->where('is_no_meeting', false)->orderBy('session_date')->orderBy('id')->get();
    }

    public static function data(int $eventId, ?int $sessionId = null): array
    {
        $event = self::event($eventId);
        $sheet = AttendanceSheet::findOrFail($event->attendance_sheet_id);
        $sessions = self::sessions($event);
        if ($sessionId && ! $sessions->contains('id', $sessionId)) abort(404);
        $scope = $sessionId ? $sessions->where('id', $sessionId) : $sessions;
        $ids = $scope->pluck('id');
        $roster = collect();
        foreach ($scope as $session) {
            $roster = $roster->merge(AttendanceParticipant::query()->where('attendance_sheet_id', $sheet->id)
                ->activeOn($session->session_date->format('Y-m-d'))->pluck('person_id')->all());
        }
        $roster = $roster->map(fn ($id) => (int) $id)->unique()->values();
        $records = AttendanceRecord::query()->whereIn('attendance_session_id', $ids)->get();
        $responses = AttendanceMeetingResponse::query()->with(['formAnswers.question', 'campusContact', 'gospelContact'])
            ->whereIn('attendance_session_id', $ids)->orderByDesc('responded_at')->orderByDesc('id')->get();
        $workflow = collect();
        foreach ($scope as $session) {
            $workflow = $workflow->union(MeetingResponseAttentionWorkflow::statuses(
                $responses->where('attendance_session_id', $session->id), $session));
        }
        $personIds = $roster->merge($records->pluck('person_id')->all())
            ->merge($responses->where('respondent_type', 'person')->pluck('person_id')->filter()->all())->unique();
        // Query only basic identity columns; do not invoke profile accessors.
        $people = DB::table('persons')->whereIn('id', $personIds)->get(['id', 'firstname', 'middlename', 'lastname', 'suffix', 'locality'])
            ->keyBy('id');
        $details = DB::table('conference_person_details')->where('conference_event_id', $eventId)->get()->keyBy('person_id');
        $teams = DB::table('conference_teams')->where('conference_event_id', $eventId)->orderBy('name')->get()->keyBy('id');
        $invitations = DB::table('conference_invitations')->where('conference_event_id', $eventId)->get();
        $attended = $records->filter(fn ($r) => in_array($r->status, ['present', 'late'], true))->pluck('person_id')->unique();
        $rows = $people->map(function ($p) use ($roster, $records, $details, $responses, $invitations, $teams): array {
            $detail = $details->get($p->id);
            $ownRecords = $records->where('person_id', $p->id);
            return [
                'id' => (int) $p->id, 'name' => self::name($p), 'locality' => $p->locality ?: 'No locality',
                'roster' => $roster->contains((int) $p->id),
                'attended' => $ownRecords->contains(fn ($r) => in_array($r->status, ['present', 'late'], true)),
                'recorded' => $ownRecords->isNotEmpty(),
                'role' => $detail?->event_role ?? '', 'team_id' => $detail?->conference_team_id,
                'team' => $teams->get($detail?->conference_team_id),
                'responses' => $responses->where('respondent_type', 'person')->where('person_id', $p->id)->values(),
                'invites' => $invitations->where('inviter_person_id', $p->id)->pluck('invitee_person_id')->all(),
            ];
        })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        $result = compact('event', 'sheet', 'sessions', 'scope', 'roster', 'records', 'responses', 'workflow', 'teams', 'rows', 'invitations') + [
            'totals' => ['roster' => $roster->count(), 'attended' => $attended->count(),
                'responses' => $responses->count(), 'yes' => $responses->where('response', 'yes')->count(),
                'review' => $workflow->filter(fn ($w) => $w['needs_action'])->count()],
        ];
        return \Illuminate\Support\Facades\Schema::hasTable('attendance_guests') ? GuestConference::extend($result) : $result;
    }

    public static function name(object $person): string
    {
        return trim($person->lastname . ', ' . $person->firstname . ' ' . ($person->middlename ?? '') . ' ' . ($person->suffix ?? ''));
    }

    public static function answerValues(object $answer): array
    {
        $json = $answer->answer_json;
        if (is_array($json)) {
            return collect($json)->filter(fn ($v) => is_scalar($v))->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->values()->all();
        }
        $value = trim((string) $answer->answer_text);
        return $value === '' ? [] : [$value];
    }

    public static function matchesAnswer(Collection $responses, int $questionId, string $option): bool
    {
        $values = $responses->flatMap(fn ($r) => $r->formAnswers->where('attendance_meeting_form_question_id', $questionId)
            ->flatMap(fn ($a) => self::answerValues($a)));
        return $option === '__unanswered' ? $values->isEmpty() : $values->containsStrict($option);
    }

    public static function savePerson(int $eventId, int $personId, ?int $teamId, string $role): void
    {
        if ($personId < 0) { GuestConference::save($eventId,$personId,$teamId,$role); return; }
        self::authorize();
        $data = self::data($eventId);
        if (! $data['rows']->has($personId)) abort(404);
        if ($teamId && ! $data['teams']->has($teamId)) abort(422, 'Choose a team in this conference.');
        if (! in_array($role, ['', 'young_people', 'serving_one', 'other'], true)) abort(422);
        DB::table('conference_person_details')->updateOrInsert(
            ['conference_event_id' => $eventId, 'person_id' => $personId],
            ['conference_team_id' => $teamId, 'event_role' => $role ?: null, 'updated_at' => now()]);
    }

    public static function deleteTeam(
        int $eventId,
        int $teamId
    ): int {
        self::authorize();

        self::event(
            $eventId
        );

        return DB::transaction(
            function () use (
                $eventId,
                $teamId
            ): int {
                DB::table(
                    'conference_events'
                )
                    ->where(
                        'id',
                        $eventId
                    )
                    ->lockForUpdate()
                    ->first();

                $team =
                    DB::table(
                        'conference_teams'
                    )
                        ->where(
                            'conference_event_id',
                            $eventId
                        )
                        ->where(
                            'id',
                            $teamId
                        )
                        ->first();

                abort_unless(
                    $team,
                    404
                );

                $unassigned =
                    DB::table(
                        'conference_person_details'
                    )
                        ->where(
                            'conference_event_id',
                            $eventId
                        )
                        ->where(
                            'conference_team_id',
                            $teamId
                        )
                        ->count();

                DB::table(
                    'conference_person_details'
                )
                    ->where(
                        'conference_event_id',
                        $eventId
                    )
                    ->where(
                        'conference_team_id',
                        $teamId
                    )
                    ->update([
                        'conference_team_id' =>
                            null,

                        'updated_at' =>
                            now(),
                    ]);

                if (
                    \Illuminate\Support\Facades\Schema::hasTable(
                        'conference_guest_details'
                    )
                ) {
                    $unassigned +=
                        DB::table(
                            'conference_guest_details'
                        )
                            ->where(
                                'conference_event_id',
                                $eventId
                            )
                            ->where(
                                'conference_team_id',
                                $teamId
                            )
                            ->count();

                    DB::table(
                        'conference_guest_details'
                    )
                        ->where(
                            'conference_event_id',
                            $eventId
                        )
                        ->where(
                            'conference_team_id',
                            $teamId
                        )
                        ->update([
                            'conference_team_id' =>
                                null,

                            'updated_at' =>
                                now(),
                        ]);
                }

                DB::table(
                    'conference_teams'
                )
                    ->where(
                        'conference_event_id',
                        $eventId
                    )
                    ->where(
                        'id',
                        $teamId
                    )
                    ->delete();

                return $unassigned;
            }
        );
    }


    public static function invite(int $eventId, int $inviter, int $invitee): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('conference_attendee_invitations')) { GuestConference::invite($eventId,$inviter,$invitee); return; }
        $data = self::data($eventId);
        if ($inviter === $invitee || ! $data['rows']->has($inviter) || ! $data['rows']->has($invitee)) {
            throw ValidationException::withMessages(['inviteeId' => 'Choose two different people from this conference.']);
        }
        // One recorded inviter per invited person; explicit Save replaces it.
        DB::table('conference_invitations')->updateOrInsert(
            ['conference_event_id' => $eventId, 'invitee_person_id' => $invitee],
            ['inviter_person_id' => $inviter, 'updated_at' => now()]);
    }
}
