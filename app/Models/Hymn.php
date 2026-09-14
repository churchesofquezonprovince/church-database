<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hymn extends Model
{
    protected $fillable = [
        'source',
        'source_id',
        'title',
        'language',
        'source_url',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function bookEntries(): HasMany
    {
        return $this->hasMany(
            HymnBookEntry::class
        );
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(
            HymnBook::class,
            'hymn_book_entries'
        )
            ->withPivot('number')
            ->withTimestamps();
    }
}
