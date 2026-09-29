<?php

namespace App\Filament\Pages;

use App\Support\LocalityOptions;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AttendanceDashboard extends Page
{
    protected string $view = 'filament.pages.attendance-dashboard';

    public function getTitle(): string
    {
        return 'Attendance Dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return 'Attendance Dashboard';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-presentation-chart-bar';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function customSheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->where('is_active', true)
            ->withCount('sessions')
            ->withDistinctParticipantCount()
            ->latest()
            ->get();
    }

    public function localities(): Collection
    {
        return LocalityOptions::primaryProvinceNamesWithPeople();
    }

    public function localityLabel(?string $locality): string
    {
        return $locality === '__no_locality'
            ? 'No Locality'
            : (string) $locality;
    }

    public function nextSundayDate(): string
    {
        $today = CarbonImmutable::today();

        return $today->dayOfWeek === 0
            ? $today->toDateString()
            : $today->next(0)->toDateString();
    }

    public function nextTuesdayDate(): string
    {
        $today = CarbonImmutable::today();

        return $today->dayOfWeek === 2
            ? $today->toDateString()
            : $today->next(2)->toDateString();
    }

    public function summary(): array
    {
        return [
            'custom_sheets' => AttendanceSheet::query()
                ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
                ->count(),

            'lords_table_localities' => AttendanceSheet::query()
                ->where('sheet_type', AttendanceSheet::TYPE_LORDS_TABLE)
                ->count(),

            'prayer_meeting_localities' => AttendanceSheet::query()
                ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
                ->count(),

            'total_participants' =>
                (int) AttendanceParticipant::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->selectRaw(
                        'COUNT(DISTINCT attendance_sheet_id, person_id) AS aggregate'
                    )
                    ->value('aggregate') + (int) \Illuminate\Support\Facades\DB::table('attendance_guests as g')->join('attendance_guest_periods as p','p.attendance_guest_id','=','g.id')->whereNull('g.linked_person_id')->where('p.is_active',true)->distinct()->count('g.id'),
        ];
    }

    public function todaysMeetings(): Collection
    {
        return AttendanceSession::query()
            ->with(['sheet'])
            ->whereHas('sheet', fn ($query) => $query->where('is_active', true))
            ->whereDate('session_date', today())
            ->withCount([
                'records as present_count' => fn ($query) => $query->where('is_present', true),
                'records as absent_count' => fn ($query) => $query->where('is_present', false),
                'records as marked_count',
            ])
            ->orderBy('session_date')
            ->get()
            ->map(fn (AttendanceSession $session): array => $this->sessionSummaryRow($session));
    }

    public function upcomingMeetings(): Collection
    {
        return AttendanceSession::query()
            ->with(['sheet'])
            ->whereHas('sheet', fn ($query) => $query->where('is_active', true))
            ->whereDate('session_date', '>=', today())
            ->orderBy('session_date')
            ->take(8)
            ->get();
    }

    public function latestMeetingSummaries(): Collection
    {
        return AttendanceSession::query()
            ->with(['sheet'])
            ->whereHas('sheet', fn ($query) => $query->where('is_active', true))
            ->where(fn($q)=>$q->whereHas('records')->orWhereExists(fn($g)=>$g->selectRaw('1')->from('attendance_guest_records')->whereColumn('attendance_session_id','attendance_sessions.id')))
            ->withCount([
                'records as present_count' => fn ($query) => $query->where('is_present', true),
                'records as absent_count' => fn ($query) => $query->where('is_present', false),
                'records as marked_count',
            ])
            ->orderByDesc('session_date')
            ->take(8)
            ->get()
            ->map(fn (AttendanceSession $session): array => $this->sessionSummaryRow($session));
    }

    public function recentRecords(): Collection
    {
        return AttendanceRecord::query()
            ->with(['person', 'session.sheet', 'markedBy'])
            ->whereHas('session.sheet', fn ($query) => $query->where('is_active', true))
            ->whereNotNull('marked_at')
            ->orderByDesc('marked_at')
            ->take(10)
            ->get();
    }

    public function attendanceReportsUrl(): string
    {
        return AttendanceReports::getUrl();
    }

    public function checkAttendanceUrl(?AttendanceSession $session = null): string
    {
        if (! $session) {
            return CheckAttendance::getUrl();
        }

        return CheckAttendance::getUrl() . '?' . http_build_query([
            'sheetId' => $session->attendance_sheet_id,
            'sessionId' => $session->id,
        ]);
    }

    public function sheetTypeLabel(?string $type): string
    {
        return match ($type) {
            AttendanceSheet::TYPE_LORDS_TABLE => "Lord's Table",
            AttendanceSheet::TYPE_PRAYER_MEETING => 'Prayer Meeting',
            default => 'Custom Sheet',
        };
    }

    private function sessionSummaryRow(AttendanceSession $session): array
    {
        $expected = $this->activeParticipantCountForDate(
            sheetId: $session->attendance_sheet_id,
            date: $session->session_date->format('Y-m-d'),
        );

        $extra=\App\Services\GuestAttendance::meetingCounts($session);
        $expected += $extra['expected'];
        $session->present_count = (int)$session->present_count + $extra['present'];
        $session->absent_count = (int)$session->absent_count + $extra['absent'];
        $session->marked_count = (int)$session->marked_count + $extra['marked'];
        $present = (int) ($session->present_count ?? 0);
        $marked = (int) ($session->marked_count ?? 0);

        return [
            'session' => $session,
            'sheet' => $session->sheet,
            'expected' => $expected,
            'present' => $present,
            'absent' => (int) ($session->absent_count ?? 0),
            'unmarked' => max($expected - $marked, 0),
            'rate' => $expected > 0 ? round(($present / $expected) * 100, 1) : 0,
        ];
    }

    private function activeParticipantCountForDate(int $sheetId, string $date): int
    {
        return AttendanceParticipant::query()
            ->where('attendance_sheet_id', $sheetId)
            ->where('is_active', true)
            ->where(function ($query) use ($date): void {
                $query->whereNull('starts_on')
                    ->orWhere('starts_on', '<=', $date);
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('ends_on')
                    ->orWhere('ends_on', '>=', $date);
            })
            ->distinct()
            ->count('person_id');
    }
}
