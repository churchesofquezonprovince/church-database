<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TraineesSection extends Page
{
    protected string $view = 'filament.pages.trainees-section';

    public function getTitle(): string
    {
        return 'Trainees Section';
    }

    public static function getNavigationLabel(): string
    {
        return 'Trainees Section';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-group';
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
