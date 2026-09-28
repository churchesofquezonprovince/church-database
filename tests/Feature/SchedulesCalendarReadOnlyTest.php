<?php

namespace Tests\Feature;

use App\Filament\Widgets\DashboardSchedulesCalendarWidget;
use App\Filament\Widgets\SchedulesCalendarWidget;
use ReflectionClass;
use Tests\TestCase;

class SchedulesCalendarReadOnlyTest extends TestCase
{
    public function test_dashboard_calendar_interactions_are_read_only(): void
    {
        $properties = (
            new ReflectionClass(
                DashboardSchedulesCalendarWidget::class
            )
        )->getDefaultProperties();

        $this->assertFalse(
            $properties['dateClickEnabled']
        );

        $this->assertFalse(
            $properties['dateSelectEnabled']
        );

        $this->assertFalse(
            $properties['eventClickEnabled']
        );

        $this->assertFalse(
            $properties['noEventsClickEnabled']
        );

        $this->assertFalse(
            $properties['eventDragEnabled']
        );

        $this->assertFalse(
            $properties['eventResizeEnabled']
        );

        $this->assertNull(
            $properties['defaultEventClickAction']
        );
    }

    public function test_posts_schedules_calendar_remains_editable(): void
    {
        $properties = (
            new ReflectionClass(
                SchedulesCalendarWidget::class
            )
        )->getDefaultProperties();

        $this->assertTrue(
            $properties['dateClickEnabled']
        );

        $this->assertTrue(
            $properties['dateSelectEnabled']
        );

        $this->assertTrue(
            $properties['eventClickEnabled']
        );

        $this->assertTrue(
            $properties['noEventsClickEnabled']
        );

        $this->assertSame(
            'editSchedule',
            $properties['defaultEventClickAction']
        );
    }
}
