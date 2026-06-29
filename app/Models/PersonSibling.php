<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonSibling extends Model
{
    protected $table = 'person_siblings';

    public $timestamps = false;

    protected $guarded = [];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function sibling(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'sibling_id');
    }
}
