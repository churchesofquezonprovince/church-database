<?php

namespace App\Http\Controllers;

use App\Filament\Pages\AddAttendanceSheet;
use App\Models\AttendanceSheet;
use App\Support\ActivityLogger;
use App\Support\LocalityOptions;
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
            'locality_id' => ['nullable', 'integer', 'exists:localities,id'],
            'meeting_day' => ['nullable', 'integer', 'between:0,6'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'is_one_time' => ['nullable', 'boolean'],
            'schedule_type' => [
                'nullable',
                'in:recurring,one_time,consecutive,manual',
            ],
            'meeting_form_type' => [
                'nullable',
                'in:disabled,normal',
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'manual_dates' => ['nullable', 'array', 'max:100'],
            'manual_dates.*' => ['required', 'date', 'distinct'],
            'remarks' => ['nullable', 'string'],
        ]);

        $locality = null;

        if (filled($data['locality_id'] ?? null)) {
            $locality = LocalityOptions::activeConfiguredLocality(
                (int) $data['locality_id']
            );

            if (! $locality) {
                throw ValidationException::withMessages([
                    'locality_id' => 'Select an active configured Locality.',
                ]);
            }
        }

        $scheduleType =
            $data['schedule_type']
            ?? (
                $request->boolean('is_one_time')
                    ? AttendanceSheet::SCHEDULE_ONE_TIME
                    : AttendanceSheet::SCHEDULE_RECURRING
            );

        $isOneTime =
            $scheduleType === AttendanceSheet::SCHEDULE_ONE_TIME;

        $meetingFormType =
            $data['meeting_form_type']
            ?? AttendanceSheet::MEETING_FORM_DISABLED;

        $meetingTime =
            blank($data['meeting_time'] ?? null)
                ? null
                : $data['meeting_time'];

        $endTime =
            blank($data['end_time'] ?? null)
                ? null
                : $data['end_time'];

        if (
            $meetingTime !== null
            && $endTime !== null
            && $endTime <= $meetingTime
        ) {
            throw ValidationException::withMessages([
                'end_time' => 'End Time must be later than Start Time.',
            ]);
        }

        $startDate = null;
        $endDate = null;
        $meetingDay = null;

        $manualDates = collect(
            $data['manual_dates'] ?? []
        )
            ->filter()
            ->map(
                fn (string $date): CarbonImmutable =>
                    CarbonImmutable::parse($date)->startOfDay()
            )
            ->unique(
                fn (CarbonImmutable $date): string =>
                    $date->toDateString()
            )
            ->sortBy(
                fn (CarbonImmutable $date): string =>
                    $date->toDateString()
            )
            ->values();

        if ($scheduleType === AttendanceSheet::SCHEDULE_ONE_TIME) {
            if (blank($data['start_date'] ?? null)) {
                throw ValidationException::withMessages([
                    'start_date' => 'Start Date is required for one-time attendance.',
                ]);
            }

            $startDate =
                CarbonImmutable::parse($data['start_date'])
                    ->startOfDay();

            $endDate = $startDate;
            $meetingDay = $startDate->dayOfWeek;
        } elseif (
            $scheduleType === AttendanceSheet::SCHEDULE_RECURRING
        ) {
            if (blank($data['start_date'] ?? null)) {
                throw ValidationException::withMessages([
                    'start_date' => 'Start Date is required for recurring attendance.',
                ]);
            }

            if (blank($data['end_date'] ?? null)) {
                throw ValidationException::withMessages([
                    'end_date' => 'End Date is required for recurring attendance.',
                ]);
            }

            if (blank($data['meeting_day'] ?? null)) {
                throw ValidationException::withMessages([
                    'meeting_day' => 'Meeting Day is required for recurring attendance.',
                ]);
            }

            $startDate =
                CarbonImmutable::parse($data['start_date'])
                    ->startOfDay();

            $endDate =
                CarbonImmutable::parse($data['end_date'])
                    ->startOfDay();

            $meetingDay = (int) $data['meeting_day'];
        } elseif (
            $scheduleType === AttendanceSheet::SCHEDULE_CONSECUTIVE
        ) {
            if (blank($data['start_date'] ?? null)) {
                throw ValidationException::withMessages([
                    'start_date' => 'Start Date is required for consecutive attendance.',
                ]);
            }

            if (blank($data['end_date'] ?? null)) {
                throw ValidationException::withMessages([
                    'end_date' => 'End Date is required for consecutive attendance.',
                ]);
            }

            $startDate =
                CarbonImmutable::parse($data['start_date'])
                    ->startOfDay();

            $endDate =
                CarbonImmutable::parse($data['end_date'])
                    ->startOfDay();
        } elseif (
            $scheduleType === AttendanceSheet::SCHEDULE_MANUAL
        ) {
            if ($manualDates->isEmpty()) {
                throw ValidationException::withMessages([
                    'manual_dates' =>
                        'Add at least one Manual Session Date.',
                ]);
            }

            /*
             * Start/End Date become the envelope of the
             * manually selected dates. They do NOT imply
             * that every date between them is a Session.
             */
            $startDate = $manualDates->first();
            $endDate = $manualDates->last();
        }

        if (
            $startDate !== null
            && $endDate !== null
            && $startDate->diffInMonths($endDate) > 18
        ) {
            throw ValidationException::withMessages([
                'end_date' => 'Attendance sheet date range must not exceed 18 months.',
            ]);
        }

        $duplicateSheet = AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->where('schedule_type', $scheduleType)
            ->whereRaw(
                'LOWER(title) = ?',
                [strtolower(trim((string) $data['title']))]
            )
            ->where(function ($query) use ($meetingDay): void {
                $meetingDay === null
                    ? $query->whereNull('meeting_day')
                    : $query->where('meeting_day', $meetingDay);
            })
            ->where(function ($query) use ($startDate): void {
                $startDate === null
                    ? $query->whereNull('start_date')
                    : $query->whereDate(
                        'start_date',
                        $startDate->toDateString()
                    );
            })
            ->where(function ($query) use ($endDate): void {
                $endDate === null
                    ? $query->whereNull('end_date')
                    : $query->whereDate(
                        'end_date',
                        $endDate->toDateString()
                    );
            })
            ->where(function ($query) use ($locality): void {
                if (! $locality) {
                    $query->whereNull('locality_id');
                } else {
                    $query->where(
                        'locality_id',
                        $locality->id
                    );
                }
            })
            ->where(function ($query) use ($meetingTime): void {
                $meetingTime === null
                    ? $query->whereNull('meeting_time')
                    : $query->where(
                        'meeting_time',
                        $meetingTime
                    );
            })
            ->where(function ($query) use ($endTime): void {
                $endTime === null
                    ? $query->whereNull('end_time')
                    : $query->where(
                        'end_time',
                        $endTime
                    );
            })
            ->exists();

        if ($duplicateSheet) {
            throw ValidationException::withMessages([
                'title' => 'A similar attendance sheet already exists.',
            ]);
        }

        $sessionDates = match ($scheduleType) {
            AttendanceSheet::SCHEDULE_ONE_TIME =>
                [$startDate],

            AttendanceSheet::SCHEDULE_CONSECUTIVE =>
                $this->consecutiveDates(
                    startDate: $startDate,
                    endDate: $endDate,
                ),

            AttendanceSheet::SCHEDULE_MANUAL =>
                $manualDates->all(),

            default =>
                $this->sessionDates(
                    startDate: $startDate,
                    endDate: $endDate,
                    meetingDay: $meetingDay,
                ),
        };

        if ($sessionDates === []) {
            throw ValidationException::withMessages([
                'meeting_day' => 'No meeting dates were found in the selected date range.',
            ]);
        }

        $sheet = DB::transaction(function () use (
            $data,
            $locality,
            $sessionDates,
            $isOneTime,
            $scheduleType,
            $startDate,
            $endDate,
            $meetingDay,
            $meetingTime,
            $endTime,
            $meetingFormType
        ): AttendanceSheet {
            $sheet = AttendanceSheet::query()->create([
                'title' => $data['title'],
                'sheet_type' => AttendanceSheet::TYPE_CUSTOM,
                'locality_id' => $locality?->id,
                'locality' => $locality?->name,
                'meeting_day' => $meetingDay,
                'meeting_time' => $meetingTime,
                'end_time' => $endTime,
                'is_one_time' => $isOneTime,
                'schedule_type' => $scheduleType,
                'meeting_form_type' => $meetingFormType,
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate?->toDateString(),
                'is_active' => true,
                'remarks' => blank($data['remarks'] ?? null) ? null : $data['remarks'],
                'created_by_id' => auth()->id(),
            ]);

            foreach ($sessionDates as $sessionDate) {
                $sheet->sessions()->create([
                    'session_date' => $sessionDate->toDateString(),
                    'session_time' => $meetingTime,
                    'session_end_time' => $endTime,
                    'title' => $sheet->title . ' - ' . $sessionDate->format('M d, Y'),
                ]);
            }

/*
 * Generate stable public meeting URLs when
 * Normal Meeting Form is enabled.
 */
$sheet->ensureMeetingFormSlugs();

            ActivityLogger::log(
                action: 'attendance_sheet.created',
                subject: $sheet,
                description: 'Created attendance sheet and generated meeting dates.',
                newValues: [
                    'title' => $sheet->title,
                    'locality' => $sheet->locality,
                    'meeting_day' => $sheet->meeting_day,
                    'meeting_time' => $sheet->meeting_time,
                    'end_time' => $sheet->end_time,
                    'is_one_time' => $sheet->is_one_time,
                    'schedule_type' => $sheet->schedule_type,
                    'meeting_form_type' => $sheet->meeting_form_type,
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

    private function consecutiveDates(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $dates = [];
        $current = $startDate;

        while ($current->lessThanOrEqualTo($endDate)) {
            $dates[] = $current;
            $current = $current->addDay();
        }

        return $dates;
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
