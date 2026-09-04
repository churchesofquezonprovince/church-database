<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMeetingResponse extends Model
{
    public const RESPONSE_YES = 'yes';

    public const RESPONSE_NO = 'no';

    protected $fillable = [
        'attendance_session_id',
        'person_id',
        'response',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSession::class,
            'attendance_session_id',
        );
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(
            Person::class
        );
    }

    public function isAttending(): bool
    {
        return $this->response === self::RESPONSE_YES;
    }
}
