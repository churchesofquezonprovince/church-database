<?php

namespace App\Services;

use App\Models\{AttendanceParticipant, CampusContact, GospelContact, Person};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AttendeeEnrollment
{
    public static function add(int $sheetId, int $sessionId, string $source, int $id, string $scope): void
    {
        GuestAttendance::authorize();
        abort_unless(in_array($source, ['person', 'campus', 'gospel'], true), 422);
        DB::transaction(function () use ($sheetId, $sessionId, $source, $id, $scope): void {
            DB::table('attendance_sheets')->where('id', $sheetId)->lockForUpdate()->first();
            $session = GuestAttendance::meeting($sheetId, $sessionId, true);
            if (! in_array($scope, ['this_meeting', 'onward'], true)
                || ($scope === 'onward' && $session->sheet->schedule_type === 'one_time')) {
                throw ValidationException::withMessages(['scope' => 'Choose an enrollment period available for this sheet.']);
            }
            $model = match ($source) {
                'person' => Person::class, 'campus' => CampusContact::class, 'gospel' => GospelContact::class,
            };
            $identity = $model::query()->findOrFail($id);
            $personId = $source === 'person' ? $id : $identity->person_id;
            $guest = $source === 'person' ? null : DB::table('attendance_guests')
                ->where('attendance_sheet_id', $sheetId)->where('source_type', $source)->where('source_id', $id)->first();
            if ($source !== 'person') {
                $mappedIds = DB::table('attendance_guest_responses as m')
                    ->join('attendance_meeting_responses as r', 'r.id', '=', 'm.attendance_meeting_response_id')
                    ->join('attendance_guests as g', 'g.id', '=', 'm.attendance_guest_id')
                    ->where('g.attendance_sheet_id', $sheetId)->where('r.respondent_type', $source)
                    ->where('r.'.$source.'_contact_id', $id)->pluck('g.id');
                if ($guest) $mappedIds->push($guest->id);
                $mappedIds = $mappedIds->map(fn ($value) => (int) $value)->unique();
                if ($mappedIds->count() > 1) {
                    throw ValidationException::withMessages(['attendee' => 'This contact maps to multiple attendance identities. Review them before enrolling again.']);
                }
                if ($mappedIds->isNotEmpty()) $guest = DB::table('attendance_guests')->find($mappedIds->first());
            }
            if ($guest?->linked_person_id) {
                if ($personId && (int) $personId !== (int) $guest->linked_person_id) {
                    throw ValidationException::withMessages(['attendee' => 'This contact has conflicting Person links. Review its identity first.']);
                }
                $personId = $guest->linked_person_id;
            }
            if ($personId) {
                Person::query()->findOrFail($personId);
                // An old unlinked enrollment must be explicitly reconciled before adding a second identity.
                $contactIds = [
                    'campus' => CampusContact::query()->where('person_id', $personId)->pluck('id'),
                    'gospel' => GospelContact::query()->where('person_id', $personId)->pluck('id'),
                ];
                $unlinked = DB::table('attendance_guests')->where('attendance_sheet_id', $sheetId)
                    ->whereNull('linked_person_id')->where(function ($q) use ($contactIds): void {
                        foreach ($contactIds as $type => $ids) {
                            $q->orWhere(fn ($part) => $part->where('source_type', $type)->whereIn('source_id', $ids));
                        }
                    })->exists();
                $mappedUnlinked = DB::table('attendance_guest_responses as m')
                    ->join('attendance_guests as g', 'g.id', '=', 'm.attendance_guest_id')
                    ->join('attendance_meeting_responses as r', 'r.id', '=', 'm.attendance_meeting_response_id')
                    ->where('g.attendance_sheet_id', $sheetId)->whereNull('g.linked_person_id')
                    ->where(function ($q) use ($contactIds): void {
                        foreach ($contactIds as $type => $ids) {
                            $q->orWhere(fn ($part) => $part->where('r.respondent_type', $type)->whereIn('r.'.$type.'_contact_id', $ids));
                        }
                    })->exists();
                if ($unlinked || $mappedUnlinked) {
                    throw ValidationException::withMessages(['attendee' => 'This contact already has a separate attendance identity. Find it in Enrolled Attendees (All enrollment history), then use Link to People first.']);
                }
                $start = $session->session_date->toDateString();
                $end = $scope === 'this_meeting' ? $start : null;
                $covered = AttendanceParticipant::query()->where('attendance_sheet_id', $sheetId)
                    ->where('person_id', $personId)->where('is_active', true)
                    ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $start))
                    ->when($end === null, fn ($q) => $q->whereNull('ends_on'),
                        fn ($q) => $q->where(fn ($p) => $p->whereNull('ends_on')->orWhereDate('ends_on', '>=', $end)))->exists();
                if (! $covered) {
                    AttendanceParticipant::create(['attendance_sheet_id' => $sheetId, 'person_id' => $personId,
                        'starts_on' => $start, 'ends_on' => $end, 'is_active' => true]);
                }
                return;
            }
            if (! $guest) {
                $guestId = DB::table('attendance_guests')->insertGetId([
                    'attendance_sheet_id' => $sheetId, 'source_type' => $source, 'source_id' => $id,
                    'name' => $identity->display_name,
                    'locality' => $identity->locality ?: null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                $guestId = (int) $guest->id;
            }
            GuestAttendance::enroll($sheetId, $sessionId, $scope, null, $guestId);
        });
    }

    public static function rows(int $sheetId, int $sessionId, bool $history = false): Collection
    {
        $session = GuestAttendance::meeting($sheetId, $sessionId);
        $date = $session->session_date->toDateString();
        $periods = AttendanceParticipant::query()->with('person')->where('attendance_sheet_id', $sheetId)
            ->when(! $history, fn ($q) => $q->activeOn($date))->get();
        $rows = $periods->groupBy('person_id')->map(function ($own, $personId) use ($date) {
            $person = $own->first()->person;
            return ['key' => 'person-'.$personId, 'name' => $person?->display_name ?? 'Unknown Person',
                'source' => 'person', 'locality' => $person?->locality, 'person_id' => (int) $personId,
                'guest_id' => null, 'periods' => $own,
                'active' => $own->contains(fn ($p) => $p->is_active && (! $p->starts_on || $p->starts_on->toDateString() <= $date)
                    && (! $p->ends_on || $p->ends_on->toDateString() >= $date))];
        })->values();
        $active = GuestAttendance::activeIds($sheetId, $date);
        $guests = GuestAttendance::guests($sheetId)->when(! $history, fn ($items) => $items->whereIn('id', $active));
        $guestPeriods = DB::table('attendance_guest_periods')->whereIn('attendance_guest_id', $guests->keys())->get();
        foreach ($guests as $g) {
            $rows->push(['key' => 'guest-'.$g->id, 'name' => $g->name, 'source' => $g->source_type,
                'locality' => $g->locality, 'person_id' => null, 'guest_id' => (int) $g->id,
                'active' => $active->contains((int) $g->id),
                'periods' => $guestPeriods->where('attendance_guest_id', $g->id)->filter(fn ($p) => $history
                    || ($p->is_active && $p->starts_on <= $date && (! $p->ends_on || $p->ends_on >= $date)))]);
        }
        return $rows->sortBy(fn ($r) => mb_strtolower($r['name']))->values();
    }
}
