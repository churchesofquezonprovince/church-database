<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Schedules extends Page
{
    protected static ?string $slug = 'schedules';

    protected string $view = 'filament.pages.schedules';

    protected static string | \UnitEnum | null $navigationGroup = 'Posts';

    protected static ?string $navigationLabel = 'Schedules';

    protected static ?string $title = 'Schedules';

    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
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
