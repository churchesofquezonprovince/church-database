<?php

namespace App\Filament\Pages;

use App\Support\HymnalNetSource;
use App\Models\Hymn;
use App\Models\HymnAdditionRequest;
use App\Models\HymnBook;
use App\Models\HymnalNetEntry;
use App\Models\HymnSource;
use App\Models\HymnVariant;
use App\Services\HymnAdditionRequestReviewService;
use App\Support\HymnSearchRanker;
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
     * Centralized Hymnal.net review state.
     * Provider synchronization remains in
     * Hymnal.net Setup; review decisions live here.
     */
    public string $hymnalReviewSearch = '';

    public ?int $selectedHymnId = null;

    public string $hymnalUrl = '';

    public ?int $reviewEntryId = null;

    public ?int $selectedVariantId = null;

    /*
     * Editable title used when a genuinely-unmatched
     * Hymnal.net entry needs a new canonical Hymn.
     */
    public string $newCanonicalTitle = '';

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
            'hymns' =>
                Hymn::query()->count(),

            'active' =>
                Hymn::query()
                    ->where('is_active', true)
                    ->count(),

            'variants' =>
                HymnVariant::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

            'sources' =>
                HymnSource::query()->count(),

            'providers' =>
                HymnSource::query()
                    ->distinct()
                    ->count('provider'),

            'pending_requests' =>
                HymnAdditionRequest::query()
                    ->where(
                        'status',
                        HymnAdditionRequest::STATUS_PENDING
                    )
                    ->count(),

            'variant_review' =>
                $this
                    ->variantReviewEntries()
                    ->count(),

            'provisional_review' =>
                $this
                    ->provisionalReviewEntries()
                    ->count(),

            'needs_review' =>
                $this
                    ->unresolvedEntries()
                    ->count(),

            /*
             * Retained for the language/book sections.
             */
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
                        ->orWhereHas(
                            'sources',
                            function ($sourceQuery) use (
                                $search
                            ): void {
                                $sourceQuery->where(
                                    function ($lyricsQuery) use (
                                        $search
                                    ): void {
                                        $lyricsQuery
                                            ->where(
                                                'first_line_search',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'lyrics_search',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                            }
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

    public function hymnalReviewMatches(): Collection
    {
        $search =
            trim($this->hymnalReviewSearch);

        if ($search === '') {
            return collect();
        }

        $like =
            '%' . $search . '%';

        return Hymn::query()
            ->where(
                'is_active',
                true
            )
            ->with([
                'bookEntries.hymnBook',
                'sources',
            ])
            ->where(
                function ($query) use (
                    $like
                ): void {
                    $query
                        ->where(
                            'title',
                            'like',
                            $like
                        )
                        ->orWhereHas(
                            'sources',
                            function ($sourceQuery) use (
                                $like
                            ): void {
                                $sourceQuery->where(
                                    function ($lyricsQuery) use (
                                        $like
                                    ): void {
                                        $lyricsQuery
                                            ->where(
                                                'first_line_search',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'lyrics_search',
                                                'like',
                                                $like
                                            );
                                    }
                                );
                            }
                        )
                        ->orWhereHas(
                            'sources',
                            fn ($sourceQuery) =>
                                $sourceQuery->where(
                                    'external_id',
                                    'like',
                                    $like
                                )
                        )
                        ->orWhereHas(
                            'bookEntries',
                            fn ($entryQuery) =>
                                $entryQuery
                                    ->where(
                                        'number',
                                        'like',
                                        $like
                                    )
                        );
                }
            )
            ->when(
                $search !== '',
                fn ($query) =>
                    HymnSearchRanker::apply(
                        $query,
                        $search,
                        true
                    )
            )
            ->limit(30)
            ->get();
    }


    public function selectHymnalReviewHymn(
        int $hymnId
    ): void {
        $hymn =
            Hymn::query()
                ->where(
                    'is_active',
                    true
                )
                ->findOrFail(
                    $hymnId
                );

        $this->selectedHymnId =
            $hymn->id;

        $variantIds =
            $hymn
                ->variants()
                ->where(
                    'is_active',
                    true
                )
                ->pluck('id');

        $reviewEntry =
            $this->reviewedEntry();

        $this->selectedVariantId =
            $reviewEntry?->section_code
                === 'new_tunes'
                ? null
                : (
                    $variantIds->count() === 1
                        ? (int)
                            $variantIds->first()
                        : null
                );

        $this->hymnalReviewSearch =
            $hymn->title;
    }


    public function selectedHymnalReviewHymn(): ?Hymn
    {
        if (! $this->selectedHymnId) {
            return null;
        }

        return Hymn::query()
            ->with([
                'bookEntries.hymnBook',
                'sources',
                'variants.sources',
            ])
            ->find(
                $this->selectedHymnId
            );
    }


    public function variantReviewEntries(): Collection
    {
        return HymnalNetEntry::query()
            ->whereIn(
                'match_status',
                [
                    'linked',
                    'variant_review',
                ]
            )
            ->whereNotNull(
                'matched_hymn_id'
            )
            ->whereNull(
                'matched_hymn_variant_id'
            )
            ->with([
                'matchedHymn.variants' =>
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        ),
            ])
            ->orderBy(
                'section_code'
            )
            ->orderBy(
                'collection_code'
            )
            ->orderByRaw(
                'CAST(number AS UNSIGNED)'
            )
            ->get()
            ->filter(
                function (
                    HymnalNetEntry $entry
                ): bool {
                    if (
                        $entry->match_status
                            === 'variant_review'
                    ) {
                        return true;
                    }

                    return (
                        $entry
                            ->matchedHymn
                            ?->variants
                            ?->count()
                        ?? 0
                    ) > 1;
                }
            )
            ->values();
    }


    public function provisionalReviewEntries(): Collection
    {
        return HymnalNetEntry::query()
            ->where(
                'match_status',
                'linked'
            )
            ->where(
                'match_method',
                'manual_review_provisional'
            )
            ->with([
                'matchedHymn',
                'matchedVariant',
            ])
            ->orderBy(
                'section_code'
            )
            ->orderBy(
                'collection_code'
            )
            ->orderByRaw(
                'CAST(number AS UNSIGNED)'
            )
            ->get();
    }


    public function unresolvedEntries(): Collection
    {
        return HymnalNetEntry::query()
            ->whereIn(
                'match_status',
                [
                    'unmatched',
                    'ambiguous',
                    'conflict',
                ]
            )
            ->orderBy(
                'section_code'
            )
            ->orderBy(
                'collection_code'
            )
            ->orderByRaw(
                'CAST(number AS UNSIGNED)'
            )
            ->get();
    }


    public function reviewedEntry(): ?HymnalNetEntry
    {
        if (! $this->reviewEntryId) {
            return null;
        }

        return HymnalNetEntry::query()
            ->find(
                $this->reviewEntryId
            );
    }


    public function beginReview(
        int $entryId
    ): void {
        $entry =
            HymnalNetEntry::query()
                ->findOrFail(
                    $entryId
                );

        $entry->load(
            'matchedHymn'
        );

        $this->reviewEntryId =
            $entry->id;

        $this->selectedHymnId =
            $entry->matched_hymn_id;

        $this->selectedVariantId =
            $entry->matched_hymn_variant_id;

        $this->hymnalReviewSearch =
            $entry->matchedHymn?->title
            ?? $entry->title
            ?? '';

        $this->newCanonicalTitle =
            $entry->title
            ?? '';

        $this->hymnalUrl =
            $entry->source_url;

        Notification::make()
            ->title(
                'Hymnal.net review loaded'
            )
            ->body(
                $entry->collection_code
                . '/'
                . $entry->number
                . ' — '
                . ($entry->title ?? 'Untitled')
            )
            ->success()
            ->send();
    }


    public function cancelReview(): void
    {
        $this->reviewEntryId =
            null;

        $this->selectedHymnId =
            null;

        $this->selectedVariantId =
            null;

        $this->hymnalReviewSearch = '';

        $this->newCanonicalTitle = '';

        $this->hymnalUrl = '';
    }


    public function resolveReviewedEntry(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $this->validate([
            'reviewEntryId' => [
                'required',
                'integer',
                'exists:hymnal_net_entries,id',
            ],

            'selectedHymnId' => [
                'required',
                'integer',
                'exists:hymns,id',
            ],
        ]);

        try {
            $entry =
                HymnalNetEntry::query()
                    ->findOrFail(
                        $this->reviewEntryId
                    );

            $hymn =
                Hymn::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->findOrFail(
                        $this->selectedHymnId
                    );

            $activeVariants =
                $hymn
                    ->variants()
                    ->where(
                        'is_active',
                        true
                    )
                    ->get();

            if (
                $entry->section_code
                    === 'new_tunes'
                && $activeVariants->isEmpty()
            ) {
                throw new \RuntimeException(
                    'This New Tunes entry belongs to '
                    . 'the canonical Hymn family, but '
                    . 'that Hymn has no active variants '
                    . 'yet. Create the tune variant '
                    . 'before resolving this entry.'
                );
            }

            $variant = null;

            if (
                $activeVariants->isNotEmpty()
            ) {
                if (! $this->selectedVariantId) {
                    throw new \RuntimeException(
                        'Select the correct Hymn variant '
                        . 'before resolving this entry.'
                    );
                }

                $variant =
                    $activeVariants
                        ->firstWhere(
                            'id',
                            $this->selectedVariantId
                        );

                if (! $variant) {
                    throw new \RuntimeException(
                        'The selected variant does not '
                        . 'belong to this Hymn.'
                    );
                }
            }

            $externalId =
                HymnalNetSource
                    ::externalIdForUrl(
                        $entry->source_url
                    );

            $existingElsewhere =
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource
                            ::PROVIDER_HYMNAL_NET
                    )
                    ->where(
                        'external_id',
                        $externalId
                    )
                    ->where(
                        'hymn_id',
                        '!=',
                        $hymn->id
                    )
                    ->with('hymn')
                    ->first();

            if ($existingElsewhere) {
                throw new \RuntimeException(
                    'That Hymnal.net page is already '
                    . 'linked to "'
                    . (
                        $existingElsewhere
                            ->hymn
                            ?->title
                        ?? 'another Hymn'
                    )
                    . '".'
                );
            }

            HymnSource::query()
                ->updateOrCreate(
                    [
                        'hymn_id' =>
                            $hymn->id,

                        'provider' =>
                            HymnSource
                                ::PROVIDER_HYMNAL_NET,

                        'external_id' =>
                            $externalId,
                    ],
                    [
                        'hymn_variant_id' =>
                            $variant?->id,

                        'source_type' =>
                            HymnSource::TYPE_CATALOG,

                        'source_url' =>
                            $entry->source_url,

                        'label' =>
                            'Hymnal.net',

                        'metadata' => [
                            'collection' =>
                                $entry
                                    ->collection_code,

                            'section' =>
                                $entry
                                    ->section_code,

                            'number' =>
                                $entry->number,

                            'title' =>
                                $entry->title,

                            'sync' =>
                                'hymnal_net_catalog',

                            'match_method' =>
                                'manual_review',
                        ],
                    ]
                );

            $entry->update([
                'matched_hymn_id' =>
                    $hymn->id,

                'matched_hymn_variant_id' =>
                    $variant?->id,

                'match_status' =>
                    'linked',

                'match_method' =>
                    'manual_review',

                'match_score' =>
                    100,
            ]);

            $this->reviewEntryId =
                null;

            $this->selectedHymnId =
                null;

            $this->selectedVariantId =
                null;

            $this->hymnalReviewSearch = '';

            $this->newCanonicalTitle = '';

            $this->hymnalUrl = '';

            Notification::make()
                ->title(
                    'Hymnal.net entry resolved'
                )
                ->body(
                    $entry->collection_code
                    . '/'
                    . $entry->number
                    . ' linked to '
                    . $hymn->title
                    . '.'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Hymnal.net entry was not resolved'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }


    public function createCanonicalFromReviewedEntry(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $this->validate([
            'reviewEntryId' => [
                'required',
                'integer',
                'exists:hymnal_net_entries,id',
            ],

            'newCanonicalTitle' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        try {
            $entry =
                HymnalNetEntry::query()
                    ->findOrFail(
                        $this->reviewEntryId
                    );

            if (
                $entry->section_code
                    === 'new_tunes'
            ) {
                throw new \RuntimeException(
                    'A New Tunes entry cannot create '
                    . 'a new canonical Hymn. Link it '
                    . 'to the existing canonical Hymn '
                    . 'and an explicit tune variant.'
                );
            }

            /*
             * Only a genuinely-unmatched entry may
             * create a new canonical Hymn.
             *
             * Ambiguous and conflict cases must be
             * reconciled with existing Hymns instead.
             */
            if (
                $entry->match_status
                    !== 'unmatched'
            ) {
                throw new \RuntimeException(
                    'A new canonical Hymn may only be '
                    . 'created from an unmatched '
                    . 'Hymnal.net entry.'
                );
            }

            $title =
                preg_replace(
                    '/\s+/u',
                    ' ',
                    trim(
                        $this->newCanonicalTitle
                    )
                )
                ?? trim(
                    $this->newCanonicalTitle
                );

            if ($title === '') {
                throw new \RuntimeException(
                    'Enter a canonical Hymn title.'
                );
            }

            /*
             * Check again immediately before creation.
             *
             * This protects against creating another
             * canonical Hymn when an exact normalized
             * title already exists.
             */
            $normalizedTitle =
                $this->normalizeReviewTitle(
                    $title
                );

            $existingTitleMatch =
                Hymn::query()
                    ->whereNotNull('title')
                    ->get([
                        'id',
                        'title',
                    ])
                    ->first(
                        fn (Hymn $hymn): bool =>
                            $this
                                ->normalizeReviewTitle(
                                    $hymn->title
                                )
                            === $normalizedTitle
                    );

            if ($existingTitleMatch) {
                throw new \RuntimeException(
                    'Canonical Hymn #'
                    . $existingTitleMatch->id
                    . ' already has the title "'
                    . $existingTitleMatch->title
                    . '". Select that existing Hymn '
                    . 'instead of creating a duplicate.'
                );
            }

            $externalId =
                HymnalNetSource
                    ::externalIdForUrl(
                        $entry->source_url
                    );

            /*
             * The same Hymnal.net page must never be
             * attached to two canonical Hymns.
             */
            $existingSource =
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource
                            ::PROVIDER_HYMNAL_NET
                    )
                    ->where(
                        'external_id',
                        $externalId
                    )
                    ->with('hymn')
                    ->first();

            if ($existingSource) {
                throw new \RuntimeException(
                    'That Hymnal.net page is already '
                    . 'attached to canonical Hymn #'
                    . $existingSource->hymn_id
                    . ' "'
                    . (
                        $existingSource
                            ->hymn
                            ?->title
                        ?? 'Unknown Hymn'
                    )
                    . '". Review that existing link '
                    . 'instead.'
                );
            }

            $hymn =
                DB::transaction(
                    function () use (
                        $entry,
                        $externalId,
                        $title
                    ): Hymn {
                        /*
                         * `source` remains a transitional
                         * legacy field.
                         *
                         * Provider truth is stored in
                         * hymn_sources, not here.
                         */
                        $hymn =
                            Hymn::query()
                                ->create([
                                    'source' =>
                                        'manual',

                                    'source_id' =>
                                        null,

                                    'title' =>
                                        $title,

                                    'language' =>
                                        'english',

                                    'is_active' =>
                                        true,
                                ]);

                        /*
                         * Do not invent a variant.
                         *
                         * Until there is evidence of a
                         * tune/revision family, attach the
                         * Hymnal.net source directly to the
                         * canonical Hymn.
                         */
                        HymnSource::query()
                            ->create([
                                'hymn_id' =>
                                    $hymn->id,

                                'hymn_variant_id' =>
                                    null,

                                'provider' =>
                                    HymnSource
                                        ::PROVIDER_HYMNAL_NET,

                                'source_type' =>
                                    HymnSource
                                        ::TYPE_CATALOG,

                                'external_id' =>
                                    $externalId,

                                'source_url' =>
                                    $entry->source_url,

                                'label' =>
                                    'Hymnal.net',

                                'metadata' => [
                                    'collection' =>
                                        $entry
                                            ->collection_code,

                                    'section' =>
                                        $entry
                                            ->section_code,

                                    'number' =>
                                        $entry->number,

                                    'title' =>
                                        $entry->title,

                                    'sync' =>
                                        'hymnal_net_catalog',

                                    'match_method' =>
                                        'manual_review',

                                    'review_action' =>
                                        'created_canonical',
                                ],
                            ]);

                        $entry->update([
                            'matched_hymn_id' =>
                                $hymn->id,

                            'matched_hymn_variant_id' =>
                                null,

                            'match_status' =>
                                'linked',

                            'match_method' =>
                                'manual_review',

                            'match_score' =>
                                100,
                        ]);

                        return $hymn;
                    }
                );

            $entryNumber =
                $entry->collection_code
                . '/'
                . $entry->number;

            $this->cancelReview();

            Notification::make()
                ->title(
                    'Canonical Hymn created'
                )
                ->body(
                    $entryNumber
                    . ' was linked to new canonical '
                    . 'Hymn #'
                    . $hymn->id
                    . ' "'
                    . $hymn->title
                    . '".'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Canonical Hymn was not created'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }


    private function normalizeReviewTitle(
        ?string $title
    ): string {
        $title =
            mb_strtolower(
                trim(
                    (string) $title
                )
            );

        $title =
            preg_replace(
                '/[^\p{L}\p{N}]+/u',
                ' ',
                $title
            )
            ?? $title;

        $title =
            preg_replace(
                '/\s+/u',
                ' ',
                $title
            )
            ?? $title;

        return trim($title);
    }


    public function hymns(): Collection
    {
        $search =
            trim($this->search);

        return Hymn::query()
            ->with([
                'bookEntries.hymnBook',
                'sources',
                'variants.sources',
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
                                ->orWhereHas(
                                    'sources',
                                    function ($sourceQuery) use (
                                        $search
                                    ): void {
                                        $sourceQuery->where(
                                            function ($lyricsQuery) use (
                                                $search
                                            ): void {
                                                $lyricsQuery
                                                    ->where(
                                                        'first_line_search',
                                                        'like',
                                                        "%{$search}%"
                                                    )
                                                    ->orWhere(
                                                        'lyrics_search',
                                                        'like',
                                                        "%{$search}%"
                                                    );
                                            }
                                        );
                                    }
                                )
                                ->orWhereHas(
                                    'sources',
                                    fn ($sourceQuery) =>
                                        $sourceQuery
                                            ->where(
                                                'external_id',
                                                'like',
                                                "%{$search}%"
                                            )
                                )
                                ->orWhereHas(
                                    'variants',
                                    function ($variantQuery) use (
                                        $search
                                    ): void {
                                        $variantQuery
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
                                                            'label',
                                                            'like',
                                                            "%{$search}%"
                                                        )
                                                        ->orWhere(
                                                            'title_override',
                                                            'like',
                                                            "%{$search}%"
                                                        );
                                                }
                                            );
                                    }
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
            ->when(
                $search !== '',
                fn ($query) =>
                    HymnSearchRanker::apply(
                        $query,
                        $search
                    )
            )
            ->when(
                $search === '',
                fn ($query) =>
                    $query->orderBy('title')
            )
            ->limit(100)
            ->get();
    }


}
