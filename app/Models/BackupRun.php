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
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'local_size_bytes' => 'integer',
        'external_size_bytes' => 'integer',
    ];
}
