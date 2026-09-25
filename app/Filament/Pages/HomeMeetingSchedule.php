<?php

namespace App\Filament\Pages;

use App\Models\HomeMeetingScheduleEntry;
use App\Models\Household;
use App\Models\Locality;
use App\Support\LocalityOptions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class HomeMeetingSchedule extends Page
{
    protected string $view =
        'filament.pages.home-meeting-schedule';

    public array $householdSelections = [];

    #[Url(as: 'locality', history: true)]
    public ?string $selectedLocality = null;


    public function mount(): void
    {
        $this->selectedLocality =
            $this->resolvedLocalityName(
                $this->selectedLocality
            );

        $this->loadHouseholdSelections();
    }


    public function updatedSelectedLocality(
        ?string $value
    ): void {
        $this->selectedLocality =
            $this->resolvedLocalityName(
                $value
            );

        $this->loadHouseholdSelections();
    }


    private function loadHouseholdSelections(): void
    {
        $this->householdSelections = [];

        foreach (
            $this->scheduleEntries()
            as $entry
        ) {
            $this->householdSelections[
                $entry->id
            ] =
                $entry->household_id
                    ? (string) $entry->household_id
                    : '';
        }
    }


    public function localityOptions(): array
    {
        return LocalityOptions::
            primaryProvinceNamesWithPeople()
            ->mapWithKeys(
                fn (string $name): array => [
                    $name => $name,
                ]
            )
            ->all();
    }


    private function resolvedLocalityName(
        ?string $requested
    ): ?string {
        $localities =
            LocalityOptions::
                primaryProvinceNamesWithPeople()
                ->values();

        $requested = trim(
            (string) $requested
        );

        if (
            $requested !== ''
            && $localities->contains(
                fn (string $name): bool =>
                    mb_strtolower($name)
                    === mb_strtolower($requested)
            )
        ) {
            return $localities->first(
                fn (string $name): bool =>
                    mb_strtolower($name)
                    === mb_strtolower($requested)
            );
        }

        $lucban = $localities->first(
            fn (string $name): bool =>
                mb_strtolower($name) === 'lucban'
        );

        return $lucban
            ?? $localities->first();
    }


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
        if (! filled($this->selectedLocality)) {
            return null;
        }

        return LocalityOptions::
            primaryProvinceLocality(
                $this->selectedLocality
            );
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

    public function householdOptions(): array
    {
        $locality = $this->locality();

        if (! $locality) {
            return [];
        }

        return Household::query()
            ->where(
                'locality_id',
                $locality->id
            )
            ->with('head')
            ->orderBy('household_name')
            ->get()
            ->mapWithKeys(
                fn (Household $household): array => [
                    (int) $household->id =>
                        $household->display_name,
                ]
            )
            ->all();
    }


    public function linkHousehold(
        int $entryId
    ): void {
        $locality = $this->locality();

        if (! $locality) {
            return;
        }

        $entry =
            HomeMeetingScheduleEntry::query()
                ->where(
                    'locality_id',
                    $locality->id
                )
                ->findOrFail(
                    $entryId
                );

        $householdId =
            (int) (
                $this->householdSelections[
                    $entryId
                ]
                ?? 0
            );

        if ($householdId <= 0) {
            Notification::make()
                ->title(
                    'Select a Household first'
                )
                ->warning()
                ->send();

            return;
        }

        $household =
            Household::query()
                ->whereKey(
                    $householdId
                )
                ->where(
                    'locality_id',
                    $entry->locality_id
                )
                ->first();

        if (! $household) {
            Notification::make()
                ->title(
                    'Household does not belong to '
                    . $locality->name
                )
                ->danger()
                ->send();

            return;
        }

        $entry->update([
            'household_id' =>
                $household->id,
        ]);

        $this->householdSelections[
            $entryId
        ] = (string) $household->id;

        Notification::make()
            ->title(
                'Household linked'
            )
            ->body(
                $entry->display_name
                . ' is now linked to '
                . $household->display_name
                . '.'
            )
            ->success()
            ->send();
    }


    public function unlinkHousehold(
        int $entryId
    ): void {
        $locality = $this->locality();

        if (! $locality) {
            return;
        }

        $entry =
            HomeMeetingScheduleEntry::query()
                ->where(
                    'locality_id',
                    $locality->id
                )
                ->findOrFail(
                    $entryId
                );

        $entry->update([
            'household_id' => null,
        ]);

        $this->householdSelections[
            $entryId
        ] = '';

        Notification::make()
            ->title(
                'Household unlinked'
            )
            ->body(
                $entry->display_name
                . ' can now be linked to another Household.'
            )
            ->success()
            ->send();
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
