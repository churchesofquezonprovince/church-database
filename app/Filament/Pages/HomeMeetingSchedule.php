<?php

namespace App\Filament\Pages;

use App\Models\HomeMeetingScheduleEntry;
use App\Models\Locality;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class HomeMeetingSchedule extends Page
{
    protected string $view =
        'filament.pages.home-meeting-schedule';

    public function getTitle(): string
    {
        return 'Home Meeting Schedule';
    }

    public static function getNavigationLabel(): string
    {
        return 'Home Meeting Schedule';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationSort(): ?int
    {
        return 40;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }

    public function locality(): ?Locality
    {
        return Locality::query()
            ->whereRaw(
                'LOWER(name) = ?',
                ['lucban']
            )
            ->first();
    }

    public function scheduleEntries(): Collection
    {
        $locality = $this->locality();

        if (! $locality) {
            return collect();
        }

        return HomeMeetingScheduleEntry::query()
            ->where(
                'locality_id',
                $locality->id
            )
            ->where('is_active', true)
            ->with([
                'household',
                'contactPerson',
            ])
            ->orderBy('sort_order')
            ->orderBy('meeting_time')
            ->get();
    }

    public function scheduleGroups(): Collection
    {
        return $this->scheduleEntries()
            ->groupBy(
                fn (
                    HomeMeetingScheduleEntry $entry
                ): string =>
                    $entry->day_of_week
                    . '|'
                    . ($entry->area_name ?? '')
            )
            ->map(
                function (
                    Collection $entries
                ): array {
                    /** @var HomeMeetingScheduleEntry $first */
                    $first = $entries->first();

                    return [
                        'day_of_week' =>
                            $first->day_of_week,
                        'day_label' =>
                            $this->dayLabel(
                                $first->day_of_week
                            ),
                        'area_name' =>
                            $first->area_name,
                        'entries' =>
                            $entries->values(),
                    ];
                }
            )
            ->values();
    }

    public function shepherdingRecordUrl(
        HomeMeetingScheduleEntry $entry
    ): ?string {
        if (! $entry->household_id) {
            return null;
        }

        return ShepherdingContacts::getUrl([
            'home_meeting_schedule' =>
                (int) $entry->id,
        ])
            . '#shepherding-contact-form';
    }


    public function dayLabel(
        int $dayOfWeek
    ): string {
        return match ($dayOfWeek) {
            0 => "Lord's Day",
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            default => 'Unknown Day',
        };
    }
}
