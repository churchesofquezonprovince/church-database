<?php

namespace App\Http\Controllers;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceSheetRecordController extends Controller
{
    public function store(Request $request, AttendanceSession $session): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'present_person_ids' => ['nullable', 'array'],
            'present_person_ids.*' => ['integer', 'exists:persons,id'],
        ]);

        $session->loadMissing('sheet');

        abort_unless($session->sheet, 404);

        $sessionDate = $session->session_date->toDateString();

        $participants = AttendanceParticipant::query()
            ->where('attendance_sheet_id', $session->attendance_sheet_id)
            ->where('is_active', true)
            ->where(function ($query) use ($sessionDate): void {
                $query
                    ->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $sessionDate);
            })
            ->where(function ($query) use ($sessionDate): void {
                $query
                    ->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $sessionDate);
            })
            ->get();

        $presentPersonIds = collect($data['present_person_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique();

        $presentStatus = defined(AttendanceRecord::class . '::STATUS_PRESENT')
            ? AttendanceRecord::STATUS_PRESENT
            : 'present';

        $absentStatus = defined(AttendanceRecord::class . '::STATUS_ABSENT')
            ? AttendanceRecord::STATUS_ABSENT
            : 'absent';

        $presentCount = 0;
        $absentCount = 0;

        foreach ($participants as $participant) {
            $isPresent = $presentPersonIds->contains((int) $participant->person_id);

            AttendanceRecord::query()->updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'person_id' => $participant->person_id,
                ],
                [
                    'status' => $isPresent ? $presentStatus : $absentStatus,
                    'is_present' => $isPresent,
                    'marked_by_id' => auth()->id(),
                    'marked_at' => now(),
                ],
            );

            if ($isPresent) {
                $presentCount++;
            } else {
                $absentCount++;
            }
        }

        ActivityLogger::log(
            action: 'attendance_sheet.records.saved',
            subject: $session,
            description: 'Saved custom attendance records.',
            newValues: [
                'attendance_sheet_id' => $session->attendance_sheet_id,
                'attendance_session_id' => $session->id,
                'session_date' => $sessionDate,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
            ],
        );

        return back()
            ->with('attendance_records_saved', true)
            ->with('attendance_present_count', $presentCount)
            ->with('attendance_absent_count', $absentCount);
    }
}
