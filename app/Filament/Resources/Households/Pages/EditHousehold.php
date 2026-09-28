<?php

namespace App\Filament\Resources\Households\Pages;

use App\Filament\Pages\FamilyTree;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Households\HouseholdResource;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Person;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHousehold extends EditRecord
{
    protected static string $resource = HouseholdResource::class;

    protected function getRedirectUrl(): string
    {
        return HouseholdResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

protected function getHeaderActions(): array
{
    return [
        Action::make('viewHeadFamilyTree')
            ->label("View Head's Family Tree")
            ->icon('heroicon-o-user-group')
            ->visible(fn (): bool => filled($this->record->household_head_id))
            ->url(fn (): string => FamilyTree::getUrl([
                'personId' => $this->record->household_head_id,
            ])),

        DeleteAction::make(),
    ];
}

    protected array $memberIdsToSync = [];

    protected array $campusContactIdsToSync = [];

    protected array $gospelContactIdsToSync = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['member_ids'] = $this->record
            ->members()
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        $data['campus_contact_ids'] =
            $this->record
                ->campusContacts()
                ->whereNull('person_id')
                ->pluck('id')
                ->map(
                    fn ($id): string =>
                        (string) $id
                )
                ->all();

        $data['gospel_contact_ids'] =
            $this->record
                ->gospelContacts()
                ->whereNull('person_id')
                ->pluck('id')
                ->map(
                    fn ($id): string =>
                        (string) $id
                )
                ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
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
            Person::query()
                ->where('household_id', $this->record->id)
                ->update([
                    'household_id' => null,
                ]);

            return;
        }

        Person::query()
            ->where('household_id', $this->record->id)
            ->whereNotIn('id', $memberIds->all())
            ->update([
                'household_id' => null,
            ]);

        Person::query()
            ->whereIn('id', $memberIds->all())
            ->update([
                'household_id' => $this->record->id,
            ]);
    }

    private function syncCampusContacts(): void
    {
        $selectedIds = collect(
            $this->campusContactIdsToSync
        )
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        CampusContact::query()
            ->whereNull('person_id')
            ->where(
                'household_id',
                $this->record->id
            )
            ->when(
                $selectedIds->isNotEmpty(),
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $selectedIds->all()
                    )
            )
            ->update([
                'household_id' => null,
            ]);

        if ($selectedIds->isEmpty()) {
            return;
        }

        CampusContact::query()
            ->whereNull('person_id')
            ->whereIn(
                'id',
                $selectedIds->all()
            )
            ->update([
                'household_id' =>
                    $this->record->id,
            ]);
    }

    private function syncGospelContacts(): void
    {
        $selectedIds = collect(
            $this->gospelContactIdsToSync
        )
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        GospelContact::query()
            ->whereNull('person_id')
            ->where(
                'household_id',
                $this->record->id
            )
            ->when(
                $selectedIds->isNotEmpty(),
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $selectedIds->all()
                    )
            )
            ->update([
                'household_id' => null,
            ]);

        if ($selectedIds->isEmpty()) {
            return;
        }

        GospelContact::query()
            ->whereNull('person_id')
            ->whereIn(
                'id',
                $selectedIds->all()
            )
            ->update([
                'household_id' =>
                    $this->record->id,
            ]);
    }


}
