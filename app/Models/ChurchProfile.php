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
        'shepherd_id',
        'introduced_by_id',
        'service',
        'status',
    ];

    protected $casts = [
        'baptism_date' => 'date',
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
