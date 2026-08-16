<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ServiceMeetingMinutes extends Page
{
    protected string $view = 'filament.pages.service-meeting-minutes';

    public function getTitle(): string
    {
        return 'Service Meeting Minutes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Service Meeting Minutes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
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
