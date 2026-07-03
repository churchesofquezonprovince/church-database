<?php

namespace App\Http\Controllers;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceRecordController extends Controller
{
    public function store(Request $request, AttendanceSession $session): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'present_person_ids' => ['nullable', 'array'],
            'present_person_ids.*' => ['integer', 'exists:persons,id'],
        ]);

        $presentPersonIds = collect($data['present_person_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $sessionDate = $session->session_date->format('Y-m-d');

        $participants = AttendanceParticipant::query()
            ->with('person')
            ->where('attendance_sheet_id', $session->attendance_sheet_id)
            ->where('is_active', true)
            ->where(function ($query) use ($sessionDate): void {
                $query->whereNull('starts_on')
                    ->orWhere('starts_on', '<=', $sessionDate);
            })
            ->where(function ($query) use ($sessionDate): void {
                $query->whereNull('ends_on')
                    ->orWhere('ends_on', '>=', $sessionDate);
            })
            ->get();

        $presentCount = 0;
        $absentCount = 0;

        DB::transaction(function () use ($session, $participants, $presentPersonIds, &$presentCount, &$absentCount): void {
            foreach ($participants as $participant) {
                $isPresent = $presentPersonIds->contains((int) $participant->person_id);

                AttendanceRecord::query()->updateOrCreate(
                    [
                        'attendance_session_id' => $session->id,
                        'person_id' => $participant->person_id,
                    ],
                    [
                        'status' => $isPresent
                            ? AttendanceRecord::STATUS_PRESENT
                            : AttendanceRecord::STATUS_ABSENT,
                        'is_present' => $isPresent,
                        'marked_by_id' => auth()->id(),
                        'marked_at' => now(),
                    ]
                );

                if ($isPresent) {
                    $presentCount++;
                } else {
                    $absentCount++;
                }
            }
        });

        ActivityLogger::log(
            action: 'attendance_records.saved',
            subject: $session,
            description: 'Saved checkbox attendance records.',
            newValues: [
                'attendance_session_id' => $session->id,
                'attendance_sheet_id' => $session->attendance_sheet_id,
                'session_date' => $session->session_date->format('Y-m-d'),
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
