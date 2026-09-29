<?php

namespace App\Models;

use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\AttendanceSheetImmichAlbum;

class AttendanceSheet extends Model
{
    public const TYPE_CUSTOM = 'custom';

    public const TYPE_LORDS_TABLE = 'lords_table';

    public const TYPE_PRAYER_MEETING = 'prayer_meeting';

    public const MEETING_FORM_DISABLED = 'disabled';

    public const MEETING_FORM_NORMAL = 'normal';

    public const MEETING_FORM_GOOGLE = 'google_form';

    public const SCHEDULE_RECURRING = 'recurring';

    public const SCHEDULE_ONE_TIME = 'one_time';

    public const SCHEDULE_CONSECUTIVE = 'consecutive';

    public const SCHEDULE_MANUAL = 'manual';

    protected $fillable = [
        'attendance_meeting_series_id',
        'title',
        'sheet_type',
        'locality',
        'locality_id',
        'meeting_day',
        'meeting_time',
        'end_time',
        'is_one_time',
        'schedule_type',
        'start_date',
        'end_date',
        'is_active',
        'meeting_form_type',
        'remarks',
        'created_by_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_one_time' => 'boolean',
        'is_active' => 'boolean',
    ];

public function meetingSeries(): BelongsTo
{
    return $this->belongsTo(
        AttendanceMeetingSeries::class,
        'attendance_meeting_series_id'
    );
}

public function immichAlbum(): HasOne
{
    return $this->hasOne(
        AttendanceSheetImmichAlbum::class,
        'attendance_sheet_id',
    );
}

    protected static function booted(): void
    {
        static::saving(function (AttendanceSheet $sheet): void {
            /*
             * Keep the legacy is_one_time flag synchronized while
             * schedule_type becomes the canonical scheduling mode.
             *
             * Existing controllers that still write is_one_time
             * therefore continue to behave correctly.
             */
            if ($sheet->isDirty('schedule_type')) {
                $sheet->is_one_time =
                    $sheet->schedule_type === self::SCHEDULE_ONE_TIME;
            } elseif ($sheet->isDirty('is_one_time')) {
                if ($sheet->is_one_time) {
                    $sheet->schedule_type = self::SCHEDULE_ONE_TIME;
                } elseif (
                    blank($sheet->schedule_type)
                    || $sheet->schedule_type === self::SCHEDULE_ONE_TIME
                ) {
                    $sheet->schedule_type = self::SCHEDULE_RECURRING;
                }
            }

            if (filled($sheet->locality_id)) {
                $locality = Locality::query()->find($sheet->locality_id);

                if ($locality) {
                    $sheet->locality = $locality->name;
                }
            }
        });
    }

    public function localityRecord(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'locality_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }


    public function meetingTimeLabel(): string
    {
        if (blank($this->meeting_time)) {
            return 'No time set';
        }

        return CarbonImmutable::parse((string) $this->meeting_time)->format('g:i A');
    }

    public function attendanceModeLabel(): string
    {
        return match ($this->schedule_type) {
            self::SCHEDULE_ONE_TIME => 'One-time',
            self::SCHEDULE_CONSECUTIVE => 'Consecutive Days',
            self::SCHEDULE_MANUAL => 'Manual Dates',
            default => 'Recurring Weekly',
        };
    }


    public function dateRangeLabel(): string
    {
        if ($this->schedule_type === self::SCHEDULE_MANUAL) {
            return 'Manual dates';
        }

        if ($this->schedule_type === self::SCHEDULE_ONE_TIME) {
            return $this->start_date?->format('M d, Y') ?? 'No date';
        }

        $start = $this->start_date?->format('M d, Y') ?? 'No start date';
        $end = $this->end_date?->format('M d, Y') ?? 'No end date';

        return $start . ' to ' . $end;
    }

    public function attendanceModeBadgeClass(): string
    {
        return match ($this->schedule_type) {
            self::SCHEDULE_ONE_TIME =>
                'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100',

            self::SCHEDULE_CONSECUTIVE =>
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100',

            self::SCHEDULE_MANUAL =>
                'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-100',

            default =>
                'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-100',
        };
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function meetingFormQuestions(): HasMany
    {
        return $this->hasMany(
            AttendanceMeetingFormQuestion::class,
            'attendance_sheet_id'
        )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function campusActivity(): HasOne
    {
        return $this->hasOne(
            CampusWorkActivity::class,
            'attendance_sheet_id'
        );
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AttendanceParticipant::class);
    }

    /**
     * Count distinct People rather than participant-period rows.
     *
     * One Person may legitimately have multiple dated
     * AttendanceParticipant periods on the same Sheet.
     */
    public function scopeWithDistinctParticipantCount(
        \Illuminate\Database\Eloquent\Builder $query,
        bool $activeOnly = false
    ): \Illuminate\Database\Eloquent\Builder {
        $participantCount =
            AttendanceParticipant::query()
                ->selectRaw(
                    'COUNT(DISTINCT person_id)'
                )
                ->whereColumn(
                    'attendance_sheet_id',
                    'attendance_sheets.id'
                );

        if ($activeOnly) {
            $participantCount->where(
                'is_active',
                true
            );
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('attendance_guests')) {
            $guestCount=\Illuminate\Support\Facades\DB::table('attendance_guests as g')
                ->join('attendance_guest_periods as p','p.attendance_guest_id','=','g.id')
                ->whereColumn('g.attendance_sheet_id','attendance_sheets.id')->whereNull('g.linked_person_id')
                ->when($activeOnly,fn($q)=>$q->where('p.is_active',true))->selectRaw('COUNT(DISTINCT g.id)');
            if ($query->getQuery()->columns===null) $query->select('attendance_sheets.*');
            $query->addSelect(['guest_participants_count'=>$guestCount]);
        }
        return $query->addSelect([
            'participants_count' =>
                $participantCount,
        ]);
    }

    public function records(): HasMany
    {
        return $this->hasManyThrough(AttendanceRecord::class, AttendanceSession::class);
    }

    public function isLordsTable(): bool
    {
        return $this->sheet_type === self::TYPE_LORDS_TABLE;
    }

    public function meetingFormEnabled(): bool
{
    return in_array(
        $this->meeting_form_type,
        [
            self::MEETING_FORM_NORMAL,
            self::MEETING_FORM_GOOGLE,
        ],
        true
    );
}

public function meetingFormLabel(): string
{
    return match ($this->meeting_form_type) {
        self::MEETING_FORM_NORMAL =>
            'Normal Meeting Form',

        self::MEETING_FORM_GOOGLE =>
            'Google Form-like',

        default =>
            'Disabled',
    };
}

public function ensureMeetingFormSlugs(): void
{
    /*
     * Disabled sheets do not need new public URLs.
     *
     * Existing slugs are deliberately NOT deleted when the
     * form is disabled. This allows a shared URL to remain
     * stable if the form is enabled again later.
     */
    if (! $this->meetingFormEnabled()) {
        return;
    }

    $this->sessions()
        ->orderBy('session_date')
        ->orderBy('id')
        ->get()
        ->each(function (AttendanceSession $session): void {
            /*
             * Never change an existing public URL.
             */
            if (filled($session->public_slug)) {
                return;
            }

            $baseSlug = $this->meetingFormSlugBase(
                $session
            );

            $slug = $baseSlug;
            $suffix = 2;

            /*
             * public_slug has a UNIQUE database index,
             * so resolve any collision before saving.
             */
            while (
                AttendanceSession::query()
                    ->where('public_slug', $slug)
                    ->where('id', '!=', $session->id)
                    ->exists()
            ) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $session->forceFill([
                'public_slug' => $slug,
            ])->save();
        });
}

private function meetingFormSlugBase(
    AttendanceSession $session
): string {
    /*
     * Example:
     *
     * August 15, 2026
     * Church Meeting
     *
     * becomes:
     *
     * 8-15-26-churchmeeting
     */
    $datePart = $session->session_date
        ?->format('n-j-y')
        ?? 'meeting';

    $titlePart = Str::of(
        (string) $this->title
    )
        ->ascii()
        ->lower()
        ->replaceMatches('/[^a-z0-9]+/', '')
        ->toString();

    if ($titlePart === '') {
        $titlePart = 'meeting';
    }

    return $datePart . '-' . $titlePart;
}

    public function getParticipantsCountAttribute($value): int
    {
        return (int)$value + (int)($this->attributes['guest_participants_count'] ?? 0);
    }

}
