<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AddAttendanceSheet extends Page
{
    protected string $view = 'filament.pages.add-attendance-sheet';

    public function getTitle(): string
    {
        return 'Add Attendance Sheet';
    }

    public static function getNavigationLabel(): string
    {
        return 'Add Attendance Sheet';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
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
