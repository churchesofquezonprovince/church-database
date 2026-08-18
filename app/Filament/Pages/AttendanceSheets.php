<?php

namespace App\Filament\Pages;

use App\Models\AttendanceSheetImmichAlbum;
use App\Services\ImmichAttendanceSyncService;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionImmichAlbum;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Services\ImmichApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AttendanceSheets extends Page
{
    protected string $view = 'filament.pages.attendance-sheets';

    public string $immichAlbumId = '';

    public function getTitle(): string
    {
        return 'Attendance Sheets';
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
            ->withCount(['sessions', 'participants'])
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
    $sheetId = request()->integer('sheetId');

    $query = AttendanceSheet::query()
        ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
        ->where('is_active', true)
        ->withCount(['sessions', 'participants'])
        ->with([
            'immichAlbum',
            'sessions' => fn ($query) => $query
                ->orderBy('session_date'),
        ]);

    if ($sheetId) {
        $selectedSheet = (clone $query)->find($sheetId);

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
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return null;
        }

        $sessionId = request()->integer('sessionId');

        if ($sessionId) {
            $session = $sheet->sessions
                ->firstWhere('id', $sessionId);

            if ($session) {
                return $session;
            }
        }

        return $sheet->sessions->first();
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
    $sheet = $this->selectedSheet();

    if (! $sheet) {
        return;
    }

    $session = $sheet->sessions->firstWhere('id', $sessionId);

    if (! $session) {
        Notification::make()
            ->title('Invalid attendance session')
            ->danger()
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


    public function participantRows(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        return AttendanceParticipant::query()
            ->with(['person.churchProfile'])
            ->where('attendance_sheet_id', $sheet->id)
            ->get()
            ->sortBy(
                fn (AttendanceParticipant $participant): string =>
                    $participant->person?->display_name ?? ''
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
            ->where('attendance_sheet_id', $sheet->id)
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
    string $albumId,
): void {
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

    public function sheetUrl(AttendanceSheet $sheet): string
    {
        return self::getUrl() . '?' . http_build_query([
            'sheetId' => $sheet->id,
            'mode' => request('mode'),
        ]);
    }
}
