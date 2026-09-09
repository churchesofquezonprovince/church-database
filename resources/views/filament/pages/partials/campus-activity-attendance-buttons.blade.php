@if (
    filled($activity->attendance_sheet_id)
    || filled($activity->attendance_session_id)
)
    @include(
        'filament.pages.partials.campus-activity-attendance-summary',
        ['activity' => $activity]
    )
@endif

@if (
    filled($activity->attendance_sheet_id)
    || filled($activity->attendance_session_id)
)
    @if ($this->attendanceUrl($activity))
        <a
            href="{{ $this->attendanceUrl($activity) }}"
            class="rounded-lg bg-emerald-600
                   px-3 py-1.5 text-xs font-bold
                   text-white hover:bg-emerald-500"
        >
            {{ $activity->attendanceSheet?->is_one_time
                ? 'Open Attendance'
                : 'Open Series' }}
        </a>
    @endif

    <span
        class="rounded-lg bg-emerald-50
               px-3 py-1.5 text-xs font-bold
               text-emerald-700
               dark:bg-emerald-950
               dark:text-emerald-300"
    >
        {{ $activity->attendanceSheet?->is_one_time
            ? 'One-time'
            : 'Recurring' }}
    </span>

    <form
        method="POST"
        action="{{ route(
            'quezonprovinceactivities.campus-work.activities.attendance.unlink',
            $activity
        ) }}"
        onsubmit="return confirm(
            'Detach this Campus Activity from Attendance? Attendance data will be preserved.'
        );"
    >
        @csrf
        @method('DELETE')

        <button
            type="submit"
            class="rounded-lg border border-gray-300
                   px-3 py-1.5 text-xs font-bold
                   text-gray-600 hover:bg-gray-100
                   dark:border-gray-700
                   dark:text-gray-300
                   dark:hover:bg-gray-800"
        >
            Detach
        </button>
    </form>
@else
    <form
        method="POST"
        action="{{ route(
            'quezonprovinceactivities.campus-work.activities.attendance.one-time',
            $activity
        ) }}"
        onsubmit="return confirm(
            'Generate one-time Attendance for this Campus Activity?'
        );"
    >
        @csrf

        <button
            type="submit"
            class="rounded-lg bg-emerald-600
                   px-3 py-1.5 text-xs font-bold
                   text-white hover:bg-emerald-500"
        >
            One-time Attendance
        </button>
    </form>

    <button
        type="button"
        onclick="document.getElementById(
            'recurring-campus-attendance-{{ $activity->id }}'
        ).showModal()"
        class="rounded-lg bg-violet-600
               px-3 py-1.5 text-xs font-bold
               text-white hover:bg-violet-500"
    >
        Recurring
    </button>

    <button
        type="button"
        onclick="document.getElementById(
            'link-campus-attendance-{{ $activity->id }}'
        ).showModal()"
        class="rounded-lg border border-primary-300
               px-3 py-1.5 text-xs font-bold
               text-primary-700 hover:bg-primary-50
               dark:border-primary-800
               dark:text-primary-300
               dark:hover:bg-primary-950"
    >
        Link Existing
    </button>
@endif
