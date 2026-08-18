<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceImmichAssetDetection extends Model
{
    protected $fillable = [
        'attendance_session_id',
        'immich_asset_id',
        'immich_person_id',
        'person_id',
        'asset_taken_at',
        'detected_at',
    ];

    protected $casts = [
        'asset_taken_at' => 'datetime',
        'detected_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSession::class,
            'attendance_session_id',
        );
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
