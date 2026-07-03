<x-filament-panels::page>
    @php
        $sheets = $this->sheets();
        $selectedSheet = $this->selectedSheet();
        $participantRows = $this->participantRows();
        $availablePeople = $this->availablePeople();

        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Attendance Module
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Attendance Sheets
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Manage attendance sheets and add participants. Attendance checking will be added in the next phase.
            </p>
        </div>

        @if (session('attendance_participants_saved'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 shadow-sm dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Participants saved.</p>
                <p class="mt-1 text-sm">
                    Added {{ session('attendance_participants_added') }} participant(s), updated {{ session('attendance_participants_updated') }} participant(s).
                </p>
            </div>
        @endif

        @if (session('attendance_participant_removed'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">Participant removed from the sheet.</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Please fix the following:</p>

                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $selectedSheet)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    No attendance sheets yet.
                </h3>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Create your first attendance sheet.
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
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-bold text-gray-900 dark:text-white">
                                Sheets
                            </h3>

                            <a
                                href="{{ \App\Filament\Pages\AddAttendanceSheet::getUrl() }}"
                                class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500"
                            >
                                Add
                            </a>
                        </div>

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
                            <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                {{ $sheet->attendanceModeLabel() }} · {{ $sheet->meetingTimeLabel() }}
                            </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sheet->locality ?: 'No locality' }} · {{ $sheet->sessions_count }} date(s)
                                    </p>
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
                                    · {{ $days[$selectedSheet->meeting_day] ?? 'No meeting day' }}
                                    · {{ optional($selectedSheet->start_date)->format('M d, Y') }}
                                    to {{ optional($selectedSheet->end_date)->format('M d, Y') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ \App\Filament\Pages\CheckAttendance::getUrl() . '?sheetId=' . $selectedSheet->id }}"
                                    class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-500"
                                >
                                    Check Attendance
                                </a>

                                <span class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white">
                                    {{ $selectedSheet->sessions_count }} session(s)
                                </span>

                                <span class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white">
                                    {{ $selectedSheet->participants_count }} participant(s)
                                </span>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($selectedSheet->sessions->take(8) as $session)
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                                    {{ $session->session_date->format('M d, Y') }}
                                </div>
                            @endforeach
                        </div>

                        @if ($selectedSheet->sessions_count > 8)
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Showing first 8 dates only. Full attendance grid will be added in the next phase.
                            </p>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
                        <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-100">
                            Add Participants
                        </h3>

                        <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-200">
                            You can add people even in the middle of the attendance date range.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('church-database.attendance-sheets.participants.store', ['sheet' => $selectedSheet]) }}"
                            class="mt-5 space-y-4"
                        >
                            @csrf

                            <div>
                                <label for="person_ids" class="block text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                    People
                                </label>

                                <select
                                    id="person_ids"
                                    name="person_ids[]"
                                    multiple
                                    required
                                    size="8"
                                    class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                                >
                                    @foreach ($availablePeople as $person)
                                        <option value="{{ $person->id }}">
                                            {{ $person->display_name }}{{ $person->locality ? ' — ' . $person->locality : '' }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-200">
                                    Hold Ctrl on Windows to select multiple people.
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label for="starts_on" class="block text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                        Starts On
                                    </label>

                                    <input
                                        id="starts_on"
                                        name="starts_on"
                                        type="date"
                                        value="{{ optional($selectedSheet->start_date)->format('Y-m-d') }}"
                                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                                    >
                                </div>

                                <div>
                                    <label for="ends_on" class="block text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                        Ends On
                                    </label>

                                    <input
                                        id="ends_on"
                                        name="ends_on"
                                        type="date"
                                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                                    >
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                            >
                                Add Selected People
                            </button>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Participants
                        </h3>

                        <div class="mt-5 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-950">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Locality</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Starts</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Ends</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @forelse ($participantRows as $participant)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                {{ $participant->person?->display_name ?? 'Unknown person' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ $participant->person?->locality ?: 'No locality' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ optional($participant->starts_on)->format('M d, Y') ?: 'Sheet start' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ optional($participant->ends_on)->format('M d, Y') ?: 'No end' }}
                                            </td>

                                            <td class="px-4 py-3 text-right">
                                                <form
                                                    method="POST"
                                                    action="{{ route('church-database.attendance-sheets.participants.destroy', ['sheet' => $selectedSheet, 'participant' => $participant]) }}"
                                                    onsubmit="return confirm('Remove this person from the attendance sheet?');"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                                                    >
                                                        Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                                No participants added yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
