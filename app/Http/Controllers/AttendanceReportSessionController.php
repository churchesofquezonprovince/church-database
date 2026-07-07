<?php

namespace App\Http\Controllers;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AttendanceReportSessionController extends Controller
{
    public function destroy(AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $attendanceSession->loadMissing('sheet');

        abort_unless($attendanceSession->sheet, 404);

        $sheet = $attendanceSession->sheet;
        $meetingDate = $attendanceSession->session_date->format('Y-m-d');

        $recordQuery = AttendanceRecord::query()
            ->where('attendance_session_id', $attendanceSession->id);

        $recordCount = (clone $recordQuery)->count();

        $personIds = (clone $recordQuery)
            ->whereNotNull('person_id')
            ->pluck('person_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($attendanceSession, $sheet, $meetingDate, $personIds, $recordCount): void {
            AttendanceRecord::query()
                ->where('attendance_session_id', $attendanceSession->id)
                ->delete();

            if (
                in_array($sheet->sheet_type, [
                    AttendanceSheet::TYPE_LORDS_TABLE,
                    AttendanceSheet::TYPE_PRAYER_MEETING,
                ], true)
                && $personIds !== []
            ) {
                AttendanceParticipant::query()
                    ->where('attendance_sheet_id', $sheet->id)
                    ->whereIn('person_id', $personIds)
                    ->whereDate('starts_on', $meetingDate)
                    ->whereDate('ends_on', $meetingDate)
                    ->delete();
            }

            $attendanceSession->delete();

            ActivityLogger::log(
                action: 'attendance_report.session.deleted',
                subject: $sheet,
                description: 'Deleted attendance session from Attendance Reports.',
                oldValues: [
                    'sheet_id' => $sheet->id,
                    'sheet_title' => $sheet->title,
                    'sheet_type' => $sheet->sheet_type,
                    'sheet_locality' => $sheet->locality,
                    'session_date' => $meetingDate,
                    'deleted_records' => $recordCount,
                ],
            );
        });

        return back()
            ->with('attendance_session_deleted', true)
            ->with('attendance_session_deleted_date', $meetingDate)
            ->with('attendance_session_deleted_records', $recordCount);
    }
}
