<?php

namespace App\Filament\Pages;

use App\Models\Country;
use App\Models\Locality;
use App\Models\Province;
use App\Models\ProvinceSetting;
use App\Support\ActivityLogger;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProvinceSetup extends Page
{
    protected string $view = 'filament.pages.province-setup';

    public string $countryName = '';

    public string $countryCode = '';

    public string $provinceName = '';

    public string $provinceCode = '';

    public string $newLocality = '';

    public string $massLocalities = '';

    public string $outsideCountryName = '';

    public string $outsideCountryCode = '';

    public string $outsideProvinceName = '';

    public string $outsideProvinceCode = '';

    public string $outsideMassLocalities = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $settings = ProvinceSetting::query()
            ->with([
                'primaryCountry',
                'primaryProvince',
            ])
            ->first();

        if (! $settings) {
            return;
        }

        $this->countryName =
            (string) ($settings->primaryCountry?->name ?? '');

        $this->countryCode =
            (string) ($settings->primaryCountry?->code ?? '');

        $this->provinceName =
            (string) ($settings->primaryProvince?->name ?? '');

        $this->provinceCode =
            (string) ($settings->primaryProvince?->code ?? '');

        $this->outsideCountryName =
            (string) ($settings->primaryCountry?->name ?? '');

        $this->outsideCountryCode =
            (string) ($settings->primaryCountry?->code ?? '');
    }

    public function getTitle(): string
    {
        return 'Province Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'Province Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-map';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function primarySetting(): ?ProvinceSetting
    {
        return ProvinceSetting::query()
            ->with([
                'primaryCountry',
                'primaryProvince',
            ])
            ->first();
    }

    public function localities()
    {
        $provinceId =
            $this->primarySetting()?->primary_province_id;

        if (! $provinceId) {
            return collect();
        }

        return Locality::query()
            ->where('province_id', $provinceId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function savePrimaryProvince(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'countryName' => [
                'required',
                'string',
                'max:150',
            ],

            'countryCode' => [
                'nullable',
                'string',
                'max:3',
            ],

            'provinceName' => [
                'required',
                'string',
                'max:150',
            ],

            'provinceCode' => [
                'nullable',
                'string',
                'max:30',
            ],
        ]);

        $countryName =
            trim($data['countryName']);

        $countryCode =
            filled($data['countryCode'] ?? null)
                ? strtoupper(trim($data['countryCode']))
                : null;

        $provinceName =
            trim($data['provinceName']);

        $provinceCode =
            filled($data['provinceCode'] ?? null)
                ? strtoupper(trim($data['provinceCode']))
                : null;

        $oldSettings =
            $this->primarySetting();

        DB::transaction(function () use (
            $countryName,
            $countryCode,
            $provinceName,
            $provinceCode
        ): void {
            $country = Country::query()
                ->firstOrCreate(
                    [
                        'name' => $countryName,
                    ],
                    [
                        'code' => $countryCode,
                        'is_active' => true,
                    ]
                );

            if (
                $country->code !== $countryCode
                || ! $country->is_active
            ) {
                $country->forceFill([
                    'code' => $countryCode,
                    'is_active' => true,
                ])->save();
            }

            $province = Province::query()
                ->firstOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $provinceName,
                    ],
                    [
                        'code' => $provinceCode,
                        'is_active' => true,
                    ]
                );

            if (
                $province->code !== $provinceCode
                || ! $province->is_active
            ) {
                $province->forceFill([
                    'code' => $provinceCode,
                    'is_active' => true,
                ])->save();
            }

            $settings =
                ProvinceSetting::query()
                    ->firstOrNew([]);

            $settings->forceFill([
                'primary_country_id' => $country->id,
                'primary_province_id' => $province->id,
            ])->save();
        });

        $newSettings =
            $this->primarySetting();

        ActivityLogger::log(
            action: 'province_setup.updated',
            subject: $newSettings,
            description: 'Updated the deployment primary country and province.',
            oldValues: [
                'primary_country' =>
                    $oldSettings?->primaryCountry?->name,

                'primary_province' =>
                    $oldSettings?->primaryProvince?->name,
            ],
            newValues: [
                'primary_country' =>
                    $newSettings?->primaryCountry?->name,

                'primary_province' =>
                    $newSettings?->primaryProvince?->name,
            ],
        );

        Notification::make()
            ->title('Province setup saved')
            ->success()
            ->send();
    }

    public function addLocality(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $settings =
            $this->primarySetting();

        if (! $settings?->primary_province_id) {
            Notification::make()
                ->title('Set the primary province first')
                ->warning()
                ->send();

            return;
        }

        $this->validate([
            'newLocality' => [
                'required',
                'string',
                'max:150',

                Rule::unique('localities', 'name')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'province_id',
                                $settings->primary_province_id
                            )
                    ),
            ],
        ]);

        $locality =
            Locality::query()->create([
                'province_id' =>
                    $settings->primary_province_id,

                'name' =>
                    trim($this->newLocality),

                'is_active' =>
                    true,
            ]);

        ActivityLogger::log(
            action: 'locality.created',
            subject: $locality,
            description: 'Added a Locality to the primary province.',
            newValues: [
                'locality' => $locality->name,
                'province' =>
                    $settings->primaryProvince?->name,
                'country' =>
                    $settings->primaryCountry?->name,
            ],
        );

        $this->newLocality = '';

        Notification::make()
            ->title('Locality added')
            ->success()
            ->send();
    }

    public function toggleLocality(int $localityId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $settings =
            $this->primarySetting();

        abort_unless(
            $settings?->primary_province_id,
            404
        );

        $locality =
            Locality::query()
                ->where(
                    'province_id',
                    $settings->primary_province_id
                )
                ->findOrFail($localityId);

        $wasActive =
            (bool) $locality->is_active;

        $locality->forceFill([
            'is_active' => ! $wasActive,
        ])->save();

        ActivityLogger::log(
            action:
                $wasActive
                    ? 'locality.archived'
                    : 'locality.restored',

            subject:
                $locality,

            description:
                $wasActive
                    ? 'Archived a Locality from Province Setup.'
                    : 'Restored a Locality in Province Setup.',

            oldValues: [
                'is_active' => $wasActive,
            ],

            newValues: [
                'is_active' => ! $wasActive,
            ],
        );

        Notification::make()
            ->title(
                $wasActive
                    ? 'Locality archived'
                    : 'Locality restored'
            )
            ->success()
            ->send();
    }

    public function addLocalities(): void
{
    abort_unless(auth()->user()?->isAdmin(), 403);

    $settings = $this->primarySetting();

    if (! $settings?->primary_province_id) {
        Notification::make()
            ->title('Set the primary province first')
            ->warning()
            ->send();

        return;
    }

    $this->validate([
        'massLocalities' => [
            'required',
            'string',
            'max:10000',
        ],
    ]);

    $names = collect(
        preg_split(
            '/[\r\n,]+/',
            $this->massLocalities
        )
    )
        ->map(fn ($name) => trim((string) $name))
        ->filter()
        ->unique(fn ($name) => mb_strtolower($name))
        ->values();

    if ($names->isEmpty()) {
        Notification::make()
            ->title('No Localities found')
            ->warning()
            ->send();

        return;
    }

    $added = 0;
    $existing = 0;
    $restored = 0;

    DB::transaction(function () use (
        $names,
        $settings,
        &$added,
        &$existing,
        &$restored
    ): void {
        foreach ($names as $name) {
            $locality = Locality::query()
                ->where(
                    'province_id',
                    $settings->primary_province_id
                )
                ->where('name', $name)
                ->first();

            if ($locality) {
                if (! $locality->is_active) {
                    $locality->forceFill([
                        'is_active' => true,
                    ])->save();

                    $restored++;
                } else {
                    $existing++;
                }

                continue;
            }

            $locality = Locality::query()->create([
                'province_id' =>
                    $settings->primary_province_id,

                'name' =>
                    $name,

                'is_active' =>
                    true,
            ]);

            ActivityLogger::log(
                action: 'locality.created',
                subject: $locality,
                description: 'Added a Locality through mass add.',
                newValues: [
                    'locality' => $locality->name,
                    'province' =>
                        $settings->primaryProvince?->name,
                    'country' =>
                        $settings->primaryCountry?->name,
                ],
            );

            $added++;
        }
    });

    $this->massLocalities = '';

    Notification::make()
        ->title('Localities processed')
        ->body(
            "{$added} added, {$restored} restored, {$existing} already existed."
        )
        ->success()
        ->send();
}

public function deleteLocality(int $localityId): void
{
    abort_unless(auth()->user()?->isAdmin(), 403);

    $settings = $this->primarySetting();

    abort_unless(
        $settings?->primary_province_id,
        404
    );

    $locality = Locality::query()
        ->where(
            'province_id',
            $settings->primary_province_id
        )
        ->findOrFail($localityId);

    $tablesUsingLocality = collect(
        DB::select("
            SELECT TABLE_NAME AS table_name
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND COLUMN_NAME = 'locality'
        ")
    )
        ->pluck('table_name')
        ->filter()
        ->values();

    $usedIn = $tablesUsingLocality
        ->filter(
            fn (string $table): bool =>
                DB::table($table)
                    ->where('locality', $locality->name)
                    ->exists()
        )
        ->values();

    if ($usedIn->isNotEmpty()) {
        Notification::make()
            ->title('Locality cannot be deleted')
            ->body(
                'This Locality is already used in database records. Archive it instead.'
            )
            ->warning()
            ->persistent()
            ->send();

        return;
    }

    ActivityLogger::log(
        action: 'locality.deleted',
        subject: $locality,
        description: 'Deleted an unused Locality from Province Setup.',
        oldValues: [
            'locality' => $locality->name,
            'province' =>
                $settings->primaryProvince?->name,
            'country' =>
                $settings->primaryCountry?->name,
        ],
    );

    $name = $locality->name;

    $locality->delete();

    Notification::make()
        ->title('Locality deleted')
        ->body("{$name} was deleted.")
        ->success()
        ->send();
}


    public function outsideLocalityGroups()
    {
        $primaryProvinceId =
            $this->primarySetting()?->primary_province_id;

        if (! $primaryProvinceId) {
            return collect();
        }

        return Province::query()
            ->with([
                'country',
                'localities' => fn ($query) =>
                    $query
                        ->orderByDesc('is_active')
                        ->orderBy('name'),
            ])
            ->where('id', '!=', $primaryProvinceId)
            ->whereHas('localities')
            ->orderBy('name')
            ->get();
    }

    public function addOutsideLocalities(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $settings = $this->primarySetting();

        if (! $settings?->primary_province_id) {
            Notification::make()
                ->title('Set the primary province first')
                ->warning()
                ->send();

            return;
        }

        $data = $this->validate([
            'outsideCountryName' => [
                'required',
                'string',
                'max:150',
            ],

            'outsideCountryCode' => [
                'nullable',
                'string',
                'max:3',
            ],

            'outsideProvinceName' => [
                'required',
                'string',
                'max:150',
            ],

            'outsideProvinceCode' => [
                'nullable',
                'string',
                'max:30',
            ],

            'outsideMassLocalities' => [
                'required',
                'string',
                'max:10000',
            ],
        ]);

        $countryName =
            trim($data['outsideCountryName']);

        $countryCode =
            filled($data['outsideCountryCode'] ?? null)
                ? strtoupper(trim($data['outsideCountryCode']))
                : null;

        $provinceName =
            trim($data['outsideProvinceName']);

        $provinceCode =
            filled($data['outsideProvinceCode'] ?? null)
                ? strtoupper(trim($data['outsideProvinceCode']))
                : null;

        $names = collect(
            preg_split(
                '/[\r\n,]+/',
                $data['outsideMassLocalities']
            )
        )
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        if ($names->isEmpty()) {
            Notification::make()
                ->title('No Localities found')
                ->warning()
                ->send();

            return;
        }

        $added = 0;
        $existing = 0;
        $restored = 0;

        DB::transaction(function () use (
            $settings,
            $countryName,
            $countryCode,
            $provinceName,
            $provinceCode,
            $names,
            &$added,
            &$existing,
            &$restored
        ): void {
            $country = Country::query()
                ->firstOrCreate(
                    [
                        'name' => $countryName,
                    ],
                    [
                        'code' => $countryCode,
                        'is_active' => true,
                    ]
                );

            if (
                $country->code !== $countryCode
                || ! $country->is_active
            ) {
                $country->forceFill([
                    'code' => $countryCode,
                    'is_active' => true,
                ])->save();
            }

            $province = Province::query()
                ->firstOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $provinceName,
                    ],
                    [
                        'code' => $provinceCode,
                        'is_active' => true,
                    ]
                );

            if (
                (int) $province->id
                === (int) $settings->primary_province_id
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'outsideProvinceName' =>
                        'The Primary Province cannot be added as an outside province.',
                ]);
            }

            if (
                $province->code !== $provinceCode
                || ! $province->is_active
            ) {
                $province->forceFill([
                    'code' => $provinceCode,
                    'is_active' => true,
                ])->save();
            }

            foreach ($names as $name) {
                $locality = Locality::query()
                    ->where('province_id', $province->id)
                    ->where('name', $name)
                    ->first();

                if ($locality) {
                    if (! $locality->is_active) {
                        $locality->forceFill([
                            'is_active' => true,
                        ])->save();

                        $restored++;
                    } else {
                        $existing++;
                    }

                    continue;
                }

                $locality = Locality::query()->create([
                    'province_id' => $province->id,
                    'name' => $name,
                    'is_active' => true,
                ]);

                ActivityLogger::log(
                    action: 'outside_locality.created',
                    subject: $locality,
                    description: 'Added an outside-primary-province Locality.',
                    newValues: [
                        'locality' => $locality->name,
                        'province' => $province->name,
                        'country' => $country->name,
                    ],
                );

                $added++;
            }
        });

        $this->outsideProvinceName = '';
        $this->outsideProvinceCode = '';
        $this->outsideMassLocalities = '';

        Notification::make()
            ->title('Outside Localities processed')
            ->body(
                "{$added} added, {$restored} restored, {$existing} already existed."
            )
            ->success()
            ->send();
    }

    public function toggleOutsideLocality(int $localityId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $settings = $this->primarySetting();

        abort_unless(
            $settings?->primary_province_id,
            404
        );

        $locality = Locality::query()
            ->with('province')
            ->whereHas(
                'province',
                fn ($query) =>
                    $query->where(
                        'id',
                        '!=',
                        $settings->primary_province_id
                    )
            )
            ->findOrFail($localityId);

        $wasActive =
            (bool) $locality->is_active;

        $locality->forceFill([
            'is_active' => ! $wasActive,
        ])->save();

        ActivityLogger::log(
            action:
                $wasActive
                    ? 'outside_locality.archived'
                    : 'outside_locality.restored',

            subject: $locality,

            description:
                $wasActive
                    ? 'Archived an outside Locality.'
                    : 'Restored an outside Locality.',

            oldValues: [
                'is_active' => $wasActive,
            ],

            newValues: [
                'is_active' => ! $wasActive,
            ],
        );

        Notification::make()
            ->title(
                $wasActive
                    ? 'Outside Locality archived'
                    : 'Outside Locality restored'
            )
            ->success()
            ->send();
    }

    public function deleteOutsideLocality(int $localityId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $settings = $this->primarySetting();

        abort_unless(
            $settings?->primary_province_id,
            404
        );

        $locality = Locality::query()
            ->with([
                'province.country',
            ])
            ->whereHas(
                'province',
                fn ($query) =>
                    $query->where(
                        'id',
                        '!=',
                        $settings->primary_province_id
                    )
            )
            ->findOrFail($localityId);

        $usedByLocalityId =
            DB::table('persons')
                ->where('locality_id', $locality->id)
                ->exists();

        $tablesUsingLocality = collect(
            DB::select("
                SELECT TABLE_NAME AS table_name
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND COLUMN_NAME = 'locality'
            ")
        )
            ->pluck('table_name')
            ->filter()
            ->values();

        $usedByText = $tablesUsingLocality
            ->contains(
                fn (string $table): bool =>
                    DB::table($table)
                        ->where('locality', $locality->name)
                        ->exists()
            );

        if ($usedByLocalityId || $usedByText) {
            Notification::make()
                ->title('Outside Locality cannot be deleted')
                ->body(
                    'This Locality is already used in database records. Archive it instead.'
                )
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        ActivityLogger::log(
            action: 'outside_locality.deleted',
            subject: $locality,
            description: 'Deleted an unused outside Locality.',
            oldValues: [
                'locality' => $locality->name,
                'province' => $locality->province?->name,
                'country' => $locality->province?->country?->name,
            ],
        );

        $name = $locality->name;

        $locality->delete();

        Notification::make()
            ->title('Outside Locality deleted')
            ->body("{$name} was deleted.")
            ->success()
            ->send();
    }

}
