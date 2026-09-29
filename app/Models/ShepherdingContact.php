<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'morning_revival_week_id',
        'morning_revival_day',
        'notes',
    ];

    protected $casts = [
        'contact_date' =>
            'date',

        'morning_revival_week_id' =>
            'integer',

        'morning_revival_day' =>
            'integer',
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

    /**
     * Include Shepherding Records that contribute to the
     * Ministry progress of a Household regardless of whether
     * the record was entered from Home Meeting Schedule,
     * directly against the Household, a Person, or a
     * Gospel Contact.
     *
     * Historical Household-member snapshots are also honored
     * when they are available.
     */
    public function scopeForHouseholdMinistryProgress(
        Builder $query,
        int $householdId
    ): Builder {
        return $query->where(
            function (Builder $query) use (
                $householdId
            ): void {
                $query
                    /*
                     * Household selected directly.
                     */
                    ->whereHas(
                        'contactedHouseholds',
                        fn (Builder $target) =>
                            $target->whereKey(
                                $householdId
                            )
                    )

                    /*
                     * Person selected directly.
                     */
                    ->orWhereHas(
                        'contactedPeople',
                        fn (Builder $target) =>
                            $target->where(
                                'persons.household_id',
                                $householdId
                            )
                    )

                    /*
                     * Gospel Contact selected directly.
                     */
                    ->orWhereHas(
                        'contactedGospelContacts',
                        fn (Builder $target) =>
                            $target->where(
                                'gospel_contacts.household_id',
                                $householdId
                            )
                    )

                    /*
                     * A Gospel Contact may later have been
                     * promoted to People. Honor the Person's
                     * current Household as well.
                     */
                    ->orWhereHas(
                        'contactedGospelContacts.person',
                        fn (Builder $target) =>
                            $target->where(
                                'persons.household_id',
                                $householdId
                            )
                    )

                    /*
                     * Historical Person Household snapshot.
                     */
                    ->orWhereHas(
                        'householdMembers',
                        fn (Builder $target) =>
                            $target->where(
                                'shepherding_contact_household_members.household_id',
                                $householdId
                            )
                    )

                    /*
                     * Historical Gospel Contact Household
                     * snapshot.
                     */
                    ->orWhereHas(
                        'householdGospelMembers',
                        fn (Builder $target) =>
                            $target->where(
                                'shepherding_contact_household_gospel_members.household_id',
                                $householdId
                            )
                    );
            }
        );
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

    public function contactedCampusContacts(): BelongsToMany
    {
        return $this->belongsToMany(
            CampusContact::class,
            'shepherding_contact_campus_contacts',
            'shepherding_contact_id',
            'campus_contact_id'
        )->withTimestamps();
    }

    public function contactedGospelContacts(): BelongsToMany
    {
        return $this->belongsToMany(
            GospelContact::class,
            'shepherding_contact_gospel_contacts',
            'shepherding_contact_id',
            'gospel_contact_id'
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

    public function householdGospelMembers(): BelongsToMany
    {
        return $this->belongsToMany(
            GospelContact::class,
            'shepherding_contact_household_gospel_members',
            'shepherding_contact_id',
            'gospel_contact_id'
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

    public function morningRevivalWeek(): BelongsTo
    {
        return $this->belongsTo(
            MorningRevivalWeek::class,
            'morning_revival_week_id'
        );
    }

    public function ministryLessons(): BelongsToMany
    {
        return $this->belongsToMany(
            MinistryLesson::class,
            'shepherding_contact_ministry_lessons'
        )->withTimestamps();
    }

    public function bibleReadings(): HasMany
    {
        return $this->hasMany(
            ShepherdingContactBibleReading::class,
            'shepherding_contact_id'
        )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function hymns(): BelongsToMany
    {
        return $this->belongsToMany(
            Hymn::class,
            'shepherding_contact_hymns'
        )
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function hymnAdditionRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            HymnAdditionRequest::class,
            'shepherding_contact_hymn_requests'
        )
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
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
