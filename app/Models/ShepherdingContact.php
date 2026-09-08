<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShepherdingContact extends Model
{
    use HasFactory;

    public const OUTCOME_COMPLETED = 'Completed';

    public const OUTCOME_UNAVAILABLE = 'Out / Unavailable';

    public const OUTCOME_DECLINED = 'Declined';

    public const OUTCOME_RESCHEDULE = 'Reschedule';

    public const OUTCOME_OTHER = 'Other';

    protected $fillable = [
        'locality_id',
        'contact_date',
        'contact_time',
        'outcome',
        'notes',
    ];

    protected $casts = [
        'contact_date' => 'date',
    ];

    public static function outcomeOptions(): array
    {
        return [
            self::OUTCOME_COMPLETED =>
                self::OUTCOME_COMPLETED,

            self::OUTCOME_UNAVAILABLE =>
                self::OUTCOME_UNAVAILABLE,

            self::OUTCOME_DECLINED =>
                self::OUTCOME_DECLINED,

            self::OUTCOME_RESCHEDULE =>
                self::OUTCOME_RESCHEDULE,

            self::OUTCOME_OTHER =>
                self::OUTCOME_OTHER,
        ];
    }

    public function contactedPeople(): BelongsToMany
    {
        return $this->belongsToMany(
            Person::class,
            'shepherding_contact_people',
            'shepherding_contact_id',
            'person_id'
        )->withTimestamps();
    }

    public function contactedHouseholds(): BelongsToMany
    {
        return $this->belongsToMany(
            Household::class,
            'shepherding_contact_households',
            'shepherding_contact_id',
            'household_id'
        )->withTimestamps();
    }

    public function householdMembers(): BelongsToMany
    {
        return $this->belongsToMany(
            Person::class,
            'shepherding_contact_household_members',
            'shepherding_contact_id',
            'person_id'
        )
            ->withPivot([
                'household_id',
                'was_present',
            ])
            ->withTimestamps();
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function activityTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ShepherdingActivityType::class,
            'shepherding_contact_activities'
        )->withTimestamps();
    }

    public function ministryLessons(): BelongsToMany
    {
        return $this->belongsToMany(
            MinistryLesson::class,
            'shepherding_contact_ministry_lessons'
        )->withTimestamps();
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            Person::class,
            'shepherding_contact_participants',
            'shepherding_contact_id',
            'person_id'
        )->withTimestamps();
    }
}
