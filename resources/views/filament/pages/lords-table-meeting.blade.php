<x-filament-panels::page>
    @php
        $localities = $this->localities();
        $selectedLocality = $this->selectedLocality();
        $selectedMeetingDate = $this->selectedMeetingDate();
        $selectedSheet = $this->selectedSheet();
        $selectedSession = $this->selectedSession();
        $selectedMeetingTime = request('meeting_time', substr((string) ($selectedSheet->meeting_time ?? ''), 0, 5));
        $people = $this->people();
        $categoryOptions = $people
            ->pluck('churchProfile.category')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $hasNoCategory = $people->contains(fn ($person) => blank($person->churchProfile?->category));
        $presentPersonIds = $this->presentPersonIds();
        $absentPersonIds = $this->absentPersonIds();

        $presentRowCount = $people
            ->filter(fn ($person) => in_array((int) $person->id, $presentPersonIds, true))
            ->count();

        $absentRowCount = $people
            ->filter(fn ($person) => in_array((int) $person->id, $absentPersonIds, true))
            ->count();

        $unmarkedRowCount = max($people->count() - $presentRowCount - $absentRowCount, 0);
        $counts = $this->counts();
        $selectedDateObject = \Carbon\CarbonImmutable::parse($selectedMeetingDate);
        $isSunday = $selectedDateObject->dayOfWeek === 0;
    @endphp

        @if (session('other_locality_attendee_removed'))
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                Other locality attendee removed from this meeting date.
            </div>
        @endif

        @if (session('other_locality_attendee_exists'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                This person is already marked present for this meeting date.
            </div>
        @endif

        @if (session('other_locality_attendee_added'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                Other locality attendee added and marked present.
            </div>
        @endif

        @if (isset($selectedSession) && $selectedSession)
            @php
                $otherLocalityCandidates = $this->otherLocalityCandidates();
                $otherLocalityPresentRecords = $this->otherLocalityPresentRecords();
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

                @if ($otherLocalityPresentRecords->isNotEmpty())
                    <div class="mt-5 rounded-xl border border-amber-200 bg-white p-4 dark:border-amber-900 dark:bg-gray-950">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                            Current other-locality attendees
                        </p>

                        <div class="mt-3 space-y-2">
                            @foreach ($otherLocalityPresentRecords as $record)
                                <div class="flex flex-col gap-3 rounded-lg bg-amber-50 p-3 text-sm dark:bg-amber-950 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            {{ $record->person?->display_name ?? 'Unknown person' }}
                                        </span>

                                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                                            {{ $record->person?->locality ?: 'No Locality' }}
                                            · marked {{ optional($record->marked_at)->format('M d, Y · g:i A') }}
                                        </span>
                                    </div>

                                    @if ($record->person)
                                        <form
                                            method="POST"
                                            action="{{ route('church-database.attendance-sheets.permanent-meeting.other-attendees.destroy', ['session' => $selectedSession, 'person' => $record->person]) }}"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                onclick="return confirm('Remove this other locality attendee from this meeting date?')"
                                                class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-500"
                                            >
                                                Remove
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
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
                        Lord's Day Date
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

                    <div class="grid grid-cols-2 gap-2 sm:flex">
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

                        <div class="mb-4 grid gap-3 lg:grid-cols-[1fr_240px_auto] lg:items-end">
                            <div>
                                <label for="lords_table_participant_search" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    Search Participants
                                </label>

                                <input
                                    id="lords_table_participant_search"
                                    data-participant-search
                                    type="search"
                                    placeholder="Search name, category, or contact..."
                                    oninput="filterPermanentMeetingChecklist(this.closest('form'))"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    Category
                                </label>

                                <select
                                    data-participant-category-filter
                                    onchange="filterPermanentMeetingChecklist(this.closest('form'))"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                >
                                    <option value="__all">All Categories</option>

                                    @foreach ($categoryOptions as $category)
                                        <option value="{{ \Illuminate\Support\Str::lower($category) }}">
                                            {{ $category }}
                                        </option>
                                    @endforeach

                                    @if ($hasNoCategory)
                                        <option value="__no_category">No category</option>
                                    @endif
                                </select>
                            </div>

                            <button
                                type="button"
                                onclick="const form = this.closest('form'); const search = form.querySelector('[data-participant-search]'); const category = form.querySelector('[data-participant-category-filter]'); search.value = ''; category.value = '__all'; filterPermanentMeetingChecklist(form); search.focus();"
                                class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900"
                            >
                                Clear Filters
                            </button>
                        </div>

                        <input type="hidden" data-attendance-status-filter value="all">

                        <div class="mb-4 flex flex-wrap gap-2">
                            <button
                                type="button"
                                data-attendance-status-button
                                onclick="setPermanentMeetingStatusFilter(this, 'all')"
                                class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 ring-2 ring-primary-500 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900"
                            >
                                All {{ $people->count() }}
                            </button>

                            <button
                                type="button"
                                data-attendance-status-button
                                onclick="setPermanentMeetingStatusFilter(this, 'present')"
                                class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
                            >
                                Present {{ $presentRowCount }}
                            </button>

                            <button
                                type="button"
                                data-attendance-status-button
                                onclick="setPermanentMeetingStatusFilter(this, 'absent')"
                                class="rounded-xl border border-red-300 bg-red-50 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                            >
                                Absent {{ $absentRowCount }}
                            </button>

                            <button
                                type="button"
                                data-attendance-status-button
                                onclick="setPermanentMeetingStatusFilter(this, 'unmarked')"
                                class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-700 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
                            >
                                Unmarked {{ $unmarkedRowCount }}
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="min-w-[720px] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-950">
                                    <tr>
                                        <th class="w-20 px-3 py-3 sm:px-4 text-center font-semibold text-gray-700 dark:text-gray-200">Present</th>
                                        <th class="px-3 py-3 sm:px-4 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                                        <th class="px-3 py-3 sm:px-4 text-left font-semibold text-gray-700 dark:text-gray-200">Category</th>
                                        <th class="px-3 py-3 sm:px-4 text-left font-semibold text-gray-700 dark:text-gray-200">Contact</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach ($people as $person)
                                        @php
                                            $attendanceStatus = in_array((int) $person->id, $presentPersonIds, true)
                                                ? 'present'
                                                : (in_array((int) $person->id, $absentPersonIds, true) ? 'absent' : 'unmarked');
                                        @endphp

                                        <tr
                                            data-category="{{ \Illuminate\Support\Str::lower($person->churchProfile?->category ?: '__no_category') }}"
                                            data-initial-attendance-status="{{ $attendanceStatus }}"
                                            data-attendance-status="{{ $attendanceStatus }}"
                                        >
                                            <td class="px-3 py-3 text-center sm:px-4">
                                                <input
                                                    type="checkbox"
                                                    name="present_person_ids[]"
                                                    value="{{ $person->id }}"
                                                    @checked(in_array((int) $person->id, $presentPersonIds, true))
                                                    data-attendance-checkbox
                                                    onchange="updatePermanentMeetingRowStatus(this); filterPermanentMeetingChecklist(this.closest('form'))"
                                                    class="lords-table-checkbox h-6 w-6 rounded border-gray-300 text-primary-600 focus:ring-primary-500 sm:h-5 sm:w-5"
                                                >
                                            </td>

                                            <td class="px-3 py-3 font-semibold sm:px-4 text-gray-900 dark:text-white">
                                                {{ $person->display_name }}
                                            </td>

                                            <td class="px-3 py-3 text-gray-500 sm:px-4 dark:text-gray-400">
                                                {{ $person->churchProfile?->category ?: 'No category' }}
                                            </td>

                                            <td class="px-3 py-3 text-gray-500 sm:px-4 dark:text-gray-400">
                                                {{ $person->contact_number ?: 'No contact' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button
                            type="submit"
                            class="mt-5 inline-flex w-full justify-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white hover:bg-primary-500 sm:w-auto sm:py-2"
                        >
                            Save Lord's Table Attendance
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
    <script>
        function filterPermanentMeetingChecklist(form) {
            const searchInput = form.querySelector('[data-participant-search]');
            const categoryFilter = form.querySelector('[data-participant-category-filter]');
            const statusFilter = form.querySelector('[data-attendance-status-filter]');

            const query = (searchInput?.value || '').toLowerCase().trim();
            const category = (categoryFilter?.value || '__all').toLowerCase();
            const status = (statusFilter?.value || 'all').toLowerCase();

            form.querySelectorAll('tbody tr[data-category]').forEach((row) => {
                const matchesSearch = query === '' || row.textContent.toLowerCase().includes(query);
                const matchesCategory = category === '__all' || row.dataset.category === category;
                const matchesStatus = status === 'all' || row.dataset.attendanceStatus === status;

                row.hidden = ! (matchesSearch && matchesCategory && matchesStatus);
            });
        }

        function setPermanentMeetingStatusFilter(button, status) {
            const form = button.closest('form');
            const statusFilter = form.querySelector('[data-attendance-status-filter]');

            statusFilter.value = status;

            form.querySelectorAll('[data-attendance-status-button]').forEach((statusButton) => {
                statusButton.classList.remove('ring-2', 'ring-primary-500');
            });

            button.classList.add('ring-2', 'ring-primary-500');

            filterPermanentMeetingChecklist(form);
        }

        function updatePermanentMeetingRowStatus(checkbox) {
            const row = checkbox.closest('tr[data-attendance-status]');

            if (! row) {
                return;
            }

            if (checkbox.checked) {
                row.dataset.attendanceStatus = 'present';

                return;
            }

            row.dataset.attendanceStatus = row.dataset.initialAttendanceStatus === 'present'
                ? 'absent'
                : row.dataset.initialAttendanceStatus;
        }
    </script>

</x-filament-panels::page>
