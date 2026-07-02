<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceParticipant extends Model
{
    protected $fillable = [
        'attendance_sheet_id',
        'person_id',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
