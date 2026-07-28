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
            ->action('view');

        if ($this->is_all_day) {
            $event->allDay();
        }

        return $event;
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
