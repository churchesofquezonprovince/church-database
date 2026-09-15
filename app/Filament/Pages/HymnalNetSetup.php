<?php

namespace App\Filament\Pages;

use App\Models\Hymn;
use App\Models\HymnalNetEntry;
use App\Models\HymnSource;
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

    public string $search = '';

    public ?int $selectedHymnId = null;

    public string $hymnalUrl = '';

    public string $linkedSearch = '';

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
            trim($this->search);

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
                        ->orWhere(
                            'lyrics_search',
                            'like',
                            $like
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
            ->orderByRaw(
                "CASE
                    WHEN language = 'english'
                    THEN 0
                    ELSE 1
                END"
            )
            ->orderBy('title')
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

        $this->search =
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
            HymnalNetEntry::query()
                ->where(
                    'collection_code',
                    'h'
                );

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
