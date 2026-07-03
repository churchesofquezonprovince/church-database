<?php

namespace App\Http\Controllers;

use App\Filament\Pages\AddAttendanceSheet;
use App\Models\AttendanceSheet;
use App\Support\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceSheetController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'locality' => ['nullable', 'string', 'max:150'],
            'meeting_day' => ['required', 'integer', 'between:0,6'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $startDate = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $endDate = CarbonImmutable::parse($data['end_date'])->startOfDay();

        if ($startDate->diffInMonths($endDate) > 18) {
            throw ValidationException::withMessages([
                'end_date' => 'Attendance sheet date range must not exceed 18 months.',
            ]);
        }

        $duplicateSheet = AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->whereRaw('LOWER(title) = ?', [strtolower(trim((string) $data['title']))])
            ->where('meeting_day', (int) $data['meeting_day'])
            ->whereDate('start_date', $startDate->toDateString())
            ->whereDate('end_date', $endDate->toDateString())
            ->where(function ($query) use ($data): void {
                if (blank($data['locality'] ?? null)) {
                    $query->whereNull('locality')
                        ->orWhere('locality', '');
                } else {
                    $query->whereRaw('LOWER(locality) = ?', [strtolower(trim((string) $data['locality']))]);
                }
            })
            ->exists();

        if ($duplicateSheet) {
            throw ValidationException::withMessages([
                'title' => 'A similar attendance sheet already exists with the same title, locality, meeting day, start date, and end date.',
            ]);
        }

        $sessionDates = $this->sessionDates(
            startDate: $startDate,
            endDate: $endDate,
            meetingDay: (int) $data['meeting_day'],
        );

        if ($sessionDates === []) {
            throw ValidationException::withMessages([
                'meeting_day' => 'No meeting dates were found in the selected date range.',
            ]);
        }

        $sheet = DB::transaction(function () use ($data, $sessionDates): AttendanceSheet {
            $sheet = AttendanceSheet::query()->create([
                'title' => $data['title'],
                'sheet_type' => AttendanceSheet::TYPE_CUSTOM,
                'locality' => blank($data['locality'] ?? null) ? null : $data['locality'],
                'meeting_day' => (int) $data['meeting_day'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => true,
                'remarks' => blank($data['remarks'] ?? null) ? null : $data['remarks'],
                'created_by_id' => auth()->id(),
            ]);

            foreach ($sessionDates as $sessionDate) {
                $sheet->sessions()->create([
                    'session_date' => $sessionDate->toDateString(),
                    'title' => $sheet->title . ' - ' . $sessionDate->format('M d, Y'),
                ]);
            }

            ActivityLogger::log(
                action: 'attendance_sheet.created',
                subject: $sheet,
                description: 'Created attendance sheet and generated sessions.',
                newValues: [
                    'title' => $sheet->title,
                    'locality' => $sheet->locality,
                    'meeting_day' => $sheet->meeting_day,
                    'start_date' => optional($sheet->start_date)->format('Y-m-d'),
                    'end_date' => optional($sheet->end_date)->format('Y-m-d'),
                    'sessions_created' => count($sessionDates),
                ],
            );

            return $sheet;
        });

        return redirect(AddAttendanceSheet::getUrl())
            ->with('attendance_sheet_created', true)
            ->with('attendance_sheet_title', $sheet->title)
            ->with('attendance_sessions_created', count($sessionDates));
    }

    private function sessionDates(CarbonImmutable $startDate, CarbonImmutable $endDate, int $meetingDay): array
    {
        $current = $startDate;

        while ($current->dayOfWeek !== $meetingDay) {
            $current = $current->addDay();
        }

        $dates = [];

        while ($current->lessThanOrEqualTo($endDate)) {
            $dates[] = $current;
            $current = $current->addWeek();
        }

        return $dates;
    }
}
