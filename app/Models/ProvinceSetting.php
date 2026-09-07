<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvinceSetting extends Model
{
    protected $fillable = [
        'primary_country_id',
        'primary_province_id',
    ];

    public function primaryCountry(): BelongsTo
    {
        return $this->belongsTo(
            Country::class,
            'primary_country_id'
        );
    }

    public function primaryProvince(): BelongsTo
    {
        return $this->belongsTo(
            Province::class,
            'primary_province_id'
        );
    }
}
