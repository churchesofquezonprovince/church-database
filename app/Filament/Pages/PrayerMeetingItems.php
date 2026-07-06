<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PrayerMeetingItems extends Page
{
    protected string $view = 'filament.pages.prayer-meeting-items';

    public function getTitle(): string
    {
        return 'Prayer Meeting Items';
    }

    public static function getNavigationLabel(): string
    {
        return 'Prayer Meeting Items';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
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
