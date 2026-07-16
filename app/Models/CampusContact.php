<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampusContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'firstname',
        'lastname',
        'sex',
        'locality',
        'school_campus',
        'course_strand',
        'grade_level',
        'contact_number',
        'email',
        'facebook_account',
        'notes',
    ];

    protected $appends = [
        'display_name',
        'effective_firstname',
        'effective_lastname',
        'effective_sex',
        'effective_locality',
        'effective_school_campus',
        'effective_course_strand',
        'effective_grade_level',
        'effective_contact_number',
        'effective_email',
        'effective_facebook_account',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $firstname = $this->person?->firstname
                    ?: $this->firstname;

                $lastname = $this->person?->lastname
                    ?: $this->lastname;

                $name = collect([
                    filled($lastname)
                        ? trim((string) $lastname) . ','
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
                $this->person?->firstname ?: $this->firstname,
        );
    }

    protected function effectiveLastname(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->lastname ?: $this->lastname,
        );
    }

    protected function effectiveSex(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->sex ?: $this->sex,
        );
    }

    protected function effectiveLocality(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->locality ?: $this->locality,
        );
    }

    protected function effectiveSchoolCampus(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->educationProfile?->school_workplace
                    ?: $this->school_campus,
        );
    }

    protected function effectiveCourseStrand(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->educationProfile?->course_strand
                    ?: $this->course_strand,
        );
    }

    protected function effectiveGradeLevel(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->person?->educationProfile?->grade_level
                    ?: $this->grade_level,
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

    public function isAddedToPeople(): bool
    {
        return filled($this->person_id);
    }
}
