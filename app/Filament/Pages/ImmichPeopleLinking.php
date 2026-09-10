<?php

namespace App\Filament\Pages;

use App\Services\ImmichPeopleThumbnailService;
use App\Services\ImmichAttendanceSyncService;
use App\Models\AttendanceSession;
use App\Models\AttendanceImmichAssetDetection;
use App\Models\ImmichPersonMapping;
use App\Models\Person;
use App\Services\ImmichApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ImmichPeopleLinking extends Page
{
    protected string $view = 'filament.pages.immich-people-linking';

    public ?int $selectedSessionId = null;

    public string $linkingScope = 'session';
    
    public string $search = '';

    public array $selectedPeople = [];

public function syncImmich(): void
{
    $session = $this->selectedSession();

    if (! $session) {
        Notification::make()
            ->title(
                'No Attendance Session selected'
            )
            ->warning()
            ->send();

        return;
    }

    $hasExactPhotos =
        $session
            ->immichAssets
            ->isNotEmpty();

    $album =
        $session
            ->sheet
            ?->immichAlbum;

    if (
        ! $hasExactPhotos
        && (
            ! $album
            || ! $album->enabled
        )
    ) {
        Notification::make()
            ->title('No Immich source linked')
            ->body(
                'This Attendance Session has '
                . 'no exact Immich photo and '
                . 'its Attendance Sheet has '
                . 'no enabled Immich album.'
            )
            ->warning()
            ->send();

        return;
    }

    try {
        $result = app(
            ImmichAttendanceSyncService::class
        )->sync($session);

        Notification::make()
            ->title(
                'Immich attendance synchronized'
            )
            ->body(
                'Source: '
                . (
                    $result['source']
                        === 'exact_photos'
                        ? 'Exact photo(s)'
                        : 'Sheet album'
                )
                . ' · Photos: '
                . $result['assets']
                . ' · Unique people: '
                . $result['unique_people']
                . ' · Mapped: '
                . $result['mapped_people']
                . ' · Added: '
                . $result['matched']
                . ' · Already present: '
                . $result['already_present']
                . ' · Unmatched: '
                . count(
                    $result['unmatched']
                )
            )
            ->success()
            ->send();

        $this->selectedPeople = [];
    } catch (\Throwable $e) {
        Log::error(
            'Immich attendance synchronization '
            . 'failed from People Linking page.',
            [
                'attendance_session_id' =>
                    $session->id,

                'message' =>
                    $e->getMessage(),

                'trace' =>
                    $e->getTraceAsString(),
            ]
        );

        Notification::make()
            ->title(
                'Immich synchronization failed'
            )
            ->body(
                $e->getMessage()
            )
            ->danger()
            ->send();
    }
}

public function unmatchedPeople(): Collection
{
    return $this->detectedPeople()
        ->filter(
            fn (array $person): bool =>
                $this->mappingFor($person['id']) === null
        )
        ->values();
}

    public function getTitle(): string
    {
        return 'Immich People Linking';
    }

    public static function getNavigationLabel(): string
    {
        return 'Immich People Linking';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-link';
    }

    public static function getNavigationSort(): ?int
    {
        return 70;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

public function sessions(): Collection
{
    return AttendanceSession::query()
        ->with([
            'sheet.immichAlbum',
            'immichAssets',
        ])
        ->where(
            function ($query): void {
                $query
                    ->whereHas(
                        'immichAssets'
                    )
                    ->orWhereHas(
                        'sheet.immichAlbum'
                    );
            }
        )
        ->orderByDesc('session_date')
        ->orderByDesc('id')
        ->get();
}

    public function selectedSession(): ?AttendanceSession
    {
        $sessions = $this->sessions();

        if ($this->selectedSessionId) {
            $session = $sessions->firstWhere(
                'id',
                $this->selectedSessionId
            );

            if ($session) {
                return $session;
            }
        }

        return $sessions->first();
    }


    public function churchPeople(): Collection
    {
        return Person::query()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function mappingFor(
        string $immichPersonId
    ): ?ImmichPersonMapping {
        return ImmichPersonMapping::query()
            ->with('person')
            ->where('immich_person_id', $immichPersonId)
            ->first();
    }

public function linkPerson(
    string $immichPersonId
): void {
    $churchPersonId = (int) (
        $this->selectedPeople[
            $immichPersonId
        ] ?? 0
    );

    if ($churchPersonId <= 0) {
        Notification::make()
            ->title('Select a Church Person')
            ->warning()
            ->send();

        return;
    }

    $immichPerson = $this
        ->detectedPeople()
        ->firstWhere(
            'id',
            $immichPersonId
        );

    if (! $immichPerson) {
        Notification::make()
            ->title('Immich person not found')
            ->danger()
            ->send();

        return;
    }

    $churchPerson =
        Person::query()
            ->find($churchPersonId);

    if (! $churchPerson) {
        Notification::make()
            ->title('Church Person not found')
            ->danger()
            ->send();

        return;
    }

    /*
     * Never silently take an Immich identity
     * away from another Church Person.
     */
    $immichMapping =
        ImmichPersonMapping::query()
            ->where(
                'immich_person_id',
                $immichPersonId
            )
            ->first();

    if (
        $immichMapping
        && (int) $immichMapping->person_id
            !== $churchPersonId
    ) {
        Notification::make()
            ->title(
                'Immich person already mapped'
            )
            ->body(
                'This Immich identity is already '
                . 'linked to another Church Person.'
            )
            ->warning()
            ->send();

        return;
    }

    /*
     * A Church Person may only have one CURRENT
     * Immich identity.
     *
     * Do not silently replace an existing one.
     * Immich face merges can legitimately require
     * replacement, so the UI exposes a separate
     * Replace Existing Mapping action.
     */
    $existing =
        ImmichPersonMapping::query()
            ->where(
                'person_id',
                $churchPersonId
            )
            ->where(
                'immich_person_id',
                '!=',
                $immichPersonId
            )
            ->first();

    if ($existing) {
        Notification::make()
            ->title(
                'Church Person already has '
                . 'an Immich mapping'
            )
            ->body(
                'This can happen after faces are '
                . 'merged in Immich. Use Replace '
                . 'Existing Mapping if the selected '
                . 'Immich identity is now correct.'
            )
            ->warning()
            ->send();

        return;
    }

    ImmichPersonMapping::updateOrCreate(
        [
            'immich_person_id' =>
                $immichPersonId,
        ],
        [
            'person_id' =>
                $churchPersonId,

            'immich_name' =>
                $immichPerson['name']
                ?? null,

            'is_verified' =>
                true,

            'last_synced_at' =>
                now(),
        ],
    );

    /*
     * Backfill already-preserved detections for
     * this Immich identity.
     */
    AttendanceImmichAssetDetection::query()
        ->where(
            'immich_person_id',
            $immichPersonId
        )
        ->update([
            'person_id' =>
                $churchPersonId,
        ]);

    unset(
        $this->selectedPeople[
            $immichPersonId
        ]
    );

    Notification::make()
        ->title('Person linked')
        ->body(
            (
                $immichPerson['name']
                ?: 'Unnamed Immich Person'
            )
            . ' → '
            . $churchPerson->display_name
        )
        ->success()
        ->send();
}


public function replacePersonMapping(
    string $immichPersonId
): void {
    $churchPersonId = (int) (
        $this->selectedPeople[
            $immichPersonId
        ] ?? 0
    );

    if ($churchPersonId <= 0) {
        Notification::make()
            ->title('Select a Church Person')
            ->warning()
            ->send();

        return;
    }

    $immichPerson = $this
        ->detectedPeople()
        ->firstWhere(
            'id',
            $immichPersonId
        );

    $churchPerson =
        Person::query()
            ->find($churchPersonId);

    if (
        ! $immichPerson
        || ! $churchPerson
    ) {
        Notification::make()
            ->title(
                'Person could not be resolved'
            )
            ->danger()
            ->send();

        return;
    }

    /*
     * The new Immich identity must not already
     * belong to a different Church Person.
     */
    $newIdentityMapping =
        ImmichPersonMapping::query()
            ->where(
                'immich_person_id',
                $immichPersonId
            )
            ->first();

    if (
        $newIdentityMapping
        && (int) $newIdentityMapping->person_id
            !== $churchPersonId
    ) {
        Notification::make()
            ->title(
                'Immich person already mapped'
            )
            ->body(
                'The new Immich identity is already '
                . 'linked to another Church Person.'
            )
            ->warning()
            ->send();

        return;
    }

    DB::transaction(
        function () use (
            $churchPersonId,
            $immichPersonId,
            $immichPerson
        ): void {
            $existing =
                ImmichPersonMapping::query()
                    ->where(
                        'person_id',
                        $churchPersonId
                    )
                    ->lockForUpdate()
                    ->first();

            if ($existing) {
                $existing->update([
                    'immich_person_id' =>
                        $immichPersonId,

                    'immich_name' =>
                        $immichPerson['name']
                        ?? null,

                    'is_verified' =>
                        true,

                    'last_synced_at' =>
                        now(),
                ]);
            } else {
                ImmichPersonMapping::create([
                    'person_id' =>
                        $churchPersonId,

                    'immich_person_id' =>
                        $immichPersonId,

                    'immich_name' =>
                        $immichPerson['name']
                        ?? null,

                    'is_verified' =>
                        true,

                    'last_synced_at' =>
                        now(),
                ]);
            }

            /*
             * Associate any already-preserved
             * detections from the surviving Immich
             * identity with the Church Person.
             *
             * Historical detections using the OLD
             * Immich UUID are deliberately untouched.
             */
            AttendanceImmichAssetDetection::query()
                ->where(
                    'immich_person_id',
                    $immichPersonId
                )
                ->update([
                    'person_id' =>
                        $churchPersonId,
                ]);
        }
    );

    unset(
        $this->selectedPeople[
            $immichPersonId
        ]
    );

    Notification::make()
        ->title('Immich mapping replaced')
        ->body(
            $churchPerson->display_name
            . ' now uses the selected Immich '
            . 'identity. Historical detections '
            . 'from the previous Immich identity '
            . 'were preserved.'
        )
        ->success()
        ->send();
}


public function setLinkingScope(string $scope): void
{
    if (! in_array($scope, ['session', 'sheet'], true)) {
        return;
    }

    $this->linkingScope = $scope;

    /*
     * Clear selections because the available people may change
     * completely when changing scope.
     */
    $this->selectedPeople = [];
    $this->search = '';
}

public function detectedPeople(): Collection
{
    $session = $this->selectedSession();

    if (! $session) {
        return collect();
    }

    try {
        $api = app(
            ImmichApiService::class
        );

        /*
         * =================================================
         * WHOLE ATTENDANCE SHEET
         * =================================================
         *
         * Mapping mode only.
         *
         * Include people found from:
         *
         * - the Sheet album
         * - exact Session photos
         *
         * Permanent mappings are useful regardless
         * of which Immich source originally found them.
         */
        if (
            $this->linkingScope
            === 'sheet'
        ) {
            $sheet = $session->sheet;

            if (! $sheet) {
                return collect();
            }

            $peopleById = [];

            $album =
                $sheet->immichAlbum;

            if (
                $album
                && $album->enabled
            ) {
                foreach (
                    $api->peopleFromAlbum(
                        albumId:
                            $album
                                ->immich_album_id,
                    )
                    as $person
                ) {
                    if (
                        filled(
                            $person['id']
                            ?? null
                        )
                    ) {
                        $peopleById[
                            $person['id']
                        ] = $person;
                    }
                }
            }

            $exactAssetIds =
                AttendanceSession::query()
                    ->where(
                        'attendance_sheet_id',
                        $sheet->id
                    )
                    ->whereHas(
                        'immichAssets'
                    )
                    ->with(
                        'immichAssets'
                    )
                    ->get()
                    ->flatMap(
                        fn (
                            AttendanceSession
                            $sheetSession
                        ) =>
                            $sheetSession
                                ->immichAssets
                                ->pluck(
                                    'immich_asset_id'
                                )
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

            if ($exactAssetIds !== []) {
                foreach (
                    $api->peopleFromAssets(
                        $exactAssetIds
                    )
                    as $person
                ) {
                    if (
                        filled(
                            $person['id']
                            ?? null
                        )
                    ) {
                        $peopleById[
                            $person['id']
                        ] = $person;
                    }
                }
            }

            $people =
                array_values(
                    $peopleById
                );
        } else {
            /*
             * =================================================
             * ONE ATTENDANCE SESSION
             * =================================================
             *
             * Exact Session photos take precedence.
             */
            $exactAssetIds =
                $session
                    ->immichAssets
                    ->pluck(
                        'immich_asset_id'
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

            if ($exactAssetIds !== []) {
                $people =
                    $api->peopleFromAssets(
                        $exactAssetIds
                    );
            } else {
                $album =
                    $session
                        ->sheet
                        ?->immichAlbum;

                if (
                    ! $album
                    || ! $album->enabled
                ) {
                    return collect();
                }

                $people =
                    $api
                        ->peopleFromAlbumForDate(
                            albumId:
                                $album
                                    ->immich_album_id,

                            date:
                                $session
                                    ->session_date
                                    ->format(
                                        'Y-m-d'
                                    ),
                        );
            }
        }

        $people = app(
            ImmichPeopleThumbnailService::class
        )->syncPeople(
            $people
        );

        return $people
            ->when(
                filled($this->search),
                function (
                    Collection $people
                ): Collection {
                    $search =
                        mb_strtolower(
                            trim(
                                $this->search
                            )
                        );

                    return $people
                        ->filter(
                            fn (
                                array $person
                            ): bool =>
                                str_contains(
                                    mb_strtolower(
                                        trim(
                                            $person[
                                                'name'
                                            ]
                                            ?? ''
                                        )
                                    ),
                                    $search
                                )
                        );
                }
            )
            ->values();
    } catch (\Throwable $e) {
        Log::warning(
            'Unable to load Immich people '
            . 'for People Linking.',
            [
                'attendance_session_id' =>
                    $session->id,

                'scope' =>
                    $this->linkingScope,

                'message' =>
                    $e->getMessage(),
            ]
        );

        return collect();
    }
}

    public function unlinkPerson(string $immichPersonId): void
    {
        $mapping = ImmichPersonMapping::query()
            ->where('immich_person_id', $immichPersonId)
            ->first();

        if (! $mapping) {
            return;
        }

        $mapping->delete();

        Notification::make()
            ->title('Person unlinked')
            ->success()
            ->send();
    }
}
