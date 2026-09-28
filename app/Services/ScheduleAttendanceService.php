<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Schedule;
use App\Support\LocalityOptions;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleAttendanceService
{
    public function generate(
        Schedule $schedule,
        ?int $userId = null,
        ?int $attendanceSheetId = null
    ): AttendanceSession {
        $start = CarbonImmutable::parse(
            $schedule->starts_at
        );

        $locality = filled($schedule->locality_id)
            ? LocalityOptions::activeConfiguredLocality(
                (int) $schedule->locality_id
            )
            : LocalityOptions::activeConfiguredLocalityByName(
                $schedule->locality
            );

        if (! $locality) {
            throw ValidationException::withMessages([
                'locality_id' =>
                    'Select an active configured Locality before generating Attendance.',
            ]);
        }

        /*
         * Canonicalize legacy text-only Schedules.
         */
        if (
            (int) $schedule->locality_id
                !== (int) $locality->id
            || $schedule->locality
                !== $locality->name
        ) {
            $schedule->forceFill([
                'locality_id' =>
                    $locality->id,

                'locality' =>
                    $locality->name,
            ])->saveQuietly();
        }

        $meetingTime =
            $schedule->is_all_day
                ? null
                : $start->format('H:i');

        $end = $schedule->ends_at
            ? CarbonImmutable::parse(
                $schedule->ends_at
            )
            : null;

        /*
         * Attendance Session stores only an end TIME,
         * therefore use Ends At only when it belongs to the
         * same calendar date.
         */
        $endTime =
            ! $schedule->is_all_day
            && $end
            && $end->isSameDay($start)
                ? $end->format('H:i')
                : null;

        [$sheetType, $masterSheetTitle] =
            $this->attendanceSheetIdentity(
                $schedule
            );

        /*
         * Locality ID is canonical.
         *
         * The legacy locality text remains synchronized by the
         * AttendanceSheet model.
         */
        $sheet = $this->resolveAttendanceSheet(
            schedule: $schedule,
            locality: $locality,
            sheetType: $sheetType,
            masterSheetTitle: $masterSheetTitle,
            start: $start,
            meetingTime: $meetingTime,
            endTime: $endTime,
            userId: $userId,
            attendanceSheetId: $attendanceSheetId,
        );

        /*
         * -------------------------------------------------------
         * Find or adopt the Attendance Session.
         * -------------------------------------------------------
         *
         * There can be two different Sessions involved:
         *
         * 1. the Session already linked to this Schedule;
         * 2. the Session already present on a user-selected
         *    Attendance Sheet for this same date.
         *
         * When the user explicitly selects an existing Sheet,
         * its same-date Session is the intended target.
         */

        $linkedSession =
            AttendanceSession::query()
                ->where(
                    'schedule_id',
                    $schedule->id
                )
                ->first();

        $targetSession =
            AttendanceSession::query()
                ->where(
                    'attendance_sheet_id',
                    $sheet->id
                )
                ->whereDate(
                    'session_date',
                    $start->toDateString()
                )
                ->first();

        $relinkFrom = null;

        if (filled($attendanceSheetId)) {
            /*
             * The picker only lists Sheets which have a Session
             * on this date. If it vanished meanwhile, stop rather
             * than manufacturing a different Session.
             */
            if (! $targetSession) {
                throw ValidationException::withMessages([
                    'attendance_sheet_id' =>
                        'The selected Attendance Sheet no longer has a Session on this Schedule date. Please reopen Generate Attendance and choose again.',
                ]);
            }

            /*
             * Never steal a Session belonging to another
             * Schedule.
             */
            if (
                filled($targetSession->schedule_id)
                && (int) $targetSession->schedule_id
                    !== (int) $schedule->id
            ) {
                throw ValidationException::withMessages([
                    'attendance_sheet_id' =>
                        'The selected Attendance Session is already linked to another Schedule.',
                ]);
            }

            /*
             * If this Schedule was previously generated into a
             * different Sheet, preserve that old Session but
             * detach its Schedule link.
             *
             * The selected existing Session then becomes the
             * Schedule-linked Session.
             */
            if (
                $linkedSession
                && (int) $linkedSession->id
                    !== (int) $targetSession->id
            ) {
                $relinkFrom =
                    $linkedSession;
            }

            $session =
                $targetSession;
        } else {
            /*
             * No explicit Sheet choice:
             * preserve the existing direct Schedule link first.
             */
            $session =
                $linkedSession;

            /*
             * Otherwise adopt a same-date Session from the
             * automatically matched Sheet.
             */
            if (
                ! $session
                && $targetSession
            ) {
                if (
                    filled(
                        $targetSession->schedule_id
                    )
                    && (int) $targetSession->schedule_id
                        !== (int) $schedule->id
                ) {
                    throw ValidationException::withMessages([
                        'attendance_session' =>
                            'The matching Attendance Session is already linked to another Schedule.',
                    ]);
                }

                $session =
                    $targetSession;
            }

            /*
             * Nothing exists yet: create a fresh Session.
             */
            $session ??=
                new AttendanceSession();
        }

        $session->fill([
            'attendance_sheet_id' =>
                $sheet->id,

            'schedule_id' =>
                $schedule->id,

            'session_date' =>
                $start->toDateString(),

            'session_time' =>
                $meetingTime,

            'session_end_time' =>
                $endTime,

            'title' =>
                $schedule->title
                . ' - '
                . $start->format('M d, Y'),

            'location' =>
                filled($schedule->location)
                    ? trim(
                        (string) $schedule->location
                    )
                    : null,

            'remarks' =>
                filled($schedule->description)
                    ? trim(
                        (string) $schedule->description
                    )
                    : null,
        ]);

        if ($relinkFrom) {
            DB::transaction(
                function () use (
                    $relinkFrom,
                    $session
                ): void {
                    /*
                     * Preserve the former Session and all of its
                     * Attendance data. Only remove its Schedule
                     * relationship.
                     */
                    $relinkFrom
                        ->forceFill([
                            'schedule_id' => null,
                        ])
                        ->save();

                    /*
                     * Link/update the user-selected existing
                     * same-date Session.
                     */
                    $session->save();
                }
            );
        } else {
            $session->save();
        }

        return $session;
    }

    public function attendanceSheetOptions(
        Schedule $schedule
    ): array {
        $locality = filled($schedule->locality_id)
            ? LocalityOptions::activeConfiguredLocality(
                (int) $schedule->locality_id
            )
            : LocalityOptions::activeConfiguredLocalityByName(
                $schedule->locality
            );

        if (
            ! $locality
            || blank($schedule->starts_at)
        ) {
            return [];
        }

        [$sheetType, $masterSheetTitle] =
            $this->attendanceSheetIdentity(
                $schedule
            );

        $scheduleDate =
            CarbonImmutable::parse(
                $schedule->starts_at
            )->toDateString();

        /*
         * Only show Attendance Sheets which already have an
         * Attendance Session on this Schedule's exact date.
         *
         * Also hide a Session when it is already linked to
         * another Schedule.
         */
        return AttendanceSheet::query()
            ->where(
                'sheet_type',
                $sheetType
            )
            ->whereHas(
                'sessions',
                function ($query) use (
                    $schedule,
                    $scheduleDate
                ): void {
                    $query
                        ->whereDate(
                            'session_date',
                            $scheduleDate
                        )
                        ->where(function ($query) use (
                            $schedule
                        ): void {
                            $query
                                ->whereNull(
                                    'schedule_id'
                                )
                                ->orWhere(
                                    'schedule_id',
                                    $schedule->id
                                );
                        });
                }
            )
            ->where(function ($query) use (
                $locality,
                $masterSheetTitle
            ): void {
                /*
                 * Canonical same-Locality Sheet.
                 */
                $query->where(
                    'locality_id',
                    $locality->id
                );

                /*
                 * Legacy Sheet with matching Locality text.
                 */
                $query->orWhere(function ($query) use (
                    $locality
                ): void {
                    $query
                        ->whereNull(
                            'locality_id'
                        )
                        ->whereRaw(
                            'LOWER(TRIM(locality)) = ?',
                            [
                                mb_strtolower(
                                    trim(
                                        $locality->name
                                    )
                                ),
                            ]
                        );
                });

                /*
                 * Legacy Sheet with no Locality may still be
                 * offered when its title exactly matches the
                 * Schedule's expected master Sheet title.
                 */
                $query->orWhere(function ($query) use (
                    $masterSheetTitle
                ): void {
                    $query
                        ->whereNull(
                            'locality_id'
                        )
                        ->where(function ($query): void {
                            $query
                                ->whereNull(
                                    'locality'
                                )
                                ->orWhere(
                                    'locality',
                                    ''
                                );
                        })
                        ->where(
                            'title',
                            $masterSheetTitle
                        );
                });
            })
            ->with([
                'sessions' =>
                    function ($query) use (
                        $schedule,
                        $scheduleDate
                    ): void {
                        $query
                            ->whereDate(
                                'session_date',
                                $scheduleDate
                            )
                            ->where(function ($query) use (
                                $schedule
                            ): void {
                                $query
                                    ->whereNull(
                                        'schedule_id'
                                    )
                                    ->orWhere(
                                        'schedule_id',
                                        $schedule->id
                                    );
                            })
                            ->orderBy(
                                'session_time'
                            );
                    },
            ])
            ->orderBy(
                'title'
            )
            ->get()
            ->mapWithKeys(
                function (
                    AttendanceSheet $sheet
                ) use (
                    $masterSheetTitle,
                    $scheduleDate
                ): array {
                    $session =
                        $sheet->sessions->first();

                    $time =
                        filled(
                            $session?->session_time
                        )
                            ? CarbonImmutable::parse(
                                $scheduleDate
                                . ' '
                                . $session->session_time
                            )->format('h:i A')
                            : 'No time';

                    $locality =
                        filled($sheet->locality)
                            ? $sheet->locality
                            : 'No Locality';

                    $suggested =
                        strcasecmp(
                            $sheet->title,
                            $masterSheetTitle
                        ) === 0;

                    $prefix =
                        $suggested
                            ? 'Suggested'
                            : 'Existing';

                    return [
                        $sheet->id =>
                            $prefix
                            . ' — #'
                            . $sheet->id
                            . ' '
                            . $sheet->title
                            . ' — '
                            . CarbonImmutable::parse(
                                $scheduleDate
                            )->format(
                                'M d, Y'
                            )
                            . ' — '
                            . $time
                            . ' — '
                            . $locality,
                    ];
                }
            )
            ->all();
    }

    private function resolveAttendanceSheet(
        Schedule $schedule,
        $locality,
        string $sheetType,
        string $masterSheetTitle,
        CarbonImmutable $start,
        ?string $meetingTime,
        ?string $endTime,
        ?int $userId,
        ?int $attendanceSheetId
    ): AttendanceSheet {
        /*
         * Explicit user-selected Sheet wins.
         */
        if (filled($attendanceSheetId)) {
            $sheet = AttendanceSheet::query()
                ->find(
                    (int) $attendanceSheetId
                );

            if (! $sheet) {
                throw ValidationException::withMessages([
                    'attendance_sheet_id' =>
                        'The selected Attendance Sheet no longer exists.',
                ]);
            }

            if (
                $sheet->sheet_type
                !== $sheetType
            ) {
                throw ValidationException::withMessages([
                    'attendance_sheet_id' =>
                        'The selected Attendance Sheet has a different meeting type.',
                ]);
            }

            if (
                filled($sheet->locality_id)
                && (int) $sheet->locality_id
                    !== (int) $locality->id
            ) {
                throw ValidationException::withMessages([
                    'attendance_sheet_id' =>
                        'The selected Attendance Sheet belongs to a different Locality.',
                ]);
            }

            if (
                blank($sheet->locality_id)
                && filled($sheet->locality)
                && mb_strtolower(
                    trim($sheet->locality)
                )
                    !==
                mb_strtolower(
                    trim($locality->name)
                )
            ) {
                throw ValidationException::withMessages([
                    'attendance_sheet_id' =>
                        'The selected Attendance Sheet has a different Locality.',
                ]);
            }

            $sheet->forceFill([
                'locality_id' =>
                    $locality->id,

                'locality' =>
                    $locality->name,
            ])->save();

            return $sheet;
        }

        /*
         * Exact canonical title + Locality.
         */
        $matches = AttendanceSheet::query()
            ->where(
                'title',
                $masterSheetTitle
            )
            ->where(
                'sheet_type',
                $sheetType
            )
            ->where(
                'locality_id',
                $locality->id
            )
            ->get();

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'attendance_sheet' =>
                    'More than one canonical Attendance Sheet matches this Schedule.',
            ]);
        }

        if ($matches->isNotEmpty()) {
            return $matches->first();
        }

        /*
         * Lord's Table / Prayer Meeting Sheets often have the
         * Locality appended to their title. Match by type +
         * canonical Locality instead of requiring exact title.
         */
        if (
            in_array(
                $sheetType,
                [
                    AttendanceSheet::TYPE_LORDS_TABLE,
                    AttendanceSheet::TYPE_PRAYER_MEETING,
                ],
                true
            )
        ) {
            $matches = AttendanceSheet::query()
                ->where(
                    'sheet_type',
                    $sheetType
                )
                ->where(
                    'locality_id',
                    $locality->id
                )
                ->get();

            if ($matches->count() > 1) {
                throw ValidationException::withMessages([
                    'attendance_sheet' =>
                        'More than one Attendance Sheet of this meeting type exists for the Locality. Select the Sheet manually.',
                ]);
            }

            if ($matches->isNotEmpty()) {
                return $matches->first();
            }
        }

        /*
         * Legacy/manual exact-title Sheet with matching or blank
         * Locality.
         */
        $legacyMatches = AttendanceSheet::query()
            ->where(
                'title',
                $masterSheetTitle
            )
            ->where(
                'sheet_type',
                $sheetType
            )
            ->whereNull('locality_id')
            ->where(function ($query) use (
                $locality
            ): void {
                $query
                    ->whereNull('locality')
                    ->orWhere(
                        'locality',
                        ''
                    )
                    ->orWhereRaw(
                        'LOWER(TRIM(locality)) = ?',
                        [
                            mb_strtolower(
                                trim($locality->name)
                            ),
                        ]
                    );
            })
            ->get();

        if ($legacyMatches->count() > 1) {
            throw ValidationException::withMessages([
                'attendance_sheet' =>
                    'More than one legacy Attendance Sheet matches this Schedule. Select the Sheet manually.',
            ]);
        }

        if ($legacyMatches->isNotEmpty()) {
            $sheet = $legacyMatches->first();

            $sheet->forceFill([
                'locality_id' =>
                    $locality->id,

                'locality' =>
                    $locality->name,
            ])->save();

            return $sheet;
        }

        /*
         * Nothing suitable exists: create the master Sheet.
         */
        return AttendanceSheet::query()
            ->create([
                'title' =>
                    $masterSheetTitle,

                'sheet_type' =>
                    $sheetType,

                'locality_id' =>
                    $locality->id,

                'locality' =>
                    $locality->name,

                'meeting_day' =>
                    $start->dayOfWeek,

                'meeting_time' =>
                    $meetingTime,

                'end_time' =>
                    $endTime,

                'is_one_time' =>
                    false,

                'is_active' =>
                    true,

                'created_by_id' =>
                    $userId ?? 1,
            ]);
    }

    private function attendanceSheetIdentity(
        Schedule $schedule
    ): array {
        if (
            $schedule->category
                === "Lord's Table"
        ) {
            return [
                AttendanceSheet::TYPE_LORDS_TABLE,
                "Lord's Table Meeting",
            ];
        }

        if (
            $schedule->category
                === 'Prayer Meeting'
        ) {
            return [
                AttendanceSheet::TYPE_PRAYER_MEETING,
                'Prayer Meeting',
            ];
        }

        return [
            AttendanceSheet::TYPE_CUSTOM,
            $schedule->title,
        ];
    }
}
