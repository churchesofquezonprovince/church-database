<?php

namespace App\Http\Controllers;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceSheetRecordController extends Controller
{
    public function store(
        Request $request,
        AttendanceSession $session
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $data = $request->validate([
            'present_person_ids' => [
                'nullable',
                'array',
            ],

            'present_person_ids.*' => [
                'integer',
                'exists:persons,id',
            ],
        ]);

        $session->loadMissing(
            'sheet'
        );

        abort_unless(
            $session->sheet,
            404
        );

        abort_unless(
            $session->sheet->is_active,
            403
        );

        $sessionDate =
            $session
                ->session_date
                ->toDateString();

        $participants =
            AttendanceParticipant::query()
                ->where(
                    'attendance_sheet_id',
                    $session->attendance_sheet_id
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    function ($query) use (
                        $sessionDate
                    ): void {
                        $query
                            ->whereNull(
                                'starts_on'
                            )
                            ->orWhereDate(
                                'starts_on',
                                '<=',
                                $sessionDate
                            );
                    }
                )
                ->where(
                    function ($query) use (
                        $sessionDate
                    ): void {
                        $query
                            ->whereNull(
                                'ends_on'
                            )
                            ->orWhereDate(
                                'ends_on',
                                '>=',
                                $sessionDate
                            );
                    }
                )
                ->get()
                ->unique(
                    fn (
                        AttendanceParticipant $participant
                    ): int =>
                        (int) $participant->person_id
                )
                ->values();

        $presentPersonIds =
            collect(
                $data[
                    'present_person_ids'
                ] ?? []
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique();

        $presentCount = 0;
        $absentCount = 0;

        /*
         * Saving the checklist is an explicit administrator
         * decision for every participant shown for this Session.
         *
         * Therefore an existing Immich record becomes MANUAL.
         * A later Immich synchronization must not overwrite it.
         */
        DB::transaction(
            function () use (
                $session,
                $participants,
                $presentPersonIds,
                &$presentCount,
                &$absentCount,
            ): void {
                foreach (
                    $participants
                    as $participant
                ) {
                    $isPresent =
                        $presentPersonIds
                            ->contains(
                                (int)
                                $participant
                                    ->person_id
                            );

                    AttendanceRecord::query()
                        ->updateOrCreate(
                            [
                                'attendance_session_id' =>
                                    $session->id,

                                'person_id' =>
                                    $participant
                                        ->person_id,
                            ],
                            [
                                'status' =>
                                    $isPresent
                                        ? AttendanceRecord::STATUS_PRESENT
                                        : AttendanceRecord::STATUS_ABSENT,

                                'is_present' =>
                                    $isPresent,

                                'attendance_source' =>
                                    AttendanceRecord::SOURCE_MANUAL,

                                /*
                                 * This row is no longer awaiting
                                 * Immich review. Immich detections
                                 * remain preserved separately.
                                 */
                                'immich_confirmed' =>
                                    false,

                                'immich_confirmed_at' =>
                                    null,

                                'immich_confirmed_by_id' =>
                                    null,

                                'marked_by_id' =>
                                    auth()->id(),

                                'marked_at' =>
                                    now(),
                            ],
                        );

                    if ($isPresent) {
                        $presentCount++;
                    } else {
                        $absentCount++;
                    }
                }
            }
        );

        ActivityLogger::log(
            action:
                'attendance_sheet.records.saved',

            subject:
                $session,

            description:
                'Saved custom attendance records as manual administrator decisions.',

            newValues: [
                'attendance_sheet_id' =>
                    $session
                        ->attendance_sheet_id,

                'attendance_session_id' =>
                    $session->id,

                'session_date' =>
                    $sessionDate,

                'present_count' =>
                    $presentCount,

                'absent_count' =>
                    $absentCount,
            ],
        );

        return back()
            ->with(
                'attendance_records_saved',
                true
            )
            ->with(
                'attendance_present_count',
                $presentCount
            )
            ->with(
                'attendance_absent_count',
                $absentCount
            );
    }
}
