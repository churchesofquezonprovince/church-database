<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\AttendanceSheetImmichAlbum;

class AttendanceSheet extends Model
{
    public const TYPE_CUSTOM = 'custom';

    public const TYPE_LORDS_TABLE = 'lords_table';

    public const TYPE_PRAYER_MEETING = 'prayer_meeting';

    protected $fillable = [
        'title',
        'sheet_type',
        'locality',
        'meeting_day',
        'meeting_time',
        'is_one_time',
        'start_date',
        'end_date',
        'is_active',
        'remarks',
        'created_by_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_one_time' => 'boolean',
        'is_active' => 'boolean',
    ];

public function immichAlbum(): HasOne
{
    return $this->hasOne(
        AttendanceSheetImmichAlbum::class,
        'attendance_sheet_id',
    );
}

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }


    public function meetingTimeLabel(): string
    {
        if (blank($this->meeting_time)) {
            return 'No time set';
        }

        return CarbonImmutable::parse((string) $this->meeting_time)->format('g:i A');
    }

    public function attendanceModeLabel(): string
    {
        return $this->is_one_time ? 'One-time' : 'Recurring';
    }


    public function dateRangeLabel(): string
    {
        if ($this->is_one_time) {
            return $this->start_date?->format('M d, Y') ?? 'No date';
        }

        $start = $this->start_date?->format('M d, Y') ?? 'No start date';
        $end = $this->end_date?->format('M d, Y') ?? 'No end date';

        return $start . ' to ' . $end;
    }

    public function attendanceModeBadgeClass(): string
    {
        return $this->is_one_time
            ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100'
            : 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-100';
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AttendanceParticipant::class);
    }

    public function records(): HasMany
    {
        return $this->hasManyThrough(AttendanceRecord::class, AttendanceSession::class);
    }

    public function isLordsTable(): bool
    {
        return $this->sheet_type === self::TYPE_LORDS_TABLE;
    }
}
