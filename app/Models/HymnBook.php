<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HymnBook extends Model
{
    protected $fillable = [
        'source',
        'source_id',
        'name',
        'slug',
        'language',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(
            HymnBookEntry::class
        );
    }

    public function hymns(): BelongsToMany
    {
        return $this->belongsToMany(
            Hymn::class,
            'hymn_book_entries'
        )
            ->withPivot('number')
            ->withTimestamps();
    }
}
