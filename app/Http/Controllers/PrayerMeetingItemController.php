<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSheet;
use App\Models\PrayerMeetingItem;
use App\Models\PrayerMeetingItemLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $locality = $request->query('locality');

        $sheet = $this->sheetForLocality($locality);

        $item = PrayerMeetingItem::query()
            ->with(['lines' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->firstOrCreate(
                ['locality' => $sheet?->locality],
                [
                    'attendance_sheet_id' => $sheet?->id,
                    'title' => 'Prayer Meeting Items',
                    'meeting_date' => $sheet?->sessions()
                        ->latest('session_date')
                        ->first()
                        ?->session_date,
                ],
            );

        if ($sheet && $item->attendance_sheet_id !== $sheet->id) {
            $item->forceFill([
                'attendance_sheet_id' => $sheet->id,
            ])->save();
        }

        return view('reports.prayer-meeting-items-print', [
            'item' => $item,
            'sheet' => $sheet,
            'lines' => $item->lines,
            'localityLabel' => $sheet?->locality ?: 'No Locality',
            'meetingSchedule' => $this->meetingScheduleLabel($sheet),
            'latestMeetingDate' => $this->latestMeetingDateLabel($sheet),
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
            return AttendanceSheet::query()
                ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
                ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
                ->orderBy('locality')
                ->first();
        }

        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
            ->where('locality', $locality)
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

    private function latestMeetingDateLabel(?AttendanceSheet $sheet): string
    {
        if (! $sheet) {
            return 'No meeting date found';
        }

        $latest = $sheet->sessions()
            ->latest('session_date')
            ->first();

        return $latest?->session_date
            ? $latest->session_date->format('F d, Y')
            : 'No attendance date recorded yet';
    }

    private function nullIfBlank(mixed $value): ?string
    {
        return filled($value)
            ? trim((string) $value)
            : null;
    }
}
