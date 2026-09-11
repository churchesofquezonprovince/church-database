<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Restrict participant periods to rows that cover a meeting date.
     *
     * AttendanceParticipant represents roster membership.
     * AttendanceRecord remains the historical attendance fact.
     */
    public function scopeActiveOn(
        Builder $query,
        string $date
    ): Builder {
        return $query
            ->where('is_active', true)
            ->where(
                function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('starts_on')
                        ->orWhereDate(
                            'starts_on',
                            '<=',
                            $date
                        );
                }
            )
            ->where(
                function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('ends_on')
                        ->orWhereDate(
                            'ends_on',
                            '>=',
                            $date
                        );
                }
            );
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
