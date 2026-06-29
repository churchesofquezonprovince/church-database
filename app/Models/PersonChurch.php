<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonChurch extends Model
{
    protected $table = 'person_church';

    public $timestamps = false;

    protected $guarded = [];

    protected $fillable = [
        'person_id',
        'category',
        'baptism_date',
        'shepherd_id',
        'introduced_by_id',
        'service',
        'status',
    ];

//    public function person()
//    {
//        return $this->belongsTo(Person::class);
//    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function shepherd(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'shepherd_id');
    }

    public function introducedBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'introduced_by_id');
    }
}
