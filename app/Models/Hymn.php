<?php

namespace App\Models;

use App\Support\HymnLyricsNormalizer;
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
        'lyrics',
        'lyrics_search',
        'source_url',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function setLyricsAttribute(
        ?string $value
    ): void {
        $this->attributes['lyrics'] =
            $value;

        $this->attributes['lyrics_search'] =
            HymnLyricsNormalizer::forSearch(
                $value
            );
    }

    public function variants(): HasMany
    {
        return $this->hasMany(
            HymnVariant::class
        )
            ->orderBy('sort_order')
            ->orderBy('variant_index');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(
            HymnSource::class
        );
    }

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
