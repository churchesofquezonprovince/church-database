<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSheet;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Support\ActivityLogger;
use App\Support\LocalityOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AttendanceSheetStatusController extends Controller
{
    public function toggleActive(AttendanceSheet $sheet): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $wasActive = (bool) $sheet->is_active;

        $sheet->forceFill([
            'is_active' => ! $wasActive,
        ])->save();

        ActivityLogger::log(
            action: $wasActive ? 'attendance_sheet.archived' : 'attendance_sheet.restored',
            subject: $sheet,
            description: $wasActive
                ? 'Archived attendance sheet.'
                : 'Restored attendance sheet.',
            oldValues: [
                'is_active' => $wasActive,
            ],
            newValues: [
                'is_active' => ! $wasActive,
            ],
        );

        return back()->with(
            $wasActive ? 'attendance_sheet_archived' : 'attendance_sheet_restored',
            true,
        );
    }

    public function destroy(AttendanceSheet $sheet): RedirectResponse
    {
        abort_unless(auth()->user()?->canDeleteRecords(), 403);

        $sheet->loadCount(['sessions', 'participants']);

        DB::transaction(function () use ($sheet): void {
            $sessionIds = AttendanceSession::query()
                ->where('attendance_sheet_id', $sheet->id)
                ->pluck('id');

            $recordCount = $sessionIds->isEmpty()
                ? 0
                : AttendanceRecord::query()
                    ->whereIn('attendance_session_id', $sessionIds)
                    ->count();

            ActivityLogger::log(
                action: 'attendance_sheet.deleted',
                subject: $sheet,
                description: 'Deleted attendance sheet from Manage Attendance Sheets.',
                oldValues: [
                    'sheet_id' => $sheet->id,
                    'title' => $sheet->title,
                    'sheet_type' => $sheet->sheet_type,
                    'locality' => $sheet->locality,
                    'sessions_count' => $sheet->sessions_count,
                    'participants_count' => $sheet->participants_count,
                    'records_count' => $recordCount,
                ],
            );

            if (! $sessionIds->isEmpty()) {
                AttendanceRecord::query()
                    ->whereIn('attendance_session_id', $sessionIds)
                    ->delete();

                AttendanceSession::query()
                    ->whereIn('id', $sessionIds)
                    ->delete();
            }

            AttendanceParticipant::query()
                ->where('attendance_sheet_id', $sheet->id)
                ->delete();

            $sheet->delete();
        });

        return back()->with('attendance_sheet_deleted', true);
    }

public function update(
    Request $request,
    AttendanceSheet $sheet
): RedirectResponse {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    $data = $request->validate([
        'title' => [
            'required',
            'string',
            'max:255',
        ],
        'locality_id' => [
            'nullable',
            'integer',
            'exists:localities,id',
        ],

        'meeting_day' => [
            'nullable',
            'integer',
            'between:0,6',
        ],

        'meeting_time' => [
            'nullable',
            'date_format:H:i',
        ],

        'end_time' => [
            'nullable',
            'date_format:H:i',
        ],

        'schedule_type' => [
            'required',
            'in:recurring,one_time,consecutive,manual',
        ],

        'is_one_time' => [
            'nullable',
            'boolean',
        ],

        'meeting_form_type' => [
            'required',
            'in:disabled,normal',
        ],

        'start_date' => [
            'nullable',
            'date',
        ],

        'end_date' => [
            'nullable',
            'date',
            'after_or_equal:start_date',
        ],

        'remarks' => [
            'nullable',
            'string',
        ],
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
        $data['schedule_type'];

    $isOneTime =
        $scheduleType
        === AttendanceSheet::SCHEDULE_ONE_TIME;

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
            'end_time' =>
                'End Time must be later than Start Time.',
        ]);
    }

    $meetingDay = null;
    $startDate = null;
    $endDate = null;
    $desiredDates = [];

    if (
        $scheduleType
        === AttendanceSheet::SCHEDULE_ONE_TIME
    ) {
        if (blank($data['start_date'] ?? null)) {
            throw ValidationException::withMessages([
                'start_date' =>
                    'Meeting Date is required for One-time Attendance.',
            ]);
        }

        $startDate =
            CarbonImmutable::parse(
                $data['start_date']
            )->startOfDay();

        $endDate = $startDate;
        $meetingDay = $startDate->dayOfWeek;

        $desiredDates = [
            $startDate->toDateString(),
        ];
    } elseif (
        $scheduleType
        === AttendanceSheet::SCHEDULE_RECURRING
    ) {
        if (blank($data['start_date'] ?? null)) {
            throw ValidationException::withMessages([
                'start_date' =>
                    'Start Date is required for Recurring Weekly.',
            ]);
        }

        if (blank($data['end_date'] ?? null)) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'End Date is required for Recurring Weekly.',
            ]);
        }

        if (blank($data['meeting_day'] ?? null)) {
            throw ValidationException::withMessages([
                'meeting_day' =>
                    'Meeting Day is required for Recurring Weekly.',
            ]);
        }

        $startDate =
            CarbonImmutable::parse(
                $data['start_date']
            )->startOfDay();

        $endDate =
            CarbonImmutable::parse(
                $data['end_date']
            )->startOfDay();

        $meetingDay =
            (int) $data['meeting_day'];

        if (
            $startDate->diffInMonths($endDate)
            > 18
        ) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'Attendance sheet date range must not exceed 18 months.',
            ]);
        }

        $desiredDates =
            $this->sessionDates(
                startDate: $startDate,
                endDate: $endDate,
                meetingDay: $meetingDay,
            );
    } elseif (
        $scheduleType
        === AttendanceSheet::SCHEDULE_CONSECUTIVE
    ) {
        if (blank($data['start_date'] ?? null)) {
            throw ValidationException::withMessages([
                'start_date' =>
                    'Start Date is required for Consecutive Days.',
            ]);
        }

        if (blank($data['end_date'] ?? null)) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'End Date is required for Consecutive Days.',
            ]);
        }

        $startDate =
            CarbonImmutable::parse(
                $data['start_date']
            )->startOfDay();

        $endDate =
            CarbonImmutable::parse(
                $data['end_date']
            )->startOfDay();

        if (
            $startDate->diffInMonths($endDate)
            > 18
        ) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'Attendance sheet date range must not exceed 18 months.',
            ]);
        }

        $current = $startDate;

        while (
            $current->lessThanOrEqualTo($endDate)
        ) {
            $desiredDates[] =
                $current->toDateString();

            $current =
                $current->addDay();
        }
    } else {
        /*
         * Manual Dates are already stored as Sessions.
         * Editing Sheet Details must not regenerate them.
         */
        $desiredDates =
            $sheet->sessions()
                ->orderBy('session_date')
                ->orderBy('id')
                ->get()
                ->map(
                    fn (AttendanceSession $session): string =>
                        $session->session_date
                            ->format('Y-m-d')
                )
                ->unique()
                ->values()
                ->all();

        if ($desiredDates !== []) {
            $startDate =
                CarbonImmutable::parse(
                    $desiredDates[0]
                );

            $endDate =
                CarbonImmutable::parse(
                    $desiredDates[
                        count($desiredDates) - 1
                    ]
                );
        }
    }

    if (
        $desiredDates === []
        && $scheduleType
            !== AttendanceSheet::SCHEDULE_MANUAL
    ) {
        throw ValidationException::withMessages([
            'schedule_type' =>
                'No Session dates were found for this schedule.',
        ]);
    }

    /*
     * Work out which existing sessions would disappear
     * before making ANY database changes.
     */
    $existingSessions =
        $sheet->sessions()
            ->withCount([
                'records',
                'meetingResponses',
                'immichDetections',
            ])
            ->orderBy('session_date')
            ->get();

    $desiredDateLookup =
        collect($desiredDates)
            ->flip();

    $sessionsToRemove =
        $existingSessions
            ->filter(
                fn (AttendanceSession $session): bool =>
                    ! $desiredDateLookup->has(
                        $session->session_date
                            ->format('Y-m-d')
                    )
            );

    $protectedSessions =
        $sessionsToRemove
            ->filter(
                fn (AttendanceSession $session): bool =>
                    $session->records_count > 0
                    ||
                    $session->meeting_responses_count > 0
                    ||
                    $session->immich_detections_count > 0
            );

    if ($protectedSessions->isNotEmpty()) {
        $dates =
            $protectedSessions
                ->map(function (
                    AttendanceSession $session
                ): string {
                    $parts = [];

                    if (
                        $session->records_count > 0
                    ) {
                        $parts[] =
                            $session->records_count
                            . ' attendance';
                    }

                    if (
                        $session
                            ->meeting_responses_count > 0
                    ) {
                        $parts[] =
                            $session
                                ->meeting_responses_count
                            . ' pre-listed';
                    }

                    if (
                        $session
                            ->immich_detections_count > 0
                    ) {
                        $parts[] =
                            $session
                                ->immich_detections_count
                            . ' Immich';
                    }

                    return
                        $session->session_date
                            ->format('M d, Y')
                        . ' ('
                        . implode(', ', $parts)
                        . ')';
                })
                ->implode('; ');

        throw ValidationException::withMessages([
            'is_one_time' =>
                'The schedule cannot be changed because these meeting dates contain historical data: '
                . $dates
                . '. Historical attendance, pre-listed responses, and Immich records were preserved.',
        ]);
    }

    $oldValues = [
        'title' =>
            $sheet->title,

        'locality' =>
            $sheet->locality,

        'meeting_day' =>
            $sheet->meeting_day,

        'meeting_time' =>
            $sheet->meeting_time,

        'end_time' =>
            $sheet->end_time,

        'schedule_type' =>
            $sheet->schedule_type,

        'is_one_time' =>
            (bool) $sheet->is_one_time,

        'meeting_form_type' =>
            $sheet->meeting_form_type,

        'start_date' =>
            $sheet->start_date
                ?->format('Y-m-d'),

        'end_date' =>
            $sheet->end_date
                ?->format('Y-m-d'),

        'remarks' =>
            $sheet->remarks,
    ];

    $sessionsCreated = 0;
    $sessionsRemoved = 0;

    DB::transaction(
        function () use (
            $sheet,
            $data,
            $meetingTime,
            $endTime,
            $meetingDay,
            $scheduleType,
            $isOneTime,
            $startDate,
            $endDate,
            $desiredDates,
            $sessionsToRemove,
            $locality,
            &$sessionsCreated,
            &$sessionsRemoved,
        ): void {
            $sheet->forceFill([
                'title' =>
                    $data['title'],
                  'locality_id' =>
                      $locality?->id,

                  'locality' =>
                      $locality?->name,

                'meeting_day' =>
                    $meetingDay,

                'meeting_time' =>
                    $meetingTime,

                'end_time' =>
                    $endTime,

                'schedule_type' =>
                    $scheduleType,

                'is_one_time' =>
                    $isOneTime,

                'meeting_form_type' =>
                    $data['meeting_form_type'],

                'start_date' =>
                    $startDate?->toDateString(),

                'end_date' =>
                    $endDate?->toDateString(),

                'remarks' =>
                    blank($data['remarks'] ?? null)
                        ? null
                        : $data['remarks'],
            ])->save();

            /*
             * Remove only generated sessions that were
             * proven above to contain no historical data.
             */
            foreach (
                $sessionsToRemove
                as $session
            ) {
                $session->delete();

                $sessionsRemoved++;
            }

            /*
             * Preserve existing session IDs whenever the
             * date already exists. Create only missing dates.
             */
            foreach (
                $desiredDates
                as $date
            ) {
                $session =
                    AttendanceSession::query()
                        ->firstOrCreate(
                            [
                                'attendance_sheet_id' =>
                                    $sheet->id,

                                'session_date' =>
                                    $date,
                            ],
                            [
                                'session_time' =>
                                    $meetingTime,

                                'session_end_time' =>
                                    $endTime,

                                'title' =>
                                    $sheet->title
                                    . ' - '
                                    . CarbonImmutable::parse(
                                        $date
                                    )->format(
                                        'M d, Y'
                                    ),
                            ]
                        );

                if (
                    $session->wasRecentlyCreated
                ) {
                    $sessionsCreated++;
                }

                /*
                 * Existing sessions keep their IDs and
                 * history; only synchronize the meeting time.
                 */
                if (
                    $session->session_time
                        !== $meetingTime
                    || $session->session_end_time
                        !== $endTime
                ) {
                    $session->forceFill([
                        'session_time' =>
                            $meetingTime,

                        'session_end_time' =>
                            $endTime,
                    ])->save();
                }
            }

            /*
             * Existing public slugs remain stable.
             * Newly-created sessions receive one when
             * Normal Meeting Form is enabled.
             */
            $sheet->ensureMeetingFormSlugs();
        }
    );

    ActivityLogger::log(
        action:
            'attendance_sheet.updated',

        subject:
            $sheet,

        description:
            'Updated attendance sheet details and reconciled meeting schedule while preserving historical session data.',

        oldValues:
            $oldValues,

        newValues: [
            'title' =>
                $sheet->title,

            'locality' =>
                $sheet->locality,

            'meeting_day' =>
                $sheet->meeting_day,

            'meeting_time' =>
                $sheet->meeting_time,

            'end_time' =>
                $sheet->end_time,

            'schedule_type' =>
                $sheet->schedule_type,

            'is_one_time' =>
                (bool) $sheet->is_one_time,

            'meeting_form_type' =>
                $sheet->meeting_form_type,

            'start_date' =>
                $sheet->start_date
                    ?->format('Y-m-d'),

            'end_date' =>
                $sheet->end_date
                    ?->format('Y-m-d'),

            'remarks' =>
                $sheet->remarks,

            'sessions_created' =>
                $sessionsCreated,

            'empty_sessions_removed' =>
                $sessionsRemoved,
        ],
    );

    return back()->with(
        'attendance_sheet_updated',
        true
    );
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
        $dates[] =
            $current->toDateString();

        $current =
            $current->addWeek();
    }

    return $dates;
}

}
