<?php

namespace App\Http\Controllers;

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

        return back()->with('attendance_participants_added', true);
    }

    public function destroy(AttendanceSheet $sheet, AttendanceParticipant $participant): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        abort_unless((int) $participant->attendance_sheet_id === (int) $sheet->id, 404);

        $oldValues = [
            'sheet_id' => $sheet->id,
            'sheet_title' => $sheet->title,
            'participant_id' => $participant->id,
            'person_id' => $participant->person_id,
            'starts_on' => optional($participant->starts_on)->format('Y-m-d'),
            'ends_on' => optional($participant->ends_on)->format('Y-m-d'),
        ];

        $participant->delete();

        ActivityLogger::log(
            action: 'attendance_sheet.participant.removed',
            subject: $sheet,
            description: 'Removed participant from attendance sheet.',
            oldValues: $oldValues,
        );

        return back()->with('attendance_participant_removed', true);
    }
}
