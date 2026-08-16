<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class YpMeeting extends Page
{
    protected string $view = 'filament.pages.yp-meeting';

    public function getTitle(): string
    {
        return 'YP Meeting';
    }

    public static function getNavigationLabel(): string
    {
        return 'YP Meeting';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-book-open';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
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
