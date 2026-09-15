<?php

namespace App\Filament\Pages;

use App\Models\Hymn;
use App\Models\HymnBook;
use App\Models\HymnSource;
use App\Services\SongbaseHymnSyncService;
use App\Support\HymnSearchRanker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SongbaseSetup extends Page
{
    protected string $view =
        'filament.pages.songbase-setup';

    protected static ?string $slug =
        'songbase-setup';

    public string $songbaseSearch = '';

    public ?int $songbaseBookId = null;

    public string $songbaseLanguage = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );
    }

    public function getTitle(): string
    {
        return 'Songbase Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'Songbase Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-path';
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

    public function summary(): array
    {
        $songbaseSources =
            HymnSource::query()
                ->where(
                    'provider',
                    HymnSource::PROVIDER_SONGBASE
                );

        return [
            /*
             * A Songbase canonical source has no
             * Hymn Variant attached.
             */
            'canonical_hymns' =>
                (clone $songbaseSources)
                    ->whereNull(
                        'hymn_variant_id'
                    )
                    ->distinct()
                    ->count('hymn_id'),

            'variant_sources' =>
                (clone $songbaseSources)
                    ->whereNotNull(
                        'hymn_variant_id'
                    )
                    ->count(),

            'source_links' =>
                (clone $songbaseSources)
                    ->count(),

            'languages' =>
                Hymn::query()
                    ->whereNotNull('language')
                    ->whereHas(
                        'sources',
                        fn ($query) =>
                            $query->where(
                                'provider',
                                HymnSource::PROVIDER_SONGBASE
                            )
                    )
                    ->distinct()
                    ->count('language'),

            'books' =>
                HymnBook::query()
                    ->where(
                        'source',
                        'songbase'
                    )
                    ->count(),

            'book_entries' =>
                DB::table(
                    'hymn_book_entries'
                )
                    ->join(
                        'hymn_books',
                        'hymn_books.id',
                        '=',
                        'hymn_book_entries.hymn_book_id'
                    )
                    ->where(
                        'hymn_books.source',
                        'songbase'
                    )
                    ->count(),

            /*
             * Legacy compatibility only.
             * Source synchronization will later get
             * its own provider sync-state record.
             */
            'last_synced_at' =>
                Hymn::query()
                    ->where(
                        'source',
                        'songbase'
                    )
                    ->max(
                        'last_synced_at'
                    ),
        ];
    }

    public function books(): Collection
    {
        return HymnBook::query()
            ->where(
                'source',
                'songbase'
            )
            ->withCount('entries')
            ->orderBy('language')
            ->orderBy('name')
            ->get();
    }

    public function languages(): Collection
    {
        return Hymn::query()
            ->select('language')
            ->selectRaw(
                'COUNT(*) AS total'
            )
            ->whereNotNull('language')
            ->whereHas(
                'sources',
                fn ($query) =>
                    $query->where(
                        'provider',
                        HymnSource::PROVIDER_SONGBASE
                    )
            )
            ->groupBy('language')
            ->orderBy('language')
            ->get();
    }

    public function toggleSongbaseBook(
        int $bookId
    ): void {
        $exists =
            HymnBook::query()
                ->whereKey($bookId)
                ->where(
                    'source',
                    'songbase'
                )
                ->exists();

        abort_unless(
            $exists,
            404
        );

        $this->songbaseBookId =
            $this->songbaseBookId === $bookId
                ? null
                : $bookId;
    }

    public function toggleSongbaseLanguage(
        string $language
    ): void {
        $exists =
            Hymn::query()
                ->where(
                    'language',
                    $language
                )
                ->whereHas(
                    'sources',
                    fn ($query) =>
                        $query->where(
                            'provider',
                            HymnSource::PROVIDER_SONGBASE
                        )
                )
                ->exists();

        abort_unless(
            $exists,
            404
        );

        $this->songbaseLanguage =
            $this->songbaseLanguage === $language
                ? ''
                : $language;
    }

    public function clearSongbaseFilters(): void
    {
        $this->songbaseSearch = '';

        $this->songbaseBookId = null;

        $this->songbaseLanguage = '';
    }

    public function songbaseHymns(): Collection
    {
        $search =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $this->songbaseSearch
                )
            )
            ?? trim(
                $this->songbaseSearch
            );

        $hasFilters =
            $search !== ''
            || $this->songbaseBookId !== null
            || $this->songbaseLanguage !== '';

        if (! $hasFilters) {
            return collect();
        }

        $like =
            '%' . $search . '%';

        return Hymn::query()
            ->with([
                'bookEntries.hymnBook',
                'sources',
                'variants.sources',
            ])
            ->where(
                'is_active',
                true
            )
            /*
             * This catalog is deliberately restricted
             * to Hymns actually connected to Songbase.
             */
            ->whereHas(
                'sources',
                fn ($query) =>
                    $query->where(
                        'provider',
                        HymnSource::PROVIDER_SONGBASE
                    )
            )
            ->when(
                $this->songbaseBookId !== null,
                fn ($query) =>
                    $query->whereHas(
                        'bookEntries',
                        fn ($entryQuery) =>
                            $entryQuery->where(
                                'hymn_book_id',
                                $this->songbaseBookId
                            )
                    )
            )
            ->when(
                $this->songbaseLanguage !== '',
                fn ($query) =>
                    $query->where(
                        'language',
                        $this->songbaseLanguage
                    )
            )
            ->when(
                $search !== '',
                function ($query) use (
                    $search,
                    $like
                ): void {
                    $query->where(
                        function ($query) use (
                            $search,
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
                                        $sourceQuery
                                            ->where(
                                                'provider',
                                                HymnSource
                                                    ::PROVIDER_SONGBASE
                                            )
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
                                        $sourceQuery
                                            ->where(
                                                'provider',
                                                HymnSource::PROVIDER_SONGBASE
                                            )
                                            ->where(
                                                'external_id',
                                                'like',
                                                $like
                                            )
                                )
                                ->orWhereHas(
                                    'bookEntries',
                                    function (
                                        $entryQuery
                                    ) use (
                                        $like
                                    ): void {
                                        $entryQuery
                                            ->where(
                                                'number',
                                                'like',
                                                $like
                                            )
                                            ->whereHas(
                                                'hymnBook',
                                                fn ($bookQuery) =>
                                                    $bookQuery
                                                        ->where(
                                                            'source',
                                                            'songbase'
                                                        )
                                            );
                                    }
                                )
                                ->orWhereHas(
                                    'variants',
                                    function (
                                        $variantQuery
                                    ) use (
                                        $like
                                    ): void {
                                        $variantQuery
                                            ->where(
                                                'is_active',
                                                true
                                            )
                                            ->where(
                                                function (
                                                    $query
                                                ) use (
                                                    $like
                                                ): void {
                                                    $query
                                                        ->where(
                                                            'label',
                                                            'like',
                                                            $like
                                                        )
                                                        ->orWhere(
                                                            'title_override',
                                                            'like',
                                                            $like
                                                        )
                                                        ->orWhereHas(
                                                            'sources',
                                                            fn ($sourceQuery) =>
                                                                $sourceQuery
                                                                    ->where(
                                                                        'provider',
                                                                        HymnSource::PROVIDER_SONGBASE
                                                                    )
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
                    );

                    HymnSearchRanker::apply(
                        $query,
                        $search,
                        false,
                        HymnSource
                            ::PROVIDER_SONGBASE
                    );
                }
            )
            ->when(
                $search === '',
                fn ($query) =>
                    $query->orderBy('title')
            )
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
            app(
                SongbaseHymnSyncService::class
            )->fullSync();

            Notification::make()
                ->title(
                    'Songbase synchronization completed'
                )
                ->body(
                    'Canonical Hymns, Songbase sources, '
                    . 'books, lyrics, and discovered '
                    . 'variants were synchronized.'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(
                    'Songbase synchronization failed'
                )
                ->body(
                    $e->getMessage()
                )
                ->danger()
                ->send();
        }
    }
}
