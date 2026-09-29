<?php

namespace App\Filament\Pages;

use App\Models\Locality;
use App\Models\Person;
use App\Models\ProvinceSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Validation\ValidationException;

class ChildrenWorkDatabase extends Page
{
    protected string $view =
        'filament.pages.children-work-database';

    public ?int $editingChildId = null;

    public ?int $childrenWorkLocalityId = null;

    public bool $childrenWorkIsActive = true;

    public ?string $childrenWorkGroupName = null;

    public ?int $childrenWorkServingOneId = null;

    public ?string $childrenWorkNotes = null;

    public function getTitle(): string
    {
        return 'Children Database';
    }

    public static function getNavigationLabel(): string
    {
        return 'Children Database';
    }

    public static function getNavigationGroup(): ?string
    {
        return "Children's Work";
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function editChildrenWorkProfile(
        int $personId
    ): void {
        $person = Person::query()
            ->with('childrenWorkProfile')
            ->whereKey($personId)
            ->whereHas(
                'churchProfile',
                fn ($query) =>
                    $query->where(
                        'category',
                        'Children'
                    )
            )
            ->firstOrFail();

        $profile = $person->childrenWorkProfile;

        $localityOptions =
            $this->childrenWorkLocalityOptions();

        $personLocalityId =
            $person->locality_id
                ? (int) $person->locality_id
                : null;

        $defaultLocalityId =
            $personLocalityId
            && array_key_exists(
                $personLocalityId,
                $localityOptions
            )
                ? $personLocalityId
                : null;

        $this->editingChildId = $person->id;

        $this->childrenWorkLocalityId =
            $profile?->locality_id
                ? (int) $profile->locality_id
                : $defaultLocalityId;

        $this->childrenWorkIsActive =
            $profile?->is_active ?? true;

        $this->childrenWorkGroupName =
            $profile?->group_name;

        $this->childrenWorkServingOneId =
            $profile?->serving_one_id
                ? (int) $profile->serving_one_id
                : null;

        $this->childrenWorkNotes =
            $profile?->notes;

        $this->resetValidation();
    }

    public function updatedChildrenWorkLocalityId(): void
    {
        /*
         * Match the Home Meeting form behavior:
         * changing Locality clears dependent Person selection.
         */
        $this->childrenWorkServingOneId = null;
    }

    public function cancelChildrenWorkProfile(): void
    {
        $this->resetChildrenWorkProfileEditor();
    }

    public function saveChildrenWorkProfile(): void
    {
        $data = $this->validate([
            'editingChildId' => [
                'required',
                'integer',
            ],
            'childrenWorkLocalityId' => [
                'required',
                'integer',
            ],
            'childrenWorkIsActive' => [
                'boolean',
            ],
            'childrenWorkGroupName' => [
                'nullable',
                'string',
                'max:100',
            ],
            'childrenWorkServingOneId' => [
                'nullable',
                'integer',
            ],
            'childrenWorkNotes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $person = Person::query()
            ->whereKey(
                $data['editingChildId']
            )
            ->whereHas(
                'churchProfile',
                fn ($query) =>
                    $query->where(
                        'category',
                        'Children'
                    )
            )
            ->firstOrFail();

        $localityId =
            (int) $data['childrenWorkLocalityId'];

        if (
            ! array_key_exists(
                $localityId,
                $this->childrenWorkLocalityOptions()
            )
        ) {
            throw ValidationException::withMessages([
                'childrenWorkLocalityId' =>
                    'Select an active Locality from '
                    . 'the configured primary province.',
            ]);
        }

        $servingOneId =
            filled($data['childrenWorkServingOneId'])
                ? (int) $data['childrenWorkServingOneId']
                : null;

        if ($servingOneId) {
            $servingOneExists = Person::query()
                ->whereKey($servingOneId)
                ->where(
                    'locality_id',
                    $localityId
                )
                ->exists();

            if (! $servingOneExists) {
                throw ValidationException::withMessages([
                    'childrenWorkServingOneId' =>
                        'The Serving One must belong to '
                        . 'the selected Children’s Work '
                        . 'Locality.',
                ]);
            }
        }

        $profile =
            $person->childrenWorkProfile()
                ->firstOrNew([]);

        $profile->fill([
            'locality_id' => $localityId,
            'is_active' =>
                (bool) $data['childrenWorkIsActive'],
            'group_name' =>
                filled($data['childrenWorkGroupName'])
                    ? trim(
                        (string)
                            $data['childrenWorkGroupName']
                    )
                    : null,
            'serving_one_id' =>
                $servingOneId,
            'notes' =>
                filled($data['childrenWorkNotes'])
                    ? trim(
                        (string)
                            $data['childrenWorkNotes']
                    )
                    : null,
        ]);

        $profile->save();

        Notification::make()
            ->title(
                "Children's Work profile saved"
            )
            ->body(
                $person->display_name
                . ' has been updated.'
            )
            ->success()
            ->send();

        $this->resetChildrenWorkProfileEditor();
    }

    public function childrenWorkLocalityOptions(): array
    {
        $primaryProvinceId =
            ProvinceSetting::query()
                ->value(
                    'primary_province_id'
                );

        if (! $primaryProvinceId) {
            return [];
        }

        return Locality::query()
            ->where(
                'province_id',
                $primaryProvinceId
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function childrenWorkServingOneOptions(): array
    {
        $localityId =
            (int) ($this->childrenWorkLocalityId ?? 0);

        if ($localityId <= 0) {
            return [];
        }

        return Person::query()
            ->where(
                'locality_id',
                $localityId
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->mapWithKeys(
                fn (Person $person): array => [
                    $person->id =>
                        $person->display_name,
                ]
            )
            ->all();
    }

    private function resetChildrenWorkProfileEditor(): void
    {
        $this->reset([
            'editingChildId',
            'childrenWorkLocalityId',
            'childrenWorkIsActive',
            'childrenWorkGroupName',
            'childrenWorkServingOneId',
            'childrenWorkNotes',
        ]);

        $this->childrenWorkIsActive = true;

        $this->resetValidation();
    }
}
