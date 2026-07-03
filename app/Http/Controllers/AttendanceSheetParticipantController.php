<?php

namespace App\Http\Controllers;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceSheetParticipantController extends Controller
{
    public function store(Request $request, AttendanceSheet $sheet): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);
        abort_unless($sheet->is_active, 403);

        $data = $request->validate([
            'person_ids' => ['required', 'array', 'min:1'],
            'person_ids.*' => ['required', 'integer', 'exists:persons,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        $startsOn = $data['starts_on'] ?? $sheet->start_date?->format('Y-m-d');
        $endsOn = $data['ends_on'] ?? null;

        if ($startsOn && $sheet->start_date && $startsOn < $sheet->start_date->format('Y-m-d')) {
            throw ValidationException::withMessages([
                'starts_on' => 'Participant start date cannot be before the sheet start date.',
            ]);
        }

        if ($endsOn && $sheet->end_date && $endsOn > $sheet->end_date->format('Y-m-d')) {
            throw ValidationException::withMessages([
                'ends_on' => 'Participant end date cannot be after the sheet end date.',
            ]);
        }

        $added = 0;
        $updated = 0;

        DB::transaction(function () use ($sheet, $data, $startsOn, $endsOn, &$added, &$updated): void {
            foreach (array_unique($data['person_ids']) as $personId) {
                $participant = AttendanceParticipant::query()
                    ->where('attendance_sheet_id', $sheet->id)
                    ->where('person_id', $personId)
                    ->first();

                if ($participant) {
                    $participant->update([
                        'starts_on' => $startsOn,
                        'ends_on' => $endsOn,
                        'is_active' => true,
                    ]);

                    $updated++;

                    continue;
                }

                AttendanceParticipant::query()->create([
                    'attendance_sheet_id' => $sheet->id,
                    'person_id' => $personId,
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                    'is_active' => true,
                ]);

                $added++;
            }
        });

        ActivityLogger::log(
            action: 'attendance_participants.added',
            subject: $sheet,
            description: 'Added or updated attendance sheet participants.',
            newValues: [
                'added' => $added,
                'updated' => $updated,
                'person_ids' => array_values(array_unique($data['person_ids'])),
            ],
        );

        return back()
            ->with('attendance_participants_saved', true)
            ->with('attendance_participants_added', $added)
            ->with('attendance_participants_updated', $updated);
    }

    public function destroy(AttendanceSheet $sheet, AttendanceParticipant $participant): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);
        abort_unless($sheet->is_active, 403);

        abort_unless((int) $participant->attendance_sheet_id === (int) $sheet->id, 404);

        $person = Person::query()->find($participant->person_id);

        $participant->delete();

        ActivityLogger::log(
            action: 'attendance_participant.removed',
            subject: $sheet,
            description: 'Removed participant from attendance sheet.',
            oldValues: [
                'person_id' => $person?->id,
                'person_name' => $person?->display_name,
            ],
        );

        return back()->with('attendance_participant_removed', true);
    }
}
