<x-filament-panels::page>
    @php
        $report = $this->report();
    @endphp

    <div class="space-y-6">
        <div
            class="rounded-2xl border border-primary-200
                   bg-primary-50 p-6 shadow-sm
                   dark:border-primary-900
                   dark:bg-primary-950"
        >
            <p
                class="text-sm font-semibold uppercase
                       tracking-wide text-primary-600
                       dark:text-primary-300"
            >
                Gospel Work
            </p>

            <div
                class="mt-2 flex flex-wrap
                       items-start justify-between gap-4"
            >
                <div>
                    <h2
                        class="text-3xl font-bold
                               text-gray-900 dark:text-white"
                    >
                        Weekly GOW Report
                    </h2>

                    <p
                        class="mt-2 text-sm text-gray-600
                               dark:text-gray-300"
                    >
                        {{ $this->periodLabel() }}
                        · Monday to Sunday
                    </p>
                </div>

                @if ($this->isCurrentWeek())
                    <span
                        class="rounded-full bg-primary-600
                               px-3 py-1 text-xs font-bold
                               text-white"
                    >
                        Current Week
                    </span>
                @endif
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <a
                    href="{{ $this->weekUrl(-1) }}"
                    class="rounded-xl border
                           border-primary-300 bg-white
                           px-4 py-2 text-sm font-bold
                           dark:border-primary-800
                           dark:bg-gray-900"
                >
                    ← Previous Week
                </a>

                @unless ($this->isCurrentWeek())
                    <a
                        href="{{ $this->currentWeekUrl() }}"
                        class="rounded-xl bg-primary-600
                               px-4 py-2 text-sm font-bold
                               text-white"
                    >
                        Current Week
                    </a>
                @endunless

                <a
                    href="{{ $this->weekUrl(1) }}"
                    class="rounded-xl border
                           border-primary-300 bg-white
                           px-4 py-2 text-sm font-bold
                           dark:border-primary-800
                           dark:bg-gray-900"
                >
                    Next Week →
                </a>
            </div>

            <p
                class="mt-4 text-xs text-gray-500
                       dark:text-gray-400"
            >
                Calculated automatically from Gospel Contacts,
                Shepherding Records, Campus Activities,
                and Attendance.
            </p>
        </div>


        {{-- Gospel Work --}}
        <section class="space-y-3">
            <div>
                <h3 class="text-xl font-bold">
                    Gospel Work
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Gospel contacts and Gospel Work activities
                    recorded during this week.
                </p>
            </div>

            <div
                class="grid gap-3
                       sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    class="rounded-2xl border
                           border-gray-200 bg-white p-5
                           dark:border-gray-800
                           dark:bg-gray-900"
                >
                    <p class="text-sm text-gray-500">
                        New Gospel Contacts
                    </p>

                    <p class="mt-2 text-3xl font-bold">
                        {{ $report['gospel']['new_contacts'] }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border
                           border-gray-200 bg-white p-5
                           dark:border-gray-800
                           dark:bg-gray-900"
                >
                    <p class="text-sm text-gray-500">
                        Gospel Work Contacts
                    </p>

                    <p class="mt-2 text-3xl font-bold">
                        {{ $report['gospel']['work_contacts'] }}
                    </p>
                </div>

                @foreach ($report['gospel']['activities'] as $activity)
                    <div
                        class="rounded-2xl border
                               border-gray-200 bg-white p-5
                               dark:border-gray-800
                               dark:bg-gray-900"
                    >
                        <p class="text-sm text-gray-500">
                            {{ $activity['code'] }}
                            ·
                            {{ $activity['name'] }}
                        </p>

                        <p class="mt-2 text-3xl font-bold">
                            {{ $activity['count'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Shepherding --}}
        <section class="space-y-3">
            <div>
                <h3 class="text-xl font-bold">
                    Shepherding
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Weekly Shepherding records and unique
                    contacted identities.
                </p>
            </div>

            <div
                class="grid gap-3
                       sm:grid-cols-2 lg:grid-cols-5"
            >
                @foreach ([
                    'records' => 'Shepherding Records',
                    'people' => 'People Contacted',
                    'households' => 'Households Contacted',
                    'campus_contacts' => 'Campus Contacts',
                    'gospel_contacts' => 'Gospel Contacts',
                ] as $key => $label)
                    <div
                        class="rounded-2xl border
                               border-gray-200 bg-white p-5
                               dark:border-gray-800
                               dark:bg-gray-900"
                    >
                        <p class="text-sm text-gray-500">
                            {{ $label }}
                        </p>

                        <p class="mt-2 text-3xl font-bold">
                            {{ $report['shepherding'][$key] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>


        {{-- Campus Work --}}
        <section class="space-y-3">
            <div>
                <h3 class="text-xl font-bold">
                    Campus Work
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Campus Activities and physical Attendance
                    during the selected week.
                </p>
            </div>

            <div
                class="grid gap-3
                       sm:grid-cols-2 lg:grid-cols-4"
            >
                @foreach ([
                    'activities' => 'Campus Activities',
                    'sessions' => 'Attendance Sessions',
                    'present_marks' => 'Present Marks',
                    'unique_people_present' => 'Unique People Present',
                ] as $key => $label)
                    <div
                        class="rounded-2xl border
                               border-gray-200 bg-white p-5
                               dark:border-gray-800
                               dark:bg-gray-900"
                    >
                        <p class="text-sm text-gray-500">
                            {{ $label }}
                        </p>

                        <p class="mt-2 text-3xl font-bold">
                            {{ $report['campus'][$key] }}
                        </p>
                    </div>
                @endforeach
            </div>

            @php
                $campusSessions =
                    $this->campusAttendanceSources();
            @endphp

            <details
                class="rounded-2xl border
                       border-gray-200 bg-white
                       dark:border-gray-800
                       dark:bg-gray-900"
            >
                <summary
                    class="cursor-pointer px-5 py-4
                           text-sm font-bold"
                >
                    View Attendance Session Sources
                    · {{ $campusSessions->count() }}
                </summary>

                <div
                    class="border-t border-gray-200
                           dark:border-gray-800"
                >
                    @forelse ($campusSessions as $session)
                        @php
                            $activity =
                                $session
                                    ->sheet
                                    ?->campusActivity;
                        @endphp

                        <a
                            href="{{ $this->campusSessionUrl(
                                $session
                            ) }}"
                            class="flex flex-wrap items-center
                                   justify-between gap-3
                                   border-b border-gray-100
                                   px-5 py-4 text-sm
                                   hover:bg-gray-50
                                   dark:border-gray-800
                                   dark:hover:bg-gray-950"
                        >
                            <div>
                                <strong>
                                    {{ $session
                                        ->session_date
                                        ?->format('M d') }}
                                </strong>

                                @if ($session->sessionTimeLabel())
                                    ·
                                    {{ $session->sessionTimeLabel() }}
                                @endif

                                ·
                                {{ $activity?->effective_title
                                    ?: $session->sheet?->title
                                    ?: 'Campus Attendance' }}
                            </div>

                            <div
                                class="font-bold text-primary-600
                                       dark:text-primary-400"
                            >
                                {{ $session->attendees_count }}
                                attendee{{ $session->attendees_count === 1
                                    ? ''
                                    : 's' }}
                                →
                            </div>
                        </a>
                    @empty
                        <div
                            class="px-5 py-4 text-sm
                                   text-gray-500"
                        >
                            No Campus Attendance Sessions
                            in this week.
                        </div>
                    @endforelse
                </div>
            </details>
        </section>


        <div
            class="rounded-2xl border border-gray-200
                   bg-gray-50 p-5 text-sm text-gray-600
                   dark:border-gray-800
                   dark:bg-gray-950
                   dark:text-gray-300"
        >
            <strong>Report ownership:</strong>
            these totals are calculated from their source records.
            This page does not create duplicate Gospel Work,
            Shepherding, Campus Work, or Attendance data.
        </div>
    </div>
</x-filament-panels::page>
