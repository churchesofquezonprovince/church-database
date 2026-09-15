<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HymnalNetEntry extends Model
{
    protected $fillable = [
        'collection_code',
        'section_code',
        'number',
        'title',
        'source_url',
        'fetch_status',
        'http_status',
        'validation_status',
        'validation_note',
        'matched_hymn_id',
        'matched_hymn_variant_id',
        'match_status',
        'match_method',
        'match_score',
        'last_fetched_at',
    ];

    protected $casts = [
        'last_fetched_at' => 'datetime',
    ];

    public function matchedVariant(): BelongsTo
    {
        return $this->belongsTo(
            HymnVariant::class,
            'matched_hymn_variant_id'
        );
    }

    public function matchedHymn(): BelongsTo
    {
        return $this->belongsTo(
            Hymn::class,
            'matched_hymn_id'
        );
    }
}
