<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Newsletter extends Page
{
    protected string $view = 'filament.pages.newsletter';

    public function getTitle(): string
    {
        return 'Newsletter';
    }

    public static function getNavigationLabel(): string
    {
        return 'Newsletter';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-newspaper';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
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
