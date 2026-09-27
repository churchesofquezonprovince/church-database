<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Household extends Model
{
    use HasFactory;

    protected $table = 'households';

    protected $fillable = [
        'household_name',
        'household_head_id',
        'gospel_contact_head_id',
        'address',
        'locality',
        'locality_id',
        'remarks',
    ];

    protected $appends = [
        'display_name',
    ];

    protected static function booted(): void
    {
        static::saving(function (Household $household): void {
            if (filled($household->locality_id)) {
                $locality = Locality::query()->find($household->locality_id);

                if ($locality) {
                    $household->locality = $locality->name;
                }
            }

            $household->validateBeforeSave();
        });

        static::saved(function (Household $household): void {
            if ($household->household_head_id) {
                Person::query()
                    ->whereKey($household->household_head_id)
                    ->update([
                        'household_id' => $household->id,
                    ]);
            }

            if ($household->gospel_contact_head_id) {
                GospelContact::query()
                    ->whereKey(
                        $household->gospel_contact_head_id
                    )
                    ->whereNull('person_id')
                    ->update([
                        'household_id' => $household->id,
                    ]);
            }
        });
    }

    public function localityRecord(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'locality_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'household_head_id');
    }

    public function gospelContactHead(): BelongsTo
    {
        return $this->belongsTo(
            GospelContact::class,
            'gospel_contact_head_id'
        );
    }

    public function members(): HasMany
    {
        return $this->hasMany(Person::class, 'household_id');
    }

    public function campusContacts(): HasMany
    {
        return $this->hasMany(
            CampusContact::class,
            'household_id'
        );
    }

    public function gospelContacts(): HasMany
    {
        return $this->hasMany(
            GospelContact::class,
            'household_id'
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! empty($this->household_name)) {
                    return $this->household_name;
                }

                if ($this->head) {
                    return "{$this->head->lastname} Family";
                }

                return 'Unnamed Household';
            }
        );
    }

    private function validateBeforeSave(): void
    {
        $errors = [];

        if (blank($this->household_name)) {
            $errors['household_name'][] = 'Household name is required.';
        }

        if (filled($this->household_name)) {
            $query = self::query()
                ->whereRaw('LOWER(household_name) = ?', [strtolower(trim((string) $this->household_name))]);

            if (filled($this->locality_id)) {
                $query->where('locality_id', $this->locality_id);
            } elseif (filled($this->locality)) {
                $query->whereRaw(
                    'LOWER(locality) = ?',
                    [mb_strtolower(trim((string) $this->locality))]
                );
            } else {
                $query->where(function ($query): void {
                    $query->whereNull('locality')
                        ->orWhere('locality', '');
                });
            }

            $duplicate = $query
                ->when($this->exists, fn ($query) => $query->where('id', '!=', $this->id))
                ->exists();

            if ($duplicate) {
                $errors['household_name'][] = 'Possible duplicate: another household already has the same name in the same locality.';
            }
        }

        if (
            filled($this->household_head_id)
            && filled($this->gospel_contact_head_id)
        ) {
            $errors['household_head_id'][] =
                'Choose either a People Household Head or a Gospel Contact Household Head, not both.';

            $errors['gospel_contact_head_id'][] =
                'Choose either a People Household Head or a Gospel Contact Household Head, not both.';
        }

        if (filled($this->gospel_contact_head_id)) {
            $gospelHead = GospelContact::query()
                ->find($this->gospel_contact_head_id);

            if (! $gospelHead) {
                $errors['gospel_contact_head_id'][] =
                    'The selected Gospel Contact could not be found.';
            } elseif (filled($gospelHead->person_id)) {
                $errors['gospel_contact_head_id'][] =
                    'A Gospel Contact already linked to People must be selected as a People Household Head instead.';
            } elseif (
                filled($gospelHead->household_id)
                && (
                    ! $this->exists
                    || (int) $gospelHead->household_id
                        !== (int) $this->id
                )
            ) {
                $errors['gospel_contact_head_id'][] =
                    'This Gospel Contact already belongs to another Household.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
