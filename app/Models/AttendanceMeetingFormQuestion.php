<?php

namespace App\Models;

use App\Support\MeetingFormDatabaseFieldRegistry;
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

    public const TYPE_DATABASE_FIELD = 'database_field';

    public const TYPE_NOTICE = 'notice';

    public const DATABASE_FIELD_BIRTHDATE = 'birthdate';

    public const DATABASE_FIELD_LOCALITY = 'locality';

    protected $fillable = [
        'attendance_sheet_id',
        'question_type',
        'database_field',
        'question_text',
        'description',
        'options',
        'is_required',
        'allow_correction',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'allow_correction' => 'boolean',
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

            self::TYPE_DATABASE_FIELD =>
                'Database Field',

            self::TYPE_NOTICE =>
                'Notice',

            default =>
                'Unknown',
        };
    }

    public function isDatabaseField(): bool
    {
        return $this->question_type
            === self::TYPE_DATABASE_FIELD;
    }

    public function isNotice(): bool
    {
        return $this->question_type
            === self::TYPE_NOTICE;
    }

    public function databaseFieldLabel(): ?string
    {
        return MeetingFormDatabaseFieldRegistry::label(
            $this->database_field
        );
    }

    public function databaseFieldDefinition(): ?array
    {
        return MeetingFormDatabaseFieldRegistry::definition(
            $this->database_field
        );
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
