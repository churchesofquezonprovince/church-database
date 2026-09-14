<?php

namespace App\Filament\Pages;

use App\Models\Hymn;
use App\Models\HymnBook;
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
