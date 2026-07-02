<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSheet extends Model
{
    public const TYPE_CUSTOM = 'custom';

    public const TYPE_LORDS_TABLE = 'lords_table';

    protected $fillable = [
        'title',
        'sheet_type',
        'locality',
        'meeting_day',
        'start_date',
        'end_date',
        'is_active',
        'remarks',
        'created_by_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AttendanceParticipant::class);
    }

    public function records(): HasMany
    {
        return $this->hasManyThrough(AttendanceRecord::class, AttendanceSession::class);
    }

    public function isLordsTable(): bool
    {
        return $this->sheet_type === self::TYPE_LORDS_TABLE;
    }
}
