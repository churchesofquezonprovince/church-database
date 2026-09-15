<?php

namespace App\Filament\Pages;

use App\Models\Hymn;
use App\Models\HymnBook;
use App\Models\HymnSource;
use App\Services\SongbaseHymnSyncService;
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
