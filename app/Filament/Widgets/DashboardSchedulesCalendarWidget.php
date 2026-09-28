<?php

namespace App\Filament\Widgets;

/**
 * Read-only schedule calendar for the Church Database Dashboard.
 *
 * The editable calendar remains SchedulesCalendarWidget and is
 * used directly by Posts -> Schedules.
 */
class DashboardSchedulesCalendarWidget extends SchedulesCalendarWidget
{
    public static function canView(): bool
    {
        return true;
    }

    protected bool $dateClickEnabled = false;

    protected bool $dateSelectEnabled = false;

    protected bool $eventClickEnabled = false;

    protected bool $noEventsClickEnabled = false;

    protected bool $eventDragEnabled = false;

    protected bool $eventResizeEnabled = false;

    protected ?string $defaultEventClickAction = null;
}
