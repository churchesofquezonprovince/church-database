<?php

namespace Tests\Feature;

use App\Filament\Widgets\SchedulesCalendarWidget;
use ReflectionClass;
use Tests\TestCase;

class SchedulesCalendarReadOnlyTest extends TestCase
{
    public function test_schedule_calendar_interactions_are_read_only(): void
    {
        $properties = (
            new ReflectionClass(
                SchedulesCalendarWidget::class
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
}
