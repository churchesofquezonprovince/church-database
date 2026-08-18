<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSessionImmichAlbum extends Model
{
    protected $fillable = [
        'attendance_session_id',
        'immich_album_id',
        'immich_album_name',
        'enabled',
        'last_modified_asset_at',
        'last_synced_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_modified_asset_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSession::class,
            'attendance_session_id',
        );
    }
}
