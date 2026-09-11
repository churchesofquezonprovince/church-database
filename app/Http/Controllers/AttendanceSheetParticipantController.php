<?php

namespace App\Http\Controllers;

use App\Models\AttendanceImmichAssetDetection;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceSheet;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        foreach ($personIds as $personId) {
            AttendanceParticipant::query()->updateOrCreate(
                [
                    'attendance_sheet_id' => $sheet->id,
                    'person_id' => $personId,
                ],
                [
                    'starts_on' => blank($data['starts_on'] ?? null) ? null : $data['starts_on'],
                    'ends_on' => blank($data['ends_on'] ?? null) ? null : $data['ends_on'],
                    'is_active' => true,
                ],
            );
        }

        ActivityLogger::log(
            action: 'attendance_sheet.participants.added',
            subject: $sheet,
            description: 'Added participant(s) to attendance sheet.',
            newValues: [
                'sheet_id' => $sheet->id,
                'sheet_title' => $sheet->title,
                'person_ids' => $personIds->all(),
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
            ],
        );

        return back()
            ->with('attendance_participants_saved', true)
            ->with('attendance_participants_added', $personIds->count())
            ->with('attendance_participants_updated', 0);
    }

public function destroy(
    Request $request,
    AttendanceSheet $sheet,
    AttendanceParticipant $participant
): RedirectResponse {
    abort_unless(auth()->user()?->canManageRecords(), 403);

    abort_unless(
        (int) $participant->attendance_sheet_id === (int) $sheet->id,
        404,
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

    /*
     * The Session comes from the Attendance Sheets page currently
     * being viewed. Never allow a Session belonging to another
     * Attendance Sheet to affect this removal.
     */
    if (
        $sessionId
        &&
        ! $sheet
            ->sessions()
            ->whereKey($sessionId)
            ->exists()
    ) {
        abort(404);
    }

    $oldValues = [
        'sheet_id' => $sheet->id,
        'sheet_title' => $sheet->title,
        'participant_id' => $participant->id,
        'person_id' => $participant->person_id,
        'starts_on' => optional($participant->starts_on)->format('Y-m-d'),
        'ends_on' => optional($participant->ends_on)->format('Y-m-d'),
        'is_active' => $participant->is_active,
        'attendance_session_id' => $sessionId,
    ];

    $correctedImmichAttendance = false;

    DB::transaction(
        function () use (
            $participant,
            $sessionId,
            &$correctedImmichAttendance,
        ): void {
            /*
             * Removing a Person from the Attendance Sheet
             * deactivates their roster membership.
             *
             * Keeping the row prevents historical AttendanceRecords
             * or a later Immich synchronization from silently
             * resurrecting somebody who was explicitly removed.
             *
             * Historical AttendanceRecord and Immich detection data must
             * remain intact.
             */
            $participant->update([
                'is_active' => false,
            ]);

            /*
             * If this Person was marked PRESENT by Immich for the
             * Session currently being viewed, removing them here is
             * treated as an administrator correction for THIS
             * Session only.
             *
             * Do not delete the AttendanceRecord or Immich detection
             * history. Convert the attendance fact to a manual ABSENT
             * decision so a later Immich sync cannot silently restore
             * the false-positive attendance.
             *
             * Attendance from every other Session is untouched.
             */
            if ($sessionId) {
                $record =
                    AttendanceRecord::query()
                        ->where(
                            'attendance_session_id',
                            $sessionId
                        )
                        ->where(
                            'person_id',
                            $participant->person_id
                        )
                        ->where(
                            'attendance_source',
                            AttendanceRecord::SOURCE_IMMICH
                        )
                        ->where(
                            'is_present',
                            true
                        )
                        ->first();

                if ($record) {
                    $record->update([
                        'status' =>
                            AttendanceRecord::STATUS_ABSENT,

                        'is_present' =>
                            false,

                        /*
                         * This is now an explicit administrator
                         * decision. Manual attendance wins over a
                         * later Immich synchronization.
                         */
                        'attendance_source' =>
                            AttendanceRecord::SOURCE_MANUAL,

                        'immich_confirmed' =>
                            true,

                        'immich_confirmed_at' =>
                            now(),

                        'immich_confirmed_by_id' =>
                            auth()->id(),

                        'marked_by_id' =>
                            auth()->id(),

                        'marked_at' =>
                            now(),
                    ]);

                    $correctedImmichAttendance =
                        true;
                }
            }
        },
    );

    ActivityLogger::log(
        action: 'attendance_sheet.participant.removed',
        subject: $sheet,
        description:
            $correctedImmichAttendance
                ? 'Removed participant from attendance sheet and corrected the selected Session Immich attendance to manual absent.'
                : 'Removed participant from attendance sheet while preserving historical attendance and Immich history.',
        oldValues: $oldValues,
        newValues: [
            'attendance_session_id' =>
                $sessionId,

            'selected_session_immich_attendance_corrected' =>
                $correctedImmichAttendance,
        ],
    );

    return back()
        ->with('attendance_participant_removed', true);
}

}
