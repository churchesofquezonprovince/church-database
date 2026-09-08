<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImmichPersonCache extends Model
{
    protected $fillable = [
        'immich_person_id',
        'immich_name',
        'immich_updated_at',
        'thumbnail_path',
        'thumbnail_synced_at',
    ];

    protected $casts = [
        'immich_updated_at' => 'datetime',
        'thumbnail_synced_at' => 'datetime',
    ];
}
