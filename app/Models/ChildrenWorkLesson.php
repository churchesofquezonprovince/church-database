<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChildrenWorkLesson extends Model
{
    protected $fillable = [
        'scheduled_on',
        'lesson_code',
        'lesson_title',
        'lesson_url',
        'suggested_hymn',
        'suggested_hymn_url',
        'memory_verse',
        'story',
        'story_url',
        'presentation_slides',
        'presentation_slides_url',
        'activity',
        'activity_url',
        'assigned_to',
        'notes',
        'status',
        'source',
        'google_sheet_row_number',
        'google_sheet_row_hash',
        'google_sheet_synced_at',
        'sync_status',
        'sync_error',
    ];

    protected $casts = [
        'scheduled_on' => 'date',
        'google_sheet_synced_at' => 'datetime',
    ];

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->whereNotNull('scheduled_on')
            ->whereDate('scheduled_on', '>=', today())
            ->orderBy('scheduled_on');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query
            ->whereNotNull('scheduled_on')
            ->whereDate('scheduled_on', '<', today())
            ->orderByDesc('scheduled_on');
    }

    public function displayTitle(): string
    {
        return filled($this->lesson_title)
            ? (string) $this->lesson_title
            : 'Untitled Children\'s Work Lesson';
    }

    public function displayDate(): string
    {
        return $this->scheduled_on
            ? $this->scheduled_on->format('M d, Y')
            : 'No date';
    }
}
