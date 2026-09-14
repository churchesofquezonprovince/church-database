<?php

namespace App\Filament\Pages;

use App\Models\Hymn;
use App\Models\HymnAdditionRequest;
use App\Models\HymnBook;
use App\Services\HymnAdditionRequestReviewService;
use App\Services\SongbaseHymnSyncService;
use App\Support\HymnSourceResolver;
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

    public string $language = '';

    public string $reviewedStatus = 'all';

    public string $reviewedSource = 'all';

    public string $reviewedSearch = '';

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

    public function reviewedHymnRequests(): Collection
    {
        $search =
            trim(
                $this->reviewedSearch
            );

        return HymnAdditionRequest::query()
            ->with([
                'requester',
                'reviewer',
                'createdHymn.sources',
                'createdHymn.bookEntries.hymnBook',
            ])
            ->whereIn(
                'status',
                [
                    HymnAdditionRequest::STATUS_APPROVED,
                    HymnAdditionRequest::STATUS_REJECTED,
                ]
            )
            ->when(
                in_array(
                    $this->reviewedStatus,
                    [
                        HymnAdditionRequest::STATUS_APPROVED,
                        HymnAdditionRequest::STATUS_REJECTED,
                    ],
                    true
                ),
                fn ($query) =>
                    $query->where(
                        'status',
                        $this->reviewedStatus
                    )
            )
            ->when(
                $this->reviewedSource === 'soundcloud',
                fn ($query) =>
                    $query->where(
                        'source_url',
                        'like',
                        '%soundcloud.com%'
                    )
            )
            ->when(
                $this->reviewedSource === 'youtube',
                fn ($query) =>
                    $query->where(
                        function ($query): void {
                            $query
                                ->where(
                                    'source_url',
                                    'like',
                                    '%youtube.com%'
                                )
                                ->orWhere(
                                    'source_url',
                                    'like',
                                    '%youtu.be%'
                                );
                        }
                    )
            )
            ->when(
                $this->reviewedSource === 'no_source',
                fn ($query) =>
                    $query->where(
                        function ($query): void {
                            $query
                                ->whereNull(
                                    'source_url'
                                )
                                ->orWhere(
                                    'source_url',
                                    ''
                                );
                        }
                    )
            )
            ->when(
                $this->reviewedSource === 'other',
                fn ($query) =>
                    $query
                        ->whereNotNull(
                            'source_url'
                        )
                        ->where(
                            'source_url',
                            '!=',
                            ''
                        )
                        ->where(
                            'source_url',
                            'not like',
                            '%soundcloud.com%'
                        )
                        ->where(
                            'source_url',
                            'not like',
                            '%youtube.com%'
                        )
                        ->where(
                            'source_url',
                            'not like',
                            '%youtu.be%'
                        )
                        ->where(
                            'source_url',
                            'not like',
                            '%hymnal.net%'
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
                                    'source_url',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'requester',
                                    fn ($userQuery) =>
                                        $userQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                )
                                ->orWhereHas(
                                    'reviewer',
                                    fn ($userQuery) =>
                                        $userQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                )
                                ->orWhereHas(
                                    'createdHymn',
                                    fn ($hymnQuery) =>
                                        $hymnQuery->where(
                                            'title',
                                            'like',
                                            "%{$search}%"
                                        )
                                );
                        }
                    );
                }
            )
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->get();
    }

    public function reviewedRequestSourceLabel(
        HymnAdditionRequest $request
    ): string {
        if (
            blank(
                $request->source_url
            )
        ) {
            return 'No Source Link';
        }

        $provider =
            HymnSourceResolver::providerForUrl(
                $request->source_url
            );

        return HymnSourceResolver
            ::labelForProvider(
                $provider
            );
    }

    public function deleteReviewedHymnRequest(
        int $requestId
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $request =
            HymnAdditionRequest::query()
                ->with(
                    'createdHymn'
                )
                ->whereIn(
                    'status',
                    [
                        HymnAdditionRequest::STATUS_APPROVED,
                        HymnAdditionRequest::STATUS_REJECTED,
                    ]
                )
                ->find($requestId);

        if (! $request) {
            Notification::make()
                ->title(
                    'Reviewed Hymn Request not found'
                )
                ->warning()
                ->send();

            return;
        }

        $hymn =
            $request->createdHymn;

        /*
         * Only a Hymn created specifically from this
         * request may be deleted with the request.
         *
         * Never delete an existing Songbase or other
         * canonical Hymn that the request was merely
         * linked to.
         */
        $requestCreatedHymn =
            $request->status
                === HymnAdditionRequest::STATUS_APPROVED
            && $hymn
            && $hymn->source === 'manual'
            && $hymn->source_id
                === 'request:' . $request->id;

        try {
            DB::transaction(
                function () use (
                    $request,
                    $hymn,
                    $requestCreatedHymn
                ): void {
                    if (
                        $requestCreatedHymn
                        && $hymn
                    ) {
                        $usedInShepherding =
                            DB::table(
                                'shepherding_contact_hymns'
                            )
                                ->where(
                                    'hymn_id',
                                    $hymn->id
                                )
                                ->exists();

                        if ($usedInShepherding) {
                            throw new \RuntimeException(
                                'This Hymn is already used '
                                . 'in a Shepherding record. '
                                . 'Remove it from those '
                                . 'records before deleting it.'
                            );
                        }

                        /*
                         * hymn_sources are cascade-deleted
                         * by their hymn_id foreign key.
                         */
                        $hymn->delete();
                    }

                    $request->delete();
                }
            );

            Notification::make()
                ->title(
                    $requestCreatedHymn
                        ? 'Hymn and request deleted'
                        : 'Reviewed Hymn Request deleted'
                )
                ->body(
                    $requestCreatedHymn
                        ? 'The request, its created Hymn, '
                            . 'and attached source links '
                            . 'were deleted.'
                        : 'The review record was deleted. '
                            . 'The existing linked canonical '
                            . 'Hymn remains unchanged.'
                )
                ->success()
                ->send();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Reviewed Hymn Request was not deleted'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }


    public function showReviewedHymn(
        int $requestId
    ): void {
        $request =
            HymnAdditionRequest::query()
                ->with(
                    'createdHymn'
                )
                ->where(
                    'status',
                    HymnAdditionRequest::STATUS_APPROVED
                )
                ->find($requestId);

        if (
            ! $request
            || ! $request->createdHymn
        ) {
            Notification::make()
                ->title(
                    'Linked Hymn not found'
                )
                ->warning()
                ->send();

            return;
        }

        /*
         * Manual Hymns may not have a language,
         * so do not hide them behind a language
         * filter when View Hymn is used.
         */
        $this->language = '';

        $this->search =
            $request
                ->createdHymn
                ->title;

        Notification::make()
            ->title(
                'Hymn Catalog filtered'
            )
            ->body(
                'Showing '
                . $request
                    ->createdHymn
                    ->title
                . ' in the Hymn Catalog.'
            )
            ->success()
            ->send();
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
                            'lyrics_search',
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
                'sources',
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
                                    'lyrics_search',
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
