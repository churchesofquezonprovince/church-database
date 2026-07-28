<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SchedulesCalendarWidget extends CalendarWidget
{
    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return Schedule::query()
            ->where(function (Builder $query) use ($info): void {
                $query
                    ->whereBetween('starts_at', [$info->start, $info->end])
                    ->orWhereBetween('ends_at', [$info->start, $info->end])
                    ->orWhere(function (Builder $query) use ($info): void {
                        $query
                            ->where('starts_at', '<=', $info->start)
                            ->where(function (Builder $query) use ($info): void {
                                $query
                                    ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $info->end);
                            });
                    });
            });
    }
}
