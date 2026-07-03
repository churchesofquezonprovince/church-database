<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSheet;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceSheetMaintenanceController extends Controller
{
    public function update(Request $request, AttendanceSheet $sheet): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);
        abort_unless($sheet->sheet_type === AttendanceSheet::TYPE_CUSTOM, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'locality' => ['nullable', 'string', 'max:150'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'remarks' => ['nullable', 'string'],
        ]);

        $oldValues = [
            'title' => $sheet->title,
            'locality' => $sheet->locality,
            'meeting_time' => $sheet->meeting_time,
            'remarks' => $sheet->remarks,
        ];

        DB::transaction(function () use ($sheet, $data, $oldValues): void {
            $meetingTime = blank($data['meeting_time'] ?? null) ? null : $data['meeting_time'];

            $sheet->forceFill([
                'title' => $data['title'],
                'locality' => blank($data['locality'] ?? null) ? null : $data['locality'],
                'meeting_time' => $meetingTime,
                'remarks' => blank($data['remarks'] ?? null) ? null : $data['remarks'],
            ])->save();

            $sheet->sessions()->update([
                'session_time' => $meetingTime,
            ]);

            ActivityLogger::log(
                action: 'attendance_sheet.updated',
                subject: $sheet,
                description: 'Updated attendance sheet details.',
                oldValues: $oldValues,
                newValues: [
                    'title' => $sheet->title,
                    'locality' => $sheet->locality,
                    'meeting_time' => $sheet->meeting_time,
                    'remarks' => $sheet->remarks,
                ],
            );
        });

        return back()->with('attendance_sheet_updated', true);
    }

    public function toggleActive(AttendanceSheet $sheet): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);
        abort_unless($sheet->sheet_type === AttendanceSheet::TYPE_CUSTOM, 404);

        $oldStatus = (bool) $sheet->is_active;

        $sheet->forceFill([
            'is_active' => ! $oldStatus,
        ])->save();

        ActivityLogger::log(
            action: $sheet->is_active ? 'attendance_sheet.restored' : 'attendance_sheet.archived',
            subject: $sheet,
            description: $sheet->is_active
                ? 'Restored attendance sheet.'
                : 'Archived attendance sheet.',
            oldValues: [
                'is_active' => $oldStatus,
            ],
            newValues: [
                'is_active' => (bool) $sheet->is_active,
            ],
        );

        return back()->with(
            $sheet->is_active ? 'attendance_sheet_restored' : 'attendance_sheet_archived',
            true,
        );
    }
}
