<?php

namespace App\Filament\Pages;

use App\Models\AttendanceSheet;
use App\Models\PrayerMeetingItem;
use App\Models\PrayerMeetingItemLine;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class PrayerMeetingItems extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Posts';

    protected static ?string $navigationLabel = 'Prayer Meeting Items';

    protected static ?string $title = 'Prayer Meeting Items';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.prayer-meeting-items';

    public function localities(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
            ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
            ->orderBy('locality')
            ->get()
            ->map(fn (AttendanceSheet $sheet): array => [
                'value' => $sheet->locality ?: '__no_locality',
                'label' => $sheet->locality ?: 'No Locality',
                'sheet' => $sheet,
            ]);
    }

    public function selectedLocality(): ?string
    {
        $locality = request()->query('locality');

        if (filled($locality)) {
            return (string) $locality;
        }

        return $this->localities()->first()['value'] ?? null;
    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $locality = $this->selectedLocality();

        if (! $locality) {
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

    public function prayerItem(): ?PrayerMeetingItem
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return null;
        }

        $item = PrayerMeetingItem::query()
            ->firstOrCreate(
                [
                    'locality' => $sheet->locality,
                ],
                [
                    'attendance_sheet_id' => $sheet->id,
                    'title' => 'Prayer Meeting Items',
                    'meeting_date' => $sheet->sessions()
                        ->latest('session_date')
                        ->first()
                        ?->session_date,
                ]
            );

        if ($item->attendance_sheet_id !== $sheet->id) {
            $item->forceFill([
                'attendance_sheet_id' => $sheet->id,
            ])->save();
        }

        return $item;
    }

    public function lines(): Collection
    {
        return $this->prayerItem()
            ?->lines()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ?? collect();
    }

    public function localityLabel(?string $locality = null): string
    {
        $locality ??= $this->selectedLocality();

        return $locality === '__no_locality'
            ? 'No Locality'
            : (string) $locality;
    }

    public function meetingScheduleLabel(): string
    {
        $sheet = $this->selectedSheet();

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
            : CarbonImmutable::parse((string) $sheet->meeting_time)->format('g:i A');

        return $day . ' · ' . $time;
    }

    public function latestMeetingDateLabel(): string
    {
        $sheet = $this->selectedSheet();

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

    public function loadUrl(string $locality): string
    {
        return static::getUrl([
            'locality' => $locality,
        ]);
    }

    public function printUrl(): string
    {
        return route('quezonprovinceactivities.posts.prayer-meeting-items.print', [
            'locality' => $this->selectedLocality(),
        ]);
    }

    public function lineTypeOptions(): array
    {
        return [
            PrayerMeetingItemLine::TYPE_ROMAN => 'I. Main Section',
            PrayerMeetingItemLine::TYPE_LETTER => 'A. Sub Section',
            PrayerMeetingItemLine::TYPE_NUMBER => '1. Numbered Item',
            PrayerMeetingItemLine::TYPE_LOWER_ROMAN => 'i. Lower Roman',
            PrayerMeetingItemLine::TYPE_BULLET => '• Bullet',
            PrayerMeetingItemLine::TYPE_PLAIN => 'Plain Text',
        ];
    }

    public function lineClass(string $type): string
    {
        return match ($type) {
            PrayerMeetingItemLine::TYPE_ROMAN =>
                'font-bold uppercase text-gray-950 dark:text-white',
            PrayerMeetingItemLine::TYPE_LETTER =>
                'ml-4 font-semibold text-gray-900 dark:text-gray-100',
            PrayerMeetingItemLine::TYPE_NUMBER =>
                'ml-8 text-gray-800 dark:text-gray-200',
            PrayerMeetingItemLine::TYPE_LOWER_ROMAN =>
                'ml-12 text-gray-800 dark:text-gray-200',
            PrayerMeetingItemLine::TYPE_BULLET =>
                'ml-8 text-gray-800 dark:text-gray-200',
            default =>
                'text-gray-800 dark:text-gray-200',
        };
    }
}
