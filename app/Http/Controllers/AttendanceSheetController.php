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
            'meeting_day' => ['nullable', 'integer', 'between:0,6'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'is_one_time' => ['nullable', 'boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $isOneTime = $request->boolean('is_one_time');

        $startDate = CarbonImmutable::parse($data['start_date'])->startOfDay();

        if ($isOneTime) {
            $endDate = $startDate;
            $meetingDay = $startDate->dayOfWeek;
        } else {
            if (blank($data['meeting_day'] ?? null)) {
                throw ValidationException::withMessages([
                    'meeting_day' => 'Meeting day is required for recurring attendance sheets.',
                ]);
            }

            if (blank($data['end_date'] ?? null)) {
                throw ValidationException::withMessages([
                    'end_date' => 'End date is required for recurring attendance sheets.',
                ]);
            }

            $endDate = CarbonImmutable::parse($data['end_date'])->startOfDay();
            $meetingDay = (int) $data['meeting_day'];

            if ($startDate->diffInMonths($endDate) > 18) {
                throw ValidationException::withMessages([
                    'end_date' => 'Attendance sheet date range must not exceed 18 months.',
                ]);
            }
        }

        $meetingTime = blank($data['meeting_time'] ?? null) ? null : $data['meeting_time'];

        $duplicateSheet = AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->where('is_one_time', $isOneTime)
            ->whereRaw('LOWER(title) = ?', [strtolower(trim((string) $data['title']))])
            ->where('meeting_day', $meetingDay)
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
            ->where(function ($query) use ($meetingTime): void {
                if ($meetingTime === null) {
                    $query->whereNull('meeting_time');
                } else {
                    $query->where('meeting_time', $meetingTime);
                }
            })
            ->exists();

        if ($duplicateSheet) {
            throw ValidationException::withMessages([
                'title' => 'A similar attendance sheet already exists.',
            ]);
        }

        $sessionDates = $isOneTime
            ? [$startDate]
            : $this->sessionDates(
                startDate: $startDate,
                endDate: $endDate,
                meetingDay: $meetingDay,
            );

        if ($sessionDates === []) {
            throw ValidationException::withMessages([
                'meeting_day' => 'No meeting dates were found in the selected date range.',
            ]);
        }

        $sheet = DB::transaction(function () use ($data, $sessionDates, $isOneTime, $startDate, $endDate, $meetingDay, $meetingTime): AttendanceSheet {
            $sheet = AttendanceSheet::query()->create([
                'title' => $data['title'],
                'sheet_type' => AttendanceSheet::TYPE_CUSTOM,
                'locality' => blank($data['locality'] ?? null) ? null : $data['locality'],
                'meeting_day' => $meetingDay,
                'meeting_time' => $meetingTime,
                'is_one_time' => $isOneTime,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'is_active' => true,
                'remarks' => blank($data['remarks'] ?? null) ? null : $data['remarks'],
                'created_by_id' => auth()->id(),
            ]);

            foreach ($sessionDates as $sessionDate) {
                $sheet->sessions()->create([
                    'session_date' => $sessionDate->toDateString(),
                    'session_time' => $meetingTime,
                    'title' => $sheet->title . ' - ' . $sessionDate->format('M d, Y'),
                ]);
            }

            ActivityLogger::log(
                action: 'attendance_sheet.created',
                subject: $sheet,
                description: 'Created attendance sheet and generated meeting dates.',
                newValues: [
                    'title' => $sheet->title,
                    'locality' => $sheet->locality,
                    'meeting_day' => $sheet->meeting_day,
                    'meeting_time' => $sheet->meeting_time,
                    'is_one_time' => $sheet->is_one_time,
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
