<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampusWorkActivity extends Model
{
    use HasFactory;

    public const TYPE_CAMPUS_VISITATION = 'Campus Visitation';

    public const TYPE_CAMPUS_MEETING_SCHEDULE = 'Campus Meeting Schedule';

    public const TYPE_BIBLE_PURSUIT = 'Bible Pursuit';

    public const TYPE_OTHER_ACTIVITY = 'Other Activity';

    protected $fillable = [
        'campus_work_term_id',
        'activity_type',
        'other_activity_name',
        'title',
        'activity_date',
        'start_time',
        'end_time',
        'school_id',
        'venue',
        'locality',
        'locality_id',
        'attendance_session_id',
        'attendance_sheet_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_CAMPUS_VISITATION => self::TYPE_CAMPUS_VISITATION,
            self::TYPE_CAMPUS_MEETING_SCHEDULE => self::TYPE_CAMPUS_MEETING_SCHEDULE,
            self::TYPE_BIBLE_PURSUIT => self::TYPE_BIBLE_PURSUIT,
            self::TYPE_OTHER_ACTIVITY => self::TYPE_OTHER_ACTIVITY,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CampusWorkActivity $activity): void {
            if (blank($activity->locality_id)) {
                $activity->locality = null;
            } else {
                $locality = Locality::query()->find(
                    $activity->locality_id
                );

                if ($locality) {
                    $activity->locality = $locality->name;
                }
            }

            if (blank($activity->school_id)) {
                $activity->school_campus = null;
            } else {
                $school = School::query()->find(
                    $activity->school_id
                );

                if ($school) {
                    $activity->school_campus = $school->name;
                }
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function localityRecord(): BelongsTo
    {
        return $this->belongsTo(
            Locality::class,
            'locality_id'
        );
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(
            CampusWorkTerm::class,
            'campus_work_term_id'
        );
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSession::class,
            'attendance_session_id'
        );
    }

    public function attendanceSheet(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSheet::class,
            'attendance_sheet_id'
        );
    }

    public function getDisplayTitleAttribute(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        if (
            $this->activity_type === self::TYPE_OTHER_ACTIVITY
            && filled($this->other_activity_name)
        ) {
            return $this->other_activity_name;
        }

        return $this->activity_type;
    }

    public function getActivityTypeLabelAttribute(): string
    {
        if (
            $this->activity_type === self::TYPE_OTHER_ACTIVITY
            && filled($this->other_activity_name)
        ) {
            return $this->other_activity_name;
        }

        return $this->activity_type;
    }

    public function getTimeLabelAttribute(): string
    {
        $formatTime = function (?string $time): ?string {
            if (blank($time)) {
                return null;
            }

            return date('g:i A', strtotime($time));
        };

        $start = $formatTime($this->start_time);
        $end = $formatTime($this->end_time);

        if ($start && $end) {
            return $start . ' – ' . $end;
        }

        return $start ?: $end ?: 'Time not specified';
    }
}
