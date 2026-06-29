<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    protected $table = 'households';
	
    public $timestamps = true;

    protected $fillable = [
        'household_name',
        'household_head_id',
        'address',
        'locality',
        'remarks',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Person::class, 'household_id');
    }

    public function householdHead(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'household_head_id');
    }

public function getDisplayNameAttribute(): string
{
    if (! empty($this->household_name)) {
        return "{$this->household_name} Family";
    }

    if ($this->householdHead) {
        return "{$this->householdHead->lastname} Family";
    }

    return "Unnamed Household";
}

public function getFullNameAttribute(): string
{
    return $this->display_name;
}


}
