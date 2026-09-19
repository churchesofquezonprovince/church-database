<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceSheet;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceSheetParticipantController extends Controller
{
    public function store(Request $request, AttendanceSheet $sheet): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'person_ids' => ['required', 'array', 'min:1'],
            'person_ids.*' => ['integer', 'exists:persons,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        $personIds = collect($data['person_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $startsOn =
            blank($data['starts_on'] ?? null)
                ? null
                : $data['starts_on'];

        $endsOn =
            blank($data['ends_on'] ?? null)
                ? null
                : $data['ends_on'];

        $added = 0;
        $reactivated = 0;
        $unchanged = 0;

        DB::transaction(
            function () use (
                $sheet,
                $personIds,
                $startsOn,
                $endsOn,
                &$added,
                &$reactivated,
                &$unchanged,
            ): void {
                foreach ($personIds as $personId) {
                    /*
                     * AttendanceParticipant is a dated membership
                     * period. Do not update an unrelated historical
                     * period merely because Sheet + Person match.
                     *
                     * Reuse only the exact requested period.
                     */
                    $matchingPeriod =
                        AttendanceParticipant::query()
                            ->where(
                                'attendance_sheet_id',
                                $sheet->id
                            )
                            ->where(
                                'person_id',
                                $personId
                            )
                            ->when(
                                $startsOn === null,
                                fn ($query) =>
                                    $query->whereNull(
                                        'starts_on'
                                    ),
                                fn ($query) =>
                                    $query->whereDate(
                                        'starts_on',
                                        $startsOn
                                    )
                            )
                            ->when(
                                $endsOn === null,
                                fn ($query) =>
                                    $query->whereNull(
                                        'ends_on'
                                    ),
                                fn ($query) =>
                                    $query->whereDate(
                                        'ends_on',
                                        $endsOn
                                    )
                            )
                            ->orderByDesc('is_active')
                            ->first();

                    if ($matchingPeriod) {
                        if (! $matchingPeriod->is_active) {
                            $matchingPeriod->update([
                                'is_active' => true,
                            ]);

                            $reactivated++;
                        } else {
                            $unchanged++;
                        }

                        continue;
                    }

                    AttendanceParticipant::create([
                        'attendance_sheet_id' =>
                            $sheet->id,

                        'person_id' =>
                            $personId,

                        'starts_on' =>
                            $startsOn,

                        'ends_on' =>
                            $endsOn,

                        'is_active' =>
                            true,
                    ]);

                    $added++;
                }
            }
        );

        ActivityLogger::log(
            action: 'attendance_sheet.participants.added',
            subject: $sheet,
            description: 'Added participant membership period(s) to attendance sheet.',
            newValues: [
                'sheet_id' => $sheet->id,
                'sheet_title' => $sheet->title,
                'person_ids' => $personIds->all(),
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'added' => $added,
                'reactivated' => $reactivated,
                'unchanged' => $unchanged,
            ],
        );

        return back()
            ->with(
                'attendance_participants_saved',
                true
            )
            ->with(
                'attendance_participants_added',
                $added
            )
            ->with(
                'attendance_participants_updated',
                $reactivated
            );
    }

public function destroyAll(
    Request $request,
    AttendanceSheet $sheet
): RedirectResponse {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    $data = $request->validate([
        'attendance_session_id' => [
            'required',
            'integer',
            'exists:attendance_sessions,id',
        ],
    ]);

    $session =
        $sheet->sessions()
            ->whereKey(
                (int) $data['attendance_session_id']
            )
            ->first();

    abort_unless(
        $session,
        404
    );

    $sessionDate =
        $session
            ->session_date
            ->format('Y-m-d');

    /*
     * Remove All means:
     *
     * Remove every Participant currently shown for this selected
     * Session date.
     *
     * This intentionally follows the same attendance-safety rules
     * as the individual Remove action below.
     *
     * The operation is all-or-nothing. If any Person has durable
     * manual Present/Late attendance for this Session, nobody is
     * removed until that attendance is cleared first.
     */
    $result =
        DB::transaction(
            function () use (
                $sheet,
                $session,
                $sessionDate
            ): array {
                /*
                 * participantRows() displays unique People active
                 * on this date.
                 *
                 * Fetch every active membership period covering
                 * the date so an accidental overlapping period
                 * cannot leave the Person on the roster after
                 * Remove All.
                 */
                $participants =
                    AttendanceParticipant::query()
                        ->with([
                            'person',
                        ])
                        ->where(
                            'attendance_sheet_id',
                            $sheet->id
                        )
                        ->activeOn(
                            $sessionDate
                        )
                        ->lockForUpdate()
                        ->get();

                if ($participants->isEmpty()) {
                    return [
                        'people_count' => 0,
                        'period_count' => 0,
                        'person_ids' => [],
                        'participant_period_ids' => [],
                        'non_present_records_removed' => 0,
                        'immich_present_records_removed' => 0,
                    ];
                }

                $personIds =
                    $participants
                        ->pluck(
                            'person_id'
                        )
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        )
                        ->unique()
                        ->values();

                /*
                 * AttendanceRecord is keyed by Session + Person.
                 *
                 * Only the currently selected Session is inspected
                 * or changed here.
                 */
                $records =
                    AttendanceRecord::query()
                        ->where(
                            'attendance_session_id',
                            $session->id
                        )
                        ->whereIn(
                            'person_id',
                            $personIds->all()
                        )
                        ->lockForUpdate()
                        ->get();

                $isAttended =
                    static function (
                        AttendanceRecord $record
                    ): bool {
                        return
                            (bool) $record->is_present
                            ||
                            in_array(
                                (string) $record->status,
                                [
                                    AttendanceRecord::STATUS_PRESENT,
                                    AttendanceRecord::STATUS_LATE,
                                ],
                                true
                            );
                    };

                /*
                 * Match the existing single Remove rule:
                 *
                 * manual Present/Late is durable history and blocks
                 * removal.
                 *
                 * Immich Present may be removed because removing the
                 * participant is also the explicit false-positive
                 * correction workflow.
                 */
                $blockingRecords =
                    $records
                        ->filter(
                            fn (
                                AttendanceRecord $record
                            ): bool =>
                                $isAttended(
                                    $record
                                )
                                &&
                                $record
                                    ->attendance_source
                                !==
                                AttendanceRecord::SOURCE_IMMICH
                        );

                if ($blockingRecords->isNotEmpty()) {
                    $blockingNames =
                        $blockingRecords
                            ->map(
                                function (
                                    AttendanceRecord $record
                                ) use (
                                    $participants
                                ): string {
                                    $participant =
                                        $participants
                                            ->first(
                                                fn (
                                                    AttendanceParticipant $participant
                                                ): bool =>
                                                    (int)
                                                        $participant
                                                            ->person_id
                                                    ===
                                                    (int)
                                                        $record
                                                            ->person_id
                                            );

                                    $name =
                                        $participant
                                            ?->person
                                            ?->display_name;

                                    return filled(
                                        $name
                                    )
                                        ? (string) $name
                                        : 'Person #'
                                            . $record
                                                ->person_id;
                                }
                            )
                            ->unique()
                            ->sort()
                            ->values();

                    $visibleNames =
                        $blockingNames
                            ->take(
                                10
                            )
                            ->implode(
                                ', '
                            );

                    $remaining =
                        max(
                            $blockingNames->count()
                            - 10,
                            0
                        );

                    if ($remaining > 0) {
                        $visibleNames .=
                            ' and '
                            . $remaining
                            . ' more';
                    }

                    throw ValidationException::withMessages([
                        'attendance_participants' =>
                            'Remove All was not completed. '
                            . 'The following participant(s) are '
                            . 'marked Present or Late manually for '
                            . $session
                                ->session_date
                                ->format('M d, Y')
                            . ': '
                            . $visibleNames
                            . '. Clear their attendance in '
                            . 'Check Attendance, Save Attendance, '
                            . 'then try Remove All again.',
                    ]);
                }

                $immichPresentRecords =
                    $records
                        ->filter(
                            fn (
                                AttendanceRecord $record
                            ): bool =>
                                $isAttended(
                                    $record
                                )
                                &&
                                $record
                                    ->attendance_source
                                ===
                                AttendanceRecord::SOURCE_IMMICH
                        );

                $nonPresentRecords =
                    $records
                        ->reject(
                            fn (
                                AttendanceRecord $record
                            ): bool =>
                                $isAttended(
                                    $record
                                )
                        );

                $participantIds =
                    $participants
                        ->pluck(
                            'id'
                        )
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        )
                        ->values();

                AttendanceParticipant::query()
                    ->whereIn(
                        'id',
                        $participantIds->all()
                    )
                    ->update([
                        'is_active' => false,
                    ]);

                $recordIds =
                    $records
                        ->pluck(
                            'id'
                        )
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        )
                        ->values();

                if ($recordIds->isNotEmpty()) {
                    AttendanceRecord::query()
                        ->whereIn(
                            'id',
                            $recordIds->all()
                        )
                        ->delete();
                }

                return [
                    'people_count' =>
                        $personIds->count(),

                    'period_count' =>
                        $participantIds->count(),

                    'person_ids' =>
                        $personIds->all(),

                    'participant_period_ids' =>
                        $participantIds->all(),

                    'non_present_records_removed' =>
                        $nonPresentRecords
                            ->count(),

                    'immich_present_records_removed' =>
                        $immichPresentRecords
                            ->count(),
                ];
            }
        );

    if (
        $result['people_count']
        > 0
    ) {
        ActivityLogger::log(
            /*
             * Reuse the existing participant-removal history action
             * so bulk removal remains visible under the existing
             * "Participants Removed" Attendance History filter.
             */
            action:
                'attendance_sheet.participant.removed',

            subject:
                $sheet,

            description:
                'Removed all participants shown for the selected Session while preserving attendance history from other Sessions.',

            oldValues: [
                'sheet_id' =>
                    $sheet->id,

                'sheet_title' =>
                    $sheet->title,

                'attendance_session_id' =>
                    $session->id,

                'session_date' =>
                    $sessionDate,

                'participants_count' =>
                    $result[
                        'people_count'
                    ],

                'person_ids' =>
                    $result[
                        'person_ids'
                    ],

                'participant_period_ids' =>
                    $result[
                        'participant_period_ids'
                    ],
            ],

            newValues: [
                'bulk_remove_all' =>
                    true,

                'participants_count' =>
                    $result[
                        'people_count'
                    ],

                'participant_periods_deactivated' =>
                    $result[
                        'period_count'
                    ],

                'selected_session_non_present_records_removed' =>
                    $result[
                        'non_present_records_removed'
                    ],

                'selected_session_immich_present_records_removed' =>
                    $result[
                        'immich_present_records_removed'
                    ],

                'attendance_from_other_sessions_preserved' =>
                    true,

                'immich_detection_history_preserved' =>
                    true,
            ],
        );
    }

    return back()
        ->with(
            'attendance_participants_removed_all',
            true
        )
        ->with(
            'attendance_participants_removed_all_count',
            $result[
                'people_count'
            ]
        )
        ->with(
            'attendance_participants_removed_all_period_count',
            $result[
                'period_count'
            ]
        )
        ->with(
            'attendance_participants_removed_all_records',
            $result[
                'non_present_records_removed'
            ]
        )
        ->with(
            'attendance_participants_removed_all_immich',
            $result[
                'immich_present_records_removed'
            ]
        )
        ->with(
            'attendance_participants_removed_all_date',
            $session
                ->session_date
                ->format('M d, Y')
        );
}

public function destroy(
    Request $request,
    AttendanceSheet $sheet,
    AttendanceParticipant $participant
): RedirectResponse {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    abort_unless(
        (int) $participant->attendance_sheet_id
        === (int) $sheet->id,
        404
    );

    $data = $request->validate([
        'attendance_session_id' => [
            'nullable',
            'integer',
            'exists:attendance_sessions,id',
        ],
    ]);

    $sessionId =
        filled($data['attendance_session_id'] ?? null)
            ? (int) $data['attendance_session_id']
            : null;

    $session = null;

    if ($sessionId) {
        $session =
            $sheet->sessions()
                ->whereKey($sessionId)
                ->first();

        abort_unless(
            $session,
            404
        );
    }

    $attendanceRecord = null;

    if ($session) {
        $attendanceRecord =
            AttendanceRecord::query()
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->where(
                    'person_id',
                    $participant->person_id
                )
                ->first();
    }

    $isAttended =
        $attendanceRecord
        && (
            (bool) $attendanceRecord->is_present
            || in_array(
                (string) $attendanceRecord->status,
                [
                    'present',
                    'late',
                ],
                true
            )
        );

    $isImmichPresent =
        $isAttended
        && $attendanceRecord->attendance_source
            === AttendanceRecord::SOURCE_IMMICH;

    /*
     * A manual Present/Late record is durable attendance history.
     *
     * Removing a Participant must not silently erase a real
     * administrator-confirmed attendance fact.
     *
     * Immich Present remains removable because that action is
     * also our explicit false-positive correction workflow.
     */
    if (
        $isAttended
        && ! $isImmichPresent
    ) {
        throw ValidationException::withMessages([
            'attendance_participant' =>
                'This participant is marked Present for '
                . $session->session_date->format('M d, Y')
                . '. Clear their attendance in Check Attendance '
                . 'and Save Attendance before removing them '
                . 'from Participants.',
        ]);
    }

    $oldValues = [
        'sheet_id' =>
            $sheet->id,

        'sheet_title' =>
            $sheet->title,

        'participant_id' =>
            $participant->id,

        'person_id' =>
            $participant->person_id,

        'starts_on' =>
            optional(
                $participant->starts_on
            )->format('Y-m-d'),

        'ends_on' =>
            optional(
                $participant->ends_on
            )->format('Y-m-d'),

        'is_active' =>
            (bool) $participant->is_active,

        'attendance_session_id' =>
            $sessionId,

        'selected_session_attendance_status' =>
            $attendanceRecord?->status,

        'selected_session_attendance_source' =>
            $attendanceRecord?->attendance_source,

        'selected_session_is_present' =>
            $attendanceRecord
                ? (bool) $attendanceRecord->is_present
                : null,
    ];

    $removedNonPresentAttendance = false;
    $removedImmichPresent = false;

    DB::transaction(
        function () use (
            $participant,
            $attendanceRecord,
            $isImmichPresent,
            &$removedNonPresentAttendance,
            &$removedImmichPresent,
        ): void {
            $participant->update([
                'is_active' => false,
            ]);

            if (! $attendanceRecord) {
                return;
            }

            /*
             * Immich false-positive correction:
             *
             * remove this selected Session's generated attendance
             * fact, while AttendanceImmichAssetDetection remains
             * untouched as historical detection evidence.
             */
            if ($isImmichPresent) {
                $attendanceRecord->delete();

                $removedImmichPresent = true;

                return;
            }

            /*
             * Absent / Excused / other non-present state is
             * roster-dependent.
             *
             * Once removed from Participants, this selected
             * Session record should disappear as well.
             */
            $attendanceRecord->delete();

            $removedNonPresentAttendance = true;
        }
    );

    ActivityLogger::log(
        action:
            'attendance_sheet.participant.removed',

        subject:
            $sheet,

        description:
            $removedImmichPresent
                ? 'Removed participant and rejected the selected Session Immich attendance while preserving Immich detection history.'
                : (
                    $removedNonPresentAttendance
                        ? 'Removed participant and cleared the selected Session non-present attendance record.'
                        : 'Removed participant while preserving attendance history from other Sessions.'
                ),

        oldValues:
            $oldValues,

        newValues: [
            'attendance_session_id' =>
                $sessionId,

            'selected_session_non_present_record_removed' =>
                $removedNonPresentAttendance,

            'selected_session_immich_present_removed' =>
                $removedImmichPresent,

            'participant_is_active' =>
                false,
        ],
    );

    return back()
        ->with(
            'attendance_participant_removed',
            true
        )
        ->with(
            'attendance_participant_removed_record',
            $removedNonPresentAttendance
        )
        ->with(
            'attendance_participant_removed_immich',
            $removedImmichPresent
        );
}
}
