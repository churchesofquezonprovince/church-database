<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ChildrenWorkDashboard extends Page
{
    protected string $view = 'filament.pages.children-work-dashboard';

    public function getTitle(): string
    {
        return "Children's Work Dashboard";
    }

    public static function getNavigationLabel(): string
    {
        return "Children's Work Dashboard";
    }

    public static function getNavigationGroup(): ?string
    {
        return "Children's Work";
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-heart';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::SixExtraLarge;
    }
}