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
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => collect([
                filled($this->lastname)
                    ? trim((string) $this->lastname) . ','
                    : null,
                $this->firstname,
            ])
                ->filter()
                ->implode(' ')
        );
    }

    public function isAddedToPeople(): bool
    {
        return filled($this->person_id);
    }
}
