<?php

namespace App\Filament\Pages;


use App\Services\ImmichAttendanceSyncService;
use App\Models\AttendanceSession;
use App\Models\ImmichPersonMapping;
use App\Models\Person;
use App\Services\ImmichApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ImmichPeopleLinking extends Page
{
    protected string $view = 'filament.pages.immich-people-linking';

    public ?int $selectedSessionId = null;

    public string $search = '';

    public array $selectedPeople = [];

public function syncImmich(): void
{
    $session = $this->selectedSession();

    if (! $session) {
        Notification::make()
            ->title('No Attendance Session selected')
            ->warning()
            ->send();

        return;
    }

    $album = $session->sheet?->immichAlbum;

    if (! $album) {
        Notification::make()
            ->title('No Immich Album linked')
            ->body(
                'The Attendance Sheet does not have an Immich Album linked.'
            )
            ->warning()
            ->send();

        return;
    }

    try {
        $result = app(ImmichAttendanceSyncService::class)
            ->sync($session);

        Notification::make()
            ->title('Immich attendance synchronized')
            ->body(
'Photos: ' . $result['assets']
. ' · Unique people: ' . $result['unique_people']
. ' · Mapped: ' . $result['mapped_people']
. ' · Added: ' . $result['matched']
. ' · Already present: ' . $result['already_present']
. ' · Unmatched: ' . count($result['unmatched'])
            )
            ->success()
            ->send();

        /*
         * Refresh the selected session/date view after synchronization.
         */
        $this->selectedPeople = [];
    } catch (\Throwable $e) {
        Log::error(
            'Immich attendance synchronization failed from People Linking page.',
            [
                'attendance_session_id' => $session->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]
        );

        Notification::make()
            ->title('Immich synchronization failed')
            ->body($e->getMessage())
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
        ])
        ->whereHas('sheet.immichAlbum')
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

public function linkPerson(string $immichPersonId): void
{
    $churchPersonId = (int) (
        $this->selectedPeople[$immichPersonId] ?? 0
    );

    if ($churchPersonId <= 0) {
        Notification::make()
            ->title('Select a Church Person')
            ->warning()
            ->send();

        return;
    }

    $immichPerson = $this->detectedPeople()
        ->firstWhere('id', $immichPersonId);

    if (! $immichPerson) {
        Notification::make()
            ->title('Immich person not found')
            ->danger()
            ->send();

        return;
    }

    $churchPerson = Person::query()->find($churchPersonId);

    if (! $churchPerson) {
        Notification::make()
            ->title('Church person not found')
            ->danger()
            ->send();

        return;
    }

    $existing = ImmichPersonMapping::query()
        ->where('person_id', $churchPersonId)
        ->where('immich_person_id', '!=', $immichPersonId)
        ->first();

    if ($existing) {
        Notification::make()
            ->title('Church Person already has an Immich mapping')
            ->body(
                'This Church Person is already linked to '
                . ($existing->immich_name ?: 'another Immich person')
                . '.'
            )
            ->warning()
            ->send();

        return;
    }

    ImmichPersonMapping::updateOrCreate(
        [
            'immich_person_id' => $immichPersonId,
        ],
        [
            'person_id' => $churchPersonId,
            'immich_name' => $immichPerson['name'] ?? null,
            'is_verified' => true,
            'last_synced_at' => now(),
        ],
    );

    unset($this->selectedPeople[$immichPersonId]);

    Notification::make()
        ->title('Person linked')
        ->body(
            ($immichPerson['name'] ?: 'Unnamed Immich Person')
            . ' → '
            . $churchPerson->display_name
        )
        ->success()
        ->send();
}

public function detectedPeople(): Collection
{
    $session = $this->selectedSession();

    $album = $session?->sheet?->immichAlbum;

    if (! $session || ! $album) {
        return collect();
    }

    try {
        $people = app(ImmichApiService::class)
            ->peopleFromAlbumForDate(
                albumId: $album->immich_album_id,
                date: $session->session_date->format('Y-m-d'),
            );

        return collect($people)
            ->when(
                filled($this->search),
                function (Collection $people): Collection {
                    $search = mb_strtolower(trim($this->search));

                    return $people->filter(
                        fn (array $person): bool =>
                            str_contains(
                                mb_strtolower(
                                    trim($person['name'] ?? '')
                                ),
                                $search
                            )
                    );
                }
            )
            ->values();

    } catch (\Throwable $e) {
        Log::warning(
            'Unable to load Immich people for attendance session.',
            [
                'attendance_session_id' => $session->id,
                'message' => $e->getMessage(),
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