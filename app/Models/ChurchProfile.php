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
        'contact_origin',
        'first_contact_date',
        'contact_origin_details',
        'service',
        'status',
    ];

    protected $casts = [
        'baptism_date' => 'date',
        'baptism_year' => 'integer',
        'baptism_month' => 'integer',
        'baptism_day' => 'integer',
        'first_contact_date' => 'date',
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

            $hasBaptismYear =
                filled($profile->baptism_year);

            $hasBaptismMonth =
                filled($profile->baptism_month);

            $hasBaptismDay =
                filled($profile->baptism_day);

            /*
             * Preserve partial baptism information exactly.
             *
             * Year, month, and day are independent optional
             * values. Never invent missing portions of the date.
             *
             * baptism_date is maintained only when all three
             * components are known.
             */
            if (
                $hasBaptismYear
                && $hasBaptismMonth
                && $hasBaptismDay
            ) {
                $year =
                    (int) $profile->baptism_year;

                $month =
                    (int) $profile->baptism_month;

                $day =
                    (int) $profile->baptism_day;

                if (
                    ! checkdate(
                        $month,
                        $day,
                        $year
                    )
                ) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'baptism_day' =>
                            'The baptism year, month, and day do not form a valid date.',
                    ]);
                }

                $profile->baptism_date =
                    sprintf(
                        '%04d-%02d-%02d',
                        $year,
                        $month,
                        $day
                    );
            } else {
                $profile->baptism_date = null;
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
