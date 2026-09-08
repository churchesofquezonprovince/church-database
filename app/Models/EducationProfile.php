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
        'school_id',
        'workplace',
    ];

    protected static function booted(): void
    {
        static::saving(function (EducationProfile $profile): void {
            if (filled($profile->school_id)) {
                $school = School::query()->find(
                    $profile->school_id
                );

                if ($school) {
                    $profile->school_workplace = $school->name;
                }
            } elseif ($profile->isDirty('school_id')) {
                $profile->school_workplace = null;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
