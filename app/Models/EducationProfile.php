<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationProfile extends Model
{
use HasFactory;
    protected $table = 'education_profiles';

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
