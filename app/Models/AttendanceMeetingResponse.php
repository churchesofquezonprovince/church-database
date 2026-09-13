<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceMeetingResponse extends Model
{
    public const RESPONSE_YES = 'yes';

    public const RESPONSE_NO = 'no';

    public const RESPONDENT_PERSON = 'person';

    public const RESPONDENT_CAMPUS = 'campus';

    public const RESPONDENT_GOSPEL = 'gospel';

    public const RESPONDENT_GUEST = 'guest';

protected $fillable = [
    'attendance_session_id',
    'respondent_type',
    'original_source',
    'person_id',
    'campus_contact_id',
    'gospel_contact_id',
    'guest_name',
    'guest_profile',
    'respondent_name',
    'response',
    'responded_at',
];

protected $casts = [
    'responded_at' => 'datetime',
    'guest_profile' => 'array',
];

public function originalSourceLabel(): string
{
    return match ($this->original_source) {
        self::RESPONDENT_PERSON =>
            'People Database',

        self::RESPONDENT_CAMPUS =>
            'Campus Database',

        self::RESPONDENT_GOSPEL =>
            'Gospel Contacts',

        self::RESPONDENT_GUEST =>
            'Guest',

        default =>
            'Unknown',
    };
}

    public function formAnswers(): HasMany
    {
        return $this->hasMany(
            AttendanceMeetingFormAnswer::class,
            'attendance_meeting_response_id'
        );
    }

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

    public function gospelContact(): BelongsTo
    {
        return $this->belongsTo(
            GospelContact::class,
            'gospel_contact_id',
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

            self::RESPONDENT_GOSPEL =>
                'Gospel Contacts',

            self::RESPONDENT_GUEST =>
                'Guest',

            default =>
                'Unknown',
        };
    }
}