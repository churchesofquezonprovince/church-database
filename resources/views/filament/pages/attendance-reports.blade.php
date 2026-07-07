<x-filament-panels::page>
    @php
        $reportTypes = $this->reportTypes();
        $selectedReportType = $this->selectedReportType();
        $sheets = $this->sheets();
        $selectedSheet = $this->selectedSheet();
        $meetingRows = $this->meetingRows();
        $personRows = $this->personRows();
        $summary = $this->summary();
        $localitySummaryRows = $this->localitySummaryRows();
        $categorySummaryRows = $this->categorySummaryRows();
        $attendanceTrendRows = $this->attendanceTrendRows();
        $selectedTrendPeriod = $this->selectedTrendPeriod();

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
                <input type="hidden" name="trend_period" value="{{ $selectedTrendPeriod }}">

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

                <form method="GET" action="{{ \App\Filament\Pages\AttendanceReports::getUrl() }}" class="mt-5 grid gap-4 lg:grid-cols-5">
                    <input type="hidden" name="report_type" value="{{ $selectedReportType }}">
                    <input type="hidden" name="sheetId" value="{{ $selectedSheet?->id }}">
                    <input type="hidden" name="trend_period" value="{{ $selectedTrendPeriod }}">

                    <div>
                        <label for="report_month_lords" class="block text-sm font-semibold text-amber-900 dark:text-amber-100">
                            Report Month
                        </label>

                        <input
                            id="report_month_lords"
                            name="report_month"
                            type="month"
                            value="{{ $this->selectedReportMonth() }}"
                            onchange="setAttendanceReportMonthRange(this)"
                            class="mt-2 block w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-amber-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

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
                    <input type="hidden" name="trend_period" value="{{ $selectedTrendPeriod }}">

                    <div>
                        <label for="report_month_prayer" class="block text-sm font-semibold text-sky-900 dark:text-sky-100">
                            Report Month
                        </label>

                        <input
                            id="report_month_prayer"
                            name="report_month"
                            type="month"
                            value="{{ $this->selectedReportMonth() }}"
                            onchange="setAttendanceReportMonthRange(this)"
                            class="mt-2 block w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-sky-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

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




            @if ($selectedSheet)
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Category Summary
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Summary by church category using the current sheet and report filters.
                        </p>
                    </div>

                    @if ($categorySummaryRows->isEmpty())
                        <div class="mt-5 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No category summary rows found for the selected filters.
                        </div>
                    @else
                        <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="min-w-[760px] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-950">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Category</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Participants</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Expected</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Absent</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Unmarked</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Rate</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach ($categorySummaryRows as $row)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                {{ $row['category'] }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['participants'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['expected'] }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-300">{{ $row['present'] }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-red-600 dark:text-red-300">{{ $row['absent'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['unmarked'] }}</td>
                                            <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $row['rate'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

            @if (in_array($selectedReportType, [
                \App\Models\AttendanceSheet::TYPE_LORDS_TABLE,
                \App\Models\AttendanceSheet::TYPE_PRAYER_MEETING,
            ], true))
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Locality Summary
                            </h3>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Comparison of all localities using the current report filters.
                            </p>
                        </div>
                    </div>

                    @if ($localitySummaryRows->isEmpty())
                        <div class="mt-5 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No locality summary rows found for the selected filters.
                        </div>
                    @else
                        <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="min-w-[820px] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-950">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Locality</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Meetings</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Participants</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Expected</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Absent</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Unmarked</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Rate</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach ($localitySummaryRows as $row)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                {{ $row['locality'] }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['meetings'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['participants'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['expected'] }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-300">{{ $row['present'] }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-red-600 dark:text-red-300">{{ $row['absent'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['unmarked'] }}</td>
                                            <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $row['rate'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

        @if ($this->hasInvalidDateRange())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Invalid date range.</p>
                <p class="mt-1 text-sm">
                    Date To must not be earlier than Date From.
                </p>
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
                            href="{{ request()->fullUrlWithQuery(['refresh' => now()->timestamp]) }}"
                            class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white hover:bg-primary-500"
                        >
                            Refresh Report
                        </a>

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
                            href="{{ $this->attendanceEntryUrl() }}"
                            class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-500"
                        >
                            {{ $this->attendanceEntryLabel() }}
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
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Attendance Trend
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Shows the attendance percentage increase or decrease compared with the previous {{ $selectedTrendPeriod === 'monthly' ? 'month' : 'week' }}.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->trendPeriodOptions() as $value => $label)
                            <a
                                href="{{ $this->trendPeriodUrl($value) }}"
                                @class([
                                    'rounded-full px-3 py-1 text-xs font-bold transition',
                                    'bg-primary-600 text-white' => $selectedTrendPeriod === $value,
                                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => $selectedTrendPeriod !== $value,
                                ])
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if ($attendanceTrendRows->isEmpty())
                    <div class="mt-5 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No attendance trend available for the selected filters.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="min-w-[860px] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-950">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                        {{ $selectedTrendPeriod === 'monthly' ? 'Month' : 'Week' }}
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Meetings</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Expected</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Absent</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Unmarked</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Attendance %</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Increase / Decrease</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                @foreach ($attendanceTrendRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                            {{ $row['label'] }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['meetings'] }}</td>
                                        <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['expected'] }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-300">{{ $row['present'] }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-red-600 dark:text-red-300">{{ $row['absent'] }}</td>
                                        <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ $row['unmarked'] }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                            {{ $this->formatPercent($row['rate']) }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span @class([
                                                'rounded-full px-2.5 py-1 text-xs font-bold',
                                                'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' => $row['change'] === null || $row['change'] == 0,
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200' => $row['change'] !== null && $row['change'] > 0,
                                                'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' => $row['change'] !== null && $row['change'] < 0,
                                            ])>
                                                {{ $this->formatChangePercent($row['change']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
    <script>
        function setAttendanceReportMonthRange(input) {
            if (! input.value) {
                return;
            }

            const form = input.closest('form');
            const [year, month] = input.value.split('-').map(Number);

            const firstDay = new Date(year, month - 1, 1);
            const lastDay = new Date(year, month, 0);

            const formatDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');

                return `${y}-${m}-${d}`;
            };

            const dateFrom = form.querySelector('input[name="date_from"]');
            const dateTo = form.querySelector('input[name="date_to"]');

            if (dateFrom) {
                dateFrom.value = formatDate(firstDay);
            }

            if (dateTo) {
                dateTo.value = formatDate(lastDay);
            }
        }
    </script>

</x-filament-panels::page>
