<?php

namespace App\Filament\Pages;

use App\Models\HymnAdditionRequest;
use App\Models\HymnSource;
use App\Support\HymnSourceResolver;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ExternalHymnsSetup extends Page
{
    protected string $view =
        'filament.pages.external-hymns-setup';

    protected static ?string $slug =
        'external-hymns-setup';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );
    }

    public function getTitle(): string
    {
        return 'External Hymns Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'External Hymns Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-play-circle';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
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
        return [
            'pending_external' =>
                HymnAdditionRequest::query()
                    ->where(
                        'status',
                        HymnAdditionRequest::STATUS_PENDING
                    )
                    ->whereNotNull(
                        'source_url'
                    )
                    ->where(
                        'source_url',
                        '!=',
                        ''
                    )
                    ->count(),

            'youtube' =>
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource::PROVIDER_YOUTUBE
                    )
                    ->count(),

            'soundcloud' =>
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource::PROVIDER_SOUNDCLOUD
                    )
                    ->count(),

            'other' =>
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource::PROVIDER_OTHER
                    )
                    ->count(),
        ];
    }

    public function linkedExternalSources(): Collection
    {
        return HymnSource::query()
            ->with([
                'hymn',
            ])
            ->whereIn(
                'provider',
                [
                    HymnSource::PROVIDER_YOUTUBE,
                    HymnSource::PROVIDER_SOUNDCLOUD,
                    HymnSource::PROVIDER_OTHER,
                ]
            )
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    public function sourceLabel(
        HymnSource $source
    ): string {
        return HymnSourceResolver
            ::labelForProvider(
                $source->provider
            );
    }
}
