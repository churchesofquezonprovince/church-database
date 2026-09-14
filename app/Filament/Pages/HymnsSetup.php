<?php

namespace App\Filament\Pages;

use App\Models\Hymn;
use App\Models\HymnAdditionRequest;
use App\Models\HymnBook;
use App\Services\HymnAdditionRequestReviewService;
use App\Services\SongbaseHymnSyncService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class HymnsSetup extends Page
{
    protected string $view =
        'filament.pages.hymns-setup';

    protected static ?string $slug =
        'hymns-setup';

    public string $search = '';

    public string $language = 'english';

    /*
     * Pending Hymn Request resolution state.
     *
     * Mode:
     * - link = attach the request/source to
     *   an existing canonical Hymn.
     * - new = create a new canonical Hymn.
     */
    public array $hymnRequestModes = [];

    public array $hymnRequestSearches = [];

    public array $hymnRequestTargetIds = [];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );
    }

    public function getTitle(): string
    {
        return 'Hymns Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'Hymns Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-musical-note';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function summary(): array
    {
        return [
            'songs' =>
                Hymn::query()->count(),

            'active' =>
                Hymn::query()
                    ->where('is_active', true)
                    ->count(),

            'languages' =>
                Hymn::query()
                    ->whereNotNull('language')
                    ->distinct()
                    ->count('language'),

            'books' =>
                HymnBook::query()->count(),

            'book_entries' =>
                DB::table(
                    'hymn_book_entries'
                )->count(),

            'pending_requests' =>
                HymnAdditionRequest::query()
                    ->where(
                        'status',
                        HymnAdditionRequest::STATUS_PENDING
                    )
                    ->count(),

            'last_synced_at' =>
                Hymn::query()
                    ->where(
                        'source',
                        'songbase'
                    )
                    ->max('last_synced_at'),
        ];
    }

    public function languages(): Collection
    {
        return Hymn::query()
            ->select('language')
            ->selectRaw(
                'COUNT(*) AS total'
            )
            ->whereNotNull('language')
            ->groupBy('language')
            ->orderBy('language')
            ->get();
    }

    public function books(): Collection
    {
        return HymnBook::query()
            ->withCount('entries')
            ->orderBy('language')
            ->orderBy('name')
            ->get();
    }

    public function pendingHymnRequests(): Collection
    {
        return HymnAdditionRequest::query()
            ->with([
                'requester',
            ])
            ->where(
                'status',
                HymnAdditionRequest::STATUS_PENDING
            )
            ->oldest('created_at')
            ->get();
    }

    public function hymnRequestMatches(
        int $requestId
    ): Collection {
        $request =
            HymnAdditionRequest::query()
                ->find($requestId);

        if (! $request) {
            return collect();
        }

        $search =
            trim(
                (string) (
                    $this->hymnRequestSearches[
                        $requestId
                    ]
                    ?? ''
                )
            );

        if ($search === '') {
            $search =
                trim(
                    (string) $request->title
                );
        }

        if ($search === '') {
            return collect();
        }

        return Hymn::query()
            ->with([
                'bookEntries.hymnBook',
                'sources',
            ])
            ->where(
                'is_active',
                true
            )
            ->where(
                function ($query) use (
                    $search
                ): void {
                    $query
                        ->where(
                            'title',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'lyrics',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'bookEntries',
                            fn ($entryQuery) =>
                                $entryQuery
                                    ->where(
                                        'number',
                                        'like',
                                        "%{$search}%"
                                    )
                        );
                }
            )
            ->orderByRaw(
                'CASE WHEN LOWER(title) = ? '
                . 'THEN 0 ELSE 1 END',
                [
                    mb_strtolower(
                        $search
                    ),
                ]
            )
            ->orderBy('title')
            ->limit(15)
            ->get();
    }

    public function setHymnRequestMode(
        int $requestId,
        string $mode
    ): void {
        if (
            ! in_array(
                $mode,
                [
                    'link',
                    'new',
                ],
                true
            )
        ) {
            return;
        }

        $this->hymnRequestModes[
            $requestId
        ] = $mode;

        if ($mode === 'new') {
            unset(
                $this->hymnRequestTargetIds[
                    $requestId
                ]
            );
        }
    }

    public function selectHymnRequestTarget(
        int $requestId,
        int $hymnId
    ): void {
        $exists =
            Hymn::query()
                ->where(
                    'is_active',
                    true
                )
                ->whereKey(
                    $hymnId
                )
                ->exists();

        if (! $exists) {
            Notification::make()
                ->title(
                    'Hymn not available'
                )
                ->warning()
                ->send();

            return;
        }

        $this->hymnRequestModes[
            $requestId
        ] = 'link';

        $this->hymnRequestTargetIds[
            $requestId
        ] = $hymnId;
    }

    public function approveHymnRequest(
        int $requestId
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $request =
            HymnAdditionRequest::query()
                ->where(
                    'status',
                    HymnAdditionRequest::STATUS_PENDING
                )
                ->find($requestId);

        if (! $request) {
            Notification::make()
                ->title(
                    'Hymn request not found'
                )
                ->body(
                    'The request may already '
                    . 'have been reviewed.'
                )
                ->warning()
                ->send();

            return;
        }

        $mode =
            $this->hymnRequestModes[
                $requestId
            ]
            ?? 'link';

        try {
            $service =
                app(
                    HymnAdditionRequestReviewService::class
                );

            if ($mode === 'new') {
                $hymn =
                    $service->approveNew(
                        $request,
                        auth()->id()
                    );

                $resolution =
                    'A new Hymn was created.';
            } else {
                $targetId =
                    (int) (
                        $this->hymnRequestTargetIds[
                            $requestId
                        ]
                        ?? 0
                    );

                if ($targetId <= 0) {
                    Notification::make()
                        ->title(
                            'Select an existing Hymn'
                        )
                        ->body(
                            'Choose the canonical Hymn '
                            . 'to link, or choose '
                            . 'Create New Hymn.'
                        )
                        ->warning()
                        ->send();

                    return;
                }

                $target =
                    Hymn::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->findOrFail(
                            $targetId
                        );

                $hymn =
                    $service->approveLink(
                        $request,
                        $target,
                        auth()->id()
                    );

                $resolution =
                    'The request was linked '
                    . 'to an existing Hymn.';
            }

            unset(
                $this->hymnRequestModes[
                    $requestId
                ],
                $this->hymnRequestSearches[
                    $requestId
                ],
                $this->hymnRequestTargetIds[
                    $requestId
                ]
            );

            Notification::make()
                ->title(
                    'Hymn request approved'
                )
                ->body(
                    $resolution
                    . ' Canonical Hymn: '
                    . $hymn->title
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Hymn request was not approved'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }


    public function rejectHymnRequest(
        int $requestId
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $request =
            HymnAdditionRequest::query()
                ->where(
                    'status',
                    HymnAdditionRequest::STATUS_PENDING
                )
                ->find($requestId);

        if (! $request) {
            Notification::make()
                ->title(
                    'Hymn request not found'
                )
                ->body(
                    'The request may already '
                    . 'have been reviewed.'
                )
                ->warning()
                ->send();

            return;
        }

        try {
            app(
                HymnAdditionRequestReviewService::class
            )->reject(
                $request,
                auth()->id()
            );

            Notification::make()
                ->title(
                    'Hymn request rejected'
                )
                ->body(
                    'The Hymn Catalog was left unchanged.'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Hymn request was not rejected'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }

    public function hymns(): Collection
    {
        $search =
            trim($this->search);

        return Hymn::query()
            ->with([
                'bookEntries.hymnBook',
            ])
            ->where('is_active', true)
            ->when(
                $this->language !== '',
                fn ($query) =>
                    $query->where(
                        'language',
                        $this->language
                    )
            )
            ->when(
                $search !== '',
                function ($query) use (
                    $search
                ): void {
                    $query->where(
                        function ($query) use (
                            $search
                        ): void {
                            $query
                                ->where(
                                    'title',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'source_id',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'lyrics',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'bookEntries',
                                    fn ($entryQuery) =>
                                        $entryQuery
                                            ->where(
                                                'number',
                                                'like',
                                                "%{$search}%"
                                            )
                                );
                        }
                    );
                }
            )
            ->orderBy('title')
            ->limit(100)
            ->get();
    }

    public function syncSongbase(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        try {
            $result =
                app(
                    SongbaseHymnSyncService::class
                )->fullSync();

            Notification::make()
                ->title(
                    'Songbase synchronization completed'
                )
                ->body(
                    number_format(
                        $result['songs_received']
                    )
                    . ' songs and '
                    . number_format(
                        $result['book_entries']
                    )
                    . ' book entries synchronized.'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title(
                    'Songbase synchronization failed'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
