<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MorningRevivalPublication extends Model
{
    protected $fillable = [
        'source_title',
        'general_subject',
        'start_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' =>
            'date',

        'is_active' =>
            'boolean',
    ];

    public function weeks(): HasMany
    {
        return $this->hasMany(
            MorningRevivalWeek::class
        )
            ->orderBy(
                'week_number'
            );
    }
}
