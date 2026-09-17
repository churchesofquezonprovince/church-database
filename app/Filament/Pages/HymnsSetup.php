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
     * Filter only the Needs Review table by Hymnal.net
     * logical section. The summary count remains global.
     */
    public string $needsReviewSection = 'all';

    /*
     * Centralized Hymnal.net review state.
     * Provider synchronization remains in
     * Hymnal.net Setup; review decisions live here.
     */
    public string $hymnalReviewSearch = '';

    /*
     * Review-only candidate suggestions generated from
     * the current unresolved Hymnal.net title.
     *
     * These never write matching decisions themselves.
     * A reviewer must still select and resolve a Hymn.
     */
    public array $hymnalReviewSuggestions = [];

    public ?int $selectedHymnId = null;

    public string $hymnalUrl = '';

    public ?int $reviewEntryId = null;

    public ?int $selectedVariantId = null;

    /*
     * When reviewing Hymnal.net New Tunes, the
     * corresponding Classic h/{number} page must also
     * receive an explicit tune assignment.
     *
     * Never infer this from Songbase variant order.
     */
    public ?int $selectedClassicVariantId = null;

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
                    ->unresolvedEntries(
                        false
                    )
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

    private function buildHymnalReviewSuggestions(
        HymnalNetEntry $entry
    ): array {
        $entryTitle =
            trim(
                (string) $entry->title
            );

        if ($entryTitle === '') {
            return [];
        }

        /*
         * Rank only lightweight Hymn identity fields first.
         *
         * Full provider/book/variant relationships are
         * loaded only for the small final candidate set.
         */
        $ranked =
            Hymn::query()
                ->where(
                    'is_active',
                    true
                )
                ->get([
                    'id',
                    'title',
                ])
                ->map(
                    function (
                        Hymn $hymn
                    ) use (
                        $entryTitle
                    ): array {
                        $comparison =
                            $this
                                ->reviewTitleSuggestionScore(
                                    $entryTitle,
                                    $hymn->title
                                );

                        return [
                            'hymn_id' =>
                                $hymn->id,

                            'score' =>
                                $comparison[
                                    'score'
                                ],

                            'reason' =>
                                $comparison[
                                    'reason'
                                ],
                        ];
                    }
                )
                /*
                 * This threshold only controls whether a
                 * candidate is useful enough to display.
                 *
                 * It is never an automatic-link threshold.
                 */
                ->filter(
                    fn (
                        array $candidate
                    ): bool =>
                        $candidate[
                            'score'
                        ] >= 65
                )
                ->sortByDesc(
                    'score'
                )
                ->take(5)
                ->values();

        if ($ranked->isEmpty()) {
            return [];
        }

        $hymnIds =
            $ranked
                ->pluck(
                    'hymn_id'
                )
                ->all();

        $hymns =
            Hymn::query()
                ->with([
                    'bookEntries.hymnBook',
                    'sources',
                    'variants' =>
                        fn ($query) =>
                            $query->where(
                                'is_active',
                                true
                            ),
                ])
                ->whereIn(
                    'id',
                    $hymnIds
                )
                ->get()
                ->keyBy(
                    'id'
                );

        return $ranked
            ->map(
                function (
                    array $candidate
                ) use (
                    $hymns
                ): ?array {
                    $hymn =
                        $hymns->get(
                            $candidate[
                                'hymn_id'
                            ]
                        );

                    if (! $hymn) {
                        return null;
                    }

                    $songbaseIds =
                        $hymn
                            ->sources
                            ->where(
                                'provider',
                                HymnSource
                                    ::PROVIDER_SONGBASE
                            )
                            ->pluck(
                                'external_id'
                            )
                            ->filter(
                                fn ($externalId): bool =>
                                    filled(
                                        $externalId
                                    )
                                    && ! str_contains(
                                        (string)
                                            $externalId,
                                        ':'
                                    )
                            )
                            ->map(
                                fn ($externalId): string =>
                                    (string)
                                        $externalId
                            )
                            ->unique()
                            ->take(4)
                            ->values()
                            ->all();

                    $books =
                        $hymn
                            ->bookEntries
                            ->map(
                                function (
                                    $bookEntry
                                ): string {
                                    $bookName =
                                        trim(
                                            (string)
                                                (
                                                    $bookEntry
                                                        ->hymnBook
                                                        ?->name
                                                    ?? 'Book'
                                                )
                                        );

                                    return $bookName
                                        . ' #'
                                        . $bookEntry
                                            ->number;
                                }
                            )
                            ->unique()
                            ->take(4)
                            ->values()
                            ->all();

                    return [
                        'hymn_id' =>
                            $hymn->id,

                        'title' =>
                            $hymn->title,

                        'language' =>
                            $hymn->language,

                        'score' =>
                            $candidate[
                                'score'
                            ],

                        'reason' =>
                            $candidate[
                                'reason'
                            ],

                        'songbase_ids' =>
                            $songbaseIds,

                        'books' =>
                            $books,

                        'active_variant_count' =>
                            $hymn
                                ->variants
                                ->count(),
                    ];
                }
            )
            ->filter()
            ->values()
            ->all();
    }


    private function reviewTitleSuggestionScore(
        ?string $entryTitle,
        ?string $candidateTitle
    ): array {
        $entryNormalized =
            $this
                ->normalizeReviewTitle(
                    $entryTitle
                );

        $candidateNormalized =
            $this
                ->normalizeReviewTitle(
                    $candidateTitle
                );

        if (
            $entryNormalized === ''
            || $candidateNormalized === ''
        ) {
            return [
                'score' => 0.0,
                'reason' =>
                    'No comparable title',
            ];
        }

        $stripParenthetical =
            function (
                ?string $title
            ): string {
                $title =
                    preg_replace(
                        '/\s*\([^)]*\)\s*/u',
                        ' ',
                        (string) $title
                    )
                    ?? (string) $title;

                return $this
                    ->normalizeReviewTitle(
                        $title
                    );
            };

        $entryStripped =
            $stripParenthetical(
                $entryTitle
            );

        $candidateStripped =
            $stripParenthetical(
                $candidateTitle
            );

        similar_text(
            $entryNormalized,
            $candidateNormalized,
            $fullSimilarity
        );

        similar_text(
            $entryStripped,
            $candidateStripped,
            $strippedSimilarity
        );

        $tokens =
            function (
                string $value
            ): array {
                return collect(
                    preg_split(
                        '/\s+/u',
                        $value
                    )
                    ?: []
                )
                    ->map(
                        fn (
                            string $token
                        ): string =>
                            trim($token)
                    )
                    ->filter(
                        fn (
                            string $token
                        ): bool =>
                            mb_strlen(
                                $token
                            ) >= 2
                    )
                    ->unique()
                    ->values()
                    ->all();
            };

        $entryTokens =
            $tokens(
                $entryStripped
            );

        $candidateTokens =
            $tokens(
                $candidateStripped
            );

        $minimumTokenCount =
            min(
                count($entryTokens),
                count($candidateTokens)
            );

        $tokenCoverage =
            $minimumTokenCount > 0
                ? (
                    count(
                        array_intersect(
                            $entryTokens,
                            $candidateTokens
                        )
                    )
                    / $minimumTokenCount
                ) * 100
                : 0.0;

        $score =
            max(
                (float) $fullSimilarity,
                (float) $strippedSimilarity,
                $tokenCoverage * 0.92
            );

        $reason =
            'Similar normalized title';

        if (
            $entryNormalized
                === $candidateNormalized
        ) {
            $score = 100.0;

            $reason =
                'Same normalized title';
        } elseif (
            $entryStripped !== ''
            && $entryStripped
                === $candidateStripped
        ) {
            $score = 100.0;

            $reason =
                'Same title after removing '
                . 'parenthetical note';
        } else {
            $shorterLength =
                min(
                    mb_strlen(
                        $entryStripped
                    ),
                    mb_strlen(
                        $candidateStripped
                    )
                );

            $containsOther =
                $shorterLength >= 8
                && (
                    str_contains(
                        $entryStripped,
                        $candidateStripped
                    )
                    || str_contains(
                        $candidateStripped,
                        $entryStripped
                    )
                );

            if ($containsOther) {
                $score =
                    max(
                        $score,
                        94.0
                    );

                $reason =
                    'One normalized title '
                    . 'contains the other';
            } elseif (
                $strippedSimilarity
                    > $fullSimilarity + 3
            ) {
                $reason =
                    'Strong match after ignoring '
                    . 'parenthetical note';
            } elseif (
                $tokenCoverage >= 80
            ) {
                $reason =
                    'Strong title-word overlap';
            }
        }

        return [
            'score' =>
                round(
                    min(
                        100,
                        $score
                    ),
                    1
                ),

            'reason' =>
                $reason,
        ];
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

        $this->selectedClassicVariantId =
            null;

        if (
            $reviewEntry?->section_code
                === 'new_tunes'
        ) {
            $classicNumber =
                $this
                    ->classicCounterpartNumberForEntry(
                        $reviewEntry
                    );

            $classicCounterpart =
                HymnalNetEntry::query()
                    ->where(
                        'section_code',
                        'classic'
                    )
                    ->where(
                        'collection_code',
                        'h'
                    )
                    ->where(
                        'number',
                        $classicNumber
                    )
                    ->where(
                        'matched_hymn_id',
                        $hymn->id
                    )
                    ->where(
                        'match_status',
                        'linked'
                    )
                    ->first();

            $this->selectedClassicVariantId =
                $classicCounterpart
                    ?->matched_hymn_variant_id;
        }

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


    public function unresolvedEntries(
        bool $applySectionFilter = true
    ): Collection {
        $allowedSections = [
            'classic',
            'new_tunes',
            'alternate_tunes',
            'new_songs',
            'children',
        ];

        return HymnalNetEntry::query()
            ->whereIn(
                'match_status',
                [
                    'unmatched',
                    'ambiguous',
                    'conflict',
                ]
            )
            ->when(
                $applySectionFilter
                && in_array(
                    $this->needsReviewSection,
                    $allowedSections,
                    true
                ),
                fn ($query) =>
                    $query->where(
                        'section_code',
                        $this->needsReviewSection
                    )
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


    private function classicCounterpartNumberForEntry(
        HymnalNetEntry $entry
    ): string {
        $number =
            (string) $entry->number;

        if (
            in_array(
                $entry->section_code,
                [
                    'new_tunes',
                    'alternate_tunes',
                ],
                true
            )
            && preg_match(
                '/^([0-9]+)[A-Za-z]+$/',
                $number,
                $matches
            )
        ) {
            return $matches[1];
        }

        return $number;
    }


    public function classicCounterpartForReviewedEntry(): ?HymnalNetEntry
    {
        $entry =
            $this->reviewedEntry();

        if (
            ! $entry
            || $entry->section_code
                !== 'new_tunes'
        ) {
            return null;
        }

        $hymnId =
            $this->selectedHymnId
            ?? $entry->matched_hymn_id;

        if (! $hymnId) {
            return null;
        }

        $classicNumber =
            $this
                ->classicCounterpartNumberForEntry(
                    $entry
                );

        return HymnalNetEntry::query()
            ->where(
                'section_code',
                'classic'
            )
            ->where(
                'collection_code',
                'h'
            )
            ->where(
                'number',
                $classicNumber
            )
            ->where(
                'matched_hymn_id',
                $hymnId
            )
            ->where(
                'match_status',
                'linked'
            )
            ->orderBy('id')
            ->first();
    }


    private function variantReviewUndoSessionKey(): string
    {
        return 'hymns_setup.variant_review_undo';
    }


    public function variantReviewUndoSummary(): ?array
    {
        $undo =
            session()->get(
                $this->variantReviewUndoSessionKey()
            );

        if (
            ! is_array($undo)
            || (int) ($undo['user_id'] ?? 0)
                !== (int) auth()->id()
        ) {
            return null;
        }

        return [
            'label' =>
                (string) (
                    $undo['label']
                    ?? 'Last Variant Review save'
                ),

            'saved_at' =>
                $undo['saved_at']
                ?? null,
        ];
    }


    private function snapshotVariantReviewFamily(
        int $hymnId,
        array $extraEntryIds = []
    ): array {
        $entryIds =
            collect($extraEntryIds)
                ->filter()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        $entries =
            DB::table(
                'hymnal_net_entries'
            )
                ->where(
                    function ($query) use (
                        $hymnId,
                        $entryIds
                    ): void {
                        $query->where(
                            'matched_hymn_id',
                            $hymnId
                        );

                        if ($entryIds !== []) {
                            $query->orWhereIn(
                                'id',
                                $entryIds
                            );
                        }
                    }
                )
                ->orderBy('id')
                ->get()
                ->map(
                    fn ($row): array =>
                        (array) $row
                )
                ->all();

        return [
            'hymn_id' =>
                $hymnId,

            'variants' =>
                DB::table(
                    'hymn_variants'
                )
                    ->where(
                        'hymn_id',
                        $hymnId
                    )
                    ->orderBy('id')
                    ->get()
                    ->map(
                        fn ($row): array =>
                            (array) $row
                    )
                    ->all(),

            'sources' =>
                DB::table(
                    'hymn_sources'
                )
                    ->where(
                        'hymn_id',
                        $hymnId
                    )
                    ->orderBy('id')
                    ->get()
                    ->map(
                        fn ($row): array =>
                            (array) $row
                    )
                    ->all(),

            'entries' =>
                $entries,
        ];
    }


    private function variantReviewSnapshotHash(
        array $snapshot
    ): string {
        return hash(
            'sha256',
            serialize($snapshot)
        );
    }


    private function storeVariantReviewUndo(
        int $hymnId,
        HymnalNetEntry $entry,
        array $before
    ): void {
        $entryIds =
            collect(
                $before['entries']
                ?? []
            )
                ->pluck('id')
                ->push($entry->id)
                ->filter()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        $after =
            $this->snapshotVariantReviewFamily(
                $hymnId,
                $entryIds
            );

        session()->put(
            $this->variantReviewUndoSessionKey(),
            [
                'user_id' =>
                    (int) auth()->id(),

                'hymn_id' =>
                    $hymnId,

                'entry_ids' =>
                    $entryIds,

                'label' =>
                    strtoupper(
                        (string)
                            $entry->collection_code
                    )
                    . $entry->number
                    . ' — '
                    . (
                        $entry->title
                        ?? 'Untitled'
                    ),

                'saved_at' =>
                    now()->toIso8601String(),

                'before' =>
                    $before,

                'after' =>
                    $after,

                'after_hash' =>
                    $this
                        ->variantReviewSnapshotHash(
                            $after
                        ),
            ]
        );
    }


    private function restoreVariantReviewRows(
        string $table,
        array $rows
    ): void {
        foreach ($rows as $row) {
            if (
                ! is_array($row)
                || ! isset($row['id'])
            ) {
                throw new \RuntimeException(
                    'Invalid Variant Review undo snapshot.'
                );
            }

            $id =
                (int) $row['id'];

            unset($row['id']);

            DB::table($table)
                ->where(
                    'id',
                    $id
                )
                ->update($row);
        }
    }


    public function rollbackLastVariantReviewSave(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $undo =
            session()->get(
                $this->variantReviewUndoSessionKey()
            );

        if (
            ! is_array($undo)
            || (int) ($undo['user_id'] ?? 0)
                !== (int) auth()->id()
        ) {
            Notification::make()
                ->title(
                    'No Variant Review save to rollback'
                )
                ->body(
                    'Only the most recent save made after '
                    . 'this rollback feature was enabled '
                    . 'can be undone.'
                )
                ->warning()
                ->send();

            return;
        }

        try {
            $hymnId =
                (int) (
                    $undo['hymn_id']
                    ?? 0
                );

            $before =
                $undo['before']
                ?? null;

            $after =
                $undo['after']
                ?? null;

            if (
                $hymnId < 1
                || ! is_array($before)
                || ! is_array($after)
            ) {
                throw new \RuntimeException(
                    'The saved rollback snapshot is invalid.'
                );
            }

            $entryIds =
                collect(
                    $undo['entry_ids']
                    ?? []
                )
                    ->filter()
                    ->map(
                        fn ($id): int =>
                            (int) $id
                    )
                    ->unique()
                    ->values()
                    ->all();

            DB::transaction(
                function () use (
                    $hymnId,
                    $entryIds,
                    $before,
                    $after,
                    $undo
                ): void {
                    Hymn::query()
                        ->whereKey(
                            $hymnId
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    $current =
                        $this
                            ->snapshotVariantReviewFamily(
                                $hymnId,
                                $entryIds
                            );

                    $expectedHash =
                        (string) (
                            $undo['after_hash']
                            ?? ''
                        );

                    $currentHash =
                        $this
                            ->variantReviewSnapshotHash(
                                $current
                            );

                    if (
                        $expectedHash === ''
                        || ! hash_equals(
                            $expectedHash,
                            $currentHash
                        )
                    ) {
                        throw new \RuntimeException(
                            'Rollback stopped because this '
                            . 'Hymn changed after the saved '
                            . 'review. No data was changed.'
                        );
                    }

                    /*
                     * Restore every row that existed
                     * before the save.
                     */
                    $this
                        ->restoreVariantReviewRows(
                            'hymn_variants',
                            $before[
                                'variants'
                            ] ?? []
                        );

                    $this
                        ->restoreVariantReviewRows(
                            'hymnal_net_entries',
                            $before[
                                'entries'
                            ] ?? []
                        );

                    $this
                        ->restoreVariantReviewRows(
                            'hymn_sources',
                            $before[
                                'sources'
                            ] ?? []
                        );

                    /*
                     * Remove source rows that the save
                     * itself created.
                     */
                    $beforeSourceIds =
                        collect(
                            $before[
                                'sources'
                            ] ?? []
                        )
                            ->pluck('id')
                            ->map(
                                fn ($id): int =>
                                    (int) $id
                            )
                            ->all();

                    $afterSourceIds =
                        collect(
                            $after[
                                'sources'
                            ] ?? []
                        )
                            ->pluck('id')
                            ->map(
                                fn ($id): int =>
                                    (int) $id
                            )
                            ->all();

                    $createdSourceIds =
                        array_values(
                            array_diff(
                                $afterSourceIds,
                                $beforeSourceIds
                            )
                        );

                    if (
                        $createdSourceIds !== []
                    ) {
                        DB::table(
                            'hymn_sources'
                        )
                            ->whereIn(
                                'id',
                                $createdSourceIds
                            )
                            ->delete();
                    }

                    /*
                     * Then remove structural variants
                     * that the save itself created.
                     */
                    $beforeVariantIds =
                        collect(
                            $before[
                                'variants'
                            ] ?? []
                        )
                            ->pluck('id')
                            ->map(
                                fn ($id): int =>
                                    (int) $id
                            )
                            ->all();

                    $afterVariantIds =
                        collect(
                            $after[
                                'variants'
                            ] ?? []
                        )
                            ->pluck('id')
                            ->map(
                                fn ($id): int =>
                                    (int) $id
                            )
                            ->all();

                    $createdVariantIds =
                        array_values(
                            array_diff(
                                $afterVariantIds,
                                $beforeVariantIds
                            )
                        );

                    if (
                        $createdVariantIds !== []
                    ) {
                        DB::table(
                            'hymn_variants'
                        )
                            ->whereIn(
                                'id',
                                $createdVariantIds
                            )
                            ->delete();
                    }
                }
            );

            session()->forget(
                $this->variantReviewUndoSessionKey()
            );

            $this->cancelReview();

            Notification::make()
                ->title(
                    'Last Variant Review save rolled back'
                )
                ->body(
                    (string) (
                        $undo['label']
                        ?? 'The last Variant Review save'
                    )
                    . ' was restored to its exact '
                    . 'pre-save state.'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Variant Review rollback stopped'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
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

        $this->selectedClassicVariantId =
            null;

        if (
            $entry->section_code
                === 'new_tunes'
            && $entry->matched_hymn_id
        ) {
            $classicNumber =
                $this
                    ->classicCounterpartNumberForEntry(
                        $entry
                    );

            $this->selectedClassicVariantId =
                HymnalNetEntry::query()
                    ->where(
                        'section_code',
                        'classic'
                    )
                    ->where(
                        'collection_code',
                        'h'
                    )
                    ->where(
                        'number',
                        $classicNumber
                    )
                    ->where(
                        'matched_hymn_id',
                        $entry->matched_hymn_id
                    )
                    ->where(
                        'match_status',
                        'linked'
                    )
                    ->value(
                        'matched_hymn_variant_id'
                    );
        }

        $this->hymnalReviewSearch =
            $entry->matchedHymn?->title
            ?? $entry->title
            ?? '';

        $this->newCanonicalTitle =
            $entry->title
            ?? '';

        $this->hymnalReviewSuggestions =
            in_array(
                $entry->match_status,
                [
                    'unmatched',
                    'ambiguous',
                    'conflict',
                ],
                true
            )
                ? $this
                    ->buildHymnalReviewSuggestions(
                        $entry
                    )
                : [];

        $this->hymnalUrl =
            $entry->source_url;

        $this->dispatch(
            'scroll-to-hymnal-review'
        );

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

        $this->selectedClassicVariantId =
            null;

        $this->hymnalReviewSearch = '';

        $this->hymnalReviewSuggestions = [];

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
                in_array(
                    $entry->section_code,
                    [
                        'new_tunes',
                        'alternate_tunes',
                    ],
                    true
                )
                && $activeVariants->isEmpty()
            ) {
                throw new \RuntimeException(
                    'This Hymnal.net tune entry belongs '
                    . 'to the canonical Hymn family, but '
                    . 'that Hymn has no active variants '
                    . 'yet. Establish or create the Tune '
                    . 'variants before resolving it.'
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

                if (
                    in_array(
                        $entry->section_code,
                        [
                            'new_tunes',
                            'alternate_tunes',
                        ],
                        true
                    )
                    && $variant->variant_type
                        !== 'tune'
                ) {
                    throw new \RuntimeException(
                        'A New Tunes entry may only be '
                        . 'linked to a Tune variant.'
                    );
                }
            }

            $classicEntry =
                null;

            $classicVariant =
                null;

            if (
                $entry->section_code
                    === 'new_tunes'
            ) {
                $classicNumber =
                    $this
                        ->classicCounterpartNumberForEntry(
                            $entry
                        );

                $classicEntry =
                    HymnalNetEntry::query()
                        ->where(
                            'section_code',
                            'classic'
                        )
                        ->where(
                            'collection_code',
                            'h'
                        )
                        ->where(
                            'number',
                            $classicNumber
                        )
                        ->where(
                            'matched_hymn_id',
                            $hymn->id
                        )
                        ->where(
                            'match_status',
                            'linked'
                        )
                        ->first();

                if ($classicEntry) {
                    if (
                        ! $this
                            ->selectedClassicVariantId
                    ) {
                        throw new \RuntimeException(
                            'Select the correct Tune '
                            . 'variant for the corresponding '
                            . 'Classic Hymnal.net entry.'
                        );
                    }

                    $classicVariant =
                        $activeVariants
                            ->firstWhere(
                                'id',
                                $this
                                    ->selectedClassicVariantId
                            );

                    if (
                        ! $classicVariant
                        || $classicVariant
                            ->variant_type
                            !== 'tune'
                    ) {
                        throw new \RuntimeException(
                            'The selected Classic Tune '
                            . 'variant does not belong to '
                            . 'this Hymn.'
                        );
                    }

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

            $classicExternalId =
                null;

            if ($classicEntry) {
                if (
                    blank(
                        $classicEntry->source_url
                    )
                ) {
                    throw new \RuntimeException(
                        'The corresponding Classic '
                        . 'Hymnal.net entry has no '
                        . 'source URL.'
                    );
                }

                $classicExternalId =
                    HymnalNetSource
                        ::externalIdForUrl(
                            $classicEntry
                                ->source_url
                        );

                $classicElsewhere =
                    HymnSource::query()
                        ->where(
                            'provider',
                            HymnSource
                                ::PROVIDER_HYMNAL_NET
                        )
                        ->where(
                            'external_id',
                            $classicExternalId
                        )
                        ->where(
                            'hymn_id',
                            '!=',
                            $hymn->id
                        )
                        ->with('hymn')
                        ->first();

                if ($classicElsewhere) {
                    throw new \RuntimeException(
                        'The corresponding Classic '
                        . 'Hymnal.net page is already '
                        . 'linked to another canonical '
                        . 'Hymn.'
                    );
                }
            }

            $undoBefore =
                $this->snapshotVariantReviewFamily(
                    $hymn->id,
                    [
                        $entry->id,
                    ]
                );

            DB::transaction(
                function () use (
                    $entry,
                    $hymn,
                    $variant,
                    $externalId,
                    $classicEntry,
                    $classicVariant,
                    $classicExternalId
                ): void {
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
                                    HymnSource
                                        ::TYPE_CATALOG,

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
                                        $classicEntry
                                            ? 'cross_provider_tune_mapping'
                                            : 'variant_assignment',
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

                    if (
                        $classicEntry
                        && $classicVariant
                        && $classicExternalId
                    ) {
                        $classicSource =
                            HymnSource::query()
                                ->firstOrNew([
                                    'hymn_id' =>
                                        $hymn->id,

                                    'provider' =>
                                        HymnSource
                                            ::PROVIDER_HYMNAL_NET,

                                    'external_id' =>
                                        $classicExternalId,
                                ]);

                        $classicMetadata =
                            is_array(
                                $classicSource
                                    ->metadata
                            )
                                ? $classicSource
                                    ->metadata
                                : [];

                        $classicSource->fill([
                            'hymn_variant_id' =>
                                $classicVariant->id,

                            'source_type' =>
                                HymnSource
                                    ::TYPE_CATALOG,

                            'source_url' =>
                                $classicEntry
                                    ->source_url,

                            'label' =>
                                'Hymnal.net',

                            'metadata' =>
                                array_merge(
                                    $classicMetadata,
                                    [
                                        'collection' =>
                                            $classicEntry
                                                ->collection_code,

                                        'section' =>
                                            $classicEntry
                                                ->section_code,

                                        'number' =>
                                            $classicEntry
                                                ->number,

                                        'title' =>
                                            $classicEntry
                                                ->title,

                                        'sync' =>
                                            'hymnal_net_catalog',

                                        'variant_match_method' =>
                                            'manual_review_cross_provider',

                                        'paired_new_tune_external_id' =>
                                            $externalId,
                                    ]
                                ),
                        ]);

                        $classicSource->save();

                        /*
                         * Preserve the original canonical
                         * match method (for example,
                         * songbase_book_number). Only the
                         * musician-reviewed variant
                         * assignment changes here.
                         */
                        $classicEntry->update([
                            'matched_hymn_variant_id' =>
                                $classicVariant->id,
                        ]);
                    }
                }
            );

            $this->storeVariantReviewUndo(
                $hymn->id,
                $entry,
                $undoBefore
            );

            $this->reviewEntryId =
                null;

            $this->selectedHymnId =
                null;

            $this->selectedVariantId =
                null;

            $this->selectedClassicVariantId =
                null;

            $this->hymnalReviewSearch = '';

            $this->newCanonicalTitle = '';

            $this->hymnalUrl = '';

            $this->dispatch(
                'scroll-to-variant-review'
            );

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


    public function createNewTuneVariantFromReviewedEntry(): void
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

            if (
                ! in_array(
                    $entry->section_code,
                    [
                        'new_tunes',
                        'alternate_tunes',
                    ],
                    true
                )
            ) {
                throw new \RuntimeException(
                    'Only Hymnal.net New Tunes or '
                    . 'Alternate Tunes entries can '
                    . 'create a new Tune variant '
                    . 'through this action.'
                );
            }

            if (
                ! in_array(
                    $entry->match_status,
                    [
                        'variant_review',
                        'unmatched',
                        'ambiguous',
                    ],
                    true
                )
            ) {
                throw new \RuntimeException(
                    'This Hymnal.net tune entry is not '
                    . 'available for new-variant review.'
                );
            }

            $hymn =
                Hymn::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->findOrFail(
                        $this->selectedHymnId
                    );

            $externalId =
                HymnalNetSource
                    ::externalIdForUrl(
                        $entry->source_url
                    );

            $undoBefore =
                $this->snapshotVariantReviewFamily(
                    $hymn->id,
                    [
                        $entry->id,
                    ]
                );

            $result =
                DB::transaction(
                    function () use (
                        $entry,
                        $hymn,
                        $externalId
                    ): array {
                        /*
                         * Lock the review row so a rapid
                         * double-click cannot create two
                         * Tune variants.
                         */
                        $lockedEntry =
                            HymnalNetEntry::query()
                                ->whereKey(
                                    $entry->id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        if (
                            $lockedEntry->match_status
                                === 'linked'
                        ) {
                            throw new \RuntimeException(
                                'This Hymnal.net tune entry has '
                                . 'already been resolved.'
                            );
                        }

                        $lockedHymn =
                            Hymn::query()
                                ->whereKey(
                                    $hymn->id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        $reviewCreatedBy =
                            $lockedEntry->section_code
                                === 'alternate_tunes'
                                ? 'hymnal_net_alternate_tune_review'
                                : 'hymnal_net_new_tune_review';

                        /*
                         * Provider identity belongs to
                         * hymn_sources. Never create a
                         * second variant if this NT page
                         * is already attached somewhere.
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
                                ->lockForUpdate()
                                ->first();

                        if ($existingSource) {
                            if (
                                (int)
                                    $existingSource
                                        ->hymn_id
                                !== (int)
                                    $lockedHymn->id
                            ) {
                                throw new \RuntimeException(
                                    'This Hymnal.net page '
                                    . 'is already linked to '
                                    . 'another canonical '
                                    . 'Hymn.'
                                );
                            }

                            throw new \RuntimeException(
                                'This Hymnal.net tune page '
                                . 'already has a source '
                                . 'record. Review the '
                                . 'existing link instead '
                                . 'of creating another '
                                . 'variant.'
                            );
                        }

                        /*
                         * Include inactive Tune variants
                         * when calculating the next index.
                         * An old Tune number must not be
                         * reused merely because that row
                         * was deactivated later.
                         */
                        $tuneVariants =
                            HymnVariant::query()
                                ->where(
                                    'hymn_id',
                                    $lockedHymn->id
                                )
                                ->where(
                                    'variant_type',
                                    'tune'
                                )
                                ->orderBy(
                                    'variant_index'
                                )
                                ->orderBy(
                                    'id'
                                )
                                ->lockForUpdate()
                                ->get();

                        $baselineVariant =
                            null;

                        /*
                         * First explicit alternate tune:
                         *
                         * The existing canonical/default
                         * tune becomes Tune 1 and this NT
                         * page becomes Tune 2.
                         *
                         * This prevents the new tune from
                         * becoming the sole active variant,
                         * which could cause future syncs to
                         * mistake it for the default tune.
                         */
                        if ($tuneVariants->isEmpty()) {
                            $baselineVariant =
                                HymnVariant::query()
                                    ->create([
                                        'hymn_id' =>
                                            $lockedHymn->id,

                                        /*
                                         * These legacy
                                         * provenance fields
                                         * identify a manual
                                         * variant decision.
                                         * Provider identity
                                         * remains in
                                         * hymn_sources.
                                         */
                                        'source' =>
                                            'manual',

                                        'source_id' =>
                                            'baseline:hymn:'
                                            . $lockedHymn->id,

                                        'variant_type' =>
                                            'tune',

                                        /*
                                         * Tune parser uses
                                         * zero-based indexes:
                                         * index 0 = Tune 1.
                                         */
                                        'variant_index' =>
                                            0,

                                        'label' =>
                                            'Tune 1',

                                        'title_override' =>
                                            null,

                                        'metadata' => [
                                            'created_by' =>
                                                $reviewCreatedBy,

                                            'role' =>
                                                'existing_tune_baseline',
                                        ],

                                        'sort_order' =>
                                            0,

                                        'is_active' =>
                                            true,
                                    ]);

                            /*
                             * If the matching Classic
                             * Hymnal.net entry already
                             * exists, h/{number} is the
                             * baseline tune counterpart of
                             * nt/{number}. Assign that
                             * Classic source to Tune 1.
                             *
                             * Other canonical-level
                             * providers are intentionally
                             * left untouched because we
                             * should not guess their tune.
                             */
                            $classicNumber =
                                $this
                                    ->classicCounterpartNumberForEntry(
                                        $lockedEntry
                                    );

                            $classicEntry =
                                HymnalNetEntry::query()
                                    ->where(
                                        'section_code',
                                        'classic'
                                    )
                                    ->where(
                                        'collection_code',
                                        'h'
                                    )
                                    ->where(
                                        'number',
                                        $classicNumber
                                    )
                                    ->where(
                                        'matched_hymn_id',
                                        $lockedHymn->id
                                    )
                                    ->where(
                                        'match_status',
                                        'linked'
                                    )
                                    ->first();

                            if ($classicEntry) {
                                if (
                                    ! $classicEntry
                                        ->matched_hymn_variant_id
                                ) {
                                    $classicEntry->update([
                                        'matched_hymn_variant_id' =>
                                            $baselineVariant->id,
                                    ]);
                                }

                                $classicExternalId =
                                    HymnalNetSource
                                        ::externalIdForUrl(
                                            $classicEntry
                                                ->source_url
                                        );

                                $classicSource =
                                    HymnSource::query()
                                        ->where(
                                            'hymn_id',
                                            $lockedHymn->id
                                        )
                                        ->where(
                                            'provider',
                                            HymnSource
                                                ::PROVIDER_HYMNAL_NET
                                        )
                                        ->where(
                                            'external_id',
                                            $classicExternalId
                                        )
                                        ->first();

                                if (
                                    $classicSource
                                    && ! $classicSource
                                        ->hymn_variant_id
                                ) {
                                    $classicSource->update([
                                        'hymn_variant_id' =>
                                            $baselineVariant->id,
                                    ]);
                                }
                            }

                            $nextIndex =
                                1;

                            $nextSortOrder =
                                1;
                        } else {
                            $indexes =
                                $tuneVariants
                                    ->pluck(
                                        'variant_index'
                                    )
                                    ->filter(
                                        fn ($value): bool =>
                                            $value !== null
                                    );

                            $nextIndex =
                                $indexes->isEmpty()
                                    ? $tuneVariants
                                        ->count()
                                    : (
                                        (int)
                                            $indexes->max()
                                        + 1
                                    );

                            $maxSortOrder =
                                $tuneVariants
                                    ->pluck(
                                        'sort_order'
                                    )
                                    ->filter(
                                        fn ($value): bool =>
                                            $value !== null
                                    )
                                    ->max();

                            $nextSortOrder =
                                $maxSortOrder === null
                                    ? $nextIndex
                                    : (
                                        (int)
                                            $maxSortOrder
                                        + 1
                                    );
                        }

                        $newVariant =
                            HymnVariant::query()
                                ->create([
                                    'hymn_id' =>
                                        $lockedHymn->id,

                                    'source' =>
                                        'manual',

                                    'source_id' =>
                                        'review:hymnal-net-entry:'
                                        . $lockedEntry->id,

                                    'variant_type' =>
                                        'tune',

                                    'variant_index' =>
                                        $nextIndex,

                                    'label' =>
                                        'Tune '
                                        . (
                                            $nextIndex
                                            + 1
                                        ),

                                    'title_override' =>
                                        null,

                                    'metadata' => [
                                        'created_by' =>
                                            $reviewCreatedBy,

                                        'section' =>
                                            $lockedEntry
                                                ->section_code,

                                        'collection' =>
                                            $lockedEntry
                                                ->collection_code,

                                        'number' =>
                                            $lockedEntry
                                                ->number,

                                        'external_id' =>
                                            $externalId,
                                    ],

                                    'sort_order' =>
                                        $nextSortOrder,

                                    'is_active' =>
                                        true,
                                ]);

                        /*
                         * This is the provider-owned row.
                         * The NT page is attached directly
                         * to the newly-created Tune.
                         */
                        HymnSource::query()
                            ->create([
                                'hymn_id' =>
                                    $lockedHymn->id,

                                'hymn_variant_id' =>
                                    $newVariant->id,

                                'provider' =>
                                    HymnSource
                                        ::PROVIDER_HYMNAL_NET,

                                'source_type' =>
                                    HymnSource
                                        ::TYPE_CATALOG,

                                'external_id' =>
                                    $externalId,

                                'source_url' =>
                                    $lockedEntry
                                        ->source_url,

                                'label' =>
                                    'Hymnal.net',

                                'metadata' => [
                                    'collection' =>
                                        $lockedEntry
                                            ->collection_code,

                                    'section' =>
                                        $lockedEntry
                                            ->section_code,

                                    'number' =>
                                        $lockedEntry
                                            ->number,

                                    'title' =>
                                        $lockedEntry
                                            ->title,

                                    'sync' =>
                                        'hymnal_net_catalog',

                                    'match_method' =>
                                        'manual_review',

                                    'review_action' =>
                                        'created_tune_variant',
                                ],
                            ]);

                        $lockedEntry->update([
                            'matched_hymn_id' =>
                                $lockedHymn->id,

                            'matched_hymn_variant_id' =>
                                $newVariant->id,

                            'match_status' =>
                                'linked',

                            'match_method' =>
                                'manual_review',

                            'match_score' =>
                                100,
                        ]);

                        return [
                            'variant' =>
                                $newVariant,

                            'baseline' =>
                                $baselineVariant,
                        ];
                    }
                );

            $variant =
                $result['variant'];

            $baseline =
                $result['baseline'];

            $entryNumber =
                strtoupper(
                    $entry->collection_code
                )
                . $entry->number;

            $hymnTitle =
                $hymn->title;

            $this->storeVariantReviewUndo(
                $hymn->id,
                $entry,
                $undoBefore
            );

            $this->cancelReview();

            Notification::make()
                ->title(
                    'Tune variant created'
                )
                ->body(
                    $baseline
                        ? (
                            'Created Tune 1 as the '
                            . 'existing-tune baseline and '
                            . $variant->label
                            . ' for '
                            . $entryNumber
                            . ' under "'
                            . $hymnTitle
                            . '".'
                        )
                        : (
                            'Created '
                            . $variant->label
                            . ' for '
                            . $entryNumber
                            . ' under "'
                            . $hymnTitle
                            . '".'
                        )
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Tune variant was not created'
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

            $this->dispatch(
                'scroll-to-variant-review'
            );

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
