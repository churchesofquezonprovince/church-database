<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampusWorkStudentCenter extends Model
{
    protected $fillable = [
        'name',
        'school_campus',
        'school_id',
        'locality',
        'locality_id',
        'place',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (CampusWorkStudentCenter $center): void {
            if (filled($center->locality_id)) {
                $locality = Locality::query()->find($center->locality_id);

                if ($locality) {
                    $center->locality = $locality->name;
                    $center->name = 'Student Center - ' . $locality->name;
                }
            }

            if (blank($center->school_id)) {
                $center->school_campus = null;
            } else {
                $school = School::query()->find($center->school_id);

                if ($school) {
                    $center->school_campus = $school->name;
                }
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function localityRecord(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'locality_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CampusWorkStudentCenterMember::class);
    }
}
