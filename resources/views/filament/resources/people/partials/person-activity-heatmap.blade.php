@php
    $summary = $snapshot['summary'];

    $days =
        $snapshot['days'];

    $events =
        $snapshot['events'];

    $from =
        \Carbon\CarbonImmutable::parse(
            $summary['from']
        );

    $to =
        \Carbon\CarbonImmutable::parse(
            $summary['to']
        );

    /*
     * Expand the visual grid to whole Sunday-to-Saturday
     * weeks. Days outside the actual 365-day range are
     * rendered as blank cells.
     */
    $gridStart =
        $from->startOfWeek(
            \Carbon\CarbonInterface::SUNDAY
        );

    $gridEnd =
        $to->endOfWeek(
            \Carbon\CarbonInterface::SATURDAY
        );

    $dayMap =
        $days->keyBy('date');

    $gridDays =
        collect();

    $cursor =
        $gridStart;

    while (
        $cursor->lessThanOrEqualTo(
            $gridEnd
        )
    ) {
        $date =
            $cursor->toDateString();

        $inRange =
            $cursor->betweenIncluded(
                $from,
                $to
            );

        $day =
            $inRange
                ? $dayMap->get($date)
                : null;

        $gridDays->push([
            'date' =>
                $date,

            'in_range' =>
                $inRange,

            'day' =>
                $day,
        ]);

        $cursor =
            $cursor->addDay();
    }

    $recentEvents =
        $events
            ->sortByDesc('date')
            ->take(10)
            ->values();

    /*
     * Interactive heatmap details.
     *
     * Attendance opens the exact Check Attendance
     * Sheet + Session.
     *
     * Shepherding opens Shepherding History.
     *
     * The aggregator itself remains UI-independent.
     */
    $interactiveDays =
        $days
            ->filter(
                fn (array $day): bool =>
                    (int) $day['intensity'] > 0
            )
            ->mapWithKeys(
                function (
                    array $day
                ) use (
                    $record
                ): array {
                    $dayEvents =
                        collect(
                            $day['events']
                            ?? []
                        )
                            ->map(
                                function (
                                    array $event
                                ) use (
                                    $record
                                ): array {
                                    $details =
                                        $event['details']
                                        ?? [];

                                    $url = null;

                                    if (
                                        $event['source']
                                        === 'attendance'
                                        &&
                                        filled(
                                            $details['sheet_id']
                                            ?? null
                                        )
                                        &&
                                        filled(
                                            $details['session_id']
                                            ?? null
                                        )
                                    ) {
                                        $url =
                                            \App\Filament\Pages\CheckAttendance::getUrl()
                                            . '?'
                                            . http_build_query([
                                                'sheetId' =>
                                                    $details['sheet_id'],

                                                'sessionId' =>
                                                    $details['session_id'],
                                            ]);
                                    } elseif (
                                        $event['source']
                                        === 'shepherding'
                                        &&
                                        class_exists(
                                            \App\Filament\Pages\ShepherdingHistory::class
                                        )
                                    ) {
                                        $url =
                                            \App\Filament\Pages\ShepherdingHistory::getUrl();
                                    }

                                    $meta =
                                        collect([
                                            filled(
                                                $details['locality']
                                                ?? null
                                            )
                                                ? $details['locality']
                                                : null,

                                            $event['source']
                                                === 'attendance'
                                                &&
                                                filled(
                                                    $details['attendance_source']
                                                    ?? null
                                                )
                                                    ? ucfirst(
                                                        $details['attendance_source']
                                                    )
                                                    : null,

                                            (
                                                $details['prophesied']
                                                ?? false
                                            )
                                                ? 'Prophesied'
                                                : null,

                                            $event['source']
                                                === 'shepherding'
                                                &&
                                                filled(
                                                    $details['outcome']
                                                    ?? null
                                                )
                                                    ? $details['outcome']
                                                    : null,
                                        ])
                                            ->filter()
                                            ->implode(' · ');

                                    return [
                                        'key' =>
                                            $event['key'],

                                        'label' =>
                                            $event['label'],

                                        'source' =>
                                            $event['source'],

                                        'source_label' =>
                                            $event['source']
                                            === 'attendance'
                                                ? 'Attendance'
                                                : 'Shepherding',

                                        'categories' =>
                                            $event['categories']
                                            ?? [],

                                        'roles' =>
                                            $details['roles']
                                            ?? [],

                                        'meta' =>
                                            $meta,

                                        'url' =>
                                            $url,
                                    ];
                                }
                            )
                            ->values();

                    return [
                        $day['date'] => [
                            'date_label' =>
                                \Carbon\CarbonImmutable::parse(
                                    $day['date']
                                )->format(
                                    'l, F j, Y'
                                ),

                            'intensity' =>
                                (int)
                                $day['intensity'],

                            'attendance_count' =>
                                (int)
                                $day['attendance_count'],

                            'shepherding_count' =>
                                (int)
                                $day['shepherding_count'],

                            'events' =>
                                $dayEvents,
                        ],
                    ];
                }
            );
@endphp

<style>
    /*
     * Person Activity Heatmap
     *
     * Self-contained palette so we do not depend on
     * Tailwind generating arbitrary color utilities.
     */
    .person-activity-heatmap {
        --activity-empty: #e5e7eb;
        --activity-empty-border: #d1d5db;

        --activity-1: #bae6fd;
        --activity-1-border: #7dd3fc;

        --activity-2: #7dd3fc;
        --activity-2-border: #38bdf8;

        --activity-3: #38bdf8;
        --activity-3-border: #0284c7;

        --activity-4: #075985;
        --activity-4-border: #0c4a6e;
    }

    .dark .person-activity-heatmap {
        --activity-empty: #27272a;
        --activity-empty-border: #3f3f46;

        --activity-1: #164e63;
        --activity-1-border: #155e75;

        --activity-2: #0e7490;
        --activity-2-border: #0891b2;

        --activity-3: #0891b2;
        --activity-3-border: #22d3ee;

        --activity-4: #67e8f9;
        --activity-4-border: #a5f3fc;
    }

    .person-activity-cell {
        display: block;
        width: 0.75rem;
        height: 0.75rem;
        border-radius: 0.2rem;
        border: 1px solid transparent;
        transition:
            transform 120ms ease,
            border-color 120ms ease,
            box-shadow 120ms ease;
    }

    .person-activity-level-0 {
        background: var(--activity-empty);
        border-color: var(--activity-empty-border);
    }

    .person-activity-level-1 {
        background: var(--activity-1);
        border-color: var(--activity-1-border);
    }

    .person-activity-level-2 {
        background: var(--activity-2);
        border-color: var(--activity-2-border);
    }

    .person-activity-level-3 {
        background: var(--activity-3);
        border-color: var(--activity-3-border);
    }

    .person-activity-level-4 {
        background: var(--activity-4);
        border-color: var(--activity-4-border);
    }

    .person-activity-outside {
        background: transparent;
        border-color: transparent;
    }

    button.person-activity-cell:hover,
    button.person-activity-cell:focus-visible {
        transform: scale(1.35);
        border-color: currentColor;
        box-shadow:
            0 0 0 2px rgba(14, 165, 233, 0.28);
        outline: none;
        position: relative;
        z-index: 10;
    }
</style>

<div class="person-activity-heatmap space-y-5">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Active Days
            </p>

            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $summary['active_days'] }}
            </p>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                Attendance
            </p>

            <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-100">
                {{ $summary['attendance_events'] }}
            </p>
        </div>

        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                Shepherding
            </p>

            <p class="mt-1 text-2xl font-bold text-sky-800 dark:text-sky-100">
                {{ $summary['shepherding_events'] }}
            </p>
        </div>

        <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-900 dark:bg-primary-950">
            <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">
                Total Events
            </p>

            <p class="mt-1 text-2xl font-bold text-primary-800 dark:text-primary-100">
                {{ $summary['total_events'] }}
            </p>
        </div>
    </div>

    <div
        x-data="{ selectedActivityDate: null }"
        class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-950"
    >
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="font-bold text-gray-900 dark:text-white">
                    Last 365 Days
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $from->format('M d, Y') }}
                    –
                    {{ $to->format('M d, Y') }}
                </p>
            </div>

            <div>
                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span>Less</span>

                    <span class="person-activity-cell person-activity-level-0"></span>
                    <span class="person-activity-cell person-activity-level-1"></span>
                    <span class="person-activity-cell person-activity-level-2"></span>
                    <span class="person-activity-cell person-activity-level-3"></span>
                    <span class="person-activity-cell person-activity-level-4"></span>

                    <span>More</span>
                </div>

                <p class="mt-2 text-right text-[11px] text-gray-400 dark:text-gray-500">
                    Hover, focus, or tap an active day
                </p>
            </div>
        </div>

        <div class="mt-5 flex items-start gap-3">
            {{-- ============================================
                 WEEKDAY / LORD'S DAY LABELS
            ============================================= --}}
            <div
                class="grid w-7 shrink-0 gap-[0.2rem] text-center text-[10px] font-semibold"
                style="grid-template-rows: repeat(7, 0.8rem);"
            >
                <span
                    title="Lord's Day"
                    class="flex h-3 items-center justify-center font-bold text-amber-700 dark:text-amber-300"
                >
                    LD
                </span>

                <span
                    title="Monday"
                    class="flex h-3 items-center justify-center text-gray-500 dark:text-gray-400"
                >
                    M
                </span>

                <span
                    title="Tuesday"
                    class="flex h-3 items-center justify-center text-gray-500 dark:text-gray-400"
                >
                    T
                </span>

                <span
                    title="Wednesday"
                    class="flex h-3 items-center justify-center text-gray-500 dark:text-gray-400"
                >
                    W
                </span>

                <span
                    title="Thursday"
                    class="flex h-3 items-center justify-center text-gray-500 dark:text-gray-400"
                >
                    Th
                </span>

                <span
                    title="Friday"
                    class="flex h-3 items-center justify-center text-gray-500 dark:text-gray-400"
                >
                    F
                </span>

                <span
                    title="Saturday"
                    class="flex h-3 items-center justify-center text-gray-500 dark:text-gray-400"
                >
                    S
                </span>
            </div>

            {{-- ============================================
                 HEATMAP GRID
            ============================================= --}}
            <div class="min-w-0 flex-1 overflow-x-auto pb-2">
                <div class="min-w-max">
                    <div
                        style="
                            display: grid;
                            grid-auto-flow: column;
                            grid-template-rows: repeat(7, 0.8rem);
                            grid-auto-columns: 0.8rem;
                            gap: 0.2rem;
                        "
                    >
                        @foreach ($gridDays as $cell)
                            @php
                                $day =
                                    $cell['day'];

                                $intensity =
                                    (int) (
                                        $day['intensity']
                                        ?? 0
                                    );

                                $tone =
                                    match (true) {
                                        ! $cell['in_range'] =>
                                            'person-activity-outside',

                                        $intensity === 0 =>
                                            'person-activity-level-0',

                                        $intensity === 1 =>
                                            'person-activity-level-1',

                                        $intensity === 2 =>
                                            'person-activity-level-2',

                                        $intensity === 3 =>
                                            'person-activity-level-3',

                                        default =>
                                            'person-activity-level-4',
                                    };

                                $categories =
                                    collect(
                                        $day['categories']
                                        ?? []
                                    )
                                        ->implode(', ');

                                $dateObject =
                                    \Carbon\CarbonImmutable::parse(
                                        $cell['date']
                                    );

                                $dayName =
                                    $dateObject->dayOfWeek
                                    ===
                                    \Carbon\CarbonInterface::SUNDAY
                                        ? "Lord's Day"
                                        : $dateObject->format('l');

                                $title =
                                    $dayName
                                    . ', '
                                    . $dateObject->format(
                                        'M d, Y'
                                    );

                                if ($cell['in_range']) {
                                    $title .=
                                        ' — '
                                        . $intensity
                                        . ' activity event'
                                        . (
                                            $intensity === 1
                                                ? ''
                                                : 's'
                                        );

                                    if ($categories !== '') {
                                        $title .=
                                            ' — '
                                            . $categories;
                                    }
                                }
                            @endphp

                            @if (
                                $cell['in_range']
                                &&
                                $intensity > 0
                            )
                                <button
                                    type="button"
                                    title="{{ $title }}"
                                    aria-label="{{ $title }}"
                                    @mouseenter="selectedActivityDate = '{{ $cell['date'] }}'"
                                    @focus="selectedActivityDate = '{{ $cell['date'] }}'"
                                    @click="selectedActivityDate = '{{ $cell['date'] }}'"
                                    class="person-activity-cell {{ $tone }}"
                                ></button>
                            @else
                                <span
                                    @if ($cell['in_range'])
                                        title="{{ $title }}"
                                        aria-label="{{ $title }}"
                                    @endif
                                    class="person-activity-cell {{ $tone }}"
                                ></span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
            <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                LD = Lord's Day
            </span>

            <span class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                1 square = 1 calendar day
            </span>

            <span class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                Intensity = canonical events
            </span>
        </div>

        {{-- ================================================
             INTERACTIVE DAY DETAILS

             Hover on desktop.
             Keyboard focus works.
             Tap/click works on touch devices.
        ================================================= --}}
        <div class="mt-5 rounded-xl border border-gray-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <div
                x-show="selectedActivityDate === null"
                class="py-3 text-center text-sm text-gray-500 dark:text-gray-400"
            >
                Hover, focus, or tap an active heatmap day to inspect its
                Attendance and Shepherding history.
            </div>

            @foreach (
                $interactiveDays
                as $date => $dayDetail
            )
                <div
                    x-cloak
                    x-show="selectedActivityDate === '{{ $date }}'"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ $dayDetail['date_label'] }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">
                                @if (
                                    $dayDetail['attendance_count']
                                    > 0
                                )
                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-800 dark:bg-sky-900 dark:text-sky-100">
                                        Attendance:
                                        {{ $dayDetail['attendance_count'] }}
                                    </span>
                                @endif

                                @if (
                                    $dayDetail['shepherding_count']
                                    > 0
                                )
                                    <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100">
                                        Shepherding:
                                        {{ $dayDetail['shepherding_count'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="selectedActivityDate = null"
                            class="self-start rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Clear
                        </button>
                    </div>

                    <div class="mt-4 space-y-2">
                        @foreach (
                            $dayDetail['events']
                            as $event
                        )
                            @php
                                $sourceTone =
                                    $event['source']
                                    === 'attendance'
                                        ? 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-100'
                                        : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100';
                            @endphp

                            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-950">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold text-gray-900 dark:text-white">
                                                {{ $event['label'] }}
                                            </span>

                                            <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $sourceTone }}">
                                                {{ $event['source_label'] }}
                                            </span>
                                        </div>

                                        @if (
                                            filled(
                                                $event['meta']
                                            )
                                        )
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $event['meta'] }}
                                            </p>
                                        @endif

                                        @if (
                                            ! empty(
                                                $event['categories']
                                            )
                                            ||
                                            ! empty(
                                                $event['roles']
                                            )
                                        )
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @foreach (
                                                    $event['categories']
                                                    as $category
                                                )
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                        {{ $category }}
                                                    </span>
                                                @endforeach

                                                @foreach (
                                                    $event['roles']
                                                    as $role
                                                )
                                                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200">
                                                        {{ $role }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    @if (
                                        filled(
                                            $event['url']
                                        )
                                    )
                                        <a
                                            href="{{ $event['url'] }}"
                                            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-primary-500"
                                        >
                                            @if (
                                                $event['source']
                                                === 'attendance'
                                            )
                                                Open Attendance
                                            @else
                                                Open History
                                            @endif
                                            →
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-950">
        <div class="flex items-center justify-between gap-3">
            <p class="font-bold text-gray-900 dark:text-white">
                Recent Activity
            </p>

            <span class="text-xs text-gray-500 dark:text-gray-400">
                Latest 10 canonical events
            </span>
        </div>

        @if ($recentEvents->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                No activity recorded in this period.
            </div>
        @else
            <div class="mt-4 space-y-2">
                @foreach ($recentEvents as $event)
                    @php
                        $details =
                            $event['details']
                            ?? [];

                        $eventDate =
                            \Carbon\CarbonImmutable::parse(
                                $event['date']
                            );

                        $sourceLabel =
                            $event['source']
                            === 'attendance'
                                ? 'Attendance'
                                : 'Shepherding';
                    @endphp

                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ $event['label'] }}
                                    </span>

                                    <span
                                        @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-bold',

                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100'
                                                => $event['source'] === 'attendance',

                                            'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-100'
                                                => $event['source'] === 'shepherding',
                                        ])
                                    >
                                        {{ $sourceLabel }}
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($event['categories'] ?? [] as $category)
                                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            {{ $category }}
                                        </span>
                                    @endforeach

                                    @if (
                                        $event['source'] === 'attendance'
                                        &&
                                        filled(
                                            $details['attendance_source']
                                            ?? null
                                        )
                                    )
                                        <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-semibold text-violet-700 dark:bg-violet-900 dark:text-violet-200">
                                            {{
                                                ucfirst(
                                                    $details['attendance_source']
                                                )
                                            }}
                                        </span>
                                    @endif

                                    @if (
                                        $event['source'] === 'attendance'
                                        &&
                                        (
                                            $details['prophesied']
                                            ?? false
                                        )
                                    )
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                                            Prophesied
                                        </span>
                                    @endif

                                    @foreach (
                                        $details['roles']
                                        ?? []
                                        as $role
                                    )
                                        <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-900 dark:text-sky-200">
                                            {{ $role }}
                                        </span>
                                    @endforeach
                                </div>

                                @if (
                                    filled(
                                        $details['locality']
                                        ?? null
                                    )
                                )
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{
                                            $details['locality']
                                        }}
                                    </p>
                                @endif
                            </div>

                            <span class="shrink-0 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                {{
                                    $eventDate->format(
                                        'M d, Y'
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
