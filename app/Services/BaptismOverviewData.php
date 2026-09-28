<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BaptismOverviewData
{
    public static function authorize(): void
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);
    }

    /** Missing components stay missing; legacy full dates are a fallback only. */
    public static function dateParts(object $row): array
    {
        $y = isset($row->baptism_year) ? (int) $row->baptism_year : null;
        $m = isset($row->baptism_month) ? (int) $row->baptism_month : null;
        $d = isset($row->baptism_day) ? (int) $row->baptism_day : null;
        if ($y === null && $m === null && $d === null
            && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) ($row->baptism_date ?? ''), $match)
            && checkdate((int) $match[2], (int) $match[3], (int) $match[1])) {
            [$y, $m, $d] = [(int) $match[1], (int) $match[2], (int) $match[3]];
        }
        $date = $y && $m && $d && checkdate($m, $d, $y)
            ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null;
        return ['year' => $y, 'month' => $m, 'day' => $d, 'date' => $date,
            'known' => $y !== null || $m !== null || $d !== null];
    }

    public static function dateLabel(array $parts): string
    {
        if ($parts['date']) {
            return \Carbon\CarbonImmutable::parse($parts['date'])->format('M j, Y');
        }
        $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return ($months[$parts['month']] ?? 'Month unknown').' · '
            .($parts['day'] ? 'Day '.$parts['day'] : 'Day unknown').' · '
            .($parts['year'] ?: 'Year unknown');
    }

    public static function attended(object $record): bool
    {
        // A recorded absent/excused status wins over an inconsistent legacy flag.
        if (in_array($record->status ?? null, ['present', 'late'], true)) {
            return true;
        }
        return blank($record->status ?? null) && (bool) ($record->is_present ?? false);
    }

    public function records(): Collection
    {
        self::authorize();
        // Query only the identity and baptism fields used on this page.
        return DB::table('church_profiles as cp')
            ->join('persons as p', 'p.id', '=', 'cp.person_id')
            ->where(function ($q): void {
                $q->whereNotNull('cp.baptism_year')->orWhereNotNull('cp.baptism_month')
                    ->orWhereNotNull('cp.baptism_day')->orWhereNotNull('cp.baptism_date');
            })
            ->select('cp.person_id', 'cp.baptism_year', 'cp.baptism_month', 'cp.baptism_day',
                'cp.baptism_date', 'p.firstname', 'p.middlename', 'p.lastname', 'p.suffix', 'p.locality')
            ->orderBy('p.lastname')->orderBy('p.firstname')->orderBy('p.id')->get()
            ->unique('person_id')->map(function ($row): array {
                $parts = self::dateParts($row);
                return ['person_id' => (int) $row->person_id,
                    'name' => trim($row->lastname.', '.implode(' ', array_filter([
                        $row->firstname, $row->middlename, $row->suffix,
                    ], fn ($v) => filled($v)))),
                    'locality' => trim((string) $row->locality),
                    'parts' => $parts, 'date' => $parts['date'], 'date_label' => self::dateLabel($parts)];
            })->filter(fn ($r) => $r['parts']['known'])->values();
    }

    public function sessions(string $start, string $end): Collection
    {
        self::authorize();
        return DB::table('attendance_sessions as s')
            ->join('attendance_sheets as sh', 'sh.id', '=', 's.attendance_sheet_id')
            ->leftJoin('schedules as sc', 'sc.id', '=', 's.schedule_id')
            ->whereBetween('s.session_date', [$start, $end])
            ->where(function ($q): void { $q->where('s.is_no_meeting', false)->orWhereNull('s.is_no_meeting'); })
            ->select('s.id', 's.session_date', 's.session_time', 's.title', 's.location',
                'sh.id as sheet_id', 'sh.title as sheet_title', 'sh.locality', 'sc.title as schedule_title')
            ->orderBy('s.session_date')->orderBy('s.session_time')->orderBy('s.id')->get()
            ->map(function ($s): array {
                return ['id' => (int) $s->id, 'date' => substr($s->session_date, 0, 10),
                    'time' => $s->session_time ? substr($s->session_time, 0, 5) : '',
                    'title' => $s->title ?: ($s->schedule_title ?: $s->sheet_title),
                    'sheet_id' => (int) $s->sheet_id, 'sheet_title' => $s->sheet_title,
                    'location' => $s->location ?: $s->locality];
            });
    }

    public function links(): Collection
    {
        self::authorize();
        return DB::table('baptism_activity_links as b')
            ->leftJoin('attendance_sessions as s', 's.id', '=', 'b.attendance_session_id')
            ->leftJoin('attendance_sheets as sh', 'sh.id', '=', 's.attendance_sheet_id')
            ->select('b.*', 's.session_date', 's.is_no_meeting', 's.title as session_title',
                'sh.id as sheet_id', 'sh.title as sheet_title')->get()->keyBy('person_id');
    }

    public static function linkIsCurrent(?object $link, ?string $date): bool
    {
        return $link && $date && $link->sheet_id && ! $link->is_no_meeting
            && substr((string) $link->baptism_date, 0, 10) === $date
            && substr((string) $link->session_date, 0, 10) === $date;
    }

    public function confirm(int $personId, int $sessionId, string $expectedDate): void
    {
        self::authorize();
        DB::transaction(function () use ($personId, $sessionId, $expectedDate): void {
            // All link writes for a person serialize on the existing profile row.
            $profile = DB::table('church_profiles')->where('person_id', $personId)->lockForUpdate()->first();
            $session = DB::table('attendance_sessions')->where('id', $sessionId)->lockForUpdate()->first();
            $date = $profile ? self::dateParts($profile)['date'] : null;
            if (! $date || $date !== $expectedDate || ! $session || $session->is_no_meeting
                || substr((string) $session->session_date, 0, 10) !== $date
                || ! DB::table('persons')->where('id', $personId)->exists()
                || ! DB::table('attendance_sheets')->where('id', $session->attendance_sheet_id)->exists()) {
                throw ValidationException::withMessages(['activity' =>
                    'The baptism or activity date changed, or the activity is unavailable. Refresh and select an activity on the baptism date.']);
            }
            $existing = DB::table('baptism_activity_links')->where('person_id', $personId)->first();
            DB::table('baptism_activity_links')->updateOrInsert(['person_id' => $personId], [
                'attendance_session_id' => $sessionId, 'baptism_date' => $date,
                'confirmed_by_id' => auth()->id(), 'updated_at' => now(),
                'created_at' => $existing?->created_at ?? now(),
            ]);
        });
    }

    public function unlink(int $personId): void
    {
        self::authorize();
        DB::transaction(function () use ($personId): void {
            DB::table('church_profiles')->where('person_id', $personId)->lockForUpdate()->first();
            DB::table('baptism_activity_links')->where('person_id', $personId)->delete();
        });
    }
}
