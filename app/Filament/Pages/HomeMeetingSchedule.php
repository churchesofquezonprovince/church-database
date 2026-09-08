<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HomeMeetingSchedule extends Page
{
    protected string $view = 'filament.pages.home-meeting-schedule';

    public function getTitle(): string
    {
        return 'Home Meeting Schedule';
    }

    public static function getNavigationLabel(): string
    {
        return 'Home Meeting Schedule';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
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
