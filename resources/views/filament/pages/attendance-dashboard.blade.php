<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $todaysMeetings = $this->todaysMeetings();
        $upcomingMeetings = $this->upcomingMeetings();
        $latestMeetingSummaries = $this->latestMeetingSummaries();
        $recentRecords = $this->recentRecords();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Attendance Module
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Attendance Dashboard
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Quick overview of attendance sheets, permanent meetings, upcoming dates, and recent attendance updates.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 text-primary-800 shadow-sm dark:border-primary-900 dark:bg-primary-950 dark:text-primary-100">
                <p class="text-sm font-semibold opacity-75">Custom Sheets</p>
                <p class="mt-3 text-3xl font-bold">{{ $summary['custom_sheets'] }}</p>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="text-sm font-semibold opacity-75">Lord's Table Localities</p>
                <p class="mt-3 text-3xl font-bold">{{ $summary['lords_table_localities'] }}</p>
            </div>

            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sky-800 shadow-sm dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                <p class="text-sm font-semibold opacity-75">Prayer Meeting Localities</p>
                <p class="mt-3 text-3xl font-bold">{{ $summary['prayer_meeting_localities'] }}</p>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                <p class="text-sm font-semibold opacity-75">Active Participants</p>
                <p class="mt-3 text-3xl font-bold">{{ $summary['total_participants'] }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Today's Meetings
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Meetings scheduled for today.
                        </p>
                    </div>

                    <a
                        href="{{ $this->checkAttendanceUrl() }}"
                        class="rounded-xl bg-primary-600 px-3 py-2 text-xs font-bold text-white hover:bg-primary-500"
                    >
                        Check Attendance
                    </a>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($todaysMeetings as $row)
                        <a
                            href="{{ $this->checkAttendanceUrl($row['session']) }}"
                            class="block rounded-xl border border-gray-200 bg-gray-50 p-4 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-800"
                        >
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ $row['sheet']?->title ?? 'Unknown Sheet' }}
                            </p>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $this->sheetTypeLabel($row['sheet']?->sheet_type) }}
                                · {{ $row['session']->session_date->format('M d, Y') }}
                            </p>

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Present: {{ $row['present'] }} /
                                Expected: {{ $row['expected'] }} /
                                Rate: {{ $row['rate'] }}%
                            </p>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No meetings scheduled for today.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Upcoming Meetings
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Next scheduled custom meeting dates.
                </p>

                <div class="mt-5 space-y-3">
                    @forelse ($upcomingMeetings as $session)
                        <a
                            href="{{ $this->checkAttendanceUrl($session) }}"
                            class="block rounded-xl border border-gray-200 bg-gray-50 p-4 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-800"
                        >
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ $session->sheet?->title ?? 'Unknown Sheet' }}
                            </p>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $session->session_date->format('l, M d, Y') }}
                                · {{ $this->sheetTypeLabel($session->sheet?->sheet_type) }}
                            </p>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No upcoming meetings found.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Latest Meeting Summaries
                    </h3>

                    <a
                        href="{{ $this->attendanceReportsUrl() }}"
                        class="rounded-xl bg-gray-700 px-3 py-2 text-xs font-bold text-white hover:bg-gray-600"
                    >
                        View Reports
                    </a>
                </div>

                <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Meeting</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Expected</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Rate</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @forelse ($latestMeetingSummaries as $row)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $row['sheet']?->title ?? 'Unknown Sheet' }}
                                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                                            {{ $row['session']->session_date->format('M d, Y') }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-300">
                                        {{ $row['present'] }}
                                    </td>

                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                        {{ $row['expected'] }}
                                    </td>

                                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                        {{ $row['rate'] }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                        No marked attendance yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Recently Marked Attendance
                </h3>

                <div class="mt-5 space-y-3">
                    @forelse ($recentRecords as $record)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ $record->person?->display_name ?? 'Unknown Person' }}
                            </p>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $record->session?->sheet?->title ?? 'Unknown Sheet' }}
                                · {{ optional($record->session?->session_date)->format('M d, Y') }}
                            </p>

                            <p class="mt-2 text-xs font-semibold {{ $record->is_present ? 'text-emerald-600 dark:text-emerald-300' : 'text-red-600 dark:text-red-300' }}">
                                {{ $record->is_present ? 'Present' : 'Absent' }}
                                · marked {{ optional($record->marked_at)->diffForHumans() }}
                            </p>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No recent attendance records.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
