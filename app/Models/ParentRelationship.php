<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentRelationship extends Model
{
    protected $table = 'parent_relationships';

    protected $fillable = [
        'person_id',
        'parent_id',
        'parent_name',
        'relationship',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'parent_id');
    }
}
