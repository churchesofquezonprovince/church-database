<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HymnVariant extends Model
{
    protected $fillable = [
        'hymn_id',
        'source',
        'source_id',
        'variant_type',
        'variant_index',
        'label',
        'title_override',
        'lyrics',
        'lyrics_search',
        'metadata',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function hymn(): BelongsTo
    {
        return $this->belongsTo(
            Hymn::class
        );
    }

    public function sources(): HasMany
    {
        return $this->hasMany(
            HymnSource::class
        );
    }
}
