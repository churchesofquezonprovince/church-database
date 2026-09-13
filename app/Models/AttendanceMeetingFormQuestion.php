<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceMeetingFormQuestion extends Model
{
    public const TYPE_SHORT_ANSWER = 'short_answer';

    public const TYPE_PARAGRAPH = 'paragraph';

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_CHECKBOXES = 'checkboxes';

    public const TYPE_DROPDOWN = 'dropdown';

    protected $fillable = [
        'attendance_sheet_id',
        'question_type',
        'question_text',
        'description',
        'options',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSheet::class,
            'attendance_sheet_id'
        );
    }

    public function answers(): HasMany
    {
        return $this->hasMany(
            AttendanceMeetingFormAnswer::class,
            'attendance_meeting_form_question_id'
        );
    }

    public function typeLabel(): string
    {
        return match ($this->question_type) {
            self::TYPE_SHORT_ANSWER =>
                'Short Answer',

            self::TYPE_PARAGRAPH =>
                'Paragraph',

            self::TYPE_MULTIPLE_CHOICE =>
                'Multiple Choice',

            self::TYPE_CHECKBOXES =>
                'Checkboxes',

            self::TYPE_DROPDOWN =>
                'Dropdown',

            default =>
                'Unknown',
        };
    }

    public function requiresOptions(): bool
    {
        return in_array(
            $this->question_type,
            [
                self::TYPE_MULTIPLE_CHOICE,
                self::TYPE_CHECKBOXES,
                self::TYPE_DROPDOWN,
            ],
            true
        );
    }
}
