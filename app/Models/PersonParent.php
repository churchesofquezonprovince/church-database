<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonParent extends Model
{
    protected $table = 'person_parents';

    public $timestamps = false;

    protected $guarded = [];

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

public function parent()
{
    return $this->belongsTo(Person::class, 'parent_id');
}

}
