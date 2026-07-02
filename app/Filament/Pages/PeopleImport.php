<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PeopleImport extends Page
{
    protected string $view = 'filament.pages.people-import';


    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canImportRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canImportRecords() ?? false;
    }

    public function getTitle(): string
    {
        return 'People Import';
    }

    public static function getNavigationLabel(): string
    {
        return 'People Import';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Church Database';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-up-tray';
    }

    public static function getNavigationSort(): ?int
    {
        return 8;
    }
}
