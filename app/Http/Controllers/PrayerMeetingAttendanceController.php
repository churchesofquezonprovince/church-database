<?php

namespace App\Http\Controllers;

use App\Support\LocalityOptions;
use App\Filament\Pages\PrayerMeeting;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Support\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrayerMeetingAttendanceController extends Controller
{
    private const MAIN_ATTENDANCE_STATUSES = ['Active', 'New One'];

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'locality' => ['required', 'string', 'max:150'],
            'meeting_day' => ['required', 'integer', 'between:0,6'],
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'present_person_ids' => ['nullable', 'array'],
            'present_person_ids.*' => ['integer', 'exists:persons,id'],
            'other_present_person_ids' => ['nullable', 'array'],
            'other_present_person_ids.*' => ['integer', 'exists:persons,id'],
        ]);

        $meetingDay = (int) $data['meeting_day'];
        $meetingDate = CarbonImmutable::parse($data['meeting_date'])->startOfDay();
        $meetingTime = blank($data['meeting_time'] ?? null) ? null : $data['meeting_time'];

        if ($meetingDate->dayOfWeek !== $meetingDay) {
            throw ValidationException::withMessages([
                'meeting_date' => 'Prayer Meeting date must match the selected meeting day.',
            ]);
        }

        $localityRecord = LocalityOptions::primaryProvinceLocality(
            $data['locality']
        );

        if (! $localityRecord) {
            throw ValidationException::withMessages([
                'locality' => 'Select an active Locality from the configured Primary Province.',
            ]);
        }

        $locality = $localityRecord->name;
        $storedLocality = $locality;

        $people = $this->peopleForLocality($localityRecord->id);

        if ($people->isEmpty()) {
            throw ValidationException::withMessages([
                'locality' => 'No people found for the selected locality.',
            ]);
        }

        $presentPersonIds = collect($data['present_person_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $otherPresentPersonIds = collect($data['other_present_person_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        [$sheet, $session, $presentCount, $absentCount] = DB::transaction(function () use ($storedLocality, $locality, $meetingDay, $meetingDate, $people, $presentPersonIds, $otherPresentPersonIds, $meetingTime): array {
            $sheet = AttendanceSheet::query()
                ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
                ->where('locality_id', $locality->id)
                ->first();

            if (! $sheet) {
                $sheet = AttendanceSheet::query()->create([
                    'title' => 'Prayer Meeting - ' . ($storedLocality ?: 'No Locality'),
                    'sheet_type' => AttendanceSheet::TYPE_PRAYER_MEETING,
                    'locality' => $storedLocality,
                    'locality_id' => $locality->id,
                    'meeting_day' => $meetingDay,
                'meeting_time' => $meetingTime,
                    'start_date' => $meetingDate->toDateString(),
                    'end_date' => null,
                    'is_active' => true,
                    'created_by_id' => auth()->id(),
                ]);
            } else {
                $updates = [
                'locality_id' => $locality->id,
                    'is_active' => true,
                    'meeting_day' => $meetingDay,
                    'end_date' => null,
                ];

                if (! $sheet->start_date || $meetingDate->lessThan($sheet->start_date)) {
                    $updates['start_date'] = $meetingDate->toDateString();
                }

                $sheet->update($updates);
            }

            if ($meetingTime !== null && $sheet->meeting_time !== $meetingTime) {

                $sheet->forceFill(['meeting_time' => $meetingTime])->save();

            }

            

            $session = AttendanceSession::query()->firstOrCreate(
                [
                    'attendance_sheet_id' => $sheet->id,
                    'session_date' => $meetingDate->toDateString(),
                ],
                [
                    'title' => 'Prayer Meeting - ' . $meetingDate->format('M d, Y'),
                ]
            );

            $presentCount = 0;
            $absentCount = 0;

            foreach ($people as $person) {
                AttendanceParticipant::query()->firstOrCreate(
                    [
                        'attendance_sheet_id' => $sheet->id,
                        'person_id' => $person->id,
                    ],
                    [
                        'starts_on' => $meetingDate->toDateString(),
                        'ends_on' => null,
                        'is_active' => true,
                    ]
                );

                $isPresent = $presentPersonIds->contains((int) $person->id);

                AttendanceRecord::query()->updateOrCreate(
                    [
                        'attendance_session_id' => $session->id,
                        'person_id' => $person->id,
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

            $visiblePersonIds = $people
                ->pluck('id')
                ->map(fn ($id): int => (int) $id);

            $otherPresentPersonIds
                ->reject(fn (int $personId): bool => $visiblePersonIds->contains($personId))
                ->each(function (int $personId) use ($sheet, $session, $meetingDate, &$presentCount): void {
                    AttendanceParticipant::query()->updateOrCreate(
                        [
                            'attendance_sheet_id' => $sheet->id,
                            'person_id' => $personId,
                        ],
                        [
                            'starts_on' => $meetingDate->toDateString(),
                            'ends_on' => $meetingDate->toDateString(),
                            'is_active' => true,
                        ]
                    );

                    AttendanceRecord::query()->updateOrCreate(
                        [
                            'attendance_session_id' => $session->id,
                            'person_id' => $personId,
                        ],
                        [
                            'status' => AttendanceRecord::STATUS_PRESENT,
                            'is_present' => true,
                            'marked_by_id' => auth()->id(),
                            'marked_at' => now(),
                        ]
                    );

                    $presentCount++;
                });

            ActivityLogger::log(
                action: 'prayer_meeting.attendance.saved',
                subject: $sheet,
                description: 'Saved Prayer Meeting attendance.',
                newValues: [
                    'locality' => $locality,
                    'meeting_day' => $meetingDay,
                    'meeting_date' => $meetingDate->toDateString(),
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                ],
            );

            return [$sheet, $session, $presentCount, $absentCount];
        });

        return redirect(PrayerMeeting::getUrl() . '?' . http_build_query([
            'locality' => $locality,
            'meeting_day' => $meetingDay,
            'meeting_date' => $meetingDate->toDateString(),
        ]))
            ->with('prayer_meeting_saved', true)
            ->with('prayer_meeting_present_count', $presentCount)
            ->with('prayer_meeting_absent_count', $absentCount);
    }

    private function peopleForLocality(int $localityId)
    {
        return Person::query()
            ->with(['churchProfile'])
            ->where('locality_id', $localityId)
            ->whereHas(
                'churchProfile',
                fn ($query) => $query->whereIn('status', self::MAIN_ATTENDANCE_STATUSES)
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }
}
