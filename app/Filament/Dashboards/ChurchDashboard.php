<?php

namespace App\Filament\Dashboards;

use App\Filament\Widgets\SchedulesCalendarWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

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

public static function getNavigationSort(): ?int
    {
        return 10;
    }

    /**
     * Keep the editable Schedules calendar off the Church
     * Dashboard. The discovered DashboardSchedulesCalendarWidget
     * remains available there as the read-only version.
     *
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return array_values(
            array_filter(
                parent::getWidgets(),
                fn (
                    string | WidgetConfiguration $widget
                ): bool =>
                    $widget !==
                    SchedulesCalendarWidget::class,
            )
        );
    }
}
