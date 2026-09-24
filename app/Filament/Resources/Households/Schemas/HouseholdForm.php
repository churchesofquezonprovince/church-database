<?php

namespace App\Filament\Resources\Households\Schemas;

use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Household;
use App\Models\Locality;
use App\Models\Person;
use App\Models\Province;
use App\Models\ProvinceSetting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class HouseholdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Household Information')
                    ->description('Create or update a household record. Members may come from People, Campus Contacts, or Gospel Contacts.')
                    ->schema([
                        TextInput::make('household_name')
                            ->label('Household Name')
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(150)
                            ->placeholder('Santos Family'),

                        Select::make('household_head_id')
                            ->label('Household Head')
                            ->options(fn (): array => self::personOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->placeholder('Select household head')
                            ->helperText('This person will be used for the household family tree shortcut. Spouse and children will be suggested automatically.')
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                $suggestedMemberIds = self::suggestedMemberIdsForHead($state);

                                if ($suggestedMemberIds === []) {
                                    return;
                                }

                                $currentMemberIds = collect($get('member_ids') ?? [])
                                    ->map(fn ($id): int => (int) $id)
                                    ->filter(fn (int $id): bool => $id > 0);

                                $set('member_ids', $currentMemberIds
                                    ->merge($suggestedMemberIds)
                                    ->unique()
                                    ->values()
                                    ->map(fn (int $id): string => (string) $id)
                                    ->all());
                            }),

                          Select::make('member_ids')
                              ->label('People Members')
                              ->options(fn (): array => self::personOptions())
                              ->multiple()
                              ->searchable()
                              ->preload()
                              ->native(false)
                              ->placeholder('Select People members')
                              ->helperText('Select People Database records who belong to this household. The household head is included automatically after saving.')
                              ->columnSpanFull(),

                          Select::make('campus_contact_ids')
                              ->label('Campus Contact Members')
                              ->options(
                                  fn ($livewire): array =>
                                      self::campusContactOptions(
                                          $livewire->record?->id
                                      )
                              )
                              ->multiple()
                              ->searchable()
                              ->preload()
                              ->native(false)
                              ->placeholder('Select unlinked Campus Contacts')
                              ->helperText('Only Campus Contacts not yet linked to the People Database are shown.')
                              ->columnSpanFull(),

                          Select::make('gospel_contact_ids')
                              ->label('Gospel Contact Members')
                              ->options(
                                  fn ($livewire): array =>
                                      self::gospelContactOptions(
                                          $livewire->record?->id
                                      )
                              )
                              ->multiple()
                              ->searchable()
                              ->preload()
                              ->native(false)
                              ->placeholder('Select unlinked Gospel Contacts')
                              ->helperText('Only Gospel Contacts not yet linked to the People Database are shown.')
                              ->columnSpanFull(),

                        Select::make('locality_id')
                            ->label('Locality')
                            ->options(fn (): array => self::localityOptions())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->placeholder('Select locality')
                            ->helperText('Primary Province and configured outside-province Localities are grouped separately.'),

                        Placeholder::make('duplicate_household_warning')
                            ->label('')
                            ->content(fn ($get, $livewire): HtmlString => self::duplicateHouseholdWarning($get, $livewire))
                            ->visible(fn ($get, $livewire): bool => self::duplicateHouseholdFromForm($get, $livewire) !== null)
                            ->columnSpanFull(),

                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(3)
                            ->placeholder('Optional notes about this household')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }



    private static function suggestedMemberIdsForHead(mixed $headId): array
    {
        if (blank($headId)) {
            return [];
        }

        $headId = (int) $headId;

        if ($headId <= 0) {
            return [];
        }

        $head = Person::query()
            ->with('spouse')
            ->find($headId);

        if (! $head) {
            return [];
        }

        $childrenIds = Person::query()
            ->whereHas('parentRelationships', fn ($query) => $query->where('parent_id', $headId))
            ->pluck('id');

        return collect([
            $head->id,
            $head->spouse_id,
        ])
            ->merge($childrenIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private static function duplicateHouseholdWarning($get, $livewire): HtmlString
    {
        $duplicate = self::duplicateHouseholdFromForm($get, $livewire);

        if (! $duplicate) {
            return new HtmlString('');
        }

        $name = e($duplicate->household_name);
        $url = e(\App\Filament\Resources\Households\HouseholdResource::getUrl('edit', [
            'record' => $duplicate,
        ]));

        return new HtmlString(
            '<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">'
            . '<p class="font-bold">Possible duplicate household found.</p>'
            . '<p class="mt-1 text-sm">A household with the same name and locality already exists: <strong>' . $name . '</strong></p>'
            . '<a href="' . $url . '" class="mt-3 inline-flex rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500">Open existing household</a>'
            . '</div>'
        );
    }

    private static function duplicateHouseholdFromForm($get, $livewire): ?Household
    {
        $householdName = trim((string) $get('household_name'));
        $localityId = (int) ($get('locality_id') ?? 0);

        if ($householdName === '' || $localityId <= 0) {
            return null;
        }

        $currentId = $livewire->record?->id ?? null;

        return Household::query()
            ->whereRaw(
                'LOWER(household_name) = ?',
                [mb_strtolower($householdName)]
            )
            ->where('locality_id', $localityId)
            ->when(
                $currentId,
                fn ($query) => $query->where('id', '!=', $currentId)
            )
            ->first();
    }

    private static function localityOptions(): array
    {
        $settings = ProvinceSetting::query()
            ->with('primaryProvince')
            ->first();

        if (! $settings?->primary_province_id) {
            return [];
        }

        $options = [];

        $primaryLocalities = Locality::query()
            ->where('province_id', $settings->primary_province_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        if ($primaryLocalities !== []) {
            $options[
                $settings->primaryProvince?->name ?? 'Primary Province'
            ] = $primaryLocalities;
        }

        $outsideProvinces = Province::query()
            ->with([
                'localities' => fn ($query) =>
                    $query
                        ->where('is_active', true)
                        ->orderBy('name'),
            ])
            ->where('id', '!=', $settings->primary_province_id)
            ->whereHas(
                'localities',
                fn ($query) => $query->where('is_active', true)
            )
            ->orderBy('name')
            ->get();

        foreach ($outsideProvinces as $province) {
            $localities = $province->localities
                ->pluck('name', 'id')
                ->all();

            if ($localities === []) {
                continue;
            }

            $options['Outside — ' . $province->name] = $localities;
        }

        return $options;
    }


    private static function campusContactOptions(
        mixed $householdId = null
    ): array {
        $householdId =
            (int) ($householdId ?? 0);

        return CampusContact::query()
            ->whereNull('person_id')
            ->where(
                function ($query) use (
                    $householdId
                ): void {
                    $query->whereNull(
                        'household_id'
                    );

                    if ($householdId > 0) {
                        $query->orWhere(
                            'household_id',
                            $householdId
                        );
                    }
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->mapWithKeys(
                fn (
                    CampusContact $contact
                ): array => [
                    $contact->id =>
                        $contact->display_name
                        . ' — '
                        . (
                            $contact->effective_locality
                            ?: 'No locality'
                        ),
                ]
            )
            ->all();
    }


    private static function gospelContactOptions(
        mixed $householdId = null
    ): array {
        $householdId =
            (int) ($householdId ?? 0);

        return GospelContact::query()
            ->whereNull('person_id')
            ->where(
                function ($query) use (
                    $householdId
                ): void {
                    $query->whereNull(
                        'household_id'
                    );

                    if ($householdId > 0) {
                        $query->orWhere(
                            'household_id',
                            $householdId
                        );
                    }
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->mapWithKeys(
                fn (
                    GospelContact $contact
                ): array => [
                    $contact->id =>
                        $contact->display_name
                        . ' — '
                        . (
                            $contact->effective_locality
                            ?: 'No locality'
                        ),
                ]
            )
            ->all();
    }


    private static function personOptions(): array
    {
        return Person::query()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->mapWithKeys(fn (Person $person): array => [
                $person->id => $person->display_name,
            ])
            ->toArray();
    }
}
