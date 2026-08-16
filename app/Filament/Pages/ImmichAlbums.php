<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ImmichAlbums extends Page
{
    protected string $view = 'filament.pages.immich-albums';

    public function getTitle(): string
    {
        return 'Immich Albums';
    }

    public static function getNavigationLabel(): string
    {
        return 'Immich Albums';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-photo';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }
}
