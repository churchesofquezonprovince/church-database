<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Schedules extends Page
{
    protected string $view = 'filament.pages.schedules';

    public function getTitle(): string
    {
        return 'Schedules';
    }

    public static function getNavigationLabel(): string
    {
        return 'Schedules';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
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
