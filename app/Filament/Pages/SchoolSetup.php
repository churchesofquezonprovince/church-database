<?php

namespace App\Filament\Pages;

use App\Models\Province;
use App\Models\School;
use App\Support\ActivityLogger;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchoolSetup extends Page
{
    protected string $view = 'filament.pages.school-setup';

    public string $newName = '';

    public string $newShortName = '';

    public ?int $newProvinceId = null;

    public string $newCityMunicipality = '';

    public string $massSchools = '';

    public ?int $massProvinceId = null;

    public ?int $editingSchoolId = null;

    public string $editName = '';

    public string $editShortName = '';

    public ?int $editProvinceId = null;

    public string $editCityMunicipality = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function getTitle(): string
    {
        return 'School Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'School Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-academic-cap';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function schools(): Collection
    {
        return School::query()
            ->with('province')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function provinceOptions(): Collection
    {
        return Province::query()
            ->orderBy('name')
            ->get();
    }

    public function addSchool(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'newName' => [
                'required',
                'string',
                'max:255',
            ],
            'newShortName' => [
                'nullable',
                'string',
                'max:100',
            ],
            'newProvinceId' => [
                'nullable',
                'integer',
                'exists:provinces,id',
            ],
            'newCityMunicipality' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $name = trim($data['newName']);

        $shortName = filled($data['newShortName'] ?? null)
            ? trim($data['newShortName'])
            : null;

        $provinceId = filled($data['newProvinceId'] ?? null)
            ? (int) $data['newProvinceId']
            : null;

        $cityMunicipality =
            filled($data['newCityMunicipality'] ?? null)
                ? trim($data['newCityMunicipality'])
                : null;

        if ($this->duplicateSchoolExists(
            $name,
            $provinceId,
            $cityMunicipality
        )) {
            Notification::make()
                ->title('School already exists')
                ->body(
                    'A School with the same name and location is already configured.'
                )
                ->warning()
                ->send();

            return;
        }

        $school = School::query()->create([
            'name' => $name,
            'short_name' => $shortName,
            'province_id' => $provinceId,
            'city_municipality' => $cityMunicipality,
            'is_active' => true,
        ]);

        ActivityLogger::log(
            action: 'school.created',
            subject: $school,
            description: 'Added a School from School Setup.',
            newValues: [
                'name' => $school->name,
                'short_name' => $school->short_name,
                'province' => $school->province?->name,
                'city_municipality' =>
                    $school->city_municipality,
            ],
        );

        $this->resetNewSchoolForm();

        Notification::make()
            ->title('School added')
            ->success()
            ->send();
    }

    public function addSchools(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'massProvinceId' => [
                'required',
                'integer',
                'exists:provinces,id',
            ],
            'massSchools' => [
                'required',
                'string',
                'max:30000',
            ],
        ]);

        $province = Province::query()
            ->findOrFail((int) $data['massProvinceId']);

        $provinceLabel = mb_strtolower(
            trim(
                preg_replace(
                    '/\s+province$/i',
                    '',
                    $province->name
                )
            )
        );

        $lines = collect(
            preg_split(
                '/\r\n|\r|\n/',
                $data['massSchools']
            )
        )
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();

        $added = 0;
        $existing = 0;
        $restored = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $lines,
            $province,
            $provinceLabel,
            &$added,
            &$existing,
            &$restored,
            &$skipped
        ): void {
            foreach ($lines as $line) {
                $parts = preg_split(
                    '/\s+—\s+|\s+\|\s+/',
                    $line
                );

                if (count($parts) !== 3) {
                    $skipped++;

                    continue;
                }

                [$shortName, $name, $location] =
                    array_map('trim', $parts);

                if ($name === '' || $location === '') {
                    $skipped++;

                    continue;
                }

                $shortName =
                    $shortName !== ''
                        ? $shortName
                        : null;

                $cityMunicipality = $location;

                if (str_contains($location, ',')) {
                    [$city, $listedProvince] =
                        array_map(
                            'trim',
                            explode(',', $location, 2)
                        );

                    $listedProvinceLabel =
                        mb_strtolower(
                            trim(
                                preg_replace(
                                    '/\s+province$/i',
                                    '',
                                    $listedProvince
                                )
                            )
                        );

                    if (
                        $listedProvinceLabel
                        !== $provinceLabel
                    ) {
                        $skipped++;

                        continue;
                    }

                    $cityMunicipality = $city;
                }

                $school = School::query()
                    ->where('name', $name)
                    ->where('province_id', $province->id)
                    ->where(
                        'city_municipality',
                        $cityMunicipality
                    )
                    ->first();

                if ($school) {
                    if (! $school->is_active) {
                        $school->forceFill([
                            'is_active' => true,
                        ])->save();

                        $restored++;
                    } else {
                        $existing++;
                    }

                    if (
                        blank($school->short_name)
                        && filled($shortName)
                    ) {
                        $school->forceFill([
                            'short_name' => $shortName,
                        ])->save();
                    }

                    continue;
                }

                $school = School::query()->create([
                    'name' => $name,
                    'short_name' => $shortName,
                    'province_id' => $province->id,
                    'city_municipality' =>
                        $cityMunicipality,
                    'is_active' => true,
                ]);

                ActivityLogger::log(
                    action: 'school.created',
                    subject: $school,
                    description:
                        'Added a School through mass add.',
                    newValues: [
                        'name' => $school->name,
                        'short_name' =>
                            $school->short_name,
                        'province' => $province->name,
                        'city_municipality' =>
                            $school->city_municipality,
                    ],
                );

                $added++;
            }
        });

        $this->massSchools = '';

        Notification::make()
            ->title('Schools processed')
            ->body(
                "{$added} added, {$restored} restored, "
                . "{$existing} already existed, "
                . "{$skipped} heading/invalid lines skipped."
            )
            ->success()
            ->send();
    }

    public function startEditing(int $schoolId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $school = School::query()->findOrFail($schoolId);

        $this->editingSchoolId = $school->id;
        $this->editName = $school->name;
        $this->editShortName =
            (string) ($school->short_name ?? '');
        $this->editProvinceId = $school->province_id;
        $this->editCityMunicipality =
            (string) ($school->city_municipality ?? '');
    }

    public function cancelEditing(): void
    {
        $this->resetEditSchoolForm();
    }

    public function saveSchool(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        abort_unless($this->editingSchoolId, 404);

        $school = School::query()
            ->findOrFail($this->editingSchoolId);

        $data = $this->validate([
            'editName' => [
                'required',
                'string',
                'max:255',
            ],
            'editShortName' => [
                'nullable',
                'string',
                'max:100',
            ],
            'editProvinceId' => [
                'nullable',
                'integer',
                'exists:provinces,id',
            ],
            'editCityMunicipality' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $name = trim($data['editName']);

        $shortName = filled($data['editShortName'] ?? null)
            ? trim($data['editShortName'])
            : null;

        $provinceId = filled($data['editProvinceId'] ?? null)
            ? (int) $data['editProvinceId']
            : null;

        $cityMunicipality =
            filled($data['editCityMunicipality'] ?? null)
                ? trim($data['editCityMunicipality'])
                : null;

        if ($this->duplicateSchoolExists(
            $name,
            $provinceId,
            $cityMunicipality,
            $school->id
        )) {
            Notification::make()
                ->title('School already exists')
                ->body(
                    'Another School with the same name and location is already configured.'
                )
                ->warning()
                ->send();

            return;
        }

        $oldValues = [
            'name' => $school->name,
            'short_name' => $school->short_name,
            'province' => $school->province?->name,
            'city_municipality' =>
                $school->city_municipality,
        ];

        $school->fill([
            'name' => $name,
            'short_name' => $shortName,
            'province_id' => $provinceId,
            'city_municipality' => $cityMunicipality,
        ])->save();

        $school->load('province');

        ActivityLogger::log(
            action: 'school.updated',
            subject: $school,
            description: 'Updated a School in School Setup.',
            oldValues: $oldValues,
            newValues: [
                'name' => $school->name,
                'short_name' => $school->short_name,
                'province' => $school->province?->name,
                'city_municipality' =>
                    $school->city_municipality,
            ],
        );

        $this->resetEditSchoolForm();

        Notification::make()
            ->title('School updated')
            ->success()
            ->send();
    }

    public function toggleSchool(int $schoolId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $school = School::query()->findOrFail($schoolId);

        $wasActive = (bool) $school->is_active;

        $school->forceFill([
            'is_active' => ! $wasActive,
        ])->save();

        ActivityLogger::log(
            action: $wasActive
                ? 'school.archived'
                : 'school.restored',
            subject: $school,
            description: $wasActive
                ? 'Archived a School from School Setup.'
                : 'Restored a School in School Setup.',
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
                    ? 'School archived'
                    : 'School restored'
            )
            ->success()
            ->send();
    }

    public function deleteSchool(int $schoolId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $school = School::query()->findOrFail($schoolId);

        $tablesUsingSchool = collect(
            DB::select("
                SELECT TABLE_NAME AS table_name
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND COLUMN_NAME = 'school_id'
                  AND TABLE_NAME != 'schools'
            ")
        )
            ->pluck('table_name')
            ->filter()
            ->values();

        $usedIn = $tablesUsingSchool
            ->filter(
                fn (string $table): bool =>
                    DB::table($table)
                        ->where('school_id', $school->id)
                        ->exists()
            )
            ->values();

        if ($usedIn->isNotEmpty()) {
            Notification::make()
                ->title('School cannot be deleted')
                ->body(
                    'This School is already used in database records. Archive it instead.'
                )
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        ActivityLogger::log(
            action: 'school.deleted',
            subject: $school,
            description: 'Deleted an unused School from School Setup.',
            oldValues: [
                'name' => $school->name,
                'short_name' => $school->short_name,
                'province' => $school->province?->name,
                'city_municipality' =>
                    $school->city_municipality,
            ],
        );

        $name = $school->name;

        $school->delete();

        if ($this->editingSchoolId === $schoolId) {
            $this->resetEditSchoolForm();
        }

        Notification::make()
            ->title('School deleted')
            ->body("{$name} was deleted.")
            ->success()
            ->send();
    }

    private function duplicateSchoolExists(
        string $name,
        ?int $provinceId,
        ?string $cityMunicipality,
        ?int $exceptSchoolId = null
    ): bool {
        return School::query()
            ->where('name', $name)
            ->when(
                $provinceId === null,
                fn ($query) =>
                    $query->whereNull('province_id'),
                fn ($query) =>
                    $query->where('province_id', $provinceId)
            )
            ->when(
                $cityMunicipality === null,
                fn ($query) =>
                    $query->whereNull('city_municipality'),
                fn ($query) =>
                    $query->where(
                        'city_municipality',
                        $cityMunicipality
                    )
            )
            ->when(
                $exceptSchoolId !== null,
                fn ($query) =>
                    $query->whereKeyNot($exceptSchoolId)
            )
            ->exists();
    }

    private function resetNewSchoolForm(): void
    {
        $this->newName = '';
        $this->newShortName = '';
        $this->newProvinceId = null;
        $this->newCityMunicipality = '';
    }

    private function resetEditSchoolForm(): void
    {
        $this->editingSchoolId = null;
        $this->editName = '';
        $this->editShortName = '';
        $this->editProvinceId = null;
        $this->editCityMunicipality = '';
    }
}
