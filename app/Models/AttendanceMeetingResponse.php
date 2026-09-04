<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMeetingResponse extends Model
{
    public const RESPONSE_YES = 'yes';

    public const RESPONSE_NO = 'no';

    public const RESPONDENT_PERSON = 'person';

    public const RESPONDENT_CAMPUS = 'campus';

    public const RESPONDENT_GUEST = 'guest';

    protected $fillable = [
        'attendance_session_id',
        'respondent_type',
        'person_id',
        'campus_contact_id',
        'guest_name',
        'respondent_name',
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

    public function campusContact(): BelongsTo
    {
        return $this->belongsTo(
            CampusContact::class,
            'campus_contact_id',
        );
    }

    public function isAttending(): bool
    {
        return $this->response === self::RESPONSE_YES;
    }

    public function sourceLabel(): string
    {
        return match ($this->respondent_type) {
            self::RESPONDENT_PERSON =>
                'People Database',

            self::RESPONDENT_CAMPUS =>
                'Campus Database',

            self::RESPONDENT_GUEST =>
                'Guest',

            default =>
                'Unknown',
        };
    }
}