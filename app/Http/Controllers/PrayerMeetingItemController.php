<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSheet;
use App\Models\PrayerMeetingItem;
use App\Models\PrayerMeetingItemLine;
use App\Models\PrayerMeetingItemSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Fluent;
use Illuminate\View\View;

class PrayerMeetingItemController extends Controller
{
    public function storeLine(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $this->validatedLine($request);

        PrayerMeetingItemLine::query()->create([
            'prayer_meeting_item_id' => $data['prayer_meeting_item_id'],
            'line_type' => $data['line_type'],
            'marker' => $this->nullIfBlank($data['marker'] ?? null),
            'content' => trim($data['content']),
            'sort_order' => (int) $data['sort_order'],
        ]);

        return back()->with('prayer_meeting_item_line_created', true);
    }

    public function updateLine(
        Request $request,
        PrayerMeetingItemLine $line
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $this->validatedLine($request, false);

        $line->update([
            'line_type' => $data['line_type'],
            'marker' => $this->nullIfBlank($data['marker'] ?? null),
            'content' => trim($data['content']),
            'sort_order' => (int) $data['sort_order'],
        ]);

        return back()->with('prayer_meeting_item_line_updated', true);
    }

    public function destroyLine(
        PrayerMeetingItemLine $line
    ): RedirectResponse {
        abort_unless(auth()->user()?->canDeleteRecords(), 403);

        $line->delete();

        return back()->with('prayer_meeting_item_line_deleted', true);
    }

    public function print(Request $request): View
    {
        $requestedLocality = $request->query('locality');

        if (blank($requestedLocality)) {
            $requestedLocality = PrayerMeetingItem::query()
                ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
                ->orderBy('locality')
                ->value('locality');
        }

        $storedLocality = $requestedLocality === '__no_locality'
            ? null
            : $requestedLocality;

        $sheet = $this->sheetForLocality($requestedLocality);

        $item = PrayerMeetingItem::query()
            ->with(['lines' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->where(function ($query) use ($storedLocality): void {
                if (blank($storedLocality)) {
                    $query->whereNull('locality')
                        ->orWhere('locality', '');
                } else {
                    $query->where('locality', $storedLocality);
                }
            })
            ->first();

        if (! $item && $sheet) {
            $item = PrayerMeetingItem::query()
                ->with(['lines' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
                ->firstOrCreate(
                    ['locality' => $sheet->locality],
                    [
                        'attendance_sheet_id' => $sheet->id,
                        'title' => 'Prayer Meeting Items',
                        'meeting_date' => $sheet->sessions()
                            ->latest('session_date')
                            ->first()
                            ?->session_date,
                    ],
                );
        }

        if ($sheet && $item && $item->attendance_sheet_id !== $sheet->id) {
            $item->forceFill([
                'attendance_sheet_id' => $sheet->id,
            ])->save();
        }

        $lines = $item
            ? $item->lines()->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        $localityLabel = $item?->locality
            ?: $sheet?->locality
            ?: ($requestedLocality === '__no_locality' ? 'No Locality' : (string) $requestedLocality);

        return view('reports.prayer-meeting-items-print', [
            'item' => $item,
            'sheet' => $sheet,
            'lines' => $lines,
            'localityLabel' => $localityLabel ?: 'No Locality',
            'meetingSchedule' => $this->meetingScheduleLabel($sheet),
            'latestMeetingDate' => $this->latestMeetingDateLabel($sheet, $item),
        ]);
    }

    public function storeSnapshot(
        Request $request,
        PrayerMeetingItem $item
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $item->load([
            'lines' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'attendanceSheet',
        ]);

        $sheet = $item->attendanceSheet
            ?: $this->sheetForLocality($item->locality);

        $lines = $item->lines
            ->map(fn (PrayerMeetingItemLine $line): array => [
                'line_type' => $line->line_type,
                'marker' => $line->marker,
                'content' => $line->content,
                'sort_order' => $line->sort_order,
            ])
            ->values()
            ->all();

        $user = auth()->user();

        PrayerMeetingItemSnapshot::query()->create([
            'prayer_meeting_item_id' => $item->id,
            'locality' => $item->locality,
            'title' => $item->title,
            'meeting_date' => $item->meeting_date,
            'meeting_schedule_snapshot' => $this->meetingScheduleLabel($sheet),
            'content_json' => $lines,
            'created_by_name' => filled($user?->name ?? null)
                ? $user?->name
                : ($user?->email ?? null),
        ]);

        return back()->with('prayer_meeting_item_snapshot_created', true);
    }

    public function printSnapshot(
        PrayerMeetingItemSnapshot $snapshot
    ): View {
        $snapshot->load('item.attendanceSheet');

        $lines = collect($snapshot->content_json ?? [])
            ->map(fn (array $line): Fluent => new Fluent($line));

        return view('reports.prayer-meeting-items-print', [
            'item' => $snapshot->item,
            'sheet' => $snapshot->item?->attendanceSheet,
            'lines' => $lines,
            'localityLabel' => $snapshot->locality ?: 'No Locality',
            'meetingSchedule' => $snapshot->meeting_schedule_snapshot ?: 'No Prayer Meeting attendance sheet found.',
            'latestMeetingDate' => $snapshot->meeting_date
                ? $snapshot->meeting_date->format('F d, Y')
                : 'No meeting date found',
        ]);
    }

    private function validatedLine(
        Request $request,
        bool $requireItem = true
    ): array {
        return $request->validate([
            'prayer_meeting_item_id' => [
                $requireItem ? 'required' : 'sometimes',
                'integer',
                'exists:prayer_meeting_items,id',
            ],
            'line_type' => [
                'required',
                Rule::in([
                    PrayerMeetingItemLine::TYPE_ROMAN,
                    PrayerMeetingItemLine::TYPE_LETTER,
                    PrayerMeetingItemLine::TYPE_NUMBER,
                    PrayerMeetingItemLine::TYPE_LOWER_ROMAN,
                    PrayerMeetingItemLine::TYPE_BULLET,
                    PrayerMeetingItemLine::TYPE_PLAIN,
                ]),
            ],
            'marker' => ['nullable', 'string', 'max:20'],
            'content' => ['required', 'string', 'max:20000'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
        ]);
    }

    private function sheetForLocality(?string $locality): ?AttendanceSheet
    {
        if (blank($locality)) {
            return null;
        }

        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
            ->where(function ($query) use ($locality): void {
                if ($locality === '__no_locality') {
                    $query->whereNull('locality')
                        ->orWhere('locality', '');
                } else {
                    $query->where('locality', $locality);
                }
            })
            ->first();
    }

    private function meetingScheduleLabel(?AttendanceSheet $sheet): string
    {
        if (! $sheet) {
            return 'No Prayer Meeting attendance sheet found.';
        }

        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $day = $days[(int) ($sheet->meeting_day ?? 2)] ?? 'Tuesday';

        $time = blank($sheet->meeting_time)
            ? 'No time set'
            : \Carbon\CarbonImmutable::parse((string) $sheet->meeting_time)->format('g:i A');

        return $day . ' · ' . $time;
    }

    private function latestMeetingDateLabel(
        ?AttendanceSheet $sheet,
        ?PrayerMeetingItem $item = null
    ): string {
        if ($sheet) {
            $latest = $sheet->sessions()
                ->latest('session_date')
                ->first();

            if ($latest?->session_date) {
                return $latest->session_date->format('F d, Y');
            }
        }

        if ($item?->meeting_date) {
            return $item->meeting_date->format('F d, Y');
        }

        return 'No meeting date found';
    }

    private function nullIfBlank(mixed $value): ?string
    {
        return filled($value)
            ? trim((string) $value)
            : null;
    }
}
