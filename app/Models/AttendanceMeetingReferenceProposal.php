<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMeetingReferenceProposal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'attendance_meeting_response_id',
        'attendance_meeting_form_question_id',
        'person_id',
        'campus_contact_id',
        'gospel_contact_id',
        'database_field',
        'proposed_label',
        'proposed_province_id',
        'proposed_province_name',
        'proposed_city_municipality',
        'status',
        'resolved_reference_type',
        'resolved_reference_id',
        'reviewed_by_id',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
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

    public function proposedProvince(): BelongsTo
    {
        return $this->belongsTo(
            Province::class,
            'proposed_province_id'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_id'
        );
    }

    public function fieldLabel(): string
    {
        return match ($this->database_field) {
            'school' =>
                'School / Campus',

            'locality' =>
                'Locality',

            default =>
                $this->database_field,
        };
    }
}
