<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ChildrenWorkLessons extends Page
{
    protected string $view = 'filament.pages.children-work-lessons';

    public function getTitle(): string
    {
        return 'Lessons';
    }

    public static function getNavigationLabel(): string
    {
        return 'Lessons';
    }

    public static function getNavigationGroup(): ?string
    {
        return "Children's Work";
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-book-open';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
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
