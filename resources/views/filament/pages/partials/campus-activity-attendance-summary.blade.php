@if (
    filled($activity->attendance_sheet_id)
    || filled($activity->attendance_session_id)
)
    @php
        $attendanceSummary =
            $this->attendanceSummary($activity);
    @endphp

    <div
        class="w-full rounded-xl border
               border-emerald-200 bg-emerald-50
               p-3 dark:border-emerald-900
               dark:bg-emerald-950"
    >
        <div class="flex flex-wrap gap-2">
            <span
                class="rounded-full bg-emerald-600
                       px-3 py-1 text-xs font-bold
                       text-white"
            >
                {{ $attendanceSummary['mode'] }}
            </span>

            @if ($attendanceSummary['range'])
                <span
                    class="rounded-full bg-white
                           px-3 py-1 text-xs font-semibold
                           text-gray-700
                           dark:bg-gray-900
                           dark:text-gray-200"
                >
                    {{ $attendanceSummary['range'] }}
                </span>
            @endif

            <span
                class="rounded-full bg-white
                       px-3 py-1 text-xs font-semibold
                       text-gray-700
                       dark:bg-gray-900
                       dark:text-gray-200"
            >
                {{ $attendanceSummary['sessions'] }}
                Session(s)
            </span>

            <span
                class="rounded-full bg-white
                       px-3 py-1 text-xs font-semibold
                       text-gray-700
                       dark:bg-gray-900
                       dark:text-gray-200"
            >
                {{ $attendanceSummary['present'] }}
                Present
                /
                {{ $attendanceSummary['marked'] }}
                Marked
            </span>
        </div>

        @if (
            $attendanceSummary['focus_session']
            && ! $activity->attendanceSheet?->is_one_time
        )
            <div class="mt-3">
                <a
                    href="{{ $this->attendanceSessionUrl(
                        $attendanceSummary['focus_session']
                    ) }}"
                    class="inline-flex rounded-lg
                           bg-primary-600 px-3 py-2
                           text-xs font-bold text-white
                           hover:bg-primary-500"
                >
                    {{ $attendanceSummary['focus_label'] }}
                    ·
                    {{ $attendanceSummary[
                        'focus_session'
                    ]->session_date?->format('M d, Y') }}
                </a>
            </div>
        @endif
    </div>
@endif
