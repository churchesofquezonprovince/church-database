<?php

namespace App\Filament\Pages;

use App\Models\Hymn;
use App\Models\HymnalNetEntry;
use App\Models\HymnSource;
use App\Support\HymnLyricsNormalizer;
use App\Support\HymnalNetCollectionCatalog;
use App\Support\HymnalNetSource;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Throwable;

class HymnalNetSetup extends Page
{
    protected string $view =
        'filament.pages.hymnal-net-setup';

    protected static ?string $slug =
        'hymnal-net-setup';

    public string $hymnSearch = '';

    public ?int $selectedHymnId = null;

    public string $hymnalUrl = '';

    public string $linkedSearch = '';

    public string $linkedSectionFilter = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );
    }

    public function getTitle(): string
    {
        return 'Hymnal.net Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'Hymnal.net Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-link';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin()
            ?? false;
    }

    public function hymnMatches(): Collection
    {
        $search =
            trim($this->hymnSearch);

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
                            function (
                                $sourceQuery
                            ) use (
                                $like
                            ): void {
                                $sourceQuery->where(
                                    function (
                                        $lyricsQuery
                                    ) use (
                                        $like
                                    ): void {
                                        $lyricsQuery
                                            ->where(
                                                'first_line_search',
                                                'like',
                                                HymnLyricsNormalizer::containsPattern($like)
                                            )
                                            ->orWhere(
                                                'lyrics_search',
                                                'like',
                                                HymnLyricsNormalizer::containsPattern($like)
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
                    \App\Support\HymnSearchRanker
                        ::apply(
                            $query,
                            $search,
                            true
                        )
            )
            ->limit(30)
            ->get();
    }

    public function selectHymn(
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

        $this->hymnSearch =
            $hymn->title;
    }

    public function selectedHymn(): ?Hymn
    {
        if (! $this->selectedHymnId) {
            return null;
        }

        return Hymn::query()
            ->with([
                'bookEntries.hymnBook',
                'sources',
            ])
            ->find(
                $this->selectedHymnId
            );
    }

    public function attachHymnalNetSource(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $this->validate([
            'selectedHymnId' => [
                'required',
                'integer',
                'exists:hymns,id',
            ],
            'hymnalUrl' => [
                'required',
                'string',
                'max:2048',
            ],
        ]);

        try {
            $url =
                HymnalNetSource::normalizeUrl(
                    $this->hymnalUrl
                );

            $externalId =
                HymnalNetSource
                    ::externalIdForUrl(
                        $url
                    );

            $existingElsewhere =
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource::PROVIDER_HYMNAL_NET
                    )
                    ->where(
                        'external_id',
                        $externalId
                    )
                    ->where(
                        'hymn_id',
                        '!=',
                        $this->selectedHymnId
                    )
                    ->with('hymn')
                    ->first();

            if ($existingElsewhere) {
                throw new \RuntimeException(
                    'That Hymnal.net page is already '
                    . 'linked to the canonical Hymn "'
                    . (
                        $existingElsewhere
                            ->hymn
                            ?->title
                        ?? 'Unknown Hymn'
                    )
                    . '".'
                );
            }

            $source =
                HymnSource::query()
                    ->updateOrCreate(
                        [
                            'hymn_id' =>
                                $this->selectedHymnId,

                            'provider' =>
                                HymnSource
                                    ::PROVIDER_HYMNAL_NET,

                            'external_id' =>
                                $externalId,
                        ],
                        [
                            'source_type' =>
                                HymnSource::TYPE_CATALOG,

                            'source_url' =>
                                $url,

                            'label' =>
                                'Hymnal.net',
                        ]
                    );

            $this->hymnalUrl = '';

            Notification::make()
                ->title(
                    $source->wasRecentlyCreated
                        ? 'Hymnal.net source linked'
                        : 'Hymnal.net source updated'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Hymnal.net source was not linked'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }

    public function filterLinkedSourcesBySection(
        string $section
    ): void {
        if (
            ! array_key_exists(
                $section,
                HymnalNetCollectionCatalog
                    ::sections()
            )
        ) {
            throw new \RuntimeException(
                'Unknown Hymnal.net collection filter.'
            );
        }

        $this->linkedSectionFilter =
            $section;

        $this->dispatch(
            'scroll-to-linked-hymnal-sources'
        );
    }


    public function clearLinkedSourcesSectionFilter(): void
    {
        $this->linkedSectionFilter = '';

        $this->dispatch(
            'scroll-to-linked-hymnal-sources'
        );
    }


    public function linkedSources(): Collection
    {
        $search =
            trim(
                $this->linkedSearch
            );

        return HymnSource::query()
            ->where(
                'provider',
                HymnSource::PROVIDER_HYMNAL_NET
            )
            ->when(
                $this->linkedSectionFilter !== '',
                function ($query): void {
                    $section =
                        $this->linkedSectionFilter;

                    $query->where(
                        function ($query) use (
                            $section
                        ): void {
                            $query
                                ->where(
                                    'metadata->section',
                                    $section
                                )
                                ->orWhereExists(
                                    function (
                                        $entryQuery
                                    ) use (
                                        $section
                                    ): void {
                                        $entryQuery
                                            ->selectRaw('1')
                                            ->from(
                                                'hymnal_net_entries'
                                            )
                                            ->whereColumn(
                                                'hymnal_net_entries.source_url',
                                                'hymn_sources.source_url'
                                            )
                                            ->where(
                                                'hymnal_net_entries.section_code',
                                                $section
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->with([
                'hymn.bookEntries.hymnBook',
            ])
            ->when(
                $search !== '',
                function ($query) use (
                    $search
                ): void {
                    $like =
                        '%' . $search . '%';

                    $query->where(
                        function ($query) use (
                            $like
                        ): void {
                            $query
                                ->where(
                                    'source_url',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'external_id',
                                    'like',
                                    $like
                                )
                                ->orWhereHas(
                                    'hymn',
                                    function (
                                        $hymnQuery
                                    ) use (
                                        $like
                                    ): void {
                                        $hymnQuery
                                            ->where(
                                                'title',
                                                'like',
                                                $like
                                            )
                                            ->orWhereHas(
                                                'sources',
                                                function (
                                                    $sourceQuery
                                                ) use (
                                                    $like
                                                ): void {
                                                    $sourceQuery
                                                        ->where(
                                                            function (
                                                                $lyricsQuery
                                                            ) use (
                                                                $like
                                                            ): void {
                                                                $lyricsQuery
                                                                    ->where(
                                                                        'first_line_search',
                                                                        'like',
                                                                        HymnLyricsNormalizer::containsPattern($like)
                                                                    )
                                                                    ->orWhere(
                                                                        'lyrics_search',
                                                                        'like',
                                                                        HymnLyricsNormalizer::containsPattern($like)
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
                                                            $like
                                                        )
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->latest('id')
            ->get();
    }

    public function catalogSummary(): array
    {
        $base =
            HymnalNetEntry::query();

        return [
            'total' =>
                (clone $base)->count(),

            'linked' =>
                (clone $base)
                    ->where(
                        'match_status',
                        'linked'
                    )
                    ->count(),

            'unmatched' =>
                (clone $base)
                    ->where(
                        'match_status',
                        'unmatched'
                    )
                    ->count(),

            'ambiguous' =>
                (clone $base)
                    ->where(
                        'match_status',
                        'ambiguous'
                    )
                    ->count(),

            'conflict' =>
                (clone $base)
                    ->where(
                        'match_status',
                        'conflict'
                    )
                    ->count(),

            'invalid' =>
                (clone $base)
                    ->where(
                        'validation_status',
                        'invalid'
                    )
                    ->count(),

            'variant_review' =>
                (clone $base)
                    ->where(
                        'match_status',
                        'variant_review'
                    )
                    ->count(),

            'failed' =>
                (clone $base)
                    ->whereIn(
                        'fetch_status',
                        [
                            'error',
                            'http_error',
                        ]
                    )
                    ->count(),
        ];
    }

    public function collectionSummaries(): Collection
    {
        return collect(
            HymnalNetCollectionCatalog
                ::sections()
        )
            ->map(
                function (
                    array $config,
                    string $section
                ): array {
                    $base =
                        HymnalNetEntry::query()
                            ->where(
                                'section_code',
                                $section
                            );

                    $routes =
                        (clone $base)
                            ->whereNotNull(
                                'collection_code'
                            )
                            ->distinct()
                            ->orderBy(
                                'collection_code'
                            )
                            ->pluck(
                                'collection_code'
                            )
                            ->values();

                    $review =
                        (clone $base)
                            ->whereIn(
                                'match_status',
                                [
                                    'unmatched',
                                    'ambiguous',
                                    'conflict',
                                    'variant_review',
                                ]
                            )
                            ->count();

                    return [
                        'code' =>
                            $section,

                        'label' =>
                            $config['label'],

                        'index_code' =>
                            $config['index_code'],

                        'primary_route' =>
                            $config[
                                'primary_route'
                            ],

                        'routes' =>
                            $routes,

                        'total' =>
                            (clone $base)
                                ->count(),

                        'pending' =>
                            (clone $base)
                                ->where(
                                    'fetch_status',
                                    'pending'
                                )
                                ->count(),

                        'linked' =>
                            (clone $base)
                                ->where(
                                    'match_status',
                                    'linked'
                                )
                                ->count(),

                        'review' =>
                            $review,

                        'failed' =>
                            (clone $base)
                                ->whereIn(
                                    'fetch_status',
                                    [
                                        'error',
                                        'http_error',
                                    ]
                                )
                                ->count(),
                    ];
                }
            )
            ->values();
    }

    public function deleteHymnalNetSource(
        int $sourceId
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $source =
            HymnSource::query()
                ->where(
                    'provider',
                    HymnSource::PROVIDER_HYMNAL_NET
                )
                ->findOrFail(
                    $sourceId
                );

        $source->delete();

        Notification::make()
            ->title(
                'Hymnal.net source removed'
            )
            ->body(
                'The canonical Hymn was not deleted.'
            )
            ->success()
            ->send();
    }
}
