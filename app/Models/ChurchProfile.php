<?php

namespace App\Models;

use App\Support\ChurchProfileOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchProfile extends Model
{
    use HasFactory;

    protected $table = 'church_profiles';

    protected $fillable = [
        'person_id',
        'category',
        'baptism_date',
        'baptism_year',
        'baptism_month',
        'baptism_day',
        'shepherd_id',
        'introduced_by_id',
        'service',
        'status',
    ];

    protected $casts = [
        'baptism_date' => 'date',
        'baptism_year' => 'integer',
        'baptism_month' => 'integer',
        'baptism_day' => 'integer',
        'service' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (ChurchProfile $profile): void {
            $person = $profile->person;

            if (! $person && $profile->person_id) {
                $person = Person::find($profile->person_id);
            }

            $profile->category = ChurchProfileOptions::categoryFromBirthdate(
                $person?->birthdate
            );

            if (blank($profile->status)) {
                $profile->status = 'Unknown';
            }

            if (filled($profile->baptism_year)) {
                $year = (int) $profile->baptism_year;
                $month = filled($profile->baptism_month) ? (int) $profile->baptism_month : 1;
                $day = filled($profile->baptism_day) ? (int) $profile->baptism_day : 1;

                $month = max(1, min(12, $month));
                $lastDay = \Carbon\CarbonImmutable::create($year, $month, 1)->daysInMonth;
                $day = max(1, min($lastDay, $day));

                $profile->baptism_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            } else {
                $profile->baptism_date = null;
                $profile->baptism_month = null;
                $profile->baptism_day = null;
            }
        });
    }

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
