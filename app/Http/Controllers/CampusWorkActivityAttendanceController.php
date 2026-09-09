<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\CampusWorkActivity;
use App\Support\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CampusWorkActivityAttendanceController extends Controller
{
    public function generateOneTime(
        CampusWorkActivity $activity
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        [$sheet, $session] = DB::transaction(
            function () use ($activity): array {
                $activity = CampusWorkActivity::query()
                    ->lockForUpdate()
                    ->findOrFail($activity->id);

                $this->assertUnlinked($activity);

                $date = CarbonImmutable::parse(
                    $activity->activity_date
                )->startOfDay();

                $meetingTime = $this->nullIfBlank(
                    $activity->start_time
                );

                $sheet = AttendanceSheet::query()->create([
                    'title' =>
                        $this->sheetTitle($activity),

                    'sheet_type' =>
                        AttendanceSheet::TYPE_CUSTOM,

                    'locality_id' =>
                        $activity->locality_id,

                    'locality' =>
                        $activity->locality,

                    'meeting_day' =>
                        $date->dayOfWeek,

                    'meeting_time' =>
                        $meetingTime,

                    'is_one_time' =>
                        true,

                    'meeting_form_type' =>
                        AttendanceSheet::MEETING_FORM_DISABLED,

                    'start_date' =>
                        $date->toDateString(),

                    'end_date' =>
                        $date->toDateString(),

                    'is_active' =>
                        true,

                    'remarks' =>
                        $this->activityRemarks($activity),

                    'created_by_id' =>
                        auth()->id(),
                ]);

                $session = $sheet->sessions()->create([
                    'session_date' =>
                        $date->toDateString(),

                    'session_time' =>
                        $meetingTime,

                    'title' =>
                        $sheet->title
                        . ' - '
                        . $date->format('M d, Y'),
                ]);

                $activity->forceFill([
                    'attendance_sheet_id' =>
                        $sheet->id,

                    'attendance_session_id' =>
                        $session->id,
                ])->save();

                ActivityLogger::log(
                    action:
                        'campus_activity.attendance.generated',

                    subject:
                        $activity,

                    description:
                        'Generated one-time Attendance from Campus Activity.',

                    newValues: [
                        'attendance_sheet_id' =>
                            $sheet->id,

                        'attendance_session_id' =>
                            $session->id,

                        'attendance_mode' =>
                            'one_time',
                    ],
                );

                return [$sheet, $session];
            }
        );

        return back()
            ->with(
                'campus_activity_attendance_generated',
                true
            )
            ->with(
                'campus_activity_attendance_title',
                $sheet->title
            );
    }

    public function generateRecurring(
        Request $request,
        CampusWorkActivity $activity
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $data = $request->validate([
            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'meeting_day' => [
                'required',
                'integer',
                'between:0,6',
            ],

            'meeting_time' => [
                'nullable',
                'date_format:H:i',
            ],
        ]);

        $startDate = CarbonImmutable::parse(
            $data['start_date']
        )->startOfDay();

        $endDate = CarbonImmutable::parse(
            $data['end_date']
        )->startOfDay();

        if (
            $startDate->diffInMonths($endDate)
            > 18
        ) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'Recurring Attendance must not exceed 18 months.',
            ]);
        }

        $meetingDay =
            (int) $data['meeting_day'];

        $meetingTime = $this->nullIfBlank(
            $data['meeting_time'] ?? null
        );

        $sessionDates = $this->sessionDates(
            startDate: $startDate,
            endDate: $endDate,
            meetingDay: $meetingDay,
        );

        if ($sessionDates === []) {
            throw ValidationException::withMessages([
                'meeting_day' =>
                    'No meeting dates were found in the selected date range.',
            ]);
        }

        $sheet = DB::transaction(
            function () use (
                $activity,
                $startDate,
                $endDate,
                $meetingDay,
                $meetingTime,
                $sessionDates
            ): AttendanceSheet {
                $activity =
                    CampusWorkActivity::query()
                        ->lockForUpdate()
                        ->findOrFail($activity->id);

                $this->assertUnlinked($activity);

                $sheet =
                    AttendanceSheet::query()->create([
                        'title' =>
                            $this->sheetTitle($activity),

                        'sheet_type' =>
                            AttendanceSheet::TYPE_CUSTOM,

                        'locality_id' =>
                            $activity->locality_id,

                        'locality' =>
                            $activity->locality,

                        'meeting_day' =>
                            $meetingDay,

                        'meeting_time' =>
                            $meetingTime,

                        'is_one_time' =>
                            false,

                        'meeting_form_type' =>
                            AttendanceSheet::MEETING_FORM_DISABLED,

                        'start_date' =>
                            $startDate->toDateString(),

                        'end_date' =>
                            $endDate->toDateString(),

                        'is_active' =>
                            true,

                        'remarks' =>
                            $this->activityRemarks($activity),

                        'created_by_id' =>
                            auth()->id(),
                    ]);

                foreach (
                    $sessionDates as $sessionDate
                ) {
                    $sheet->sessions()->create([
                        'session_date' =>
                            $sessionDate->toDateString(),

                        'session_time' =>
                            $meetingTime,

                        'title' =>
                            $sheet->title
                            . ' - '
                            . $sessionDate->format(
                                'M d, Y'
                            ),
                    ]);
                }

                $activity->forceFill([
                    'attendance_sheet_id' =>
                        $sheet->id,

                    'attendance_session_id' =>
                        null,
                ])->save();

                ActivityLogger::log(
                    action:
                        'campus_activity.attendance.generated',

                    subject:
                        $activity,

                    description:
                        'Generated recurring Attendance from Campus Activity.',

                    newValues: [
                        'attendance_sheet_id' =>
                            $sheet->id,

                        'attendance_mode' =>
                            'recurring',

                        'sessions_created' =>
                            count($sessionDates),
                    ],
                );

                return $sheet;
            }
        );

        return back()
            ->with(
                'campus_activity_attendance_generated',
                true
            )
            ->with(
                'campus_activity_attendance_title',
                $sheet->title
            )
            ->with(
                'campus_activity_attendance_sessions',
                count($sessionDates)
            );
    }

    public function linkExisting(
        Request $request,
        CampusWorkActivity $activity
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $data = $request->validate([
            'attendance_sheet_id' => [
                'required',
                'integer',
                'exists:attendance_sheets,id',
            ],

            'attendance_session_id' => [
                'nullable',
                'integer',
                'exists:attendance_sessions,id',
            ],
        ]);

        $sheet = DB::transaction(
            function () use (
                $activity,
                $data
            ): AttendanceSheet {
                $activity =
                    CampusWorkActivity::query()
                        ->lockForUpdate()
                        ->findOrFail($activity->id);

                $this->assertUnlinked($activity);

                $sheet = AttendanceSheet::query()
                    ->whereKey(
                        (int) $data[
                            'attendance_sheet_id'
                        ]
                    )
                    ->where(
                        'sheet_type',
                        AttendanceSheet::TYPE_CUSTOM
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $sheet) {
                    throw ValidationException::withMessages([
                        'attendance_sheet_id' =>
                            'Select an existing Custom Attendance Sheet.',
                    ]);
                }

                $alreadyLinked =
                    CampusWorkActivity::query()
                        ->where(
                            'attendance_sheet_id',
                            $sheet->id
                        )
                        ->where(
                            'id',
                            '!=',
                            $activity->id
                        )
                        ->exists();

                if ($alreadyLinked) {
                    throw ValidationException::withMessages([
                        'attendance_sheet_id' =>
                            'That Attendance Sheet is already linked to another Campus Activity.',
                    ]);
                }

                $session = null;

                if (
                    filled(
                        $data[
                            'attendance_session_id'
                        ] ?? null
                    )
                ) {
                    $session =
                        AttendanceSession::query()
                            ->whereKey(
                                (int) $data[
                                    'attendance_session_id'
                                ]
                            )
                            ->where(
                                'attendance_sheet_id',
                                $sheet->id
                            )
                            ->lockForUpdate()
                            ->first();

                    if (! $session) {
                        throw ValidationException::withMessages([
                            'attendance_session_id' =>
                                'The selected Attendance Session does not belong to the selected Attendance Sheet.',
                        ]);
                    }
                }

                if (
                    ! $session
                    && $sheet->is_one_time
                ) {
                    $session =
                        AttendanceSession::query()
                            ->where(
                                'attendance_sheet_id',
                                $sheet->id
                            )
                            ->orderBy('session_date')
                            ->orderBy('id')
                            ->first();

                    if (! $session) {
                        throw ValidationException::withMessages([
                            'attendance_sheet_id' =>
                                'The selected one-time Attendance Sheet has no Attendance Session.',
                        ]);
                    }
                }

                if ($session) {
                    $sessionAlreadyLinked =
                        CampusWorkActivity::query()
                            ->where(
                                'attendance_session_id',
                                $session->id
                            )
                            ->where(
                                'id',
                                '!=',
                                $activity->id
                            )
                            ->exists();

                    if ($sessionAlreadyLinked) {
                        throw ValidationException::withMessages([
                            'attendance_session_id' =>
                                'That Attendance Session is already linked to another Campus Activity.',
                        ]);
                    }
                }

                $activity->forceFill([
                    'attendance_sheet_id' =>
                        $sheet->id,

                    'attendance_session_id' =>
                        $session?->id,
                ])->save();

                ActivityLogger::log(
                    action:
                        'campus_activity.attendance.linked',

                    subject:
                        $activity,

                    description:
                        'Linked Campus Activity to existing Attendance.',

                    newValues: [
                        'attendance_sheet_id' =>
                            $sheet->id,

                        'attendance_session_id' =>
                            $session?->id,
                    ],
                );

                return $sheet;
            }
        );

        return back()
            ->with(
                'campus_activity_attendance_linked',
                true
            )
            ->with(
                'campus_activity_attendance_title',
                $sheet->title
            );
    }

    public function unlink(
        CampusWorkActivity $activity
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        DB::transaction(
            function () use ($activity): void {
                $activity =
                    CampusWorkActivity::query()
                        ->lockForUpdate()
                        ->findOrFail($activity->id);

                $oldSheetId =
                    $activity->attendance_sheet_id;

                $oldSessionId =
                    $activity->attendance_session_id;

                $activity->forceFill([
                    'attendance_sheet_id' =>
                        null,

                    'attendance_session_id' =>
                        null,
                ])->save();

                ActivityLogger::log(
                    action:
                        'campus_activity.attendance.unlinked',

                    subject:
                        $activity,

                    description:
                        'Detached Campus Activity from Attendance while preserving Attendance data.',

                    oldValues: [
                        'attendance_sheet_id' =>
                            $oldSheetId,

                        'attendance_session_id' =>
                            $oldSessionId,
                    ],

                    newValues: [
                        'attendance_sheet_id' =>
                            null,

                        'attendance_session_id' =>
                            null,
                    ],
                );
            }
        );

        return back()->with(
            'campus_activity_attendance_unlinked',
            true
        );
    }

    private function assertUnlinked(
        CampusWorkActivity $activity
    ): void {
        if (
            filled($activity->attendance_sheet_id)
            || filled(
                $activity->attendance_session_id
            )
        ) {
            throw ValidationException::withMessages([
                'attendance' =>
                    'This Campus Activity is already linked to Attendance.',
            ]);
        }
    }

    private function sessionDates(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        int $meetingDay
    ): array {
        $current = $startDate;

        while (
            $current->dayOfWeek
            !== $meetingDay
        ) {
            $current =
                $current->addDay();
        }

        $dates = [];

        while (
            $current->lessThanOrEqualTo(
                $endDate
            )
        ) {
            $dates[] = $current;

            $current =
                $current->addWeek();
        }

        return $dates;
    }

    private function sheetTitle(
        CampusWorkActivity $activity
    ): string {
        return Str::limit(
            'Campus - '
            . $activity->display_title,
            255,
            ''
        );
    }

    private function activityRemarks(
        CampusWorkActivity $activity
    ): string {
        return collect([
            'Generated from Campus Activity #'
                . $activity->id
                . '.',

            filled(
                $activity->activity_type_label
            )
                ? 'Activity Type: '
                    . $activity->activity_type_label
                : null,

            filled(
                $activity->school?->name
            )
                ? 'School / Campus: '
                    . $activity->school->name
                : null,

            filled($activity->venue)
                ? 'Venue: '
                    . $activity->venue
                : null,

            filled($activity->description)
                ? 'Notes: '
                    . $activity->description
                : null,
        ])
            ->filter()
            ->implode(PHP_EOL);
    }

    private function nullIfBlank(
        mixed $value
    ): ?string {
        return filled($value)
            ? trim((string) $value)
            : null;
    }
}
