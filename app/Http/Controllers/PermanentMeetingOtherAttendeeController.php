<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermanentMeetingOtherAttendeeController extends Controller
{

    public function destroy(AttendanceSession $session, Person $person): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $session->loadMissing('sheet');

        abort_unless($session->sheet, 404);
        abort_unless($session->sheet->is_active, 403);

        abort_unless(
            in_array($session->sheet->sheet_type, [
                AttendanceSheet::TYPE_LORDS_TABLE,
                AttendanceSheet::TYPE_PRAYER_MEETING,
            ], true),
            404,
        );

        $meetingDate = $session->session_date->format('Y-m-d');

        DB::transaction(function () use ($session, $person, $meetingDate): void {
            AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('person_id', $person->id)
                ->delete();

            $participant = $session->sheet->participants()
                ->where('person_id', $person->id)
                ->first();

            if ($participant) {
                $startsOn = $participant->starts_on?->format('Y-m-d');
                $endsOn = $participant->ends_on?->format('Y-m-d');

                if ($startsOn === $meetingDate && $endsOn === $meetingDate) {
                    $participant->delete();
                }
            }

            ActivityLogger::log(
                action: 'permanent_meeting_other_attendee.removed',
                subject: $session->sheet,
                description: 'Removed mistaken other locality attendee from permanent meeting.',
                oldValues: [
                    'sheet_title' => $session->sheet->title,
                    'sheet_type' => $session->sheet->sheet_type,
                    'sheet_locality' => $session->sheet->locality,
                    'session_date' => $meetingDate,
                    'person_id' => $person->id,
                    'person_name' => $person->display_name ?? null,
                    'person_locality' => $person->locality,
                ],
            );
        });

        return back()->with('other_locality_attendee_removed', true);
    }

    public function store(Request $request, AttendanceSession $session): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $session->loadMissing('sheet');

        abort_unless($session->sheet, 404);
        abort_unless($session->sheet->is_active, 403);

        abort_unless(
            in_array($session->sheet->sheet_type, [
                AttendanceSheet::TYPE_LORDS_TABLE,
                AttendanceSheet::TYPE_PRAYER_MEETING,
            ], true),
            404,
        );

        $data = $request->validate([
            'person_id' => ['required', 'integer', 'exists:persons,id'],
        ]);

        $person = Person::query()->findOrFail($data['person_id']);
        $meetingDate = $session->session_date->format('Y-m-d');

        $alreadyPresent = AttendanceRecord::query()
            ->where('attendance_session_id', $session->id)
            ->where('person_id', $person->id)
            ->where('is_present', true)
            ->exists();

        if ($alreadyPresent) {
            return back()->with('other_locality_attendee_exists', true);
        }

        DB::transaction(function () use ($session, $person, $meetingDate): void {
            $participant = $session->sheet->participants()
                ->where('person_id', $person->id)
                ->first();

            if (! $participant) {
                $session->sheet->participants()->create([
                    'person_id' => $person->id,
                    'starts_on' => $meetingDate,
                    'ends_on' => $meetingDate,
                    'is_active' => true,
                ]);
            } else {
                $startsOn = $participant->starts_on?->format('Y-m-d');
                $endsOn = $participant->ends_on?->format('Y-m-d');

                $participant->forceFill([
                    'is_active' => true,
                    'starts_on' => $startsOn === null || $startsOn > $meetingDate ? $meetingDate : $startsOn,
                    'ends_on' => $endsOn === null || $endsOn < $meetingDate ? $meetingDate : $endsOn,
                ])->save();
            }

            AttendanceRecord::query()->updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'person_id' => $person->id,
                ],
                [
                    'status' => AttendanceRecord::STATUS_PRESENT,
                    'is_present' => true,
                    'marked_by_id' => auth()->id(),
                    'marked_at' => now(),
                ],
            );

            ActivityLogger::log(
                action: 'permanent_meeting_other_attendee.added',
                subject: $session->sheet,
                description: 'Added other locality attendee to permanent meeting and marked present.',
                newValues: [
                    'sheet_title' => $session->sheet->title,
                    'sheet_type' => $session->sheet->sheet_type,
                    'sheet_locality' => $session->sheet->locality,
                    'session_date' => $meetingDate,
                    'person_id' => $person->id,
                    'person_name' => $person->display_name ?? null,
                    'person_locality' => $person->locality,
                ],
            );
        });

        return back()->with('other_locality_attendee_added', true);
    }
}
