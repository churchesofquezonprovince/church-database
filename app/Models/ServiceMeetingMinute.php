<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceMeetingMinute extends Model
{
    protected $fillable = [
        'meeting_date',
        'attendees',
        'agenda',
        'decisions',
        'follow_up_items',
        'remarks',
        'created_by_id',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}