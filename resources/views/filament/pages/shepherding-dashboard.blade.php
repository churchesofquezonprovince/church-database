<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header / Locality Filter --}}
        <div
            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-4 md:flex-row
                       md:items-end md:justify-between"
            >
                <div>
                    <h2
                        class="text-xl font-bold text-gray-900
                               dark:text-white"
                    >
                        Shepherding & GOW Dashboard
                    </h2>

                    <p
                        class="mt-1 text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Weekly Gospel/Shepherding work,
                        contact follow-up, Campus Work,
                        and people needing care.
                    </p>
                </div>

                <div class="w-full md:max-w-sm">
                    <label
                        for="locality"
                        class="mb-2 block text-sm font-medium
                               text-gray-700 dark:text-gray-200"
                    >
                        Locality
                    </label>

                    <select
                        id="locality"
                        wire:model.live="locality"
                        class="block w-full rounded-lg
                               border-gray-300 shadow-sm
                               focus:border-primary-500
                               focus:ring-primary-500
                               dark:border-gray-700
                               dark:bg-gray-800
                               dark:text-white"
                    >
                        <option value="">
                            All Localities
                        </option>

                        @foreach ($localities as $localityOption)
                            <option value="{{ $localityOption }}">
                                {{ $localityOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div
                class="mt-4 rounded-xl bg-gray-50 px-4 py-3
                       text-sm text-gray-600
                       dark:bg-gray-800 dark:text-gray-300"
            >
                Showing:
                <span
                    class="font-semibold text-gray-900
                           dark:text-white"
                >
                    {{ filled($locality) ? $locality : 'All Localities' }}
                </span>

                · Monthly sections:
                {{ now()->format('F Y') }}

                · Weekly GOW:
                {{ $weeklyGow['period'] }}
            </div>
        </div>

        {{-- Weekly GOW Summary --}}
        <section class="space-y-6">
            <div
                class="flex flex-col gap-4
                       lg:flex-row lg:items-end
                       lg:justify-between"
            >
                <div>
                    <h3
                        class="text-xl font-bold
                               text-gray-900 dark:text-white"
                    >
                        Weekly GOW Summary
                    </h3>

                    <p
                        class="mt-1 text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Gospel Work, Shepherding, and Campus Work
                        during {{ $weeklyGow['period'] }}.
                    </p>
                </div>

                <div
                    class="flex flex-wrap items-center gap-2"
                >
                    <a
                        href="{{ $this->weeklyGowUrl(
                            $weeklyGow['previous_week']
                        ) }}"
                        class="inline-flex items-center
                               rounded-lg border border-gray-300
                               bg-white px-3 py-2 text-sm
                               font-medium text-gray-700
                               transition hover:bg-gray-50
                               dark:border-gray-700
                               dark:bg-gray-900
                               dark:text-gray-200
                               dark:hover:bg-gray-800"
                    >
                        ← Previous Week
                    </a>

                    <a
                        href="{{ $this->weeklyGowUrl(
                            $weeklyGow['current_week']
                        ) }}"
                        class="inline-flex items-center
                               rounded-lg border px-3 py-2
                               text-sm font-medium transition
                               {{ $weeklyGow['is_current_week']
                                    ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-300'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' }}"
                    >
                        Current Week
                    </a>

                    <a
                        href="{{ $this->weeklyGowUrl(
                            $weeklyGow['next_week']
                        ) }}"
                        class="inline-flex items-center
                               rounded-lg border border-gray-300
                               bg-white px-3 py-2 text-sm
                               font-medium text-gray-700
                               transition hover:bg-gray-50
                               dark:border-gray-700
                               dark:bg-gray-900
                               dark:text-gray-200
                               dark:hover:bg-gray-800"
                    >
                        Next Week →
                    </a>
                </div>
            </div>

            @php
                $shepherdingWeekCopyText =
                    $this->shepherdingThisWeekCopyText();
            @endphp

            <div
                x-data="{ copied: false }"
                class="flex flex-wrap items-center gap-x-4 gap-y-3"
            >
                <button
                    type="button"
                    @disabled(blank($shepherdingWeekCopyText))
                    x-on:click="
                        const text =
                            @js($shepherdingWeekCopyText);

                        if (! text) {
                            return;
                        }

                        navigator.clipboard
                            .writeText(text)
                            .then(() => {
                                copied = true;

                                setTimeout(
                                    () => copied = false,
                                    1800
                                );
                            });
                    "
                    class="inline-flex items-center rounded-lg
                           bg-primary-600 px-4 py-2
                           text-sm font-semibold text-white
                           transition hover:bg-primary-500
                           disabled:cursor-not-allowed
                           disabled:opacity-50"
                >
                    <span x-show="! copied">
                        Copy Shepherding This Week
                    </span>

                    <span
                        x-show="copied"
                        x-cloak
                    >
                        Copied!
                    </span>
                </button>

                <label
                    class="inline-flex cursor-pointer
                           items-center gap-2 text-sm
                           text-gray-700
                           dark:text-gray-200"
                >
                    <input
                        type="checkbox"
                        wire:model.live="copyAddServingOnes"
                        class="rounded border-gray-300
                               text-primary-600
                               focus:ring-primary-500
                               dark:border-gray-600
                               dark:bg-gray-900"
                    >

                    <span>
                        Add Serving Ones
                    </span>
                </label>

                <label
                    class="inline-flex cursor-pointer
                           items-center gap-2 text-sm
                           text-gray-700
                           dark:text-gray-200"
                >
                    <input
                        type="checkbox"
                        wire:model.live="copySoNickname"
                        @disabled(! $copyAddServingOnes)
                        class="rounded border-gray-300
                               text-primary-600
                               focus:ring-primary-500
                               disabled:cursor-not-allowed
                               disabled:opacity-50
                               dark:border-gray-600
                               dark:bg-gray-900"
                    >

                    <span>
                        SO Nickname
                    </span>
                </label>

                <label
                    class="inline-flex cursor-pointer
                           items-center gap-2 text-sm
                           text-gray-700
                           dark:text-gray-200"
                >
                    <input
                        type="checkbox"
                        wire:model.live="copyContactNickname"
                        class="rounded border-gray-300
                               text-primary-600
                               focus:ring-primary-500
                               dark:border-gray-600
                               dark:bg-gray-900"
                    >

                    <span>
                        Contact Nickname
                    </span>
                </label>

                @if (blank($shepherdingWeekCopyText))
                    <span
                        class="text-xs text-gray-500
                               dark:text-gray-400"
                    >
                        No Shepherding History for the selected week.
                    </span>
                @endif
            </div>


            {{-- Gospel Work --}}
            <div>
                <div class="mb-3">
                    <h4
                        class="text-lg font-semibold
                               text-gray-900 dark:text-white"
                    >
                        Gospel Work
                    </h4>

                    <p
                        class="text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        New Gospel Contacts and Gospel Work
                        contact records during the selected week.
                    </p>
                </div>

                <div
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <div
                        class="rounded-2xl border border-gray-200
                               bg-white p-5 shadow-sm
                               dark:border-gray-700
                               dark:bg-gray-900"
                    >
                        <p
                            class="text-sm font-medium
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            New Gospel Contacts
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold
                                   text-gray-900 dark:text-white"
                        >
                            {{ $weeklyGow['gospel']['new_contacts'] }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-gray-200
                               bg-white p-5 shadow-sm
                               dark:border-gray-700
                               dark:bg-gray-900"
                    >
                        <p
                            class="text-sm font-medium
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Gospel Work Contacts
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold
                                   text-gray-900 dark:text-white"
                        >
                            {{ $weeklyGow['gospel']['work_contacts'] }}
                        </p>
                    </div>
                </div>
            </div>


            {{-- Shepherding --}}
            <div>
                <div class="mb-3">
                    <h4
                        class="text-lg font-semibold
                               text-gray-900 dark:text-white"
                    >
                        Shepherding
                    </h4>

                    <p
                        class="text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Weekly Shepherding Records and unique
                        contacted identities.
                    </p>
                </div>

                @php
                    $weeklyShepherdingCards = [
                        'records' =>
                            'Shepherding Records',

                        'people' =>
                            'People Contacted',

                        'households' =>
                            'Households Contacted',

                        'campus_contacts' =>
                            'Campus Contacts',

                        'gospel_contacts' =>
                            'Gospel Contacts',
                    ];
                @endphp

                <div
                    class="grid gap-4 sm:grid-cols-2
                           xl:grid-cols-5"
                >
                    @foreach (
                        $weeklyShepherdingCards
                        as $key => $label
                    )
                        <div
                            class="rounded-2xl border
                                   border-gray-200 bg-white
                                   p-5 shadow-sm
                                   dark:border-gray-700
                                   dark:bg-gray-900"
                        >
                            <p
                                class="text-sm font-medium
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                {{ $label }}
                            </p>

                            <p
                                class="mt-2 text-3xl font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                {{ $weeklyGow['shepherding'][$key] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>


            {{-- Campus Work --}}
            <div>
                <div class="mb-3">
                    <h4
                        class="text-lg font-semibold
                               text-gray-900 dark:text-white"
                    >
                        Campus Work
                    </h4>

                    <p
                        class="text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Campus Activities and physical Attendance
                        during the selected week.
                    </p>
                </div>

                @php
                    $weeklyCampusCards = [
                        'activities' =>
                            'Campus Activities',

                        'sessions' =>
                            'Attendance Sessions',

                        'present_marks' =>
                            'Present Marks',

                        'unique_people_present' =>
                            'Unique People Present',
                    ];
                @endphp

                <div
                    class="grid gap-4 sm:grid-cols-2
                           xl:grid-cols-4"
                >
                    @foreach (
                        $weeklyCampusCards
                        as $key => $label
                    )
                        <div
                            class="rounded-2xl border
                                   border-gray-200 bg-white
                                   p-5 shadow-sm
                                   dark:border-gray-700
                                   dark:bg-gray-900"
                        >
                            <p
                                class="text-sm font-medium
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                {{ $label }}
                            </p>

                            <p
                                class="mt-2 text-3xl font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                {{ $weeklyGow['campus'][$key] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>


        {{-- Recent Shepherding + Activity Summary --}}
        <div class="grid gap-6 xl:grid-cols-3">

            <div
                class="rounded-2xl border border-gray-200
                       bg-white p-5 shadow-sm
                       dark:border-gray-700 dark:bg-gray-900
                       xl:col-span-2"
            >
                <div
                    class="flex items-center justify-between gap-4"
                >
                    <div>
                        <h3
                            class="text-lg font-semibold
                                   text-gray-900 dark:text-white"
                        >
                            Recent Shepherding
                        </h3>

                        <p
                            class="text-sm text-gray-500
                                   dark:text-gray-400"
                        >
                            Latest Shepherding Records.
                        </p>
                    </div>

                    <a
                        href="{{ \App\Filament\Pages\ShepherdingHistory::getUrl() }}"
                        class="text-sm font-medium text-primary-600
                               hover:text-primary-500
                               dark:text-primary-400"
                    >
                        View history →
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($recentShepherding as $record)
                        @php
                            $targets = collect(
                                $record['targets']
                            )
                                ->flatten()
                                ->filter()
                                ->values();

                            $activityCodes =
                                collect(
                                    $record['activity_codes']
                                    ?? []
                                )
                                    ->filter()
                                    ->values();

                            $ministryUsed =
                                collect(
                                    $record['ministry']
                                    ?? []
                                )
                                    ->filter(
                                        fn ($row) =>
                                            filled(
                                                $row[
                                                    'lesson_code'
                                                ]
                                                ?? null
                                            )
                                    )
                                    ->values();
                        @endphp

                        <div
                            class="rounded-xl border border-gray-200
                                   p-4 dark:border-gray-700"
                        >
                            <div
                                class="flex flex-col gap-3
                                       md:flex-row
                                       md:items-start
                                       md:justify-between"
                            >
                                <div>
                                    <p
                                        class="font-semibold
                                               text-gray-900
                                               dark:text-white"
                                    >
                                        {{ $targets->isNotEmpty()
                                            ? $targets->join(', ')
                                            : 'No target recorded' }}
                                    </p>

                                    <p
                                        class="mt-1 text-sm
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        {{ $record['date'] }}

                                        @if (filled($record['time']))
                                            · {{ $record['time'] }}
                                        @endif

                                        ·
                                        {{ $record['locality']
                                            ?? 'No Locality' }}
                                    </p>
                                </div>

                                <span
                                    class="inline-flex self-start
                                           rounded-full bg-gray-100
                                           px-3 py-1 text-xs
                                           font-medium text-gray-700
                                           dark:bg-gray-800
                                           dark:text-gray-300"
                                >
                                    {{ $record['outcome']
                                        ?? 'No outcome' }}
                                </span>
                            </div>

                            <div class="mt-3">
                                <a
                                    href="{{ $record['url'] }}"
                                    class="text-xs font-semibold
                                           text-primary-600
                                           hover:text-primary-500
                                           dark:text-primary-400"
                                >
                                    Open record →
                                </a>
                            </div>

                            @if ($activityCodes->isNotEmpty())
                                <div
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    @foreach (
                                        $activityCodes
                                        as $code
                                    )
                                        <span
                                            class="rounded-md
                                                   bg-primary-50
                                                   px-2 py-1
                                                   text-xs font-semibold
                                                   text-primary-700
                                                   dark:bg-primary-950
                                                   dark:text-primary-300"
                                        >
                                            {{ $code }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($ministryUsed->isNotEmpty())
                                <div class="mt-3">
                                    <p
                                        class="text-xs font-semibold
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Ministry Used
                                    </p>

                                    <div
                                        class="mt-1
                                               space-y-1"
                                    >
                                        @foreach (
                                            $ministryUsed
                                            as $ministry
                                        )
                                            <p
                                                class="text-xs
                                                       text-gray-600
                                                       dark:text-gray-300"
                                            >
                                                <strong
                                                    class="font-mono
                                                           text-violet-700
                                                           dark:text-violet-300"
                                                >
                                                    {{
                                                        $ministry[
                                                            'lesson_code'
                                                        ]
                                                    }}
                                                </strong>

                                                @if (
                                                    filled(
                                                        $ministry[
                                                            'lesson_title'
                                                        ]
                                                        ?? null
                                                    )
                                                )
                                                    ·
                                                    {{
                                                        $ministry[
                                                            'lesson_title'
                                                        ]
                                                    }}
                                                @endif
                                            </p>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed
                                   border-gray-300 px-5 py-10
                                   text-center
                                   dark:border-gray-700"
                        >
                            <p
                                class="font-medium text-gray-700
                                       dark:text-gray-300"
                            >
                                No Shepherding Records yet.
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-500
                                       dark:text-gray-400"
                            >
                                Recorded visits, prayer, fellowship,
                                ministry use, and other activities
                                will appear here.
                            </p>

                            <a
                                href="{{ \App\Filament\Pages\ShepherdingHistory::getUrl() }}"
                                class="mt-4 inline-flex text-sm
                                       font-medium text-primary-600
                                       hover:text-primary-500
                                       dark:text-primary-400"
                            >
                                Open Shepherding History →
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <div
                class="rounded-2xl border border-gray-200
                       bg-white p-5 shadow-sm
                       dark:border-gray-700 dark:bg-gray-900"
            >
                <h3
                    class="text-lg font-semibold text-gray-900
                           dark:text-white"
                >
                    Activities This Month
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    All active Shepherding and Gospel Work
                    activity codes recorded this month.
                </p>

                <div class="mt-4 space-y-2">
                    @foreach ($activitySummary as $activity)
                        <a
                            href="{{ $activity['url'] }}"
                            class="flex items-center justify-between
                                   rounded-xl bg-gray-50 px-3 py-2
                                   transition hover:bg-gray-100
                                   dark:bg-gray-800
                                   dark:hover:bg-gray-700"
                        >
                            <div class="min-w-0">
                                <span
                                    class="font-semibold
                                           text-primary-700
                                           dark:text-primary-300"
                                >
                                    {{ $activity['code'] }}
                                </span>

                                <span
                                    class="ml-2 text-sm
                                           text-gray-600
                                           dark:text-gray-300"
                                >
                                    {{ $activity['name'] }}
                                </span>
                            </div>

                            <div
                                class="ml-3 flex items-center gap-2"
                            >
                                <span
                                    class="font-bold text-gray-900
                                           dark:text-white"
                                >
                                    {{ $activity['count'] }}
                                </span>

                                <span
                                    class="text-xs text-gray-400"
                                >
                                    →
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div
                    class="my-5 border-t border-gray-200
                           dark:border-gray-700"
                ></div>

                <h4
                    class="text-base font-semibold
                           text-gray-900 dark:text-white"
                >
                    Ministry Used This Month
                </h4>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Distinct Shepherding Records that used
                    lessons from each ministry book. Used books
                    can be expanded to show the topics covered.
                </p>

                <div class="mt-4 space-y-2">
                    @foreach ($ministrySummary as $book)
                        @php
                            $usedTopics =
                                collect(
                                    $book['topics'] ?? []
                                );
                        @endphp

                        @if ($usedTopics->isNotEmpty())
                            <details
                                class="overflow-hidden
                                       rounded-xl
                                       bg-gray-50
                                       dark:bg-gray-800"
                            >
                                <summary
                                    class="cursor-pointer
                                           list-none px-3 py-2"
                                >
                                    <div
                                        class="flex items-center
                                               justify-between"
                                    >
                                        <div class="min-w-0">
                                            <span
                                                class="font-semibold
                                                       text-primary-700
                                                       dark:text-primary-300"
                                            >
                                                {{ $book['code'] }}
                                            </span>

                                            <span
                                                class="ml-2 text-sm
                                                       text-gray-600
                                                       dark:text-gray-300"
                                            >
                                                {{ $book['title'] }}
                                            </span>
                                        </div>

                                        <div
                                            class="ml-3 flex
                                                   items-center gap-2"
                                        >
                                            <span
                                                class="font-bold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $book['count'] }}
                                            </span>

                                            <span
                                                class="text-xs
                                                       text-gray-400"
                                            >
                                                ▾
                                            </span>
                                        </div>
                                    </div>
                                </summary>

                                <div
                                    class="border-t
                                           border-gray-200
                                           dark:border-gray-700"
                                >
                                    @foreach ($usedTopics as $topic)
                                        <div
                                            class="flex items-start
                                                   justify-between
                                                   gap-3 px-4 py-3
                                                   text-sm
                                                   border-b
                                                   border-gray-200
                                                   last:border-b-0
                                                   dark:border-gray-700"
                                        >
                                            <div class="min-w-0">
                                                <span
                                                    class="font-semibold
                                                           text-gray-800
                                                           dark:text-gray-200"
                                                >
                                                    {{ $topic['code'] }}
                                                </span>

                                                <span
                                                    class="ml-2
                                                           text-gray-600
                                                           dark:text-gray-300"
                                                >
                                                    {{ $topic['title'] }}
                                                </span>
                                            </div>

                                            <span
                                                class="shrink-0
                                                       font-bold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $topic['count'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @else
                            <div
                                class="flex items-center
                                       justify-between
                                       rounded-xl bg-gray-50
                                       px-3 py-2
                                       dark:bg-gray-800"
                            >
                                <div class="min-w-0">
                                    <span
                                        class="font-semibold
                                               text-primary-700
                                               dark:text-primary-300"
                                    >
                                        {{ $book['code'] }}
                                    </span>

                                    <span
                                        class="ml-2 text-sm
                                               text-gray-600
                                               dark:text-gray-300"
                                    >
                                        {{ $book['title'] }}
                                    </span>
                                </div>

                                <span
                                    class="ml-3 font-bold
                                           text-gray-900
                                           dark:text-white"
                                >
                                    {{ $book['count'] }}
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Needs Follow-up --}}
        <div
            class="rounded-2xl border border-amber-200
                   bg-white p-5 shadow-sm
                   dark:border-amber-900 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-3
                       sm:flex-row sm:items-start
                       sm:justify-between"
            >
                <div>
                    <h3
                        class="text-lg font-semibold text-gray-900
                               dark:text-white"
                    >
                        Needs Follow-up
                    </h3>

                    <p
                        class="text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Out / Unavailable, Reschedule, and Declined
                        Shepherding Records during
                        {{ now()->format('F Y') }}.
                    </p>
                </div>

                <a
                    href="{{ $this->followUpHistoryUrl() }}"
                    class="shrink-0 text-sm font-medium
                           text-amber-700 hover:text-amber-600
                           dark:text-amber-300
                           dark:hover:text-amber-200"
                >
                    View follow-up records →
                </a>
            </div>

            {{-- Outcome Summary --}}
            <div
                class="mt-4 grid gap-3
                       sm:grid-cols-3"
            >
                @foreach ($followUpSummary as $key => $summary)
                    @php
                        $tone = match ($key) {
                            'unavailable' =>
                                'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',

                            'reschedule' =>
                                'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200',

                            'declined' =>
                                'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200',

                            default =>
                                'border-gray-200 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200',
                        };
                    @endphp

                    <div
                        class="rounded-xl border px-4 py-3
                               {{ $tone }}"
                    >
                        <p class="text-xs font-medium opacity-80">
                            {{ $summary['label'] }}
                        </p>

                        <p class="mt-1 text-2xl font-bold">
                            {{ $summary['count'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Follow-up Records --}}
            <div class="mt-5 space-y-3">
                @forelse ($followUpItems as $record)
                    @php
                        $targetNames = collect(
                            $record['targets']
                        )
                            ->flatten()
                            ->filter()
                            ->values();

                        $codes = collect(
                            $record['shepherding_codes']
                        )
                            ->filter()
                            ->values();
                    @endphp

                    <div
                        class="rounded-xl border border-gray-200
                               p-4 dark:border-gray-700"
                    >
                        <div
                            class="flex flex-col gap-3
                                   md:flex-row md:items-start
                                   md:justify-between"
                        >
                            <div class="min-w-0">
                                <p
                                    class="font-semibold text-gray-900
                                           dark:text-white"
                                >
                                    {{ $targetNames->isNotEmpty()
                                        ? $targetNames->join(', ')
                                        : 'No target recorded' }}
                                </p>

                                <p
                                    class="mt-1 text-sm text-gray-500
                                           dark:text-gray-400"
                                >
                                    {{ $record['date'] }}

                                    @if (filled($record['time']))
                                        · {{ $record['time'] }}
                                    @endif

                                    ·
                                    {{ $record['locality']
                                        ?? 'No Locality' }}
                                </p>
                            </div>

                            <span
                                class="inline-flex self-start
                                       rounded-full bg-amber-100
                                       px-3 py-1 text-xs font-semibold
                                       text-amber-800
                                       dark:bg-amber-950
                                       dark:text-amber-200"
                            >
                                {{ $record['outcome'] }}
                            </span>
                        </div>

                        @if ($codes->isNotEmpty())
                            <div
                                class="mt-3 flex flex-wrap gap-2"
                            >
                                @foreach ($codes as $code)
                                    <span
                                        class="rounded-lg bg-gray-100
                                               px-2.5 py-1 text-xs
                                               font-semibold text-gray-700
                                               dark:bg-gray-800
                                               dark:text-gray-300"
                                    >
                                        {{ $code }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if (filled($record['notes']))
                            <p
                                class="mt-3 text-sm text-gray-600
                                       dark:text-gray-300"
                            >
                                {{ \Illuminate\Support\Str::limit(
                                    $record['notes'],
                                    180
                                ) }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed
                               border-gray-300 px-5 py-8
                               text-center
                               dark:border-gray-700"
                    >
                        <p
                            class="font-medium text-gray-700
                                   dark:text-gray-300"
                        >
                            No follow-up outcomes this month.
                        </p>

                        <p
                            class="mt-1 text-sm text-gray-500
                                   dark:text-gray-400"
                        >
                            Out / Unavailable, Reschedule, and
                            Declined records will appear here.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Locality Activity --}}
        <div
            class="rounded-2xl border border-gray-200
                   bg-white p-5 shadow-sm
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <h3
                class="text-lg font-semibold text-gray-900
                       dark:text-white"
            >
                Shepherding by Locality
            </h3>

            <p
                class="text-sm text-gray-500
                       dark:text-gray-400"
            >
                Shepherding Records during {{ now()->format('F Y') }}.
            </p>

            <div class="mt-4">
                @forelse ($localitySummary as $row)
                    @if (filled($row['url']))
                        <a
                            href="{{ $row['url'] }}"
                            class="flex items-center justify-between
                                   border-b border-gray-100 py-3
                                   transition hover:bg-gray-50
                                   last:border-b-0
                                   dark:border-gray-800
                                   dark:hover:bg-gray-800"
                        >
                            <span
                                class="font-medium text-gray-800
                                       dark:text-gray-200"
                            >
                                {{ $row['locality'] }}
                            </span>

                            <div
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="rounded-full bg-gray-100
                                           px-3 py-1 text-sm font-semibold
                                           text-gray-700
                                           dark:bg-gray-800
                                           dark:text-gray-300"
                                >
                                    {{ $row['count'] }}
                                </span>

                                <span class="text-xs text-gray-400">
                                    →
                                </span>
                            </div>
                        </a>
                    @else
                        <div
                            class="flex items-center justify-between
                                   border-b border-gray-100 py-3
                                   last:border-b-0
                                   dark:border-gray-800"
                        >
                            <span
                                class="font-medium text-gray-800
                                       dark:text-gray-200"
                            >
                                {{ $row['locality'] }}
                            </span>

                            <span
                                class="rounded-full bg-gray-100
                                       px-3 py-1 text-sm font-semibold
                                       text-gray-700
                                       dark:bg-gray-800
                                       dark:text-gray-300"
                            >
                                {{ $row['count'] }}
                            </span>
                        </div>
                    @endif
                @empty
                    <p
                        class="py-6 text-center text-sm
                               text-gray-500 dark:text-gray-400"
                    >
                        No Shepherding activity recorded this month.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Weekly GOW Source Records --}}
        @php
            $gowSources =
                $weeklyGow['sources'];
        @endphp

        <section class="space-y-3">
            <div>
                <h3
                    class="text-xl font-bold
                           text-gray-900 dark:text-white"
                >
                    GOW Source Records
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Campus Work, Gospel Work, and Shepherding
                    records behind the selected week's totals.
                    Open a row to continue in its owning module.
                </p>
            </div>


            {{-- Campus Work Sources --}}
            <details
                class="overflow-hidden rounded-2xl border
                       border-gray-200 bg-white
                       dark:border-gray-700
                       dark:bg-gray-900"
            >
                <summary
                    class="cursor-pointer px-5 py-4
                           text-sm font-bold text-gray-900
                           dark:text-gray-100"
                >
                    Campus Work Sources
                    · {{ count($gowSources['campus_activities']) }}
                    activities
                    · {{ count($gowSources['campus_sessions']) }}
                    sessions
                </summary>

                <div
                    class="border-t border-gray-200
                           bg-white
                           dark:border-gray-700
                           dark:bg-gray-900"
                >
                    <div
                        class="px-5 py-3 text-xs font-bold
                               uppercase tracking-wide
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Campus Activities
                    </div>

                    @forelse (
                        $gowSources['campus_activities']
                        as $row
                    )
                        <a
                            href="{{ $this->campusActivitiesUrl() }}"
                            class="block border-t
                                   border-gray-100 bg-white
                                   px-5 py-4 text-sm
                                   text-gray-900 transition
                                   hover:bg-gray-50
                                   dark:border-gray-800
                                   dark:bg-gray-900
                                   dark:text-gray-100
                                   dark:hover:bg-gray-800"
                        >
                            <strong>
                                {{ $row['date'] }}
                            </strong>

                            · {{ $row['title'] }}

                            @if (filled($row['time']))
                                · {{ $row['time'] }}
                            @endif

                            @if (filled($row['school']))
                                · {{ $row['school'] }}
                            @endif
                        </a>
                    @empty
                        <div
                            class="px-5 py-4 text-sm
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            No Campus Activities
                            during this week.
                        </div>
                    @endforelse

                    <div
                        class="border-t border-gray-200
                               px-5 py-3 text-xs font-bold
                               uppercase tracking-wide
                               text-gray-500
                               dark:border-gray-700
                               dark:text-gray-400"
                    >
                        Attendance Sessions
                    </div>

                    @forelse (
                        $gowSources['campus_sessions']
                        as $row
                    )
                        <a
                            href="{{ $this->campusSessionUrl(
                                $row['sheet_id'],
                                $row['id']
                            ) }}"
                            class="flex flex-wrap items-center
                                   justify-between gap-3
                                   border-t border-gray-100
                                   bg-white px-5 py-4
                                   text-sm text-gray-900
                                   transition hover:bg-gray-50
                                   dark:border-gray-800
                                   dark:bg-gray-900
                                   dark:text-gray-100
                                   dark:hover:bg-gray-800"
                        >
                            <div>
                                <strong>
                                    {{ $row['date'] }}
                                </strong>

                                @if (filled($row['time']))
                                    · {{ $row['time'] }}
                                @endif

                                · {{ $row['title'] }}
                            </div>

                            <div
                                class="font-bold
                                       text-primary-600
                                       dark:text-primary-400"
                            >
                                {{ $row['attendees'] }}
                                attendee{{ $row['attendees'] === 1
                                    ? ''
                                    : 's' }}
                                →
                            </div>
                        </a>
                    @empty
                        <div
                            class="px-5 py-4 text-sm
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            No Campus Attendance Sessions
                            during this week.
                        </div>
                    @endforelse
                </div>
            </details>


            {{-- New Gospel Contacts --}}
            <details
                class="overflow-hidden rounded-2xl border
                       border-gray-200 bg-white
                       dark:border-gray-700
                       dark:bg-gray-900"
            >
                <summary
                    class="cursor-pointer px-5 py-4
                           text-sm font-bold text-gray-900
                           dark:text-gray-100"
                >
                    New Gospel Contacts
                    · {{ count(
                        $gowSources['new_gospel_contacts']
                    ) }}
                </summary>

                <div
                    class="border-t border-gray-200
                           dark:border-gray-700"
                >
                    @forelse (
                        $gowSources['new_gospel_contacts']
                        as $row
                    )
                        <a
                            href="{{ $this->gospelContactsUrl() }}"
                            class="block border-b
                                   border-gray-100 bg-white
                                   px-5 py-4 text-sm
                                   text-gray-900 transition
                                   hover:bg-gray-50
                                   dark:border-gray-800
                                   dark:bg-gray-900
                                   dark:text-gray-100
                                   dark:hover:bg-gray-800"
                        >
                            <strong>
                                {{ $row['name'] }}
                            </strong>

                            @if (filled($row['date']))
                                · {{ $row['date'] }}
                            @endif

                            @if (filled($row['locality']))
                                · {{ $row['locality'] }}
                            @endif

                            @if (filled($row['place']))
                                · {{ $row['place'] }}
                            @endif
                        </a>
                    @empty
                        <div
                            class="px-5 py-4 text-sm
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            No new Gospel Contacts
                            during this week.
                        </div>
                    @endforelse
                </div>
            </details>


            {{-- Gospel Work Contacts --}}
            <details
                class="overflow-hidden rounded-2xl border
                       border-gray-200 bg-white
                       dark:border-gray-700
                       dark:bg-gray-900"
            >
                <summary
                    class="cursor-pointer px-5 py-4
                           text-sm font-bold text-gray-900
                           dark:text-gray-100"
                >
                    Gospel Work Contacts
                    · {{ count(
                        $gowSources['gospel_work_records']
                    ) }}
                </summary>

                <div
                    class="border-t border-gray-200
                           dark:border-gray-700"
                >
                    @forelse (
                        $gowSources['gospel_work_records']
                        as $row
                    )
                        <a
                            href="{{ $this->shepherdingRecordUrl(
                                $row['id']
                            ) }}"
                            class="block border-b
                                   border-gray-100 bg-white
                                   px-5 py-4 text-sm
                                   text-gray-900 transition
                                   hover:bg-gray-50
                                   dark:border-gray-800
                                   dark:bg-gray-900
                                   dark:text-gray-100
                                   dark:hover:bg-gray-800"
                        >
                            <strong>
                                {{ $row['date'] }}
                            </strong>

                            @if (! empty($row['codes']))
                                · {{ implode(
                                    ', ',
                                    $row['codes']
                                ) }}
                            @endif

                            @if (filled($row['outcome']))
                                · {{ $row['outcome'] }}
                            @endif

                            @if (filled($row['locality']))
                                · {{ $row['locality'] }}
                            @endif

                            · {{ $row['identity_count'] }}
                            contacted
                        </a>
                    @empty
                        <div
                            class="px-5 py-4 text-sm
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            No Gospel Work Contacts
                            during this week.
                        </div>
                    @endforelse
                </div>
            </details>


            {{-- Shepherding Records --}}
            <details
                class="overflow-hidden rounded-2xl border
                       border-gray-200 bg-white
                       dark:border-gray-700
                       dark:bg-gray-900"
            >
                <summary
                    class="cursor-pointer px-5 py-4
                           text-sm font-bold text-gray-900
                           dark:text-gray-100"
                >
                    Shepherding Records
                    · {{ count(
                        $gowSources['shepherding_records']
                    ) }}
                </summary>

                <div
                    class="border-t border-gray-200
                           dark:border-gray-700"
                >
                    @forelse (
                        $gowSources['shepherding_records']
                        as $row
                    )
                        <a
                            href="{{ $this->shepherdingRecordUrl(
                                $row['id']
                            ) }}"
                            class="block border-b
                                   border-gray-100 bg-white
                                   px-5 py-4 text-sm
                                   text-gray-900 transition
                                   hover:bg-gray-50
                                   dark:border-gray-800
                                   dark:bg-gray-900
                                   dark:text-gray-100
                                   dark:hover:bg-gray-800"
                        >
                            <strong>
                                {{ $row['date'] }}
                            </strong>

                            @if (! empty($row['codes']))
                                · {{ implode(
                                    ', ',
                                    $row['codes']
                                ) }}
                            @endif

                            @if (filled($row['outcome']))
                                · {{ $row['outcome'] }}
                            @endif

                            @if (filled($row['locality']))
                                · {{ $row['locality'] }}
                            @endif

                            · {{ $row['identity_count'] }}
                            contacted
                        </a>
                    @empty
                        <div
                            class="px-5 py-4 text-sm
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            No Shepherding Records
                            during this week.
                        </div>
                    @endforelse
                </div>
            </details>


            <div
                class="rounded-xl border border-gray-200
                       bg-gray-50 px-4 py-3
                       text-xs text-gray-500
                       dark:border-gray-800
                       dark:bg-gray-950
                       dark:text-gray-400"
            >
                Weekly totals are calculated directly from
                their source records. This dashboard does not
                create duplicate Gospel Work, Shepherding,
                Campus Work, or Attendance records.
            </div>
        </section>


        {{-- People Shepherding Needs --}}
        <div>
            <div class="mb-3">
                <h3
                    class="text-lg font-semibold text-gray-900
                           dark:text-white"
                >
                    People Shepherding Needs
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Church status, shepherd assignments,
                    and Shepherding Group coverage.
                </p>
            </div>

            <div
                class="grid gap-4 sm:grid-cols-2
                       lg:grid-cols-3 xl:grid-cols-6"
            >
                @foreach ($peopleStats as $key => $stat)
                    @php
                        $tone = match ($key) {
                            'active' =>
                                'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300',

                            'new_ones' =>
                                'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',

                            'gospel_friends' =>
                                'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',

                            'without_shepherd',
                            'without_service' =>
                                'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',

                            default =>
                                'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
                        };
                    @endphp

                    <a
                        href="{{ $stat['url'] }}"
                        class="rounded-2xl border p-4 shadow-sm
                               transition hover:scale-[1.01]
                               hover:shadow-md {{ $tone }}"
                    >
                        <p class="text-xs font-medium opacity-80">
                            {{ $stat['label'] }}
                        </p>

                        <p class="mt-2 text-3xl font-bold">
                            {{ $stat['count'] }}
                        </p>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @include('filament.pages.partials.shepherding-list', [
                'title' => 'People Without Shepherd',
                'people' => $peopleWithoutShepherd,
                'viewAllUrl' => $listUrls['without_shepherd'] ?? null,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'Dormant People',
                'people' => $dormantPeople,
                'viewAllUrl' => $listUrls['dormant'] ?? null,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'New Ones',
                'people' => $newOnes,
                'viewAllUrl' => $listUrls['new_ones'] ?? null,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'Gospel Friends',
                'people' => $gospelFriends,
                'viewAllUrl' => $listUrls['gospel_friends'] ?? null,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'People Without Shepherding Group',
                'people' => $peopleWithoutService,
                'viewAllUrl' => $listUrls['without_service'] ?? null,
            ])
        </div>

    </div>
</x-filament-panels::page>
