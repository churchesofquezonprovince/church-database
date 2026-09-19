<x-filament-panels::page>
    @php
        $sheets = $this->sheets();
        $selectedSheet = $this->selectedSheet();
        $sessions = $this->sessions();
        $selectedSession = $this->selectedSession();
        $participantRows = $this->participantRows();
        $presentPersonIds = $this->presentPersonIds();
        $recordCounts = $this->recordCounts();
        $attendanceRecords = $this->attendanceRecords();
        $immichConfirmationCounts = $this->immichConfirmationCounts();

        /*
         * Spreadsheet / Grid view only makes sense when the
         * Attendance Sheet represents multiple meeting dates.
         */
        $gridAvailable =
            $selectedSheet
            && ! $selectedSheet->is_one_time
            && $sessions->count() > 1;

        $attendanceGrid =
            $gridAvailable
            && $this->attendanceView === 'grid'
                ? $this->attendanceGrid(
                    $selectedSheet,
                    $sessions
                )
                : [
                    'sessions' => collect(),
                    'rows' => collect(),
                ];

        $lordsTableLocalities =
            $this->permanentMeetingLocalities(
                \App\Models\AttendanceSheet::TYPE_LORDS_TABLE
            );

        $prayerMeetingLocalities =
            $this->permanentMeetingLocalities(
                \App\Models\AttendanceSheet::TYPE_PRAYER_MEETING
            );
        $immichPresentCount =
    $attendanceRecords
        ->filter(
            fn ($record) =>
                $record->attendance_source
                    === \App\Models\AttendanceRecord::SOURCE_IMMICH
                &&
                $record->is_present
        )
        ->count();

        @endphp

    <div class="space-y-6">
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

    'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950'
        => $selectedSheet?->id === $sheet->id,

    'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-800'
        => $selectedSheet?->id !== $sheet->id,
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

                        <details class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950">
                            <summary class="cursor-pointer font-bold text-amber-900 dark:text-amber-100">
                                Lord's Table Meeting
                            </summary>

                            <div class="mt-3 space-y-2">
                                @forelse ($lordsTableLocalities as $row)
                                    <a
                                        href="{{ $this->permanentMeetingUrl(\App\Models\AttendanceSheet::TYPE_LORDS_TABLE, $row['locality'], $row['sheet']) }}"
                                        class="block rounded-lg border border-amber-200 bg-white p-3 hover:bg-amber-100 dark:border-amber-900 dark:bg-gray-950 dark:hover:bg-amber-950"
                                    >
                                        <p class="font-bold text-gray-900 dark:text-white">
                                            {{ $row['label'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $row['sessions_count'] }} date(s)
                                            · {{ $row['participants_count'] }} participant(s)
                                        </p>
                                    </a>
                                @empty
                                    <p class="rounded-lg border border-dashed border-amber-300 p-3 text-xs text-amber-800 dark:border-amber-900 dark:text-amber-100">
                                        No localities found.
                                    </p>
                                @endforelse
                            </div>
                        </details>

                        <details class="rounded-xl border border-sky-200 bg-sky-50 p-3 dark:border-sky-900 dark:bg-sky-950">
                            <summary class="cursor-pointer font-bold text-sky-900 dark:text-sky-100">
                                Prayer Meeting
                            </summary>

                            <div class="mt-3 space-y-2">
                                @forelse ($prayerMeetingLocalities as $row)
                                    <a
                                        href="{{ $this->permanentMeetingUrl(\App\Models\AttendanceSheet::TYPE_PRAYER_MEETING, $row['locality'], $row['sheet']) }}"
                                        class="block rounded-lg border border-sky-200 bg-white p-3 hover:bg-sky-100 dark:border-sky-900 dark:bg-gray-950 dark:hover:bg-sky-950"
                                    >
                                        <p class="font-bold text-gray-900 dark:text-white">
                                            {{ $row['label'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $row['sessions_count'] }} date(s)
                                            · {{ $row['participants_count'] }} participant(s)
                                        </p>
                                    </a>
                                @empty
                                    <p class="rounded-lg border border-dashed border-sky-300 p-3 text-xs text-sky-800 dark:border-sky-900 dark:text-sky-100">
                                        No localities found.
                                    </p>
                                @endforelse
                            </div>
                        </details>
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
                                    'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100' => $selectedSession?->id === $session->id,
                                    'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800' => $selectedSession?->id !== $session->id,
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
                @if (! $selectedSheet || ! $selectedSession)
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                        Select an attendance sheet and meeting date.
                    </div>
                @else
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div
                style="
                    flex-shrink: 0;
                    white-space: nowrap;
                "
            >
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $selectedSheet->title }}
                                </h3>

                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $selectedSheet->locality ?: 'No locality' }}

                                    @if (
                                        $gridAvailable
                                        && $this->attendanceView === 'grid'
                                    )
                                        · Attendance Grid
                                        · {{ $sessions->count() }} meeting date(s)
                                    @else
                                        · {{ $selectedSession->dateTimeLabel('l, F d, Y') }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if ($gridAvailable)
                                    <div
                                        class="inline-flex overflow-hidden
                                               rounded-lg border
                                               border-gray-300
                                               dark:border-gray-700"
                                    >
                                        <button
                                            type="button"
                                            wire:click="setAttendanceView('checklist')"
                                            wire:loading.attr="disabled"
                                            @class([
                                                'px-3 py-1 text-xs font-bold transition',
                                                'bg-emerald-600 text-white'
                                                    => $this->attendanceView === 'checklist',
                                                'bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
                                                    => $this->attendanceView !== 'checklist',
                                            ])
                                        >
                                            Checklist
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="setAttendanceView('grid')"
                                            wire:loading.attr="disabled"
                                            @class([
                                                'border-l border-gray-300 px-3 py-1 text-xs font-bold transition dark:border-gray-700',
                                                'bg-primary-600 text-white'
                                                    => $this->attendanceView === 'grid',
                                                'bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
                                                    => $this->attendanceView !== 'grid',
                                            ])
                                        >
                                            Grid
                                        </button>
                                    </div>
                                @endif

                                <a
                                    href="{{ \App\Filament\Pages\AttendanceReports::getUrl() . '?sheetId=' . $selectedSheet->id }}"
                                    class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white hover:bg-primary-500"
                                >
                                    View Report
                                </a>

                                @if (
                                    ! $gridAvailable
                                    || $this->attendanceView === 'checklist'
                                )
                                    <span class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white">
                                        Present: {{ $recordCounts['present'] }}
                                    </span>

                                    <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">
                                        Absent: {{ $recordCounts['absent'] }}
                                    </span>

                                    <span class="rounded-full bg-gray-600 px-3 py-1 text-xs font-bold text-white">
                                        Participants: {{ $participantRows->count() }}
                                    </span>
                                @else
                                    <span class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white">
                                        Dates:
                                        {{ $attendanceGrid['sessions']->count() }}
                                    </span>

                                    <span class="rounded-full bg-gray-600 px-3 py-1 text-xs font-bold text-white">
                                        People:
                                        {{ $attendanceGrid['rows']->count() }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>






@if (
    ! $gridAvailable
    || $this->attendanceView === 'checklist'
)
@if ($immichConfirmationCounts['detected'] > 0)
    <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold text-violet-900 dark:text-violet-100">
                    Immich Attendance Review
                </p>

                <p class="mt-1 text-xs text-violet-700 dark:text-violet-200">
                    Immich has automatically marked
                    {{ $immichConfirmationCounts['detected'] }}
                    attendance record(s) as present.
                    Review and confirm them below.
                </p>
            </div>

            <div class="flex flex-wrap gap-2 text-xs font-bold">
                <span class="rounded-full bg-violet-600 px-3 py-1 text-white">
                    Detected: {{ $immichConfirmationCounts['detected'] }}
                </span>

                <span class="rounded-full bg-amber-500 px-3 py-1 text-white">
                    Pending: {{ $immichConfirmationCounts['pending'] }}
                </span>

                <span class="rounded-full bg-emerald-600 px-3 py-1 text-white">
                    Confirmed: {{ $immichConfirmationCounts['confirmed'] }}
                </span>
            </div>
        </div>
    </div>
@endif



                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Attendance Checklist
                                </h3>
                            </div>

                            <div class="flex gap-2">


@if ($attendanceRecords->where('attendance_source', \App\Models\AttendanceRecord::SOURCE_IMMICH)->where('is_present', true)->where('immich_confirmed', false)->isNotEmpty())
    <button
        type="button"
        wire:click="confirmAllImmichAttendance"
        wire:confirm="Confirm all pending Immich attendance records for this session? This records that an administrator reviewed them."
        wire:loading.attr="disabled"
        class="rounded-lg border border-violet-300 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-200"
    >
        Confirm All Pending Immich
    </button>
@endif


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

<div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Present
        </p>

        <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">
            {{ $recordCounts['present'] }}
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Absent
        </p>

        <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">
            {{ $recordCounts['absent'] }}
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Participants
        </p>

        <p class="mt-1 text-xl font-bold text-sky-600 dark:text-sky-400">
            {{ $participantRows->count() }}
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Immich Present
        </p>

        <p class="mt-1 text-xl font-bold text-violet-600 dark:text-violet-400">
            {{ $immichPresentCount }}
        </p>

        @if ($immichConfirmationCounts['pending'] > 0)
            <p class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-300">
                {{ $immichConfirmationCounts['pending'] }}
                pending review
            </p>
        @endif
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
                                action="{{ route('quezonprovinceactivities.attendance-sheets.records.store', ['session' => $selectedSession]) }}"
                                class="mt-5"
                            >
                                @csrf

<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-950">
<tr>
    <th class="w-20 px-3 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">
        Present
    </th>

    <th class="min-w-44 px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
        Name
    </th>

    <th class="min-w-40 px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
        Participant
    </th>

<th class="min-w-40 px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
    Attendance
</th>

<th class="min-w-40 px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
    Review
</th>
</tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
@foreach ($participantRows as $participant)
    @php
        $person =
            $participant->person;

        $personId =
            (int) $participant->person_id;


        $record =
            $attendanceRecords
                ->get($personId);

        $sessionDate =
            $selectedSession
                ->session_date
                ->format('Y-m-d');

        /*
         * PARTICIPANT STATUS
         *
         * participantRows() may create a temporary,
         * unsaved AttendanceParticipant when a Person
         * has an attendance record but is not formally
         * enrolled as a participant.
         */
        if (! $participant->exists) {
            $participantStatus =
                'Not added';

            $participantStatusDetail =
                'Attendance record only';
        } elseif (
            $participant->starts_on?->format('Y-m-d')
            === $sessionDate
            &&
            $participant->ends_on?->format('Y-m-d')
            === $sessionDate
        ) {
            $participantStatus =
                'This meeting only';

            $participantStatusDetail =
                null;
        } elseif (
            $participant->starts_on?->format('Y-m-d')
            === $sessionDate
            &&
            blank($participant->ends_on)
        ) {
            $participantStatus =
                'From this meeting onward';

            $participantStatusDetail =
                null;
        } elseif (
            blank($participant->starts_on)
            &&
            blank($participant->ends_on)
        ) {
            $participantStatus =
                'All meetings';

            $participantStatusDetail =
                null;
        } elseif (
            blank($participant->ends_on)
        ) {
            $participantStatus =
                'Ongoing';

            $participantStatusDetail =
                $participant->starts_on
                    ? 'Since '
                        . $participant
                            ->starts_on
                            ->format('M d, Y')
                    : null;
        } else {
            $participantStatus =
                'Date range';

            $participantStatusDetail =
                collect([
                    $participant->starts_on
                        ?->format('M d, Y'),

                    $participant->ends_on
                        ?->format('M d, Y'),
                ])
                    ->filter()
                    ->implode(' → ');
        }

        /*
         * ACTUAL ATTENDANCE STATUS
         */
        if (! $record) {
            $attendanceStatus =
                'Not recorded';
        } elseif ($record->is_present) {
            $attendanceStatus =
                'Present';
        } else {
            $attendanceStatus =
                'Absent';
        }

    @endphp

                                                <tr>
                                                    <td class="px-3 py-3 text-center">
                                                        <input
                                                            type="checkbox"
                                                            name="present_person_ids[]"
                                                            value="{{ $personId }}"
                                                            @checked(in_array($personId, $presentPersonIds, true))
                                                            class="attendance-checkbox h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                        >
                                                    </td>

<td class="min-w-44 px-3 py-3">
    <div class="space-y-1">
        <p class="font-semibold text-gray-900 dark:text-white">
            {{ $person?->display_name ?? 'Unknown person' }}
        </p>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $person?->locality ?: 'No locality' }}
        </p>

        <p class="text-xs text-gray-400 dark:text-gray-500">
            {{ $person?->churchProfile?->category ?: 'No category' }}
        </p>
    </div>
</td>

<td class="px-3 py-3">
    @if ($participant->exists)
        <div class="space-y-1">
            <span
                class="inline-flex rounded-full bg-sky-100 px-2 py-1 text-xs font-bold text-sky-800 dark:bg-sky-900 dark:text-sky-100"
            >
                {{ $participantStatus }}
            </span>

            @if ($participantStatusDetail)
                <p
                    class="text-xs text-gray-400 dark:text-gray-500"
                >
                    {{ $participantStatusDetail }}
                </p>
            @endif
        </div>
    @else
        <div class="space-y-1">
            <span
                class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
            >
                Not added
            </span>

            <p
                class="text-xs text-gray-400 dark:text-gray-500"
            >
                Attendance record only
            </p>
        </div>
    @endif
</td>

<td class="px-3 py-3">
    <div class="space-y-2">
        {{-- Actual attendance --}}
        @if ($attendanceStatus === 'Present')
            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                Present
            </span>

        @elseif ($attendanceStatus === 'Absent')
            <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-800 dark:bg-red-900 dark:text-red-100">
                Absent
            </span>

        @else
            <span class="text-xs text-gray-400 dark:text-gray-500">
                Not recorded
            </span>
        @endif

        {{-- Attendance source --}}
        @if ($record?->attendance_source === \App\Models\AttendanceRecord::SOURCE_IMMICH)
            <div>
                <span class="inline-flex rounded-full bg-violet-100 px-2 py-1 text-xs font-bold text-violet-800 dark:bg-violet-900 dark:text-violet-100">
                    Immich
                </span>
            </div>


        @elseif ($record?->attendance_source === \App\Models\AttendanceRecord::SOURCE_MANUAL)
            <div>
                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    Manual
                </span>
            </div>
        @endif
    </div>
</td>


<td class="px-3 py-3">
    @if (
        $record?->attendance_source
        === \App\Models\AttendanceRecord::SOURCE_IMMICH
        &&
        $record->is_present
    )
        @if ($record->immich_confirmed)
            <div class="space-y-1">
                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                    Confirmed
                </span>

                @if ($record->immichConfirmedBy)
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        by {{ $record->immichConfirmedBy->name }}
                    </p>
                @endif

                @if ($record->immich_confirmed_at)
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $record->immich_confirmed_at->format('M d, Y · g:i A') }}
                    </p>
                @endif
            </div>
        @else
            <div class="space-y-2">
                <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                    Pending Review
                </span>

                <button
                    type="button"
                    wire:click="confirmImmichAttendance({{ $personId }})"
                    wire:loading.attr="disabled"
                    wire:target="confirmImmichAttendance({{ $personId }})"
                    class="block rounded-lg border border-violet-300 bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-200"
                >
                    Confirm
                </button>
            </div>
        @endif
    @else
        <span class="text-xs text-gray-400 dark:text-gray-500">
            —
        </span>
    @endif
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
@else
    {{-- ============================================= --}}
    {{-- Whole-Sheet Attendance Grid                  --}}
    {{-- ============================================= --}}

    <div
        class="overflow-hidden rounded-2xl border
               border-gray-200 bg-white shadow-sm
               dark:border-gray-700 dark:bg-gray-900"
    >
        <div
            class="border-b border-gray-200 px-5 py-4
                   dark:border-gray-700"
            style="
                position: relative;
                padding-right: 7.5rem;
            "
        >
            <div
                style="
                    display: flex;
                    align-items: center;
                    width: 100%;
                    gap: 1.5rem;
                "
            >
            <div>
                <h3
                    class="text-lg font-bold
                           text-gray-900 dark:text-white"
                >
                    Attendance Grid
                </h3>

            </div>

            <div
                class="text-xs text-gray-500 dark:text-gray-400"
                style="
                    display: flex;
                    flex: 1 1 auto;
                    flex-wrap: nowrap;
                    align-items: center;
                    column-gap: 1rem;
                    min-width: 0;
                "
            >
                <span
                    style="
                        display: inline-flex;
                        align-items: center;
                        gap: 0.25rem;
                        white-space: nowrap;
                    "
                >
                    <strong
                        class="text-emerald-600
                               dark:text-emerald-400"
                    >
                        ✓
                    </strong>
                    Present
                </span>

                <span
                    style="
                        display: inline-flex;
                        align-items: center;
                        gap: 0.25rem;
                        white-space: nowrap;
                    "
                >
                    <strong
                        class="text-red-600
                               dark:text-red-400"
                    >
                        A
                    </strong>
                    Absent
                </span>

                <span
                    style="
                        display: inline-flex;
                        align-items: center;
                        gap: 0.25rem;
                        white-space: nowrap;
                    "
                >
                    <strong
                        class="text-amber-600
                               dark:text-amber-400"
                    >
                        ·
                    </strong>
                    Not recorded
                </span>

                <span
                    style="
                        display: inline-flex;
                        align-items: center;
                        gap: 0.25rem;
                        white-space: nowrap;
                    "
                >
                    <strong
                        class="text-gray-400
                               dark:text-gray-500"
                    >
                        —
                    </strong>
                    Not on roster
                </span>

                <button
                    type="button"
                    style="
                        position: absolute;
                        right: 1.25rem;
                        top: 50%;
                        transform: translateY(-50%);
                        white-space: nowrap;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                    "
                    wire:click="toggleGridEditMode"
                    wire:loading.attr="disabled"
                    wire:target="toggleGridEditMode"
                    @class([
                        'rounded-lg border px-3 py-1.5 text-xs font-bold transition',

                        'border-primary-600 bg-primary-600 text-white hover:bg-primary-500'
                            => ! $this->gridEditMode,

                        'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-500'
                            => $this->gridEditMode,
                    ])
                >
                    {{
                        $this->gridEditMode
                            ? 'Done Editing'
                            : 'Make Edits'
                    }}
                </button>
            </div>

            </div>

            <p
                class="mt-2 text-xs
                       text-gray-500 dark:text-gray-400"
            >
                Whole-sheet attendance history.
                Click a meeting date to select that session.
            </p>
        </div>

        @if ($this->gridEditMode)
            <div
                class="border-b border-amber-200
                       bg-amber-50 px-5 py-3
                       text-xs font-semibold
                       text-amber-800
                       dark:border-amber-900
                       dark:bg-amber-950/40
                       dark:text-amber-200"
            >
                @if ($this->gridEditSessionId)
                    @php
                        $editingSession =
                            $attendanceGrid['sessions']
                                ->firstWhere(
                                    'id',
                                    $this->gridEditSessionId
                                );
                    @endphp

                    @if ($editingSession)
                        Editing attendance for
                        <strong>
                            {{
                                $editingSession
                                    ->session_date
                                    ->format(
                                        'l, F d, Y'
                                    )
                            }}
                        </strong>.

                        Click an existing participant's cell
                        in this column to toggle Present / Absent.
                    @endif
                @else
                    Editing is enabled.
                    <strong>
                        Click a meeting date heading above the Grid
                        before changing attendance.
                    </strong>
                @endif
            </div>
        @endif

        @if ($attendanceGrid['rows']->isEmpty())
            <div
                class="p-8 text-center text-sm
                       text-gray-500 dark:text-gray-400"
            >
                No participant or attendance history
                is available for this Sheet yet.
            </div>
        @else
            <div
                wire:key="attendance-grid-{{ $selectedSheet->id }}"
                class="overflow-x-auto"
            >
                <table
                    class="min-w-max border-collapse
                           text-xs"
                >
                    <thead>
                        <tr>
                            <th
                                class="sticky left-0 top-0 z-30
                                       min-w-56 border
                                       border-gray-200
                                       bg-gray-100 px-3 py-3
                                       text-left font-bold
                                       text-gray-700
                                       dark:border-gray-700
                                       dark:bg-gray-800
                                       dark:text-gray-100"
                            >
                                Participant
                            </th>

                            @foreach (
                                $attendanceGrid['sessions']
                                as $gridSession
                            )
                                <th
                                    @class([
                                        'sticky top-0 z-20 min-w-24 border border-gray-200 px-2 py-2 text-center font-semibold dark:border-gray-700',

                                        'ring-2 ring-inset ring-amber-500'
                                            =>
                                                $this->gridEditMode
                                                &&
                                                ! $gridSession->is_no_meeting
                                                &&
                                                $this->gridEditSessionId
                                                ===
                                                (int) $gridSession->id,

                                        'bg-red-100 text-red-900 dark:bg-red-200 dark:text-red-900'
                                            =>
                                                $gridSession->is_no_meeting,

                                        'bg-primary-100 text-primary-900 dark:bg-primary-950 dark:text-primary-100'
                                            =>
                                                ! $gridSession->is_no_meeting
                                                &&
                                                $selectedSession?->id
                                                ===
                                                $gridSession->id,

                                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-100'
                                            =>
                                                ! $gridSession->is_no_meeting
                                                &&
                                                $selectedSession?->id
                                                !==
                                                $gridSession->id,
                                    ])
                                >
                                    <button
                                        type="button"
                                        wire:click="selectGridSession({{ $gridSession->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="selectGridSession({{ $gridSession->id }})"
                                        class="block w-full rounded
                                               hover:underline
                                               focus:outline-none
                                               focus:ring-2
                                               focus:ring-primary-500"
                                        title="Select {{
                                            $gridSession
                                                ->session_date
                                                ->format(
                                                    'F d, Y'
                                                )
                                        }}"
                                    >
                                        <span class="block">
                                            {{
                                                $gridSession
                                                    ->session_date
                                                    ->format('M d')
                                            }}
                                        </span>

                                        <span
                                            class="mt-0.5 block
                                                   text-[10px]
                                                   font-normal opacity-70"
                                        >
                                            {{
                                                $gridSession
                                                    ->session_date
                                                    ->format('D')
                                            }}
                                        </span>
                                    </button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach (
                            $attendanceGrid['rows']
                            as $gridRow
                        )
                            @php
                                $gridPerson =
                                    $gridRow['person'];
                            @endphp

                            <tr>
                                <th
                                    class="sticky left-0 z-10
                                           min-w-56 border
                                           border-gray-200
                                           bg-white px-3 py-2
                                           text-left
                                           dark:border-gray-700
                                           dark:bg-gray-900"
                                >
                                    <p
                                        class="font-semibold
                                               text-gray-900
                                               dark:text-white"
                                    >
                                        {{
                                            $gridPerson
                                                ?->display_name
                                            ?? 'Unknown person'
                                        }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-[10px]
                                               font-normal
                                               text-gray-400
                                               dark:text-gray-500"
                                    >
                                        {{
                                            $gridPerson
                                                ?->locality
                                            ?: 'No locality'
                                        }}
                                    </p>
                                </th>

                                @foreach (
                                    $attendanceGrid['sessions']
                                    as $gridSession
                                )
                                    @php
                                        $gridCell =
                                            $gridRow['cells'][
                                                (int)
                                                $gridSession->id
                                            ]
                                            ?? [
                                                'status' =>
                                                    'not_roster',

                                                'source' =>
                                                    null,

                                                'immich_pending' =>
                                                    false,
                                            ];

                                        $gridStatus =
                                            $gridCell[
                                                'status'
                                            ];

                                        $gridNoMeeting =
                                            (bool)
                                            $gridSession
                                                ->is_no_meeting;

                                        $gridCellEditable =
                                            ! $gridNoMeeting
                                            &&
                                            $this->gridEditMode
                                            &&
                                            $this->gridEditSessionId
                                                ===
                                                (int)
                                                $gridSession->id
                                            &&
                                            (
                                                $gridCell[
                                                    'on_roster'
                                                ]
                                                ?? false
                                            );

                                        $gridStatusLabel =
                                            match (
                                                $gridStatus
                                            ) {
                                                'present' =>
                                                    'Present',

                                                'absent' =>
                                                    'Absent',

                                                'not_recorded' =>
                                                    'Active participant; attendance not recorded',

                                                default =>
                                                    'Not on roster for this meeting',
                                            };
                                    @endphp

                                    <td
                                        @if ($gridCellEditable)
                                            wire:click="toggleGridAttendance({{ $gridSession->id }}, {{ $gridPerson->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleGridAttendance({{ $gridSession->id }}, {{ $gridPerson->id }})"
                                        @endif
                                        @class([
                                            'relative border border-gray-200 px-3 py-3 text-center font-bold dark:border-gray-700',

                                            'cursor-pointer transition hover:ring-2 hover:ring-inset hover:ring-amber-400'
                                                =>
                                                    $gridCellEditable,

                                            'ring-inset ring-1 ring-primary-300 dark:ring-primary-800'
                                                =>
                                                    $selectedSession?->id
                                                    ===
                                                    $gridSession->id,

                                            'bg-red-100 text-red-900 dark:bg-red-200 dark:text-red-900'
                                                =>
                                                    $gridNoMeeting,

                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                                =>
                                                    ! $gridNoMeeting
                                                    &&
                                                    $gridStatus
                                                    ===
                                                    'present',

                                            'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300'
                                                =>
                                                    ! $gridNoMeeting
                                                    &&
                                                    $gridStatus
                                                    ===
                                                    'absent',

                                            'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-300'
                                                =>
                                                    ! $gridNoMeeting
                                                    &&
                                                    $gridStatus
                                                    ===
                                                    'not_recorded',

                                            'bg-gray-50 text-gray-300 dark:bg-gray-950 dark:text-gray-600'
                                                =>
                                                    ! $gridNoMeeting
                                                    &&
                                                    $gridStatus
                                                    ===
                                                    'not_roster',
                                        ])
                                        title="{{
                                            $gridNoMeeting
                                                ? 'No meeting on this date'
                                                : $gridStatusLabel
                                        }}"
                                    >
                                        @if ($gridNoMeeting)
                                            <span
                                                class="select-none"
                                                aria-label="No meeting"
                                            >
                                                &nbsp;
                                            </span>
                                        @elseif (
                                            $gridStatus
                                            === 'present'
                                        )
                                            <span
                                                class="text-base"
                                            >
                                                ✓
                                            </span>

                                            @if (
                                                $gridCell[
                                                    'immich_pending'
                                                ]
                                            )
                                                <span
                                                    class="absolute
                                                           right-1 top-1
                                                           h-2 w-2
                                                           rounded-full
                                                           bg-amber-500"
                                                    title="Immich attendance pending review"
                                                ></span>
                                            @endif
                                        @elseif (
                                            $gridStatus
                                            === 'absent'
                                        )
                                            <span>
                                                A
                                            </span>
                                        @elseif (
                                            $gridStatus
                                            === 'not_recorded'
                                        )
                                            <span
                                                class="text-lg
                                                       leading-none"
                                            >
                                                ·
                                            </span>
                                        @else
                                            <span>
                                                —
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
                @endif
            </div>
        </div>
    </div>





<script>
/*
 * Check Attendance can change sheetId/sessionId through Livewire
 * without re-rendering Filament's sidebar navigation.
 *
 * Therefore the sidebar's Attendance Sheets href may still contain
 * the Session that existed when this page was initially rendered.
 *
 * Just before that navigation is followed, synchronize it with the
 * CURRENT browser URL.
 */
const attendanceSheetsNavigationUrl =
    @json(
        \App\Filament\Pages\AttendanceSheets::getUrl()
    );

const attendanceSheetsNavigationPath =
    new URL(
        attendanceSheetsNavigationUrl,
        window.location.origin
    ).pathname;

document.addEventListener(
    'click',
    (event) => {
        const link =
            event.target.closest('a[href]');

        if (! link) {
            return;
        }

        let destination;

        try {
            destination =
                new URL(
                    link.href,
                    window.location.origin
                );
        } catch {
            return;
        }

        if (
            destination.pathname
            !== attendanceSheetsNavigationPath
        ) {
            return;
        }

        const current =
            new URL(window.location.href);

        const sheetId =
            current.searchParams.get(
                'sheetId'
            );

        const sessionId =
            current.searchParams.get(
                'sessionId'
            );

        if (sheetId) {
            destination.searchParams.set(
                'sheetId',
                sheetId
            );
        } else {
            destination.searchParams.delete(
                'sheetId'
            );
        }

        if (sessionId) {
            destination.searchParams.set(
                'sessionId',
                sessionId
            );
        } else {
            destination.searchParams.delete(
                'sessionId'
            );
        }

        /*
         * "view=grid" belongs only to Check Attendance.
         * Attendance Sheets receives only Sheet + Session context.
         */
        destination.searchParams.delete(
            'view'
        );

        link.href =
            destination.toString();
    },
    true
);

document.addEventListener('DOMContentLoaded', () => {
    const searchUrl = @json(
        route(
            'quezonprovinceactivities.attendance-meeting-responses.person-search'
        )
    );

    document
        .querySelectorAll('[data-person-link-form]')
        .forEach((form) => {
            const searchInput =
                form.querySelector(
                    '[data-person-search]'
                );

            const status =
                form.querySelector(
                    '[data-person-search-status]'
                );

            const results =
                form.querySelector(
                    '[data-person-results]'
                );

            const personId =
                form.querySelector(
                    '[data-person-id]'
                );

            const selected =
                form.querySelector(
                    '[data-selected-person]'
                );

            const selectedName =
                form.querySelector(
                    '[data-selected-person-name]'
                );

            const clearButton =
                form.querySelector(
                    '[data-clear-person]'
                );

            let timer = null;
            let controller = null;


            function clearResults() {
                results.innerHTML = '';
                results.classList.add('hidden');
            }


            function clearSelection() {
                personId.value = '';
                selectedName.textContent = '';
                selected.classList.add('hidden');

                searchInput.value = '';
                searchInput.disabled = false;

                status.textContent =
                    'Search by first name, last name, or nickname.';

                searchInput.focus();
            }


            function choosePerson(person) {
                personId.value = person.id;

                selectedName.textContent =
                    person.name
                    + (
                        person.locality
                            ? ' · ' + person.locality
                            : ''
                    );

                selected.classList.remove('hidden');

                searchInput.value = person.name;
                searchInput.disabled = true;

                clearResults();

                status.textContent =
                    'Person selected.';
            }


            searchInput.addEventListener(
                'input',
                () => {
                    clearTimeout(timer);

                    const query =
                        searchInput.value.trim();

                    personId.value = '';
                    selected.classList.add('hidden');

                    if (query.length < 2) {
                        clearResults();

                        status.textContent =
                            'Type at least 2 characters.';

                        return;
                    }

                    timer = setTimeout(
                        async () => {
                            if (controller) {
                                controller.abort();
                            }

                            controller =
                                new AbortController();

                            status.textContent =
                                'Searching...';

                            try {
                                const response =
                                    await fetch(
                                        searchUrl
                                        + '?q='
                                        + encodeURIComponent(
                                            query
                                        ),
                                        {
                                            headers: {
                                                'Accept':
                                                    'application/json'
                                            },

                                            signal:
                                                controller.signal
                                        }
                                    );

                                if (! response.ok) {
                                    throw new Error(
                                        'Search failed'
                                    );
                                }

                                const data =
                                    await response.json();

                                clearResults();

                                const rows =
                                    Array.isArray(
                                        data.results
                                    )
                                        ? data.results
                                        : [];

                                if (rows.length === 0) {
                                    status.textContent =
                                        'No matching People found.';

                                    return;
                                }

                                rows.forEach(
                                    (person) => {
                                        const button =
                                            document.createElement(
                                                'button'
                                            );

                                        button.type =
                                            'button';

                                        button.className =
                                            'block w-full border-t border-gray-100 px-3 py-3 text-left first:border-t-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800';

                                        const name =
                                            document.createElement(
                                                'div'
                                            );

                                        name.className =
                                            'text-sm font-bold text-gray-900 dark:text-white';

                                        name.textContent =
                                            person.name;

                                        button.appendChild(
                                            name
                                        );

                                        if (
                                            person.locality
                                        ) {
                                            const locality =
                                                document.createElement(
                                                    'div'
                                                );

                                            locality.className =
                                                'mt-1 text-xs text-gray-500 dark:text-gray-400';

                                            locality.textContent =
                                                person.locality;

                                            button.appendChild(
                                                locality
                                            );
                                        }

                                        button.addEventListener(
                                            'click',
                                            () =>
                                                choosePerson(
                                                    person
                                                )
                                        );

                                        results.appendChild(
                                            button
                                        );
                                    }
                                );

                                results.classList.remove(
                                    'hidden'
                                );

                                status.textContent =
                                    rows.length
                                    + ' match(es).';
                            } catch (error) {
                                if (
                                    error.name
                                    === 'AbortError'
                                ) {
                                    return;
                                }

                                clearResults();

                                status.textContent =
                                    'Unable to search right now.';
                            }
                        },
                        250
                    );
                }
            );


            clearButton.addEventListener(
                'click',
                clearSelection
            );


            form.addEventListener(
                'submit',
                (event) => {
                    if (! personId.value) {
                        event.preventDefault();

                        status.textContent =
                            'Select a Person before linking.';

                        searchInput.focus();
                    }
                }
            );
        });
});
</script>



</x-filament-panels::page>
