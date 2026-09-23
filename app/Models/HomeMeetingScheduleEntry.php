<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeMeetingScheduleEntry extends Model
{
    use HasFactory;

    protected $table =
        'home_meeting_schedules';

    protected $fillable = [
        'locality_id',
        'household_id',
        'contact_person_id',
        'display_name',
        'area_name',
        'day_of_week',
        'meeting_time',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'locality_id' => 'integer',
        'household_id' => 'integer',
        'contact_person_id' => 'integer',
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function locality(): BelongsTo
    {
        return $this->belongsTo(
            Locality::class
        );
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(
            Household::class
        );
    }

    public function contactPerson(): BelongsTo
    {
        return $this->belongsTo(
            Person::class,
            'contact_person_id'
        );
    }
}
