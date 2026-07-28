<?php

namespace App\Models;

use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model implements Eventable
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'is_all_day',
        'location',
        'locality',
        'category',
        'source',
        'google_calendar_id',
        'google_event_id',
        'google_etag',
        'synced_at',
        'created_by',
        'google_sync_status',
        'google_sync_error',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function toCalendarEvent(): CalendarEvent
    {
        $event = CalendarEvent::make($this)
            ->title($this->title)
            ->start($this->starts_at)
            ->end($this->ends_at ?? $this->starts_at?->copy()->addHour())
            ->backgroundColor($this->calendarColor())
            ->textColor($this->calendarTextColor())
            ->action('edit');

        if ($this->is_all_day) {
            $event->allDay();
        }

        return $event;
    }


    public function calendarColor(): string
    {
        $calendarId = trim((string) $this->google_calendar_id);

        foreach ((array) config('services.google_calendar.calendars', []) as $calendar) {
            if (
                $calendarId !== ''
                && trim((string) ($calendar['id'] ?? '')) === $calendarId
            ) {
                return (string) ($calendar['color'] ?? '#3b82f6');
            }
        }

        return '#3b82f6';
    }

    public function calendarTextColor(): string
    {
        $hex = ltrim($this->calendarColor(), '#');

        if (strlen($hex) !== 6) {
            return '#ffffff';
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $brightness > 150 ? '#111827' : '#ffffff';
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
