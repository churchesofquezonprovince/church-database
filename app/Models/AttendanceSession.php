<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $fillable = [
        'attendance_sheet_id',
        'session_date',
        'session_time',
        'title',
        'public_slug',
        'remarks',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function meetingResponses(): HasMany
{
    return $this->hasMany(
        AttendanceMeetingResponse::class,
        'attendance_session_id',
    );
}

public function immichAssets(): HasMany
{
    return $this->hasMany(
        AttendanceSessionImmichAsset::class,
        'attendance_session_id',
    );
}

public function immichDetections(): HasMany
{
    return $this->hasMany(
        AttendanceImmichAssetDetection::class,
        'attendance_session_id',
    );
}

    public function sessionTimeLabel(): ?string
    {
        $time = $this->session_time ?: $this->sheet?->meeting_time;

        if (blank($time)) {
            return null;
        }

        return CarbonImmutable::parse((string) $time)->format('g:i A');
    }

    public function dateTimeLabel(string $dateFormat = 'M d, Y'): string
    {
        $date = $this->session_date?->format($dateFormat) ?? 'No date';
        $time = $this->sessionTimeLabel();

        return $time ? $date . ' · ' . $time : $date;
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function campusActivity(): HasOne
    {
        return $this->hasOne(
            CampusWorkActivity::class,
            'attendance_session_id'
        );
    }

public function publicMeetingUrl(): ?string
{
    if (blank($this->public_slug)) {
        return null;
    }

    return secure_url(
        '/meeting/' . $this->public_slug
    );
}

}
