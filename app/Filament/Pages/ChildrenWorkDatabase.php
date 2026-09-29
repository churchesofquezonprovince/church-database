<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ChildrenWorkDatabase extends Page
{
    protected string $view =
        'filament.pages.children-work-database';

    public function getTitle(): string
    {
        return 'Children Database';
    }

    public static function getNavigationLabel(): string
    {
        return 'Children Database';
    }

    public static function getNavigationGroup(): ?string
    {
        return "Children's Work";
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
