<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class LordsTableMeeting extends Page
{
    protected string $view = 'filament.pages.lords-table-meeting';

    public function getTitle(): string
    {
        return "Lord's Table Meeting";
    }

    public static function getNavigationLabel(): string
    {
        return "Lord's Table Meeting";
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-check-circle';
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
