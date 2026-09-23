<?php

namespace App\Filament\Resources\Households\Pages;

use App\Filament\Resources\Households\HouseholdResource;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Person;
use Filament\Resources\Pages\CreateRecord;

class CreateHousehold extends CreateRecord
{
    protected static string $resource = HouseholdResource::class;
    protected array $memberIdsToSync = [];

    protected array $campusContactIdsToSync = [];

    protected array $gospelContactIdsToSync = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
