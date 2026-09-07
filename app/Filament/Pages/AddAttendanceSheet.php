<?php

namespace App\Filament\Pages;

use App\Support\LocalityOptions;
use Filament\Pages\Page;

class AddAttendanceSheet extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'attendance-sheets/add-attendance-sheet';

    protected string $view = 'filament.pages.add-attendance-sheet';

    public function localityOptions(): array
    {
        return LocalityOptions::groupedActiveConfigured();
    }

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
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }
}
