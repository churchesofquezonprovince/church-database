<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Person extends Model
{
    protected $table = 'persons';

    public $timestamps = false;

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | Display Name
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        $parts = [
            $this->lastname . ',',
            $this->firstname,
        ];

        if ($this->middlename) {
            $parts[] = $this->middlename;
        }

        if ($this->suffix) {
            $parts[] = $this->suffix;
        }

        return trim(implode(' ', $parts));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }

    /*
    |--------------------------------------------------------------------------
    | Household
    |--------------------------------------------------------------------------
    */

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Church
    |--------------------------------------------------------------------------
    */

    public function church(): HasOne
    {
        return $this->hasOne(PersonChurch::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Education
    |--------------------------------------------------------------------------
    */

    public function education(): HasOne
    {
        return $this->hasOne(PersonEducation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Parents
    |--------------------------------------------------------------------------
    */

    public function parents(): HasMany
    {
        return $this->hasMany(PersonParent::class);
    }

    public function mother(): HasOne
    {
        return $this->hasOne(PersonParent::class)
            ->where('relationship', 'Mother');
    }

    public function father(): HasOne
    {
        return $this->hasOne(PersonParent::class)
            ->where('relationship', 'Father');
    }

    /*
    |--------------------------------------------------------------------------
    | Siblings
    |--------------------------------------------------------------------------
    */

    public function siblings(): HasMany
    {
        return $this->hasMany(PersonSibling::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Spouse
    |--------------------------------------------------------------------------
    */

    public function spouse(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'spouse_id');
    }
}
