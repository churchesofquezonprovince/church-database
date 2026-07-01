<?php

namespace App\Models;

use App\Support\ChurchProfileOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Person extends Model
{
use HasFactory;
    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    protected $table = 'persons';

    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'suffix',

        'sex',

        'nickname',

        'birthdate',
        'birthplace',

        'household_id',
        'spouse_id',

        'locality',
        'permanent_address',
        'home_address',
        'geocoordinates',

        'email',
        'contact_number',

        'emergency_contact_id',
        'emergency_contact_relationship',
        'emergency_contact_number',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];

    protected $appends = [
        'display_name',
        'full_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Household this person belongs to.
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'household_id');
    }

    /**
     * Person's spouse.
     */
    public function spouse(): BelongsTo
    {
        return $this->belongsTo(self::class, 'spouse_id');
    }

    /**
     * Emergency contact.
     */
    public function emergencyContact(): BelongsTo
    {
        return $this->belongsTo(self::class, 'emergency_contact_id');
    }

    /**
     * Church profile.
     */
    public function churchProfile(): HasOne
    {
        return $this->hasOne(ChurchProfile::class, 'person_id');
    }

    /**
     * Education profile.
     */
    public function educationProfile(): HasOne
    {
        return $this->hasOne(EducationProfile::class, 'person_id');
    }

    /**
     * Parent relationships.
     */
    public function parentRelationships(): HasMany
    {
        return $this->hasMany(ParentRelationship::class, 'person_id');
    }

    /**
     * Households headed by this person.
     */
    public function householdsHeaded(): HasMany
    {
        return $this->hasMany(Household::class, 'household_head_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Computed Attributes
    |--------------------------------------------------------------------------
    */

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {

                $parts = [];

                if (! empty($this->lastname)) {
                    $parts[] = $this->lastname . ',';
                }

                if (! empty($this->firstname)) {
                    $parts[] = $this->firstname;
                }

                if (! empty($this->middlename)) {
                    $parts[] = $this->middlename;
                }

                if (! empty($this->suffix)) {
                    $parts[] = $this->suffix;
                }

                return trim(implode(' ', $parts));
            }
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->display_name,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isMale(): bool
    {
        return $this->sex === 'Male';
    }

    public function isFemale(): bool
    {
        return $this->sex === 'Female';
    }

    public function hasSpouse(): bool
    {
        return ! is_null($this->spouse_id);
    }

    public function isMarried(): bool
    {
        return $this->hasSpouse();
    }

    public function hasChurchProfile(): bool
    {
        return $this->churchProfile()->exists();
    }

    public function hasEducationProfile(): bool
    {
        return $this->educationProfile()->exists();
    }

    public function hasEmergencyContact(): bool
    {
        return ! is_null($this->emergency_contact_id);
    }

    public function headsHousehold(): bool
    {
        return $this->householdsHeaded()->exists();
    }

public function hasHousehold(): bool
{
    return ! is_null($this->household_id);
}

    public function fullAddress(): string
    {
        return $this->home_address
            ?: $this->permanent_address
            ?: '';
    }

public function initials(): string
{
    return collect([
        $this->firstname,
        $this->middlename,
        $this->lastname,
    ])
        ->filter()
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
}

protected static function booted(): void
{
    static::saved(function (Person $person): void {
        $person->syncChurchProfile();
    });
}

public function syncChurchProfile(): void
{
    if (! $this->exists) {
        return;
    }

    $profile = $this->churchProfile()->firstOrNew([]);

    $profile->category = ChurchProfileOptions::categoryFromBirthdate($this->birthdate);

    if (blank($profile->status)) {
        $profile->status = 'Unknown';
    }

    $profile->save();
}


}
