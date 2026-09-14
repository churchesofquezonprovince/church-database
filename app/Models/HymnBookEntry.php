<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HymnBookEntry extends Model
{
    protected $fillable = [
        'hymn_book_id',
        'hymn_id',
        'number',
    ];

    public function hymnBook(): BelongsTo
    {
        return $this->belongsTo(
            HymnBook::class
        );
    }

    public function hymn(): BelongsTo
    {
        return $this->belongsTo(
            Hymn::class
        );
    }
}
