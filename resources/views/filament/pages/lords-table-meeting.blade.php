<x-filament-panels::page>
    @php
        $localities = $this->localities();
        $selectedLocality = $this->selectedLocality();
        $selectedMeetingDate = $this->selectedMeetingDate();
        $selectedSheet = $this->selectedSheet();
        $selectedSession = $this->selectedSession();
        $selectedMeetingTime = request('meeting_time', substr((string) ($selectedSheet->meeting_time ?? ''), 0, 5));
        $people = $this->people();
        $presentPersonIds = $this->presentPersonIds();
        $counts = $this->counts();
        $selectedDateObject = \Carbon\CarbonImmutable::parse($selectedMeetingDate);
        $isSunday = $selectedDateObject->dayOfWeek === 0;
    @endphp

        @if (session('other_locality_attendee_added'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                Other locality attendee added and marked present.
            </div>
        @endif

        @if (isset($selectedSession) && $selectedSession)
            @php
                $otherLocalityCandidates = $this->otherLocalityCandidates();
            @endphp

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-900 dark:bg-amber-950">
                <h3 class="text-lg font-bold text-amber-900 dark:text-amber-100">
                    Other Locality Attendee
                </h3>

                <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                    Use this if someone from another locality attended this meeting. The person will be added to this meeting date and marked Present.
                </p>

                <form
                    method="POST"
                    action="{{ route('church-database.attendance-sheets.permanent-meeting.other-attendees.store', $selectedSession) }}"
                    class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]"
                >
                    @csrf

                    <div class="space-y-2">
                        <input
                            type="search"
                            placeholder="Search name or locality..."
                            oninput="const q = this.value.toLowerCase(); this.closest('form').querySelectorAll('select[name=person_id] option').forEach((option, index) => { if (index === 0) return; option.hidden = ! option.textContent.toLowerCase().includes(q); });"
                            class="block w-full rounded-xl border border-amber-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-amber-800 dark:bg-gray-950 dark:text-gray-100"
                        >

                        <select
                            name="person_id"
                            required
                            class="block w-full rounded-xl border border-amber-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-amber-800 dark:bg-gray-950 dark:text-gray-100"
                        >
                        <option value="">Select person from other locality</option>

                        @foreach ($otherLocalityCandidates as $person)
                            <option value="{{ $person->id }}">
                                {{ $person->display_name }} — {{ $person->locality ?: 'No Locality' }}
                            </option>
                        @endforeach
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="rounded-xl bg-amber-600 px-5 py-3 text-sm font-bold text-white hover:bg-amber-500"
                    >
                        Add as Present
                    </button>
                </form>

                @if ($otherLocalityCandidates->isEmpty())
                    <p class="mt-3 text-xs text-amber-700 dark:text-amber-200">
                        No other locality candidates available.
                    </p>
                @endif
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 p-5 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                Select a locality and meeting date first to add other locality attendees.
            </div>
        @endif


    <div class="space-y-6">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900 dark:bg-amber-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                Attendance Module
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Lord's Table Meeting
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Permanent Sunday attendance by locality. Select a locality, choose a Sunday, then check the saints who attended.
            </p>
        </div>

        @if (session('lords_table_saved'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 shadow-sm dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Lord's Table attendance saved.</p>
                <p class="mt-1 text-sm">
                    Present: {{ session('lords_table_present_count') }}.
                    Absent: {{ session('lords_table_absent_count') }}.
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
            <form method="GET" action="{{ \App\Filament\Pages\LordsTableMeeting::getUrl() }}" class="grid gap-4 md:grid-cols-[1.2fr_1fr_1fr_auto] md:items-end">
                <div>
                    <label for="locality" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Locality
                    </label>

                    <select
                        id="locality"
                        name="locality"
                        required
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
                    <label for="meeting_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Sunday Date
                    </label>

                    <input
                        id="meeting_date"
                        name="meeting_date"
                        type="date"
                        value="{{ $selectedMeetingDate }}"
                        required
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div>
                    <label for="meeting_time" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Time
                    </label>

                    <input
                        id="meeting_time"
                        name="meeting_time"
                        type="time"
                        value="{{ $selectedMeetingTime }}"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-amber-600 px-6 py-3 text-sm font-bold text-white hover:bg-amber-500"
                >
                    Load Locality
                </button>
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
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    <p class="text-sm font-semibold opacity-75">Locality</p>
                    <p class="mt-3 text-2xl font-bold">{{ $this->localityLabel() }}</p>
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
                            onclick="document.querySelectorAll('.lords-table-checkbox').forEach((box) => box.checked = true)"
                            class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
                        >
                            Check All
                        </button>

                        <button
                            type="button"
                            onclick="document.querySelectorAll('.lords-table-checkbox').forEach((box) => box.checked = false)"
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
                        action="{{ route('church-database.attendance-sheets.lords-table.store') }}"
                        class="mt-5"
                    >
                        @csrf
                            <input
                                type="hidden"
                                name="meeting_time"
                                value="{{ request('meeting_time', substr((string) ($selectedSheet->meeting_time ?? ''), 0, 5)) }}"
                            >


                        <input type="hidden" name="locality" value="{{ $selectedLocality }}">
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
                                                    class="lords-table-checkbox h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
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
                            Save Lord's Table Attendance
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
