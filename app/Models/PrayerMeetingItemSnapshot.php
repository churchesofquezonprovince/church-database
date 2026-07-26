<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerMeetingItemSnapshot extends Model
{
    protected $fillable = [
        'prayer_meeting_item_id',
        'locality',
        'title',
        'meeting_date',
        'meeting_schedule_snapshot',
        'content_json',
        'created_by_name',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'content_json' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PrayerMeetingItem::class, 'prayer_meeting_item_id');
    }
}
