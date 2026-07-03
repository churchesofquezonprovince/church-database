<x-filament-panels::page>
    @php
        $sheets = $this->sheets();
        $selectedSheet = $this->selectedSheet();
        $sessions = $this->sessions();
        $selectedSession = $this->selectedSession();
        $participantRows = $this->participantRows();
        $presentPersonIds = $this->presentPersonIds();
        $recordCounts = $this->recordCounts();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Attendance Module
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Check Attendance
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Select an attendance sheet and session date, then check the people who are present.
            </p>
        </div>

        @if (session('attendance_records_saved'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 shadow-sm dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Attendance saved.</p>
                <p class="mt-1 text-sm">
                    Present: {{ session('attendance_present_count') }}.
                    Absent: {{ session('attendance_absent_count') }}.
                </p>
            </div>
        @endif

        @if (! $selectedSheet || ! $selectedSession)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    No attendance sheet/session found.
                </h3>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Create an attendance sheet first, then add participants.
                </p>

                <a
                    href="{{ \App\Filament\Pages\AddAttendanceSheet::getUrl() }}"
                    class="mt-5 inline-flex rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                >
                    Add Attendance Sheet
                </a>
            </div>
        @else
            <div class="grid gap-6 xl:grid-cols-4">
                <div class="space-y-4 xl:col-span-1">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            Sheets
                        </h3>

                        <div class="mt-4 space-y-2">
                            @foreach ($sheets as $sheet)
                                <a
                                    href="{{ $this->sheetUrl($sheet) }}"
                                    @class([
                                        'block rounded-xl border p-3 transition',
                                        'border-primary-300 bg-primary-50 dark:border-primary-800 dark:bg-primary-950' => $selectedSheet->id === $sheet->id,
                                        'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-800' => $selectedSheet->id !== $sheet->id,
                                    ])
                                >
                                    <p class="font-bold text-gray-900 dark:text-white">
                                        {{ $sheet->title }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sheet->locality ?: 'No locality' }}
                                        · {{ $sheet->sessions_count }} date(s)
                                        · {{ $sheet->participants_count }} participant(s)
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            Meeting Dates
                        </h3>

                        <div class="mt-4 max-h-96 space-y-2 overflow-auto pr-1">
                            @foreach ($sessions as $session)
                                <a
                                    href="{{ $this->sessionUrl($selectedSheet, $session) }}"
                                    @class([
                                        'block rounded-xl border p-3 text-sm transition',
                                        'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100' => $selectedSession->id === $session->id,
                                        'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800' => $selectedSession->id !== $session->id,
                                    ])
                                >
                                    <span class="font-bold">
                                        {{ $session->dateTimeLabel() }}
                                    </span>

                                    <span class="block text-xs opacity-75">
                                        {{ $session->session_date->format('l') }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="space-y-6 xl:col-span-3">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $selectedSheet->title }}
                                </h3>

                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $selectedSheet->locality ?: 'No locality' }}
                                    · {{ $selectedSession->session_date->format('l, F d, Y') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ \App\Filament\Pages\AttendanceReports::getUrl() . '?sheetId=' . $selectedSheet->id }}"
                                    class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white hover:bg-primary-500"
                                >
                                    View Report
                                </a>

                                <span class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white">
                                    Present: {{ $recordCounts['present'] }}
                                </span>

                                <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">
                                    Absent: {{ $recordCounts['absent'] }}
                                </span>

                                <span class="rounded-full bg-gray-600 px-3 py-1 text-xs font-bold text-white">
                                    Participants: {{ $participantRows->count() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Attendance Checklist
                                </h3>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Checked means present. Unchecked means absent.
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    onclick="document.querySelectorAll('.attendance-checkbox').forEach((box) => box.checked = true)"
                                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
                                >
                                    Check All
                                </button>

                                <button
                                    type="button"
                                    onclick="document.querySelectorAll('.attendance-checkbox').forEach((box) => box.checked = false)"
                                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                                >
                                    Clear All
                                </button>
                            </div>
                        </div>

                        @if ($participantRows->isEmpty())
                            <div class="mt-5 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                No participants active for this date.

                                <div class="mt-4">
                                    <a
                                        href="{{ \App\Filament\Pages\AttendanceSheets::getUrl() . '?sheetId=' . $selectedSheet->id }}"
                                        class="inline-flex rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                                    >
                                        Add Participants
                                    </a>
                                </div>
                            </div>
                        @else
                            <form
                                method="POST"
                                action="{{ route('church-database.attendance-sheets.records.store', ['session' => $selectedSession]) }}"
                                class="mt-5"
                            >
                                @csrf

                                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-950">
                                            <tr>
                                                <th class="w-20 px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Locality</th>
                                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Category</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                            @foreach ($participantRows as $participant)
                                                @php
                                                    $person = $participant->person;
                                                    $personId = (int) $participant->person_id;
                                                @endphp

                                                <tr>
                                                    <td class="px-4 py-3 text-center">
                                                        <input
                                                            type="checkbox"
                                                            name="present_person_ids[]"
                                                            value="{{ $personId }}"
                                                            @checked(in_array($personId, $presentPersonIds, true))
                                                            class="attendance-checkbox h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                        >
                                                    </td>

                                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                        {{ $person?->display_name ?? 'Unknown person' }}
                                                    </td>

                                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                        {{ $person?->locality ?: 'No locality' }}
                                                    </td>

                                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                        {{ $person?->churchProfile?->category ?: 'No category' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <button
                                    type="submit"
                                    class="mt-5 inline-flex rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                                >
                                    Save Attendance
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
