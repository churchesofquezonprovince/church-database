<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceMeetingSeries extends Model
{
    protected $table =
        'attendance_meeting_series';

    protected $fillable = [
        'name',
        'public_slug',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sheets(): HasMany
    {
        return $this->hasMany(
            AttendanceSheet::class,
            'attendance_meeting_series_id'
        );
    }

    public function activeSheets(): HasMany
    {
        return $this
            ->sheets()
            ->where(
                'is_active',
                true
            );
    }

    public function publicUrl(): string
    {
        /*
         * Canonical / normal public URL.
         */
        return secure_url(
            '/meeting/'
            . $this->public_slug
        );
    }

    public function shortPublicUrl(): string
    {
        /*
         * Compact shareable URL.
         */
        return 'https://m.overcomers.win/'
            . $this->public_slug;
    }
}
