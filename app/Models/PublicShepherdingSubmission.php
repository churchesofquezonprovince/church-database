<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicShepherdingSubmission extends Model
{
    public const STATUS_PENDING =
        'pending';

    public const STATUS_APPROVED =
        'approved';

    public const STATUS_REJECTED =
        'rejected';

    protected $fillable = [
        'public_id',
        'submitted_by_name',
        'submitted_by_contact',
        'contact_date',
        'contact_time',
        'locality_id',
        'outcome',
        'contact_targets_text',
        'participant_names_text',
        'activity_type_ids',
        'ministry_lesson_ids',
        'bible_references',
        'morning_revival_text',
        'hymns_text',
        'notes',
        'status',
        'reviewed_by_id',
        'reviewed_at',
        'review_note',
        'shepherding_contact_id',
    ];

    protected $casts = [
        'contact_date' =>
            'date',

        'activity_type_ids' =>
            'array',

        'ministry_lesson_ids' =>
            'array',

        'bible_references' =>
            'array',

        'reviewed_at' =>
            'datetime',
    ];

    public function locality(): BelongsTo
    {
        return $this->belongsTo(
            Locality::class
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_id'
        );
    }

    public function shepherdingContact(): BelongsTo
    {
        return $this->belongsTo(
            ShepherdingContact::class
        );
    }
}
