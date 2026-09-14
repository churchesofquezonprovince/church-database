<?php

namespace App\Models;

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

    protected $fillable = [
        'hymn_id',
        'provider',
        'source_type',
        'external_id',
        'source_url',
        'label',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function hymn(): BelongsTo
    {
        return $this->belongsTo(
            Hymn::class
        );
    }
}
