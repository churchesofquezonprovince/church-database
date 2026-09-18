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
