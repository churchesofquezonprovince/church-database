<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmichPersonMapping extends Model
{
    protected $fillable = [
        'person_id',
        'immich_person_id',
        'immich_name',
        'is_verified',
        'last_synced_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
