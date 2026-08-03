<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRun extends Model
{
    protected $fillable = [
        'filename',
        'status',
        'database_name',
        'local_path',
        'local_size_bytes',
        'external_path',
        'external_status',
        'external_size_bytes',
        'google_drive_status',
        'google_drive_file_id',
        'google_drive_path',
        'google_drive_uploaded_at',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'google_drive_uploaded_at' => 'datetime',
        'local_size_bytes' => 'integer',
        'external_size_bytes' => 'integer',
    ];
}
