<?php

namespace App\Filament\Resources\Households\Pages;

use App\Filament\Resources\Households\HouseholdResource;
use App\Models\Person;
use Filament\Resources\Pages\CreateRecord;

class CreateHousehold extends CreateRecord
{
    protected static string $resource = HouseholdResource::class;
    protected array $memberIdsToSync = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->memberIdsToSync = $this->normalizeMemberIds($data['member_ids'] ?? []);

        unset($data['member_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncHouseholdMembers();
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


}
