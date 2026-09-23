<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GospelContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'household_id',
        'firstname',
        'lastname',
        'sex',
        'locality',
        'locality_id',
        'contact_number',
        'email',
        'facebook_account',
        'address',
        'contact_place',
        'notes',
    ];

    protected $appends = [
        'display_name',
        'effective_firstname',
        'effective_lastname',
        'effective_sex',
        'effective_locality',
        'effective_contact_number',
        'effective_email',
        'effective_facebook_account',
        'effective_address',
    ];

    protected static function booted(): void
    {
        static::saving(
            function (
                GospelContact $contact
            ): void {
                if (
                    blank(
                        $contact->locality_id
                    )
                ) {
                    $contact->locality = null;

                    return;
                }

                $locality = Locality::query()
                    ->find(
                        $contact->locality_id
                    );

                if ($locality) {
                    $contact->locality =
                        $locality->name;
                }
            }
        );

        /*
         * When a Gospel Contact becomes a Person, promote
         * every Parent / Guardian relationship that was
         * temporarily pointing to this Gospel Contact.
         */
        static::saved(
            function (
                GospelContact $contact
            ): void {
                if (blank($contact->person_id)) {
                    return;
                }

                ParentRelationship::query()
                    ->where(
                        'gospel_contact_id',
                        $contact->id
                    )
                    ->update([
                        'parent_id' =>
                            $contact->person_id,

                        'gospel_contact_id' =>
                            null,

                        'parent_name' =>
                            null,
                    ]);
            }
        );
    }

    public function localityRecord(): BelongsTo
    {
        return $this->belongsTo(
            Locality::class,
            'locality_id'
        );
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(
            Household::class,
            'household_id'
        );
    }

    public function shepherdingRecords(): BelongsToMany
    {
        return $this->belongsToMany(
            ShepherdingContact::class,
            'shepherding_contact_gospel_contacts',
            'gospel_contact_id',
            'shepherding_contact_id'
        )->withTimestamps();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(
            Person::class
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $firstname =
                    $this->person?->firstname
                    ?: $this->firstname;

                $lastname =
                    $this->person?->lastname
                    ?: $this->lastname;

                $name = collect([
                    filled($lastname)
                        ? trim(
                            (string) $lastname
                        ) . ','
                        : null,

                    $firstname,
                ])
                    ->filter()
                    ->implode(' ');

                return filled($name)
                    ? $name
                    : 'Unnamed contact';
            }
        );
    }

    protected function effectiveFirstname(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->firstname
                    ?: $this->firstname,
        );
    }

    protected function effectiveLastname(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->lastname
                    ?: $this->lastname,
        );
    }

    protected function effectiveSex(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->sex
                    ?: $this->sex,
        );
    }

    protected function effectiveLocality(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->locality
                    ?: $this->locality,
        );
    }

    protected function effectiveContactNumber(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->contact_number
                    ?: $this->contact_number,
        );
    }

    protected function effectiveEmail(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->email
                    ?: $this->email,
        );
    }

    protected function effectiveFacebookAccount(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->facebook_account
                    ?: $this->facebook_account,
        );
    }

    protected function effectiveAddress(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->home_address
                    ?: $this->person?->permanent_address
                    ?: $this->address,
        );
    }

    public function isAddedToPeople(): bool
    {
        return filled(
            $this->person_id
        );
    }
}
