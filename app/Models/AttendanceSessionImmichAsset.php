<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSessionImmichAsset extends Model
{
    protected $fillable = [
        'attendance_session_id',
        'immich_asset_id',
        'immich_asset_name',
        'asset_taken_at',
    ];

    protected $casts = [
        'asset_taken_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSession::class,
            'attendance_session_id',
        );
    }
}
