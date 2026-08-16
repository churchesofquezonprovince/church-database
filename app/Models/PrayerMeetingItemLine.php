<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerMeetingItemLine extends Model
{
    public const TYPE_ROMAN = 'roman';
    public const TYPE_LETTER = 'letter';
    public const TYPE_NUMBER = 'number';
    public const TYPE_LOWER_ROMAN = 'lower_roman';
    public const TYPE_BULLET = 'bullet';
    public const TYPE_PLAIN = 'plain';

    protected $fillable = [
        'prayer_meeting_item_id',
        'line_type',
        'marker',
        'content',
        'sort_order',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PrayerMeetingItem::class, 'prayer_meeting_item_id');
    }
}
