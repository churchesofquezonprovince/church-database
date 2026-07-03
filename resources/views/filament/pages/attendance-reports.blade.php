<x-filament-panels::page>
    @php
        $reportTypes = $this->reportTypes();
        $selectedReportType = $this->selectedReportType();
        $sheets = $this->sheets();
        $selectedSheet = $this->selectedSheet();
        $meetingRows = $this->meetingRows();
        $personRows = $this->personRows();
        $summary = $this->summary();

        $customSheets = $this->customSheets();
        $lordsTableSheets = $this->lordsTableSheets();
        $prayerMeetingSheets = $this->prayerMeetingSheets();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Attendance Module
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Attendance Reports
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Reports are separated by type to avoid mixing custom attendance sheets, Lord's Table Meeting, and Prayer Meeting.
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <form method="GET" action="{{ \App\Filament\Pages\AttendanceReports::getUrl() }}">
                <input type="hidden" name="report_type" value="{{ $selectedReportType }}">

                <label for="sheetId" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                    @if ($selectedReportType === \App\Models\AttendanceSheet::TYPE_CUSTOM)
                        Attendance Sheet
                    @elseif ($selectedReportType === \App\Models\AttendanceSheet::TYPE_LORDS_TABLE)
                        Lord's Table Locality
                    @else
                        Prayer Meeting Locality
                    @endif
                </label>

                <select
                    id="sheetId"
                    name="sheetId"
                    onchange="this.form.submit()"
                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                >
                    @forelse ($sheets as $sheet)
                        <option value="{{ $sheet->id }}" @selected($selectedSheet?->id === $sheet->id)>
                            {{ $this->sheetLabel($sheet) }}
                            — {{ $sheet->sessions_count }} meeting(s)
                            — {{ $sheet->participants_count }} participant(s)
                        </option>
                    @empty
                        <option value="">
                            No records yet
                        </option>
                    @endforelse
                </select>
            </form>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <a
                href="{{ $this->reportTypeUrl(\App\Models\AttendanceSheet::TYPE_CUSTOM) }}"
                @class([
                    'rounded-2xl border p-5 shadow-sm transition',
                    'border-primary-300 bg-primary-50 dark:border-primary-800 dark:bg-primary-950' => $selectedReportType === \App\Models\AttendanceSheet::TYPE_CUSTOM,
                    'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800' => $selectedReportType !== \App\Models\AttendanceSheet::TYPE_CUSTOM,
                ])
            >
                <p class="font-bold text-gray-900 dark:text-white">Custom Attendance Sheets</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $customSheets->count() }} sheet(s)
                </p>
            </a>

            <a
                href="{{ $this->reportTypeUrl(\App\Models\AttendanceSheet::TYPE_LORDS_TABLE) }}"
                @class([
                    'rounded-2xl border p-5 shadow-sm transition',
                    'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950' => $selectedReportType === \App\Models\AttendanceSheet::TYPE_LORDS_TABLE,
                    'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800' => $selectedReportType !== \App\Models\AttendanceSheet::TYPE_LORDS_TABLE,
                ])
            >
                <p class="font-bold text-gray-900 dark:text-white">Lord's Table Meeting</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $lordsTableSheets->count() }} localit{{ $lordsTableSheets->count() === 1 ? 'y' : 'ies' }}
                </p>
            </a>

            <a
                href="{{ $this->reportTypeUrl(\App\Models\AttendanceSheet::TYPE_PRAYER_MEETING) }}"
                @class([
                    'rounded-2xl border p-5 shadow-sm transition',
                    'border-sky-300 bg-sky-50 dark:border-sky-800 dark:bg-sky-950' => $selectedReportType === \App\Models\AttendanceSheet::TYPE_PRAYER_MEETING,
                    'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800' => $selectedReportType !== \App\Models\AttendanceSheet::TYPE_PRAYER_MEETING,
                ])
            >
                <p class="font-bold text-gray-900 dark:text-white">Prayer Meeting</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $prayerMeetingSheets->count() }} localit{{ $prayerMeetingSheets->count() === 1 ? 'y' : 'ies' }}
                </p>
            </a>
        </div>

        @if ($selectedReportType === \App\Models\AttendanceSheet::TYPE_LORDS_TABLE)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900 dark:bg-amber-950">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                            Lord's Table Filters
                        </p>

                        <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                            Filter by date range and category
                        </h3>
                    </div>

                    <a
                        href="{{ $this->clearFiltersUrl() }}"
                        class="inline-flex rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                    >
                        Clear Filters
                    </a>
                </div>

                <form method="GET" action="{{ \App\Filament\Pages\AttendanceReports::getUrl() }}" class="mt-5 grid gap-4 lg:grid-cols-4">
                    <input type="hidden" name="report_type" value="{{ $selectedReportType }}">
                    <input type="hidden" name="sheetId" value="{{ $selectedSheet?->id }}">

                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-amber-900 dark:text-amber-100">
                            Date From
                        </label>

                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="{{ $this->selectedDateFrom() }}"
                            class="mt-2 block w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-amber-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-amber-900 dark:text-amber-100">
                            Date To
                        </label>

                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="{{ $this->selectedDateTo() }}"
                            class="mt-2 block w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-amber-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-semibold text-amber-900 dark:text-amber-100">
                            Category
                        </label>

                        <select
                            id="category"
                            name="category"
                            class="mt-2 block w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-amber-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                            <option value="">All Categories</option>

                            @foreach ($this->categoryOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($this->selectedCategory() === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="inline-flex w-full justify-center rounded-xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500"
                        >
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        @endif


        @if ($selectedReportType === \App\Models\AttendanceSheet::TYPE_PRAYER_MEETING)
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-6 shadow-sm dark:border-sky-900 dark:bg-sky-950">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                            Prayer Meeting Filters
                        </p>

                        <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                            Filter by date range, meeting day, and category
                        </h3>
                    </div>

                    <a
                        href="{{ $this->clearFiltersUrl() }}"
                        class="inline-flex rounded-xl border border-sky-300 bg-white px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100"
                    >
                        Clear Filters
                    </a>
                </div>

                <form method="GET" action="{{ \App\Filament\Pages\AttendanceReports::getUrl() }}" class="mt-5 grid gap-4 lg:grid-cols-5">
                    <input type="hidden" name="report_type" value="{{ $selectedReportType }}">
                    <input type="hidden" name="sheetId" value="{{ $selectedSheet?->id }}">

                    <div>
                        <label for="date_from_prayer" class="block text-sm font-semibold text-sky-900 dark:text-sky-100">
                            Date From
                        </label>

                        <input
                            id="date_from_prayer"
                            name="date_from"
                            type="date"
                            value="{{ $this->selectedDateFrom() }}"
                            class="mt-2 block w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-sky-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

                    <div>
                        <label for="date_to_prayer" class="block text-sm font-semibold text-sky-900 dark:text-sky-100">
                            Date To
                        </label>

                        <input
                            id="date_to_prayer"
                            name="date_to"
                            type="date"
                            value="{{ $this->selectedDateTo() }}"
                            class="mt-2 block w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-sky-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

                    <div>
                        <label for="meeting_day_prayer" class="block text-sm font-semibold text-sky-900 dark:text-sky-100">
                            Meeting Day
                        </label>

                        <select
                            id="meeting_day_prayer"
                            name="meeting_day"
                            class="mt-2 block w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-sky-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                            <option value="">All Days</option>

                            @foreach ($this->dayOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($this->selectedMeetingDayFilter() === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="category_prayer" class="block text-sm font-semibold text-sky-900 dark:text-sky-100">
                            Category
                        </label>

                        <select
                            id="category_prayer"
                            name="category"
                            class="mt-2 block w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-sky-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                            <option value="">All Categories</option>

                            @foreach ($this->categoryOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($this->selectedCategory() === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="inline-flex w-full justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-500"
                        >
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @if (! $selectedSheet)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    No {{ $this->selectedReportTypeLabel() }} records yet.
                </h3>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Create attendance first, then return to this report.
                </p>
            </div>
        @else
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                            {{ $this->selectedReportTypeLabel() }}
                        </p>

                        <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $this->sheetLabel($selectedSheet) }}
                        </h3>

                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ $selectedSheet->locality ?: 'No locality' }}
                            · {{ $this->reportPeriodLabel($selectedSheet) }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            {{ $this->activeFilterLabel() }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a
                            href="{{ $this->printUrl() }}"
                            target="_blank"
                            class="rounded-full bg-gray-700 px-3 py-1 text-xs font-bold text-white hover:bg-gray-600"
                        >
                            Print Report
                        </a>

                        <a
                            href="{{ $this->exportUrl() }}"
                            class="rounded-full bg-amber-600 px-3 py-1 text-xs font-bold text-white hover:bg-amber-500"
                        >
                            Export CSV
                        </a>

                        <a
                            href="{{ \App\Filament\Pages\CheckAttendance::getUrl() . '?sheetId=' . $selectedSheet->id }}"
                            class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-500"
                        >
                            Check Attendance
                        </a>

                        @if ($selectedSheet->sheet_type === \App\Models\AttendanceSheet::TYPE_CUSTOM)
                            <a
                                href="{{ \App\Filament\Pages\AttendanceSheets::getUrl() . '?sheetId=' . $selectedSheet->id }}"
                                class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white hover:bg-primary-500"
                            >
                                Manage Participants
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 text-primary-800 shadow-sm dark:border-primary-900 dark:bg-primary-950 dark:text-primary-100">
                    <p class="text-sm font-semibold opacity-75">Meetings</p>
                    <p class="mt-3 text-3xl font-bold">{{ $summary['meetings'] }}</p>
                </div>

                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sky-800 shadow-sm dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                    <p class="text-sm font-semibold opacity-75">Participants</p>
                    <p class="mt-3 text-3xl font-bold">{{ $summary['participants'] }}</p>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                    <p class="text-sm font-semibold opacity-75">Present Total</p>
                    <p class="mt-3 text-3xl font-bold">{{ $summary['present_total'] }}</p>
                </div>

                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                    <p class="text-sm font-semibold opacity-75">Absent Total</p>
                    <p class="mt-3 text-3xl font-bold">{{ $summary['absent_total'] }}</p>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    <p class="text-sm font-semibold opacity-75">Overall Rate</p>
                    <p class="mt-3 text-3xl font-bold">{{ $summary['overall_rate'] }}%</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Report by Meeting Date
                </h3>

                <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Meeting Date</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Expected</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Absent</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Unmarked</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Rate</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @forelse ($meetingRows as $row)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $row['session']->session_date->format('M d, Y') }}
                                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                                            {{ $row['session']->session_date->format('l') }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ $row['active_participants'] }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-300">{{ $row['present'] }}</td>
                                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-300">{{ $row['absent'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['unmarked'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $row['rate'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                        No meeting records yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Report by Person
                </h3>

                <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Locality</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Expected</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Absent</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Unmarked</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Rate</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @forelse ($personRows as $row)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $row['person']?->display_name ?? 'Unknown person' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ $row['person']?->locality ?: 'No locality' }}
                                    </td>

                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ $row['expected'] }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-300">{{ $row['present'] }}</td>
                                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-300">{{ $row['absent'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['unmarked'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $row['rate'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                        No people records yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
