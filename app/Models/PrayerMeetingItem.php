<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrayerMeetingItem extends Model
{
    protected $fillable = [
        'attendance_sheet_id',
        'locality',
        'title',
        'meeting_date',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function attendanceSheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PrayerMeetingItemLine::class);
    }
}
