<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonEducation extends Model
{
    protected $table = 'person_education';

    public $timestamps = false;

    protected $guarded = [];

    protected $fillable = [
        'person_id',
        'grade_level',
        'course_strand',
        'occupation',
        'school_workplace',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
