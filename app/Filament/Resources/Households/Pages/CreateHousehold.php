<?php

namespace App\Filament\Resources\Households\Pages;

use App\Filament\Pages\HomeMeetingSchedule;
use App\Filament\Resources\Households\HouseholdResource;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\HomeMeetingScheduleEntry;
use App\Models\Person;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

class CreateHousehold extends CreateRecord
{
    protected static string $resource = HouseholdResource::class;
    protected array $memberIdsToSync = [];

    protected array $campusContactIdsToSync = [];

    protected array $gospelContactIdsToSync = [];

    #[Locked]
    public ?int $homeMeetingScheduleId = null;


    public function mount(): void
    {
        $this->homeMeetingScheduleId =
            request()->integer(
                'home_meeting_schedule'
            ) ?: null;

        parent::mount();
    }


    protected function afterFill(): void
    {
        $entry =
            $this->homeMeetingScheduleEntry();

        if (! $entry) {
            return;
        }

        $this->form->fill([
            'locality_id' =>
                (int) $entry->locality_id,
        ]);
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $entry =
            $this->homeMeetingScheduleEntry();

        if (
            $entry
            && (int) ($data['locality_id'] ?? 0)
                !== (int) $entry->locality_id
        ) {
            throw ValidationException::withMessages([
                'locality_id' => [
                    'This Household must use the same Locality as the Home Meeting schedule.',
                ],
            ]);
        }

        $this->memberIdsToSync =
            $this->normalizeMemberIds(
                $data['member_ids'] ?? []
            );

        $this->campusContactIdsToSync =
            $this->normalizeMemberIds(
                $data['campus_contact_ids'] ?? []
            );

        $this->gospelContactIdsToSync =
            $this->normalizeMemberIds(
                $data['gospel_contact_ids'] ?? []
            );

        if (
            filled(
                $data['gospel_contact_head_id']
                    ?? null
            )
        ) {
            $this->gospelContactIdsToSync =
                collect(
                    $this->gospelContactIdsToSync
                )
                    ->push(
                        (int)
                        $data[
                            'gospel_contact_head_id'
                        ]
                    )
                    ->unique()
                    ->values()
                    ->all();
        }

        unset(
            $data['member_ids'],
            $data['campus_contact_ids'],
            $data['gospel_contact_ids']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncHouseholdMembers();
        $this->syncCampusContacts();
        $this->syncGospelContacts();
        $this->linkHomeMeetingSchedule();
    }

    protected function getRedirectUrl(): string
    {
        return HouseholdResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }


    private function homeMeetingScheduleEntry(): ?HomeMeetingScheduleEntry
    {
        if (! $this->homeMeetingScheduleId) {
            return null;
        }

        return HomeMeetingScheduleEntry::query()
            ->with('locality')
            ->where('is_active', true)
            ->find(
                $this->homeMeetingScheduleId
            );
    }


    private function linkHomeMeetingSchedule(): void
    {
        $entry =
            $this->homeMeetingScheduleEntry();

        if (! $entry) {
            return;
        }

        HomeMeetingScheduleEntry::query()
            ->whereKey($entry->id)
            ->whereNull('household_id')
            ->where(
                'locality_id',
                (int) $this->record->locality_id
            )
            ->update([
                'household_id' =>
                    (int) $this->record->id,
            ]);
    }


    private function normalizeMemberIds(mixed $ids): array
    {
        return collect($ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function syncHouseholdMembers(): void
    {
        $memberIds = collect($this->memberIdsToSync);

        if (filled($this->record->household_head_id)) {
            $memberIds->push((int) $this->record->household_head_id);
        }

        $memberIds = $memberIds
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($memberIds->isEmpty()) {
            return;
        }

        Person::query()
            ->whereIn('id', $memberIds->all())
            ->update([
                'household_id' => $this->record->id,
            ]);
    }

    private function syncCampusContacts(): void
    {
        if ($this->campusContactIdsToSync === []) {
            return;
        }

        CampusContact::query()
            ->whereNull('person_id')
            ->whereIn(
                'id',
                $this->campusContactIdsToSync
            )
            ->update([
                'household_id' =>
                    $this->record->id,
            ]);
    }

    private function syncGospelContacts(): void
    {
        if ($this->gospelContactIdsToSync === []) {
            return;
        }

        GospelContact::query()
            ->whereNull('person_id')
            ->whereIn(
                'id',
                $this->gospelContactIdsToSync
            )
            ->update([
                'household_id' =>
                    $this->record->id,
            ]);
    }


}
