<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EnjoymentPosts extends Page
{
    protected string $view = 'filament.pages.enjoyment-posts';

    public function getTitle(): string
    {
        return 'Enjoyment Posts';
    }

    public static function getNavigationLabel(): string
    {
        return 'Enjoyment Posts';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
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
