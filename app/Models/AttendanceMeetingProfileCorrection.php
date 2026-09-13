<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMeetingProfileCorrection extends Model
{
    public const TYPE_CORRECTION =
        'correction';

    public const TYPE_PROFILE_COMPLETION =
        'profile_completion';

    public const STATUS_PENDING =
        'pending';

    public const STATUS_APPROVED =
        'approved';

    public const STATUS_REJECTED =
        'rejected';

    protected $fillable = [
        'attendance_meeting_response_id',
        'attendance_meeting_form_question_id',
        'person_id',
        'campus_contact_id',
        'gospel_contact_id',
        'database_field',
        'field_owner',
        'change_type',
        'original_value_text',
        'original_value_json',
        'proposed_value_text',
        'proposed_value_json',
        'status',
        'reviewed_by_id',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'original_value_json' => 'array',
        'proposed_value_json' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceMeetingResponse::class,
            'attendance_meeting_response_id'
        );
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceMeetingFormQuestion::class,
            'attendance_meeting_form_question_id'
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
            CampusContact::class
        );
    }

    public function gospelContact(): BelongsTo
    {
        return $this->belongsTo(
            GospelContact::class
        );
    }

    public function changeTypeLabel(): string
    {
        return match ($this->change_type) {
            self::TYPE_CORRECTION =>
                'Correction',

            self::TYPE_PROFILE_COMPLETION =>
                'Profile Completion',

            default =>
                'Database Update',
        };
    }
}
