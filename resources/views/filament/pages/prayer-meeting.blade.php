<x-filament-panels::page>
    @php
        $localities = $this->localities();
        $selectedLocality = $this->selectedLocality();
        $selectedMeetingDay = $this->selectedMeetingDay();
        $selectedMeetingDate = $this->selectedMeetingDate();
        $people = $this->people();
        $presentPersonIds = $this->presentPersonIds();
        $counts = $this->counts();
        $selectedDateObject = \Carbon\CarbonImmutable::parse($selectedMeetingDate);
        $isCorrectDay = $selectedDateObject->dayOfWeek === $selectedMeetingDay;

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
        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-6 shadow-sm dark:border-sky-900 dark:bg-sky-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                Attendance Module
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Prayer Meeting
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Permanent prayer meeting attendance by locality. Default day is Tuesday, but each locality can use a different meeting day.
            </p>
        </div>

        @if (session('prayer_meeting_saved'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 shadow-sm dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Prayer Meeting attendance saved.</p>
                <p class="mt-1 text-sm">
                    Present: {{ session('prayer_meeting_present_count') }}.
                    Absent: {{ session('prayer_meeting_absent_count') }}.
                </p>
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

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <form method="GET" action="{{ \App\Filament\Pages\PrayerMeeting::getUrl() }}" class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="locality" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Locality
                    </label>

                    <select
                        id="locality"
                        name="locality"
                        required
                        onchange="this.form.submit()"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        @foreach ($localities as $locality)
                            <option value="{{ $locality }}" @selected($selectedLocality === $locality)>
                                {{ $this->localityLabel($locality) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="meeting_day" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Meeting Day
                    </label>

                    <select
                        id="meeting_day"
                        name="meeting_day"
                        required
                        onchange="
                            const meetingDateInput = document.getElementById('meeting_date');
                            const date = meetingDateInput.value ? new Date(meetingDateInput.value + 'T00:00:00') : new Date();
                            const selectedDay = Number(this.value);
                            const daysToAdd = (selectedDay - date.getDay() + 7) % 7;
                            date.setDate(date.getDate() + daysToAdd);
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            meetingDateInput.value = year + '-' + month + '-' + day;
                            this.form.submit();
                        "
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        @foreach ($days as $value => $label)
                            <option value="{{ $value }}" @selected((int) $selectedMeetingDay === (int) $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Default is Tuesday. Saving remembers this day for the locality.
                    </p>
                </div>

                <div>
                    <label for="meeting_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Meeting Date
                    </label>

                    <input
                        id="meeting_date"
                        name="meeting_date"
                        type="date"
                        value="{{ $selectedMeetingDate }}"
                        required
                        onchange="this.form.submit()"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @if (! $isCorrectDay)
                        <p class="mt-2 text-xs font-semibold text-red-600 dark:text-red-300">
                            Selected date is not {{ $this->dayLabel($selectedMeetingDay) }}. Saving will be blocked.
                        </p>
                    @endif
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                    >
                        Load
                    </button>
                </div>
            </form>
        </div>

        @if (! $selectedLocality)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    No localities found.
                </h3>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Add people with locality first.
                </p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sky-800 shadow-sm dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                    <p class="text-sm font-semibold opacity-75">Locality</p>
                    <p class="mt-3 text-2xl font-bold">{{ $this->localityLabel() }}</p>
                </div>

                <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 text-primary-800 shadow-sm dark:border-primary-900 dark:bg-primary-950 dark:text-primary-100">
                    <p class="text-sm font-semibold opacity-75">Meeting Day</p>
                    <p class="mt-3 text-2xl font-bold">{{ $this->dayLabel($selectedMeetingDay) }}</p>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                    <p class="text-sm font-semibold opacity-75">Present</p>
                    <p class="mt-3 text-2xl font-bold">{{ $counts['present'] }}</p>
                </div>

                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                    <p class="text-sm font-semibold opacity-75">Absent</p>
                    <p class="mt-3 text-2xl font-bold">{{ $counts['absent'] }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Attendance Checklist
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $this->localityLabel() }} · {{ $selectedDateObject->format('l, F d, Y') }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            onclick="document.querySelectorAll('.prayer-meeting-checkbox').forEach((box) => box.checked = true)"
                            class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
                        >
                            Check All
                        </button>

                        <button
                            type="button"
                            onclick="document.querySelectorAll('.prayer-meeting-checkbox').forEach((box) => box.checked = false)"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                        >
                            Clear All
                        </button>
                    </div>
                </div>

                @if ($people->isEmpty())
                    <div class="mt-5 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No people found for this locality.
                    </div>
                @else
                    <form
                        method="POST"
                        action="{{ route('church-database.attendance-sheets.prayer-meeting.store') }}"
                        class="mt-5"
                    >
                        @csrf

                        <input type="hidden" name="locality" value="{{ $selectedLocality }}">
                        <input type="hidden" name="meeting_day" value="{{ $selectedMeetingDay }}">
                        <input type="hidden" name="meeting_date" value="{{ $selectedMeetingDate }}">

                        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-950">
                                    <tr>
                                        <th class="w-20 px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Category</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Contact</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach ($people as $person)
                                        <tr>
                                            <td class="px-4 py-3 text-center">
                                                <input
                                                    type="checkbox"
                                                    name="present_person_ids[]"
                                                    value="{{ $person->id }}"
                                                    @checked(in_array((int) $person->id, $presentPersonIds, true))
                                                    class="prayer-meeting-checkbox h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                >
                                            </td>

                                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                {{ $person->display_name }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ $person->churchProfile?->category ?: 'No category' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ $person->contact_number ?: 'No contact' }}
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
                            Save Prayer Meeting Attendance
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
