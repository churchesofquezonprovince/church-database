<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LATE = 'late';

    public const STATUS_EXCUSED = 'excused';

    protected $fillable = [
        'attendance_session_id',
        'person_id',
        'status',
        'prophesied',
        'is_present',
        'attendance_source',
        'remarks',
        'marked_by_id',
        'marked_at',
    ];

    protected $casts = [
        'prophesied' => 'boolean',
        'is_present' => 'boolean',
        'marked_at' => 'datetime',
    ];

    public const SOURCE_MANUAL = 'manual';
    
    public const SOURCE_IMMICH = 'immich';

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_id');
    }
}
