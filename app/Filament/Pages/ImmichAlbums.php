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

    /*
     * Create Album modal.
     */
    public bool $showCreateAlbumModal = false;

    public string $newAlbumName = '';

    public string $newAlbumDescription = '';

    /*
     * Create Shared Link modal.
     */
    public bool $showCreateLinkModal = false;

    public ?string $createLinkAlbumId = null;

    public string $createLinkAlbumName = '';

    public string $shareSlug = '';

    public string $sharePassword = '';

    public string $shareDescription = '';

    public string $shareExpiry = 'never';

    public bool $shareShowMetadata = true;

    public bool $shareAllowDownload = true;

    public bool $shareAllowUpload = false;

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

            /*
             * Read the real Shared Links from Immich.
             *
             * A single album may have more than one historical link.
             * Prefer a non-expired custom-slug link, then the newest
             * non-expired normal link.
             */
            $activeSharedLinks =
                collect(
                    $immich->sharedLinks()
                )
                    ->filter(
                        function ($link): bool {
                            if (! is_array($link)) {
                                return false;
                            }

                            if (
                                strtoupper(
                                    (string) (
                                        $link['type']
                                        ?? ''
                                    )
                                )
                                !== 'ALBUM'
                            ) {
                                return false;
                            }

                            if (
                                blank(
                                    data_get(
                                        $link,
                                        'album.id'
                                    )
                                )
                            ) {
                                return false;
                            }

                            $expiresAt =
                                $link['expiresAt']
                                ?? null;

                            if (blank($expiresAt)) {
                                return true;
                            }

                            try {
                                return CarbonImmutable::parse(
                                    $expiresAt
                                )->isFuture();
                            } catch (Throwable) {
                                return false;
                            }
                        }
                    )
                    ->groupBy(
                        fn (array $link): string =>
                            (string)
                            data_get(
                                $link,
                                'album.id'
                            )
                    )
                    ->map(
                        function ($links): array {
                            return $links
                                ->sort(
                                    function (
                                        array $a,
                                        array $b
                                    ): int {
                                        $aSlug =
                                            filled(
                                                $a['slug']
                                                ?? null
                                            )
                                                ? 1
                                                : 0;

                                        $bSlug =
                                            filled(
                                                $b['slug']
                                                ?? null
                                            )
                                                ? 1
                                                : 0;

                                        if (
                                            $aSlug
                                            !== $bSlug
                                        ) {
                                            return $bSlug
                                                <=>
                                                $aSlug;
                                        }

                                        return strcmp(
                                            (string) (
                                                $b['createdAt']
                                                ?? ''
                                            ),
                                            (string) (
                                                $a['createdAt']
                                                ?? ''
                                            )
                                        );
                                    }
                                )
                                ->first();
                        }
                    );

            $this->albums =
                collect($this->albums)
                    ->map(
                        function (
                            array $album
                        ) use (
                            $activeSharedLinks
                        ): array {
                            $albumId =
                                (string)
                                $album['id'];

                            $sharedLink =
                                $activeSharedLinks
                                    ->get(
                                        $albumId
                                    );

                            $sharedUrl =
                                is_array($sharedLink)
                                    ? $this
                                        ->sharedLinkWebUrl(
                                            $sharedLink
                                        )
                                    : null;

                            $album['sharedUrl'] =
                                $sharedUrl;

                            /*
                             * Whole card:
                             *
                             * public Shared Link when available,
                             * otherwise authenticated Immich album.
                             */
                            $album['clickUrl'] =
                                $sharedUrl
                                ?: $this
                                    ->albumWebUrl(
                                        $albumId
                                    );

                            return $album;
                        }
                    )
                    ->values()
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

    public function immichPublicUrl(): string
    {
        return rtrim(
            (string) (
                config('services.immich.public_url')
                ?: config('services.immich.url')
            ),
            '/'
        );
    }

    public function albumWebUrl(
        string $albumId
    ): string {
        return $this->immichPublicUrl()
            . '/albums/'
            . rawurlencode($albumId);
    }

    public function sharedLinkWebUrl(
        array $link
    ): ?string {
        /*
         * Preserve an Immich custom URL when one exists.
         */
        $slug = trim(
            (string) ($link['slug'] ?? '')
        );

        if ($slug !== '') {
            return $this->immichPublicUrl()
                . '/s/'
                . rawurlencode($slug);
        }

        /*
         * Otherwise use Immich's normal secret share key.
         */
        $key = trim(
            (string) ($link['key'] ?? '')
        );

        if ($key === '') {
            return null;
        }

        return $this->immichPublicUrl()
            . '/share/'
            . rawurlencode($key);
    }

    public function openCreateAlbumModal(): void
    {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $this->newAlbumName = '';
        $this->newAlbumDescription = '';
        $this->showCreateAlbumModal = true;
    }

    public function closeCreateAlbumModal(): void
    {
        $this->showCreateAlbumModal = false;
    }

    public function createAlbum(): void
    {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $albumName =
            trim(
                $this->newAlbumName
            );

        if ($albumName === '') {
            Notification::make()
                ->title('Album name is required')
                ->warning()
                ->send();

            return;
        }

        try {
            $created =
                app(
                    ImmichApiService::class
                )
                    ->createAlbum(
                        albumName:
                            $albumName,

                        description:
                            trim(
                                $this
                                    ->newAlbumDescription
                            )
                    );

            $this->showCreateAlbumModal =
                false;

            $this->refreshImmich(
                showNotification: false
            );

            Notification::make()
                ->title('Immich Album created')
                ->body(
                    (
                        $created['albumName']
                        ?? $albumName
                    )
                    . ' was created successfully.'
                )
                ->success()
                ->send();

        } catch (Throwable $e) {
            Log::error(
                'Unable to create Immich Album.',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            Notification::make()
                ->title('Could not create album')
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }

    public function openCreateLinkModal(
        string $albumId
    ): void {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $album =
            collect(
                $this->albums
            )
                ->first(
                    fn (
                        array $album
                    ): bool =>
                        (string) (
                            $album['id']
                            ?? ''
                        )
                        === $albumId
                );

        if (! $album) {
            Notification::make()
                ->title(
                    'Immich album not found'
                )
                ->danger()
                ->send();

            return;
        }

        if (
            filled(
                $album['sharedUrl']
                ?? null
            )
        ) {
            Notification::make()
                ->title(
                    'Shared Link already exists'
                )
                ->warning()
                ->send();

            return;
        }

        $this->createLinkAlbumId =
            $albumId;

        $this->createLinkAlbumName =
            (string) (
                $album['albumName']
                ?? 'Album'
            );

        $this->shareSlug = '';
        $this->sharePassword = '';
        $this->shareDescription = '';
        $this->shareExpiry = 'never';

        /*
         * Match the current Immich Create Link UI defaults.
         */
        $this->shareShowMetadata = true;
        $this->shareAllowDownload = true;
        $this->shareAllowUpload = false;

        $this->showCreateLinkModal = true;
    }

    public function closeCreateLinkModal(): void
    {
        $this->showCreateLinkModal = false;
        $this->createLinkAlbumId = null;
    }

    public function createSharedLink(): void
    {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $albumId =
            trim(
                (string) (
                    $this
                        ->createLinkAlbumId
                    ?? ''
                )
            );

        if ($albumId === '') {
            Notification::make()
                ->title(
                    'No album selected'
                )
                ->danger()
                ->send();

            return;
        }

        $album =
            collect(
                $this->albums
            )
                ->first(
                    fn (
                        array $album
                    ): bool =>
                        (string) (
                            $album['id']
                            ?? ''
                        )
                        === $albumId
                );

        if (! $album) {
            Notification::make()
                ->title(
                    'Immich album not found'
                )
                ->danger()
                ->send();

            return;
        }

        if (
            filled(
                $album['sharedUrl']
                ?? null
            )
        ) {
            $this->showCreateLinkModal =
                false;

            Notification::make()
                ->title(
                    'Shared Link already exists'
                )
                ->warning()
                ->send();

            return;
        }

        try {
            app(
                ImmichApiService::class
            )
                ->createAlbumSharedLink(
                    albumId:
                        $albumId,

                    options: [
                        'slug' =>
                            trim(
                                $this->shareSlug
                            ),

                        'password' =>
                            $this->sharePassword,

                        'description' =>
                            trim(
                                $this
                                    ->shareDescription
                            ),

                        'expiresAt' =>
                            $this
                                ->sharedLinkExpiryIso(),

                        'showMetadata' =>
                            $this
                                ->shareShowMetadata,

                        'allowDownload' =>
                            $this
                                ->shareAllowDownload,

                        'allowUpload' =>
                            $this
                                ->shareAllowUpload,
                    ]
                );

            $this->showCreateLinkModal =
                false;

            $this->createLinkAlbumId =
                null;

            /*
             * Reload from Immich. Immich remains the
             * source of truth for shared links.
             */
            $this->refreshImmich(
                showNotification: false
            );

            Notification::make()
                ->title(
                    'Immich Shared Link created'
                )
                ->body(
                    (
                        $album['albumName']
                        ?? 'Album'
                    )
                    . ' can now be shared publicly.'
                )
                ->success()
                ->send();

        } catch (Throwable $e) {
            Log::error(
                'Unable to create Immich album Shared Link.',
                [
                    'immich_album_id' =>
                        $albumId,

                    /*
                     * Do not log passwords, slugs,
                     * or generated share keys.
                     */
                    'message' =>
                        $e->getMessage(),
                ]
            );

            Notification::make()
                ->title(
                    'Could not create Shared Link'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }

    private function sharedLinkExpiryIso(): ?string
    {
        return match (
            $this->shareExpiry
        ) {
            '1_day' =>
                CarbonImmutable::now()
                    ->addDay()
                    ->toISOString(),

            '7_days' =>
                CarbonImmutable::now()
                    ->addDays(7)
                    ->toISOString(),

            '30_days' =>
                CarbonImmutable::now()
                    ->addDays(30)
                    ->toISOString(),

            '3_months' =>
                CarbonImmutable::now()
                    ->addMonths(3)
                    ->toISOString(),

            '1_year' =>
                CarbonImmutable::now()
                    ->addYear()
                    ->toISOString(),

            default =>
                null,
        };
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
