<?php

namespace App\Filament\Dashboards;

use Filament\Pages\Dashboard as BaseDashboard;

class ChurchDashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return 'Churches of Quezon Database Dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Church Database';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-home';
    }
}
