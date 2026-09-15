<?php

namespace App\Models;

use App\Support\HymnLyricsNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HymnSource extends Model
{
    public const PROVIDER_SONGBASE =
        'songbase';

    public const PROVIDER_SOUNDCLOUD =
        'soundcloud';

    public const PROVIDER_YOUTUBE =
        'youtube';

    public const PROVIDER_HYMNAL_NET =
        'hymnal_net';

    public const PROVIDER_OTHER =
        'other';

    public const TYPE_CATALOG =
        'catalog';

    public const TYPE_AUDIO =
        'audio';

    public const TYPE_VIDEO =
        'video';

    public const TYPE_REFERENCE =
        'reference';

    public const LYRICS_FORMAT_PLAIN =
        'plain';

    public const LYRICS_FORMAT_CHORDED =
        'chorded';

    public const LYRICS_FORMAT_TRANSCRIPT =
        'transcript';

    public const LYRICS_FORMAT_UNKNOWN =
        'unknown';

    protected $fillable = [
        'hymn_id',
        'hymn_variant_id',
        'provider',
        'source_type',
        'external_id',
        'source_url',
        'label',
        'lyrics',
        'lyrics_search',
        'first_line_search',
        'lyrics_format',
        'lyrics_synced_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' =>
            'array',

        'lyrics_synced_at' =>
            'datetime',
    ];

    /*
     * Provider lyrics own their own normalized search
     * fields.
     *
     * Model-based writes therefore cannot accidentally
     * forget to update lyrics_search / first_line_search.
     *
     * Bulk upserts still populate these fields
     * explicitly because Eloquent mutators do not run
     * for query-builder upserts.
     */
    public function setLyricsAttribute(
        ?string $value
    ): void {
        $this->attributes['lyrics'] =
            $value;

        $this->attributes['lyrics_search'] =
            HymnLyricsNormalizer::forSearch(
                $value
            );

        $this->attributes[
            'first_line_search'
        ] =
            HymnLyricsNormalizer
                ::firstLineForSearch(
                    $value
                );
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            HymnVariant::class,
            'hymn_variant_id'
        );
    }

    public function hymn(): BelongsTo
    {
        return $this->belongsTo(
            Hymn::class
        );
    }
}
