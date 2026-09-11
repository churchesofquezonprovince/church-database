<?php

namespace App\Filament\Pages;

use App\Models\AttendanceRecord;
use App\Models\AttendanceImmichAssetDetection;
use App\Models\AttendanceSheetImmichAlbum;
use App\Services\ImmichAttendanceSyncService;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionImmichAsset;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Services\ImmichApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AttendanceSheets extends Page
{
    public ?int $selectedSheetId = null;

    public ?int $selectedSessionId = null;

    public function mount(): void
    {
        $this->selectedSheetId =
            request()->integer(
                'sheetId'
            ) ?: null;

        $this->selectedSessionId =
            request()->integer(
                'sessionId'
            ) ?: null;

        /*
         * Session ID is the stronger identity.
         *
         * If a valid Session is supplied, derive its Sheet directly.
         * This also prevents a mismatched sheetId/sessionId pair.
         */
        if ($this->selectedSessionId) {
            $session =
                AttendanceSession::query()
                    ->find(
                        $this->selectedSessionId
                    );

            if ($session) {
                $this->selectedSheetId =
                    (int)
                    $session->attendance_sheet_id;
            } else {
                $this->selectedSessionId =
                    null;
            }
        }
    }

    protected string $view = 'filament.pages.attendance-sheets';

    public string $immichAlbumId = '';

    public string $immichAssetInput = '';


    public function getTitle(): string
    {
        return 'Attendance Sheets';
    }

    public static function getNavigationUrl(): string
    {
        $query = [];

        /*
         * When moving from Check Attendance through the
         * Filament navigation, preserve the currently selected
         * Attendance Sheet and Session.
         */
        if (
            request()->routeIs(
                'filament.quezonprovinceactivities.pages.check-attendance'
            )
        ) {
            $sheetId =
                request()->integer(
                    'sheetId'
                );

            $sessionId =
                request()->integer(
                    'sessionId'
                );

            if ($sheetId) {
                $query['sheetId'] =
                    $sheetId;
            }

            if ($sessionId) {
                $query['sessionId'] =
                    $sessionId;
            }
        }

        return static::getUrl()
            . (
                $query
                    ? '?'
                        . http_build_query(
                            $query
                        )
                    : ''
            );
    }


    public static function getNavigationLabel(): string
    {
        return 'Attendance Sheets';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function selectedMode(): string
    {
        $mode = request('mode', 'all');

        return in_array($mode, ['all', 'recurring', 'one_time'], true)
            ? $mode
            : 'all';
    }

    public function modeOptions(): array
    {
        return [
            'all' => 'All Sheets',
            'recurring' => 'Recurring',
            'one_time' => 'One-time',
        ];
    }

    public function modeUrl(string $mode): string
    {
        $query = array_merge(request()->query(), [
            'mode' => $mode,
        ]);

        if ($mode === 'all') {
            unset($query['mode']);
        }

        return static::getUrl() . ($query ? ('?' . http_build_query($query)) : '');
    }

    public function sheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->where('is_active', true)
            ->when(
                $this->selectedMode() === 'recurring',
                fn ($query) => $query->where('is_one_time', false)
            )
            ->when(
                $this->selectedMode() === 'one_time',
                fn ($query) => $query->where('is_one_time', true)
            )
            ->withCount([
            'sessions',
            'participants as participants_count' =>
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    ),
        ])
            ->orderByDesc('is_active')
            ->orderByRaw(
                'CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END'
            )
            ->orderBy('locality')
            ->latest()
            ->get();
    }

public function selectedSheet(): ?AttendanceSheet
{
    $query = AttendanceSheet::query()
        ->where(
            'sheet_type',
            AttendanceSheet::TYPE_CUSTOM
        )
        ->where(
            'is_active',
            true
        )
        ->withCount([
            'sessions',

            'participants as participants_count' =>
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    ),
        ])
        ->with([
            'immichAlbum',

            'sessions' =>
                fn ($query) =>
                    $query
                        ->with(
                            'immichAssets'
                        )
                        ->orderBy(
                            'session_date'
                        ),
        ]);

    /*
     * Keep the selected Sheet as Livewire component state.
     * Internal Livewire requests do not reliably contain the
     * original browser query string.
     */
    if ($this->selectedSheetId) {
        $selectedSheet =
            (clone $query)->find(
                $this->selectedSheetId
            );

        if ($selectedSheet) {
            return $selectedSheet;
        }
    }

    return $query
        ->latest()
        ->first();
}

    public function selectedSession(): ?AttendanceSession
    {
        $sheet =
            $this->selectedSheet();

        if (! $sheet) {
            return null;
        }

        if ($this->selectedSessionId) {
            $session =
                $sheet
                    ->sessions
                    ->firstWhere(
                        'id',
                        $this->selectedSessionId
                    );

            if ($session) {
                return $session;
            }
        }

        return $sheet
            ->sessions
            ->first();
    }

    public function sessionUrl(AttendanceSession $session): string
    {
        return self::getUrl() . '?' . http_build_query([
            'sheetId' => $session->attendance_sheet_id,
            'sessionId' => $session->id,
            'mode' => request('mode'),
        ]);
    }

public function syncImmich(int $sessionId): void
{
    /*
     * Resolve the Session directly from the ID supplied by the
     * button. Do not depend on request() query parameters here:
     * Livewire action requests do not reliably preserve the
     * original ?sheetId=...&sessionId=... browser query string.
     */
    $session = AttendanceSession::query()
        ->with([
            'sheet.immichAlbum',
            'immichAssets',
        ])
        ->whereHas(
            'sheet',
            fn ($query) =>
                $query
                    ->where(
                        'sheet_type',
                        AttendanceSheet::TYPE_CUSTOM
                    )
                    ->where(
                        'is_active',
                        true
                    )
        )
        ->find($sessionId);

    if (! $session) {
        Notification::make()
            ->title('Invalid attendance session')
            ->danger()
            ->send();

        return;
    }

    $sheet = $session->sheet;

    try {
        $result = app(ImmichAttendanceSyncService::class)
            ->sync($session);

        Notification::make()
            ->title('Immich attendance synchronized')
            ->body(
                'Source: '
                . (
                    $result['source'] === 'exact_photos'
                        ? 'Exact photo(s)'
                        : 'Sheet album'
                )
                . ' · Photos: ' . $result['assets']
                . ' · Detected: ' . $result['detections']
                . ' · Added: ' . $result['matched']
                . ' · Already present: ' . $result['already_present']
                . ' · Unmatched: ' . count($result['unmatched'])
            )
            ->success()
            ->send();

        $this->redirect(
            $this->sessionUrl($session),
            navigate: false,
        );

    } catch (\Throwable $e) {
        Log::error('Immich attendance synchronization failed.', [
            'attendance_session_id' => $session->id,
            'message' => $e->getMessage(),
        ]);

        Notification::make()
            ->title('Immich synchronization failed')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}

public function immichDetectionHistory(): Collection
{
    $sheet = $this->selectedSheet();

    if (! $sheet) {
        return collect();
    }

    /*
     * Detection history belongs to the whole Attendance Sheet.
     *
     * Individual attendance decisions remain Session-scoped.
     * Removing a Person from one Session must never hide their
     * Immich detections from that Session or affect another one.
     */
    $sessions = AttendanceSession::query()
        ->where(
            'attendance_sheet_id',
            $sheet->id
        )
        ->orderByDesc(
            'session_date'
        )
        ->get()
        ->keyBy('id');

    if ($sessions->isEmpty()) {
        return collect();
    }

    $sessionIds =
        $sessions
            ->keys()
            ->values();

    $detections =
        AttendanceImmichAssetDetection::query()
            ->with([
                'person.churchProfile',
            ])
            ->whereIn(
                'attendance_session_id',
                $sessionIds
            )
            ->orderBy(
                'detected_at'
            )
            ->get();

    if ($detections->isEmpty()) {
        return collect();
    }

    /*
     * AttendanceRecord is the actual attendance fact.
     */
    $records =
        AttendanceRecord::query()
            ->whereIn(
                'attendance_session_id',
                $sessionIds
            )
            ->get()
            ->keyBy(
                fn (
                    AttendanceRecord $record
                ): string =>
                    $record->attendance_session_id
                    . ':'
                    . $record->person_id
            );

    /*
     * Participant rows are used only to recognize a previously
     * rejected exact-meeting Immich detection.
     *
     * An inactive one-day participant row means the administrator
     * explicitly removed that Person from that Session.
     */
    $participants =
        AttendanceParticipant::query()
            ->where(
                'attendance_sheet_id',
                $sheet->id
            )
            ->get()
            ->groupBy('person_id');

    return $detections
        ->groupBy(
            'attendance_session_id'
        )
        ->map(
            function (
                Collection $sessionDetections,
                $sessionId
            ) use (
                $sessions,
                $records,
                $participants
            ): ?array {
                $session =
                    $sessions->get(
                        (int) $sessionId
                    );

                if (! $session) {
                    return null;
                }

                $sessionDate =
                    $session
                        ->session_date
                        ->format('Y-m-d');

                /*
                 * One Person can appear in multiple photos.
                 *
                 * Group mapped detections by Church Person.
                 * Unmapped faces fall back to Immich Person ID.
                 */
                $people =
                    $sessionDetections
                        ->groupBy(
                            function (
                                AttendanceImmichAssetDetection $detection
                            ): string {
                                if (
                                    filled(
                                        $detection->person_id
                                    )
                                ) {
                                    return 'person:'
                                        . (int)
                                            $detection->person_id;
                                }

                                return 'immich:'
                                    . $detection
                                        ->immich_person_id;
                            }
                        )
                        ->map(
                            function (
                                Collection $personDetections
                            ) use (
                                $session,
                                $sessionDate,
                                $records,
                                $participants
                            ): array {
                                $detection =
                                    $personDetections
                                        ->first();

                                $personId =
                                    filled(
                                        $detection
                                            ->person_id
                                    )
                                        ? (int)
                                            $detection
                                                ->person_id
                                        : null;

                                $record =
                                    $personId
                                        ? $records->get(
                                            $session->id
                                            . ':'
                                            . $personId
                                        )
                                        : null;

                                $removed =
                                    false;

                                if (
                                    $personId
                                    &&
                                    ! $record
                                ) {
                                    $removed =
                                        collect(
                                            $participants
                                                ->get(
                                                    $personId,
                                                    collect()
                                                )
                                        )
                                            ->contains(
                                                function (
                                                    AttendanceParticipant $participant
                                                ) use (
                                                    $sessionDate
                                                ): bool {
                                                    return
                                                        ! $participant
                                                            ->is_active
                                                        &&
                                                        $participant
                                                            ->starts_on
                                                            ?->format(
                                                                'Y-m-d'
                                                            )
                                                            ===
                                                            $sessionDate
                                                        &&
                                                        $participant
                                                            ->ends_on
                                                            ?->format(
                                                                'Y-m-d'
                                                            )
                                                            ===
                                                            $sessionDate;
                                                }
                                            );
                                }

                                $status =
                                    match (true) {
                                        ! $personId =>
                                            'unmatched',

                                        (bool)
                                            $record
                                                ?->is_present =>
                                            'present',

                                        $record !== null =>
                                            'absent',

                                        $removed =>
                                            'removed',

                                        default =>
                                            'detected',
                                    };

                                return [
                                    'identity_key' =>
                                        $personId
                                            ? 'person:'
                                                . $personId
                                            : 'immich:'
                                                . $detection
                                                    ->immich_person_id,

                                    'person_id' =>
                                        $personId,

                                    'person' =>
                                        $detection
                                            ->person,

                                    'name' =>
                                        $detection
                                            ->person
                                            ?->display_name
                                        ?? 'Unmatched Immich Person',

                                    'immich_person_id' =>
                                        $detection
                                            ->immich_person_id,

                                    'status' =>
                                        $status,

                                    'detection_events' =>
                                        $personDetections
                                            ->count(),

                                    'asset_count' =>
                                        $personDetections
                                            ->pluck(
                                                'immich_asset_id'
                                            )
                                            ->unique()
                                            ->count(),

                                    'detected_at' =>
                                        $personDetections
                                            ->max(
                                                'detected_at'
                                            ),
                                ];
                            }
                        )
                        ->sortBy('name')
                        ->values();

                return [
                    'session' =>
                        $session,

                    'session_id' =>
                        (int) $session->id,

                    'session_date' =>
                        $sessionDate,

                    'detection_events' =>
                        $sessionDetections
                            ->count(),

                    'detected_people' =>
                        $people->count(),

                    'present' =>
                        $people
                            ->where(
                                'status',
                                'present'
                            )
                            ->count(),

                    'absent' =>
                        $people
                            ->where(
                                'status',
                                'absent'
                            )
                            ->count(),

                    'removed' =>
                        $people
                            ->where(
                                'status',
                                'removed'
                            )
                            ->count(),

                    'unmatched' =>
                        $people
                            ->where(
                                'status',
                                'unmatched'
                            )
                            ->count(),

                    'detected_only' =>
                        $people
                            ->where(
                                'status',
                                'detected'
                            )
                            ->count(),

                    'people' =>
                        $people,
                ];
            }
        )
        ->filter()
        ->sortByDesc(
            'session_date'
        )
        ->values();
}

public function sessionAttendanceSummary(AttendanceSession $session): array
{
    $records = AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->get();

    $present = $records->where('is_present', true);

    return [
        'present' => $present->count(),
        'absent' => $records->where('is_present', false)->count(),
        'marked' => $records->count(),
        'immich' => $present
            ->where('attendance_source', AttendanceRecord::SOURCE_IMMICH)
            ->count(),
        'manual' => $present
            ->where('attendance_source', AttendanceRecord::SOURCE_MANUAL)
            ->count(),
        'immich_pending' => $present
            ->where('attendance_source', AttendanceRecord::SOURCE_IMMICH)
            ->where('immich_confirmed', false)
            ->count(),
        'immich_confirmed' => $present
            ->where('attendance_source', AttendanceRecord::SOURCE_IMMICH)
            ->where('immich_confirmed', true)
            ->count(),
    ];
}

    public function participantRows(): Collection
    {
        $session = $this->selectedSession();

        if (! $session) {
            return collect();
        }

        $sessionDate =
            $session
                ->session_date
                ->format('Y-m-d');

        return AttendanceParticipant::query()
            ->with([
                'person.churchProfile',
            ])
            ->where(
                'attendance_sheet_id',
                $session->attendance_sheet_id
            )
            ->activeOn($sessionDate)
            ->get()
            ->unique(
                fn (
                    AttendanceParticipant $participant
                ): int =>
                    (int) $participant->person_id
            )
            ->sortBy(
                fn (
                    AttendanceParticipant $participant
                ): string =>
                    $participant
                        ->person
                        ?->display_name
                    ?? ''
            )
            ->values();
    }

    public function availablePeople(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        $existingPersonIds = AttendanceParticipant::query()
            ->where(
                'attendance_sheet_id',
                $sheet->id
            )
            ->where(
                'is_active',
                true
            )
            ->pluck('person_id');

        return Person::query()
            ->with(['churchProfile'])
            ->whereNotIn('id', $existingPersonIds)
            ->when(
                filled($sheet->locality),
                fn ($query) => $query->orderByRaw(
                    'CASE WHEN locality = ? THEN 0 ELSE 1 END',
                    [$sheet->locality]
                )
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    /*
     * ============================================================
     * PHASE 23A — IMMICH ALBUM LINKING
     * ============================================================
     */

    public function immichAlbums(): array
    {
        try {
            return app(ImmichApiService::class)->albums();
        } catch (\Throwable $e) {
            Log::warning('Unable to load Immich albums.', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

public function linkImmichAlbum(
    int $sheetId,
): void {
    $albumId =
        trim(
            $this->immichAlbumId
        );

    $sheet = AttendanceSheet::query()
        ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
        ->where('is_active', true)
        ->find($sheetId);

    if (! $sheet) {
        Notification::make()
            ->title('Attendance Sheet not found')
            ->danger()
            ->send();

        return;
    }

    if (blank($albumId)) {
        Notification::make()
            ->title('No Immich album selected')
            ->warning()
            ->send();

        return;
    }

    try {
        $album = app(ImmichApiService::class)
            ->album($albumId);

        AttendanceSheetImmichAlbum::updateOrCreate(
            [
                'attendance_sheet_id' => $sheet->id,
            ],
            [
                'immich_album_id' => $album['id'],
                'immich_album_name' =>
                    $album['albumName'] ?? 'Unnamed album',
                'enabled' => true,
                'last_modified_asset_at' =>
                    $album['lastModifiedAssetTimestamp'] ?? null,
            ],
        );

        $this->immichAlbumId = '';

        Notification::make()
            ->title('Immich album linked')
            ->body(
                ($album['albumName'] ?? 'Unnamed album')
                . ' is now linked to '
                . $sheet->title . '.'
            )
            ->success()
            ->send();

        $this->redirect(
            $this->sheetUrl($sheet),
            navigate: false,
        );
    } catch (\Throwable $e) {
        Log::error('Failed to link Immich album.', [
            'attendance_sheet_id' => $sheet->id,
            'immich_album_id' => $albumId,
            'message' => $e->getMessage(),
        ]);

        Notification::make()
            ->title('Could not link Immich album')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}

public function unlinkImmichAlbum(int $sheetId): void
{
    $sheet = AttendanceSheet::query()
        ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
        ->where('is_active', true)
        ->find($sheetId);

    if (! $sheet) {
        return;
    }

    AttendanceSheetImmichAlbum::query()
        ->where('attendance_sheet_id', $sheet->id)
        ->delete();

    $this->immichAlbumId = '';

    Notification::make()
        ->title('Immich album unlinked')
        ->success()
        ->send();

    $this->redirect(
        $this->sheetUrl($sheet),
        navigate: false,
    );
}

    public function linkImmichAsset(
        int $sessionId,
    ): void {
        /*
         * Resolve by Session ID rather than by selectedSheet().
         * This keeps the action stable across Livewire requests.
         */
        $session = AttendanceSession::query()
            ->with('sheet')
            ->whereHas(
                'sheet',
                fn ($query) =>
                    $query
                        ->where(
                            'sheet_type',
                            AttendanceSheet::TYPE_CUSTOM
                        )
                        ->where(
                            'is_active',
                            true
                        )
            )
            ->find($sessionId);

        if (! $session) {
            Notification::make()
                ->title('Attendance Session not found')
                ->danger()
                ->send();

            return;
        }

        $sheet = $session->sheet;

        $assetId = $this->extractImmichAssetId(
            $this->immichAssetInput
        );

        if (! $assetId) {
            Notification::make()
                ->title('Invalid Immich photo')
                ->body(
                    'Paste an Immich photo URL '
                    . 'or its asset UUID.'
                )
                ->warning()
                ->send();

            return;
        }

        try {
            $asset = app(
                ImmichApiService::class
            )->asset($assetId);

            if (
                filled($asset['type'] ?? null)
                && strtoupper(
                    (string) $asset['type']
                ) !== 'IMAGE'
            ) {
                Notification::make()
                    ->title('Immich asset is not a photo')
                    ->body(
                        'Only IMAGE assets can be '
                        . 'linked as exact attendance photos.'
                    )
                    ->warning()
                    ->send();

                return;
            }

            AttendanceSessionImmichAsset::updateOrCreate(
                [
                    'attendance_session_id' =>
                        $session->id,

                    'immich_asset_id' =>
                        $asset['id'] ?? $assetId,
                ],
                [
                    'immich_asset_name' =>
                        $asset['originalFileName']
                        ?? $asset['originalPath']
                        ?? 'Immich Photo',

                    'asset_taken_at' =>
                        $asset['localDateTime']
                        ?? $asset['fileCreatedAt']
                        ?? null,
                ],
            );

            $this->immichAssetInput = '';

            Notification::make()
                ->title('Immich photo linked')
                ->body(
                    'This exact photo now overrides '
                    . 'the Sheet album for this Session.'
                )
                ->success()
                ->send();

            $this->redirect(
                $this->sessionUrl($session),
                navigate: false,
            );
        } catch (\Throwable $e) {
            Log::error(
                'Failed to link exact Immich photo.',
                [
                    'attendance_session_id' =>
                        $session->id,
                    'immich_asset_id' =>
                        $assetId,
                    'message' =>
                        $e->getMessage(),
                ],
            );

            Notification::make()
                ->title('Could not link Immich photo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function unlinkImmichAsset(
        int $assetLinkId,
    ): void {
        $sheet = $this->selectedSheet();
        $session = $this->selectedSession();

        if (! $sheet || ! $session) {
            return;
        }

        AttendanceSessionImmichAsset::query()
            ->where('id', $assetLinkId)
            ->where(
                'attendance_session_id',
                $session->id
            )
            ->delete();

        Notification::make()
            ->title('Immich photo unlinked')
            ->body(
                'Existing attendance and Immich '
                . 'detection history were preserved.'
            )
            ->success()
            ->send();

        $this->redirect(
            $this->sessionUrl($session),
            navigate: false,
        );
    }

    protected function extractImmichAssetId(
        string $value,
    ): ?string {
        /*
         * Accept:
         *
         * - bare Immich asset UUID
         * - public Immich photo URL
         * - LAN Immich photo URL
         * - any other URL containing the asset UUID
         *
         * The hostname is deliberately ignored.
         * The configured Immich API connection is used
         * after the asset UUID has been extracted.
         */
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (
            preg_match(
                '/(?<![0-9a-f])'
                . '[0-9a-f]{8}-'
                . '[0-9a-f]{4}-'
                . '[0-9a-f]{4}-'
                . '[0-9a-f]{4}-'
                . '[0-9a-f]{12}'
                . '(?![0-9a-f])/i',
                $value,
                $matches,
            ) !== 1
        ) {
            return null;
        }

        return strtolower($matches[0]);
    }

    public function sheetUrl(AttendanceSheet $sheet): string
    {
        return self::getUrl() . '?' . http_build_query([
            'sheetId' => $sheet->id,
            'mode' => request('mode'),
        ]);
    }
}
