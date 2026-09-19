<?php

namespace App\Filament\Pages;

use App\Models\AttendanceSheetImmichAlbum;
use App\Services\ImmichApiService;
use App\Services\ImmichAlbumThumbnailService;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ImmichAlbums extends Page
{
    protected string $view = 'filament.pages.immich-albums';

    public array $albums = [];

    public array $serverVersion = [];

    public array $serverStatistics = [];

    public array $serverStorage = [];

    public string $albumSearch = '';

    public string $albumFilter = 'all';

    public bool $connectionHealthy = false;

    public ?string $connectionError = null;

    public array $loadWarnings = [];

    public ?string $lastLoadedAt = null;

    public function mount(): void
    {
        $this->refreshImmich(
            showNotification: false
        );
    }

    public function getTitle(): string
    {
        return 'Immich Albums';
    }

    public static function getNavigationLabel(): string
    {
        return 'Immich Albums';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-photo';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function refreshImmich(
        bool $showNotification = true
    ): void {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $this->connectionError = null;
        $this->loadWarnings = [];
        $this->connectionHealthy = false;

        $immich =
            app(ImmichApiService::class);

        /*
         * Test the base connection first.
         */
        try {
            if (! $immich->ping()) {
                throw new RuntimeException(
                    'Immich did not respond to the server ping.'
                );
            }

            $this->connectionHealthy = true;
        } catch (Throwable $e) {
            $this->albums = [];
            $this->serverVersion = [];
            $this->serverStatistics = [];
            $this->serverStorage = [];

            $this->connectionError =
                'Unable to connect to Immich.';

            Log::error(
                'Immich Albums connection failed.',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            if ($showNotification) {
                Notification::make()
                    ->title(
                        'Unable to connect to Immich'
                    )
                    ->body(
                        'Check the Immich service and connection settings.'
                    )
                    ->danger()
                    ->send();
            }

            return;
        }

        /*
         * Load version independently.
         *
         * A problem with one optional dashboard endpoint must
         * not hide otherwise-valid album data.
         */
        try {
            $this->serverVersion =
                $immich->version();
        } catch (Throwable $e) {
            $this->serverVersion = [];

            $this->loadWarnings[] =
                'Server version could not be loaded.';

            Log::warning(
                'Unable to load Immich server version.',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );
        }

        /*
         * Albums are intentionally sanitized before being stored
         * as Livewire state.
         *
         * Do not expose albumUsers or other unnecessary Immich
         * response fields inside the Livewire browser snapshot.
         */
        try {
            $rawAlbums =
                $immich->albums();

            $this->albums =
                collect($rawAlbums)
                    ->filter(
                        fn ($album): bool =>
                            is_array($album)
                            &&
                            filled(
                                $album['id']
                                ?? null
                            )
                    )
                    ->map(
                        function (
                            array $album
                        ): array {
                            return [
                                'id' =>
                                    (string)
                                    $album['id'],

                                'albumName' =>
                                    trim(
                                        (string) (
                                            $album[
                                                'albumName'
                                            ]
                                            ?? 'Unnamed album'
                                        )
                                    ),

                                'description' =>
                                    trim(
                                        (string) (
                                            $album[
                                                'description'
                                            ]
                                            ?? ''
                                        )
                                    ),

                                'albumThumbnailAssetId' =>
                                    filled(
                                        $album[
                                            'albumThumbnailAssetId'
                                        ]
                                        ?? null
                                    )
                                        ? (string)
                                            $album[
                                                'albumThumbnailAssetId'
                                            ]
                                        : null,

                                'assetCount' =>
                                    (int) (
                                        $album[
                                            'assetCount'
                                        ]
                                        ?? 0
                                    ),

                                'shared' =>
                                    (bool) (
                                        $album[
                                            'shared'
                                        ]
                                        ?? false
                                    ),

                                'hasSharedLink' =>
                                    (bool) (
                                        $album[
                                            'hasSharedLink'
                                        ]
                                        ?? false
                                    ),

                                'createdAt' =>
                                    $album[
                                        'createdAt'
                                    ]
                                    ?? null,

                                'updatedAt' =>
                                    $album[
                                        'updatedAt'
                                    ]
                                    ?? null,

                                'startDate' =>
                                    $album[
                                        'startDate'
                                    ]
                                    ?? null,

                                'endDate' =>
                                    $album[
                                        'endDate'
                                    ]
                                    ?? null,

                                'lastModifiedAssetTimestamp' =>
                                    $album[
                                        'lastModifiedAssetTimestamp'
                                    ]
                                    ?? null,
                            ];
                        }
                    )
                    ->sortByDesc(
                        fn (
                            array $album
                        ): string =>
                            (string) (
                                $album[
                                    'lastModifiedAssetTimestamp'
                                ]
                                ?? $album[
                                    'updatedAt'
                                ]
                                ?? ''
                            )
                    )
                    ->values()
                    ->all();

            /*
             * Cache album cover pictures locally during every
             * Immich Refresh, just like Immich People Linking.
             *
             * Unchanged covers are reused from local storage.
             * A changed albumThumbnailAssetId causes the new
             * cover to be downloaded automatically.
             */
            $this->albums =
                app(
                    ImmichAlbumThumbnailService::class
                )
                    ->syncAlbums(
                        $this->albums
                    )
                    ->all();

        } catch (Throwable $e) {
            $this->albums = [];

            $this->loadWarnings[] =
                'Albums could not be loaded.';

            Log::error(
                'Unable to load Immich albums.',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );
        }

        try {
            $this->serverStatistics =
                $immich
                    ->serverStatistics();
        } catch (Throwable $e) {
            $this->serverStatistics = [];

            $this->loadWarnings[] =
                'Server statistics could not be loaded.';

            Log::warning(
                'Unable to load Immich server statistics.',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );
        }

        try {
            $this->serverStorage =
                $immich
                    ->serverStorage();
        } catch (Throwable $e) {
            $this->serverStorage = [];

            $this->loadWarnings[] =
                'Storage information could not be loaded.';

            Log::warning(
                'Unable to load Immich server storage.',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );
        }

        $this->lastLoadedAt =
            now()->toIso8601String();

        if (! $showNotification) {
            return;
        }

        if ($this->loadWarnings !== []) {
            Notification::make()
                ->title(
                    'Immich refreshed with warnings'
                )
                ->body(
                    implode(
                        ' ',
                        $this->loadWarnings
                    )
                )
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title(
                'Immich data refreshed'
            )
            ->body(
                count($this->albums)
                . ' album(s) loaded.'
            )
            ->success()
            ->send();
    }

    public function setAlbumFilter(
        string $filter
    ): void {
        $this->albumFilter =
            in_array(
                $filter,
                [
                    'all',
                    'linked',
                    'unlinked',
                    'shared',
                ],
                true
            )
                ? $filter
                : 'all';
    }

    public function albumRows(): array
    {
        $links =
            $this->linkedAlbumMap();

        $search =
            mb_strtolower(
                trim(
                    $this->albumSearch
                )
            );

        return collect(
            $this->albums
        )
            ->map(
                function (
                    array $album
                ) use (
                    $links
                ): array {
                    $link =
                        $links[
                            $album['id']
                        ]
                        ?? null;

                    $album['linked'] =
                        $link !== null;

                    $album['link'] =
                        $link;

                    return $album;
                }
            )
            ->filter(
                function (
                    array $album
                ): bool {
                    return match (
                        $this->albumFilter
                    ) {
                        'linked' =>
                            $album[
                                'linked'
                            ],

                        'unlinked' =>
                            ! $album[
                                'linked'
                            ],

                        'shared' =>
                            (bool)
                            $album[
                                'hasSharedLink'
                            ],

                        default =>
                            true,
                    };
                }
            )
            ->filter(
                function (
                    array $album
                ) use (
                    $search
                ): bool {
                    if ($search === '') {
                        return true;
                    }

                    $haystack =
                        mb_strtolower(
                            implode(
                                ' ',
                                array_filter([
                                    $album[
                                        'albumName'
                                    ]
                                    ?? '',

                                    $album[
                                        'description'
                                    ]
                                    ?? '',

                                    data_get(
                                        $album,
                                        'link.sheet_title'
                                    ),

                                    data_get(
                                        $album,
                                        'link.sheet_locality'
                                    ),
                                ])
                            )
                        );

                    return str_contains(
                        $haystack,
                        $search
                    );
                }
            )
            ->values()
            ->all();
    }

    public function linkedAlbumMap(): array
    {
        return AttendanceSheetImmichAlbum::query()
            ->with([
                'attendanceSheet:id,title,locality',
            ])
            ->get()
            ->mapWithKeys(
                function (
                    AttendanceSheetImmichAlbum $link
                ): array {
                    return [
                        $link->immich_album_id => [
                            'attendance_sheet_id' =>
                                $link
                                    ->attendance_sheet_id,

                            'sheet_title' =>
                                $link
                                    ->attendanceSheet
                                    ?->title,

                            'sheet_locality' =>
                                $link
                                    ->attendanceSheet
                                    ?->locality,

                            'enabled' =>
                                (bool)
                                $link->enabled,

                            'last_synced_at' =>
                                optional(
                                    $link
                                        ->last_synced_at
                                )
                                    ->toIso8601String(),
                        ],
                    ];
                }
            )
            ->all();
    }

    public function linkedAlbumCount(): int
    {
        return AttendanceSheetImmichAlbum::query()
            ->count();
    }

    public function sharedLinkCount(): int
    {
        return collect(
            $this->albums
        )
            ->where(
                'hasSharedLink',
                true
            )
            ->count();
    }

    public function storageUsagePercentage(): float
    {
        $percentage =
            $this->serverStorage[
                'diskUsagePercentage'
            ]
            ?? null;

        if (! is_numeric($percentage)) {
            return 0;
        }

        return max(
            0,
            min(
                100,
                (float) $percentage
            )
        );
    }

    public function versionLabel(): string
    {
        if (
            ! isset(
                $this->serverVersion[
                    'major'
                ],
                $this->serverVersion[
                    'minor'
                ],
                $this->serverVersion[
                    'patch'
                ]
            )
        ) {
            return 'Unknown version';
        }

        $version =
            $this->serverVersion[
                'major'
            ]
            . '.'
            . $this->serverVersion[
                'minor'
            ]
            . '.'
            . $this->serverVersion[
                'patch'
            ];

        $prerelease =
            trim(
                (string) (
                    $this->serverVersion[
                        'prerelease'
                    ]
                    ?? ''
                )
            );

        return $prerelease !== ''
            ? $version
                . '-'
                . $prerelease
            : $version;
    }

    public function formatBytes(
        mixed $bytes
    ): string {
        if (
            $bytes === null
            ||
            $bytes === ''
            ||
            ! is_numeric($bytes)
        ) {
            return '—';
        }

        $value =
            (float) $bytes;

        $units = [
            'B',
            'KiB',
            'MiB',
            'GiB',
            'TiB',
            'PiB',
        ];

        $unitIndex = 0;

        while (
            $value >= 1024
            &&
            $unitIndex
                < count($units) - 1
        ) {
            $value /= 1024;
            $unitIndex++;
        }

        $precision =
            $unitIndex === 0
                ? 0
                : (
                    $value >= 100
                        ? 0
                        : (
                            $value >= 10
                                ? 1
                                : 2
                        )
                );

        return number_format(
            $value,
            $precision
        )
            . ' '
            . $units[
                $unitIndex
            ];
    }

    public function formatNumber(
        mixed $value
    ): string {
        return is_numeric($value)
            ? number_format(
                (int) $value
            )
            : '—';
    }

    public function formatDate(
        mixed $value
    ): string {
        if (blank($value)) {
            return '—';
        }

        try {
            return CarbonImmutable::parse(
                (string) $value
            )->format(
                'M d, Y'
            );
        } catch (Throwable) {
            return '—';
        }
    }

    public function formatDateTime(
        mixed $value
    ): string {
        if (blank($value)) {
            return '—';
        }

        try {
            return CarbonImmutable::parse(
                (string) $value
            )->format(
                'M d, Y g:i A'
            );
        } catch (Throwable) {
            return '—';
        }
    }
}
