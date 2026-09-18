<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MorningRevivalWeek extends Model
{
    protected $fillable = [
        'morning_revival_publication_id',
        'week_number',
        'title',
        'start_date',
        'is_active',
    ];

    protected $casts = [
        'week_number' =>
            'integer',

        'start_date' =>
            'date',

        'is_active' =>
            'boolean',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(
            MorningRevivalPublication::class,
            'morning_revival_publication_id'
        );
    }

    /*
     * Returns Day 1-6 when the supplied date belongs
     * to this Morning Revival week.
     *
     * Sunday returns null because Lord's Day is not
     * represented in the Morning Revival schedule.
     */
    public function dayNumberForDate(
        CarbonInterface $date
    ): ?int {
        $days =
            $this->start_date
                ->startOfDay()
                ->diffInDays(
                    $date->copy()->startOfDay(),
                    false
                );

        if (
            $days < 0
            || $days > 5
        ) {
            return null;
        }

        return $days + 1;
    }

    public function dateForDay(
        int $day
    ): ?CarbonInterface {
        if (
            $day < 1
            || $day > 6
        ) {
            return null;
        }

        return $this->start_date
            ->copy()
            ->addDays(
                $day - 1
            );
    }

    public function displayLabel(): string
    {
        return 'Week '
            . $this->week_number
            . ': '
            . $this->title;
    }
}
