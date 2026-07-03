<?php

namespace App\Http\Controllers;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportExportController extends Controller
{
    public function print(Request $request)
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'report_type' => ['required', 'string'],
            'sheetId' => ['required', 'integer', 'exists:attendance_sheets,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:150'],
            'meeting_day' => ['nullable', 'integer', 'between:0,6'],
        ]);

        $sheet = AttendanceSheet::query()
            ->where('id', $data['sheetId'])
            ->where('sheet_type', $data['report_type'])
            ->firstOrFail();

        $meetingRows = $this->meetingRows($sheet, $data);
        $personRows = $this->personRows($sheet, $data);

        $expectedTotal = $personRows->sum('expected');
        $presentTotal = $personRows->sum('present');
        $absentTotal = $personRows->sum('absent');

        $summary = [
            'meetings' => $meetingRows->count(),
            'participants' => $personRows->count(),
            'expected_total' => $expectedTotal,
            'present_total' => $presentTotal,
            'absent_total' => $absentTotal,
            'overall_rate' => $expectedTotal > 0
                ? round(($presentTotal / $expectedTotal) * 100, 1)
                : 0,
        ];

        return view('reports.attendance-print', [
            'sheet' => $sheet,
            'meetingRows' => $meetingRows,
            'personRows' => $personRows,
            'summary' => $summary,
            'reportTypeLabel' => $this->reportTypeLabel($sheet->sheet_type),
            'sheetLabel' => $this->sheetLabel($sheet),
            'periodLabel' => $this->reportPeriodLabel($sheet, $data),
            'categoryLabel' => $data['category'] ?? 'All Categories',
            'meetingDayLabel' => isset($data['meeting_day']) && $data['meeting_day'] !== null
                ? $this->dayLabel((int) $data['meeting_day'])
                : 'All Days',
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'report_type' => ['required', 'string'],
            'sheetId' => ['required', 'integer', 'exists:attendance_sheets,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:150'],
            'meeting_day' => ['nullable', 'integer', 'between:0,6'],
        ]);

        $sheet = AttendanceSheet::query()
            ->where('id', $data['sheetId'])
            ->where('sheet_type', $data['report_type'])
            ->firstOrFail();

        $filename = 'attendance-report-' . str($sheet->title)->slug() . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($sheet, $data): void {
            $output = fopen('php://output', 'w');

            echo "\xEF\xBB\xBF";

            $this->writeSummary($output, $sheet, $data);

            fputcsv($output, []);
            fputcsv($output, ['Report by Meeting Date']);
            fputcsv($output, [
                'Meeting Date',
                'Day',
                'Expected',
                'Present',
                'Absent',
                'Unmarked',
                'Rate',
            ]);

            foreach ($this->meetingRows($sheet, $data) as $row) {
                fputcsv($output, [
                    $row['session']->session_date->format('Y-m-d'),
                    $row['session']->session_date->format('l'),
                    $row['active_participants'],
                    $row['present'],
                    $row['absent'],
                    $row['unmarked'],
                    $row['rate'] . '%',
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Report by Person']);
            fputcsv($output, [
                'Name',
                'Locality',
                'Category',
                'Expected',
                'Present',
                'Absent',
                'Unmarked',
                'Rate',
            ]);

            foreach ($this->personRows($sheet, $data) as $row) {
                fputcsv($output, [
                    $row['person']?->display_name ?? 'Unknown person',
                    $row['person']?->locality ?: 'No locality',
                    $row['person']?->churchProfile?->category ?: 'No category',
                    $row['expected'],
                    $row['present'],
                    $row['absent'],
                    $row['unmarked'],
                    $row['rate'] . '%',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function writeSummary($output, AttendanceSheet $sheet, array $data): void
    {
        $personRows = $this->personRows($sheet, $data);

        $expectedTotal = $personRows->sum('expected');
        $presentTotal = $personRows->sum('present');
        $absentTotal = $personRows->sum('absent');

        $overallRate = $expectedTotal > 0
            ? round(($presentTotal / $expectedTotal) * 100, 1)
            : 0;

        fputcsv($output, ['Attendance Report']);
        fputcsv($output, ['Report Type', $this->reportTypeLabel($sheet->sheet_type)]);
        fputcsv($output, ['Sheet / Locality', $this->sheetLabel($sheet)]);
        fputcsv($output, ['Date Range', $this->reportPeriodLabel($sheet, $data)]);
        fputcsv($output, ['Category', $data['category'] ?? 'All Categories']);
        fputcsv($output, ['Meeting Day', isset($data['meeting_day']) && $data['meeting_day'] !== null ? $this->dayLabel((int) $data['meeting_day']) : 'All Days']);
        fputcsv($output, ['Participants', $personRows->count()]);
        fputcsv($output, ['Expected Total', $expectedTotal]);
        fputcsv($output, ['Present Total', $presentTotal]);
        fputcsv($output, ['Absent Total', $absentTotal]);
        fputcsv($output, ['Overall Rate', $overallRate . '%']);
        fputcsv($output, ['Generated At', now()->format('Y-m-d H:i:s')]);
    }

    private function meetingRows(AttendanceSheet $sheet, array $data)
    {
        return AttendanceSession::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->when($data['date_from'] ?? null, fn ($query, $date) => $query->whereDate('session_date', '>=', $date))
            ->when($data['date_to'] ?? null, fn ($query, $date) => $query->whereDate('session_date', '<=', $date))
            ->when(isset($data['meeting_day']) && $data['meeting_day'] !== null && $data['meeting_day'] !== '', fn ($query) => $query->whereRaw('DAYOFWEEK(session_date) = ?', [(int) $data['meeting_day'] + 1]))
            ->withCount([
                'records as present_count' => fn ($query) => $query
                    ->where('is_present', true)
                    ->when($data['category'] ?? null, fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category))),

                'records as absent_count' => fn ($query) => $query
                    ->where('is_present', false)
                    ->when($data['category'] ?? null, fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category))),

                'records as marked_count' => fn ($query) => $query
                    ->when($data['category'] ?? null, fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category))),
            ])
            ->orderBy('session_date')
            ->get()
            ->map(function (AttendanceSession $session) use ($sheet, $data): array {
                $activeParticipants = $this->activeParticipantCountForDate(
                    sheet: $sheet,
                    date: $session->session_date->format('Y-m-d'),
                    data: $data,
                );

                return [
                    'session' => $session,
                    'active_participants' => $activeParticipants,
                    'present' => $session->present_count,
                    'absent' => $session->absent_count,
                    'marked' => $session->marked_count,
                    'unmarked' => max($activeParticipants - $session->marked_count, 0),
                    'rate' => $activeParticipants > 0
                        ? round(($session->present_count / $activeParticipants) * 100, 1)
                        : 0,
                ];
            });
    }

    private function personRows(AttendanceSheet $sheet, array $data)
    {
        return AttendanceParticipant::query()
            ->with(['person.churchProfile'])
            ->where('attendance_sheet_id', $sheet->id)
            ->when($data['category'] ?? null, fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)))
            ->get()
            ->sortBy(fn (AttendanceParticipant $participant): string => $participant->person?->display_name ?? '')
            ->values()
            ->map(function (AttendanceParticipant $participant) use ($sheet, $data): array {
                $sessionIds = AttendanceSession::query()
                    ->where('attendance_sheet_id', $sheet->id)
                    ->when($participant->starts_on, fn ($query) => $query->whereDate('session_date', '>=', $participant->starts_on))
                    ->when($participant->ends_on, fn ($query) => $query->whereDate('session_date', '<=', $participant->ends_on))
                    ->when($data['date_from'] ?? null, fn ($query, $date) => $query->whereDate('session_date', '>=', $date))
                    ->when($data['date_to'] ?? null, fn ($query, $date) => $query->whereDate('session_date', '<=', $date))
                    ->when(isset($data['meeting_day']) && $data['meeting_day'] !== null && $data['meeting_day'] !== '', fn ($query) => $query->whereRaw('DAYOFWEEK(session_date) = ?', [(int) $data['meeting_day'] + 1]))
                    ->pluck('id');

                $expected = $sessionIds->count();

                $present = AttendanceRecord::query()
                    ->whereIn('attendance_session_id', $sessionIds)
                    ->where('person_id', $participant->person_id)
                    ->where('is_present', true)
                    ->count();

                $absent = AttendanceRecord::query()
                    ->whereIn('attendance_session_id', $sessionIds)
                    ->where('person_id', $participant->person_id)
                    ->where('is_present', false)
                    ->count();

                $marked = $present + $absent;

                return [
                    'participant' => $participant,
                    'person' => $participant->person,
                    'expected' => $expected,
                    'present' => $present,
                    'absent' => $absent,
                    'marked' => $marked,
                    'unmarked' => max($expected - $marked, 0),
                    'rate' => $expected > 0
                        ? round(($present / $expected) * 100, 1)
                        : 0,
                ];
            });
    }

    private function activeParticipantCountForDate(AttendanceSheet $sheet, string $date, array $data): int
    {
        return AttendanceParticipant::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->where('is_active', true)
            ->when($data['category'] ?? null, fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)))
            ->where(function ($query) use ($date): void {
                $query->whereNull('starts_on')
                    ->orWhere('starts_on', '<=', $date);
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('ends_on')
                    ->orWhere('ends_on', '>=', $date);
            })
            ->count();
    }

    private function reportTypeLabel(string $type): string
    {
        return match ($type) {
            AttendanceSheet::TYPE_LORDS_TABLE => "Lord's Table Meeting",
            AttendanceSheet::TYPE_PRAYER_MEETING => 'Prayer Meeting',
            default => 'Custom Attendance Sheet',
        };
    }

    private function sheetLabel(AttendanceSheet $sheet): string
    {
        return $sheet->sheet_type === AttendanceSheet::TYPE_CUSTOM
            ? $sheet->title
            : ($sheet->locality ?: 'No Locality');
    }

    private function reportPeriodLabel(AttendanceSheet $sheet, array $data): string
    {
        $from = filled($data['date_from'] ?? null)
            ? CarbonImmutable::parse($data['date_from'])->format('M d, Y')
            : optional($sheet->start_date)->format('M d, Y');

        $to = filled($data['date_to'] ?? null)
            ? CarbonImmutable::parse($data['date_to'])->format('M d, Y')
            : optional($sheet->end_date)->format('M d, Y');

        return ($from ?: 'No start date') . ' to ' . ($to ?: 'Present');
    }

    private function dayLabel(int $day): string
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ][$day] ?? 'Unknown';
    }
}
