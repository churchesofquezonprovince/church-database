<?php

namespace App\Models;

use App\Support\ChurchProfileOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Person extends Model
{
    use HasFactory;

    protected static bool $syncingSpouse = false;

    protected static array $previousSpouseIds = [];

    /**
     * Temporarily bypass only the exact-person duplicate guard.
     *
     * All other Person validation remains active.
     */
    protected static bool $allowExactDuplicateCreation = false;

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
        'locality_id',
        'permanent_address',
        'home_address',
        'geocoordinates',
        'email',
        'facebook_account',
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

    protected static function booted(): void
    {
        static::saving(function (Person $person): void {
            if (filled($person->locality_id)) {
                $locality = Locality::query()->find($person->locality_id);

                if ($locality) {
                    $person->locality = $locality->name;
                }
            }

            $person->validateBeforeSave();
        });

        static::updating(function (Person $person): void {
            static::$previousSpouseIds[$person->id] = $person->getOriginal('spouse_id');
        });

        static::saved(function (Person $person): void {
            $person->syncChurchProfile();
            $person->syncReciprocalSpouse();
        });
    }

public function immichMapping(): HasOne
{
    return $this->hasOne(ImmichPersonMapping::class);
}

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'household_id');
    }

    public function localityRecord(): BelongsTo
{
    return $this->belongsTo(Locality::class, 'locality_id');
}

    public function spouse(): BelongsTo
    {
        return $this->belongsTo(self::class, 'spouse_id');
    }

    public function emergencyContact(): BelongsTo
    {
        return $this->belongsTo(self::class, 'emergency_contact_id');
    }

    public function churchProfile(): HasOne
    {
        return $this->hasOne(ChurchProfile::class, 'person_id');
    }

    public function educationProfile(): HasOne
    {
        return $this->hasOne(EducationProfile::class, 'person_id');
    }

    public function campusContact(): HasOne
    {
        return $this->hasOne(CampusContact::class, 'person_id');
    }

    public function gospelContact(): HasOne
    {
        return $this->hasOne(
            GospelContact::class,
            'person_id'
        );
    }

    public function parentRelationships(): HasMany
    {
        return $this->hasMany(ParentRelationship::class, 'person_id');
    }

    public function householdsHeaded(): HasMany
    {
        return $this->hasMany(Household::class, 'household_head_id');
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $middleInitials = collect(preg_split('/\s+/', trim((string) $this->middlename)))
                    ->filter()
                    ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)) . '.')
                    ->implode(' ');

                return collect([
                    filled($this->lastname) ? trim((string) $this->lastname) . ',' : null,
                    $this->firstname,
                    $middleInitials,
                    $this->suffix,
                ])
                    ->filter(fn ($part): bool => filled($part))
                    ->implode(' ');
            }
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->display_name,
        );
    }

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

    private function syncReciprocalSpouse(): void
    {
        if (static::$syncingSpouse || ! $this->exists) {
            return;
        }

        $oldSpouseId = static::$previousSpouseIds[$this->id] ?? null;
        unset(static::$previousSpouseIds[$this->id]);

        $newSpouseId = $this->spouse_id ? (int) $this->spouse_id : null;

        static::$syncingSpouse = true;

        try {
            // If spouse was removed, clear anyone still pointing to this person.
            if (! $newSpouseId) {
                static::query()
                    ->where('spouse_id', $this->id)
                    ->update(['spouse_id' => null]);

                return;
            }

            // Remove this person from the old spouse if the old spouse still points back.
            if ($oldSpouseId && (int) $oldSpouseId !== $newSpouseId) {
                static::query()
                    ->whereKey($oldSpouseId)
                    ->where('spouse_id', $this->id)
                    ->update(['spouse_id' => null]);
            }

            // Clear any other person who still points to this person as spouse.
            static::query()
                ->where('spouse_id', $this->id)
                ->whereKeyNot($newSpouseId)
                ->update(['spouse_id' => null]);

            $newSpouse = static::query()->find($newSpouseId);

            if (! $newSpouse) {
                return;
            }

            $newSpouseOldSpouseId = $newSpouse->spouse_id ? (int) $newSpouse->spouse_id : null;

            // If the new spouse had another spouse, clear that old partner too.
            if ($newSpouseOldSpouseId && $newSpouseOldSpouseId !== (int) $this->id) {
                static::query()
                    ->whereKey($newSpouseOldSpouseId)
                    ->where('spouse_id', $newSpouseId)
                    ->update(['spouse_id' => null]);
            }

            // Finally, make the new spouse point back to this person.
            static::query()
                ->whereKey($newSpouseId)
                ->update(['spouse_id' => $this->id]);
        } finally {
            static::$syncingSpouse = false;
        }
    }


    /**
     * Create one Person while intentionally allowing an exact
     * first-name + last-name duplicate.
     *
     * The bypass is always restored, even if saving fails.
     */
    /**
     * Save this Person while intentionally allowing an exact
     * first-name + last-name duplicate.
     *
     * All other Person validation remains active.
     */
    public function saveAllowingExactDuplicate(
        array $options = []
    ): bool {
        $previous =
            static::$allowExactDuplicateCreation;

        static::$allowExactDuplicateCreation = true;

        try {
            return $this->save($options);
        } finally {
            static::$allowExactDuplicateCreation =
                $previous;
        }
    }

    /**
     * Create one Person while intentionally allowing an exact
     * first-name + last-name duplicate.
     */
    public static function createAllowingExactDuplicate(
        array $attributes
    ): static {
        $person = new static($attributes);

        $person->saveAllowingExactDuplicate();

        return $person;
    }

    private function validateBeforeSave(): void
    {
        $errors = [];

        if (blank($this->firstname)) {
            $errors['firstname'][] = 'First name is required.';
        }

        if (blank($this->lastname)) {
            $errors['lastname'][] = 'Last name is required.';
        }

        if (filled($this->sex) && ! in_array($this->sex, ['Male', 'Female'], true)) {
            $errors['sex'][] = 'Sex must be Male or Female.';
        }

        if (filled($this->email) && ! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email address is invalid.';
        }

        if ($this->exists && filled($this->spouse_id) && (int) $this->spouse_id === (int) $this->id) {
            $errors['spouse_id'][] = 'A person cannot be their own spouse.';
        }

        if ($this->exists && filled($this->emergency_contact_id) && (int) $this->emergency_contact_id === (int) $this->id) {
            $errors['emergency_contact_id'][] = 'A person cannot be their own emergency contact.';
        }

        $shouldCheckExactDuplicate =
            ! $this->exists
            || $this->isDirty([
                'firstname',
                'lastname',
            ]);

        if (
            ! static::$allowExactDuplicateCreation
            && $shouldCheckExactDuplicate
            && filled($this->firstname)
            && filled($this->lastname)
        ) {
            $firstnameKey =
                strtolower(
                    trim(
                        (string) $this->firstname
                    )
                );

            $lastnameKey =
                strtolower(
                    trim(
                        (string) $this->lastname
                    )
                );

            $duplicatePerson = self::query()
                ->whereRaw(
                    'LOWER(firstname) = ?',
                    [$firstnameKey]
                )
                ->whereRaw(
                    'LOWER(lastname) = ?',
                    [$lastnameKey]
                )
                ->when(
                    $this->exists,
                    fn ($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $this->id
                        )
                )
                ->exists();

            $duplicateGospelContact =
                GospelContact::query()
                    ->whereNull('person_id')
                    ->whereRaw(
                        'LOWER(firstname) = ?',
                        [$firstnameKey]
                    )
                    ->whereRaw(
                        'LOWER(lastname) = ?',
                        [$lastnameKey]
                    )
                    ->exists();

            $duplicateCampusContact =
                CampusContact::query()
                    ->whereNull('person_id')
                    ->whereRaw(
                        'LOWER(firstname) = ?',
                        [$firstnameKey]
                    )
                    ->whereRaw(
                        'LOWER(lastname) = ?',
                        [$lastnameKey]
                    )
                    ->exists();

            if (
                $duplicatePerson
                || $duplicateGospelContact
                || $duplicateCampusContact
            ) {
                $sources = collect([
                    $duplicatePerson
                        ? 'People Database'
                        : null,

                    $duplicateGospelContact
                        ? 'Gospel Contacts'
                        : null,

                    $duplicateCampusContact
                        ? 'Campus Contacts'
                        : null,
                ])
                    ->filter()
                    ->implode(', ');

                $errors['firstname'][] =
                    'Possible duplicate: the same first name and last name already exist in '
                    . $sources
                    . '.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
