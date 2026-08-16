<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSheet;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function update(Request $request, AttendanceSheet $sheet): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'locality' => ['nullable', 'string', 'max:150'],
            'meeting_day' => ['nullable', 'integer', 'between:0,6'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $oldValues = $sheet->only([
            'title',
            'locality',
            'meeting_day',
            'meeting_time',
            'start_date',
            'end_date',
        ]);

        $sheet->forceFill([
            'title' => $data['title'],
            'locality' => blank($data['locality'] ?? null) ? null : $data['locality'],
            'meeting_day' => $data['meeting_day'] ?? $sheet->meeting_day,
            'meeting_time' => blank($data['meeting_time'] ?? null) ? null : $data['meeting_time'],
            'start_date' => blank($data['start_date'] ?? null) ? null : $data['start_date'],
            'end_date' => blank($data['end_date'] ?? null) ? null : $data['end_date'],
        ])->save();

        ActivityLogger::log(
            action: 'attendance_sheet.updated',
            subject: $sheet,
            description: 'Updated attendance sheet details.',
            oldValues: $oldValues,
            newValues: $sheet->only([
                'title',
                'locality',
                'meeting_day',
                'meeting_time',
                'start_date',
                'end_date',
            ]),
        );

        return back()->with('attendance_sheet_updated', true);
    }

}
