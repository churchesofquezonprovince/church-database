<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HymnAdditionRequest extends Model
{
    public const STATUS_PENDING =
        'pending';

    public const STATUS_APPROVED =
        'approved';

    public const STATUS_REJECTED =
        'rejected';

    protected $fillable = [
        'requested_by_id',
        'title',
        'language',
        'lyrics',
        'source_url',
        'book_name',
        'hymn_number',
        'status',
        'reviewed_by_id',
        'reviewed_at',
        'review_note',
        'created_hymn_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by_id'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_id'
        );
    }

    public function createdHymn(): BelongsTo
    {
        return $this->belongsTo(
            Hymn::class,
            'created_hymn_id'
        );
    }
}
