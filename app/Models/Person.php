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

    protected static function booted(): void
    {
        static::saving(function (Person $person): void {
            $person->validateBeforeSave();
        });

        static::saved(function (Person $person): void {
            $person->syncChurchProfile();
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'household_id');
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

        if (filled($this->firstname) && filled($this->lastname) && filled($this->birthdate)) {
            $birthdate = $this->birthdate instanceof \DateTimeInterface
                ? $this->birthdate->format('Y-m-d')
                : (string) $this->birthdate;

            $duplicate = self::query()
                ->whereRaw('LOWER(firstname) = ?', [strtolower(trim((string) $this->firstname))])
                ->whereRaw('LOWER(lastname) = ?', [strtolower(trim((string) $this->lastname))])
                ->whereDate('birthdate', $birthdate)
                ->when($this->exists, fn ($query) => $query->where('id', '!=', $this->id))
                ->exists();

            if ($duplicate) {
                $errors['firstname'][] = 'Possible duplicate: another person already has the same first name, last name, and birthdate.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
