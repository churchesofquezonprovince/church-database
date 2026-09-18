<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function shepherdingContacts(): BelongsToMany
    {
        return $this->belongsToMany(
            ShepherdingContact::class,
            'shepherding_contact_hymn_requests'
        )
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function createdHymn(): BelongsTo
    {
        return $this->belongsTo(
            Hymn::class,
            'created_hymn_id'
        );
    }
}
