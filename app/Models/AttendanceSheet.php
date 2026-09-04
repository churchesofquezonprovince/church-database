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

    protected $fillable = [
        'title',
        'sheet_type',
        'locality',
        'meeting_day',
        'meeting_time',
        'is_one_time',
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

public function immichAlbum(): HasOne
{
    return $this->hasOne(
        AttendanceSheetImmichAlbum::class,
        'attendance_sheet_id',
    );
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
        return $this->is_one_time ? 'One-time' : 'Recurring';
    }


    public function dateRangeLabel(): string
    {
        if ($this->is_one_time) {
            return $this->start_date?->format('M d, Y') ?? 'No date';
        }

        $start = $this->start_date?->format('M d, Y') ?? 'No start date';
        $end = $this->end_date?->format('M d, Y') ?? 'No end date';

        return $start . ' to ' . $end;
    }

    public function attendanceModeBadgeClass(): string
    {
        return $this->is_one_time
            ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100'
            : 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-100';
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AttendanceParticipant::class);
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
    return $this->meeting_form_type === self::MEETING_FORM_NORMAL;
}

public function meetingFormLabel(): string
{
    return match ($this->meeting_form_type) {
        self::MEETING_FORM_NORMAL => 'Normal Meeting Form',
        default => 'Disabled',
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

}
