<?php

namespace App\Models;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Household extends Model
{
// Add 1 to Household number because 0 is returned if the household head is incuded
protected static function booted(): void
{
    static::saved(function (Household $household): void {
        if (! $household->household_head_id) {
            return;
        }

        Person::query()
            ->whereKey($household->household_head_id)
            ->update([
                'household_id' => $household->id,
            ]);
    });
}


use HasFactory;
    protected $table = 'households';

    protected $fillable = [
        'household_name',
        'household_head_id',
        'address',
        'locality',
        'remarks',
    ];

    protected $appends = [
        'display_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function head(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'household_head_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

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
}
