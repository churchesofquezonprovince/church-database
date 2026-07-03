<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $customSheets = $this->customSheets();
        $localities = $this->localities();
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


        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Quick Access
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Jump directly to the most-used attendance pages.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-4">
                <form
                    method="GET"
                    action="{{ \App\Filament\Pages\CheckAttendance::getUrl() }}"
                    class="rounded-2xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-900 dark:bg-primary-950"
                >
                    <p class="font-bold text-gray-900 dark:text-white">
                        Custom Sheet
                    </p>

                    <label for="quick_sheet_id" class="mt-4 block text-sm font-semibold text-primary-900 dark:text-primary-100">
                        Attendance Sheet
                    </label>

                    <select
                        id="quick_sheet_id"
                        name="sheetId"
                        class="mt-2 block w-full rounded-xl border border-primary-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-primary-900 dark:bg-gray-950 dark:text-gray-100"
                    >
                        @forelse ($customSheets as $sheet)
                            <option value="{{ $sheet->id }}">
                                {{ $sheet->title }} — {{ $sheet->sessions_count }} date(s)
                            </option>
                        @empty
                            <option value="">No custom sheets</option>
                        @endforelse
                    </select>

                    <button
                        type="submit"
                        class="mt-4 w-full rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-500"
                    >
                        Check Attendance
                    </button>
                </form>

                <form
                    method="GET"
                    action="{{ \App\Filament\Pages\LordsTableMeeting::getUrl() }}"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950"
                >
                    <p class="font-bold text-gray-900 dark:text-white">
                        Lord's Table
                    </p>

                    <label for="quick_lords_locality" class="mt-4 block text-sm font-semibold text-amber-900 dark:text-amber-100">
                        Locality
                    </label>

                    <select
                        id="quick_lords_locality"
                        name="locality"
                        class="mt-2 block w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-amber-900 dark:bg-gray-950 dark:text-gray-100"
                    >
                        @forelse ($localities as $locality)
                            <option value="{{ $locality }}">
                                {{ $this->localityLabel($locality) }}
                            </option>
                        @empty
                            <option value="">No localities</option>
                        @endforelse
                    </select>

                    <input
                        type="date"
                        name="meeting_date"
                        value="{{ $this->nextSundayDate() }}"
                        class="mt-3 block w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-amber-900 dark:bg-gray-950 dark:text-gray-100"
                    >

                    <button
                        type="submit"
                        class="mt-4 w-full rounded-xl bg-amber-600 px-4 py-2 text-sm font-bold text-white hover:bg-amber-500"
                    >
                        Open Lord's Table
                    </button>
                </form>

                <form
                    method="GET"
                    action="{{ \App\Filament\Pages\PrayerMeeting::getUrl() }}"
                    class="rounded-2xl border border-sky-200 bg-sky-50 p-5 dark:border-sky-900 dark:bg-sky-950"
                >
                    <p class="font-bold text-gray-900 dark:text-white">
                        Prayer Meeting
                    </p>

                    <label for="quick_prayer_locality" class="mt-4 block text-sm font-semibold text-sky-900 dark:text-sky-100">
                        Locality
                    </label>

                    <select
                        id="quick_prayer_locality"
                        name="locality"
                        class="mt-2 block w-full rounded-xl border border-sky-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-sky-900 dark:bg-gray-950 dark:text-gray-100"
                    >
                        @forelse ($localities as $locality)
                            <option value="{{ $locality }}">
                                {{ $this->localityLabel($locality) }}
                            </option>
                        @empty
                            <option value="">No localities</option>
                        @endforelse
                    </select>

                    <input type="hidden" name="meeting_day" value="2">

                    <input
                        type="date"
                        name="meeting_date"
                        value="{{ $this->nextTuesdayDate() }}"
                        class="mt-3 block w-full rounded-xl border border-sky-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-sky-900 dark:bg-gray-950 dark:text-gray-100"
                    >

                    <button
                        type="submit"
                        class="mt-4 w-full rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-500"
                    >
                        Open Prayer Meeting
                    </button>
                </form>

                <form
                    method="GET"
                    action="{{ \App\Filament\Pages\AttendanceReports::getUrl() }}"
                    class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-950"
                >
                    <p class="font-bold text-gray-900 dark:text-white">
                        Reports
                    </p>

                    <label for="quick_report_type" class="mt-4 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Report Type
                    </label>

                    <select
                        id="quick_report_type"
                        name="report_type"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    >
                        <option value="{{ \App\Models\AttendanceSheet::TYPE_CUSTOM }}">Custom Sheets</option>
                        <option value="{{ \App\Models\AttendanceSheet::TYPE_LORDS_TABLE }}">Lord's Table</option>
                        <option value="{{ \App\Models\AttendanceSheet::TYPE_PRAYER_MEETING }}">Prayer Meeting</option>
                    </select>

                    <button
                        type="submit"
                        class="mt-4 w-full rounded-xl bg-gray-700 px-4 py-2 text-sm font-bold text-white hover:bg-gray-600"
                    >
                        Open Reports
                    </button>
                </form>
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
                                · {{ $row['session']->dateTimeLabel() }}
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
                                {{ $session->dateTimeLabel('l, M d, Y') }}
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
                                            {{ $row['session']->dateTimeLabel() }}
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
                                · {{ $record->session?->dateTimeLabel() ?? 'No date' }}
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
