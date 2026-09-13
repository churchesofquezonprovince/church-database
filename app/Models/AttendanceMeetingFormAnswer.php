<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMeetingFormAnswer extends Model
{
    protected $fillable = [
        'attendance_meeting_response_id',
        'attendance_meeting_form_question_id',
        'answer_text',
        'answer_json',
    ];

    protected $casts = [
        'answer_json' => 'array',
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
}
