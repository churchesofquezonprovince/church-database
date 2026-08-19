<x-filament-panels::page>
    <style>
        .attendance-sheets-page,
        .attendance-sheets-page * {
            box-sizing: border-box;
            min-width: 0;
        }

        .attendance-sheets-page h1,
        .attendance-sheets-page h2,
        .attendance-sheets-page h3,
        .attendance-sheets-page p,
        .attendance-sheets-page a,
        .attendance-sheets-page span,
        .attendance-sheets-page label,
        .attendance-sheets-page td,
        .attendance-sheets-page th {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .attendance-sheets-page table {
            width: 100%;
            table-layout: fixed;
        }

        .attendance-sheets-page td,
        .attendance-sheets-page th {
            white-space: normal;
            vertical-align: top;
        }

        @media (max-width: 640px) {
            .attendance-sheets-page table {
                min-width: 0 !important;
            }

            .attendance-sheets-page td,
            .attendance-sheets-page th {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
                font-size: 0.75rem;
            }

            .attendance-sheets-page td form button {
                width: 100%;
            }
        }
    </style>

    <div class="attendance-sheets-page">

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="text-sm font-bold text-gray-900 dark:text-white">
            Sheet Filter
        </p>

        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($this->modeOptions() as $modeValue => $modeLabel)
                <a
                    href="{{ $this->modeUrl($modeValue) }}"
                    class="rounded-full px-4 py-2 text-sm font-bold transition
                        {{ $this->selectedMode() === $modeValue
                            ? 'bg-primary-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                >
                    {{ $modeLabel }}
                </a>
            @endforeach
        </div>
    </div>

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
            <div class="grid min-w-0 gap-6 xl:grid-cols-4">
                <div class="min-w-0 space-y-4 xl:col-span-1">
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
                                        'block min-w-0 rounded-xl border p-3 transition',
                                        'border-primary-300 bg-primary-50 dark:border-primary-800 dark:bg-primary-950' => $selectedSheet->id === $sheet->id,
                                        'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-800' => $selectedSheet->id !== $sheet->id,
                                    ])
                                >
                                    <p class="break-words font-bold text-gray-900 dark:text-white">
                                        {{ $sheet->title }}
                                    </p>
                            <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $sheet->attendanceModeBadgeClass() }}">
                                    {{ $sheet->attendanceModeLabel() }}
                                </span>
                                <span>
                                    {{ $sheet->meetingTimeLabel() }}
                                </span>
                                <span>
                                    {{ $sheet->dateRangeLabel() }}
                                </span>
                            </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sheet->locality ?: 'No locality' }} · {{ $sheet->sessions_count }} date(s)
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="min-w-0 space-y-6 xl:col-span-3">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <h3 class="break-words text-2xl font-bold text-gray-900 dark:text-white">
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

<div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($selectedSheet->sessions->take(8) as $session)
        @php
            $sessionAttendance = $this->sessionAttendanceSummary($session);
        @endphp

        <a
            href="{{ $this->sessionUrl($session) }}"
            @class([
                'block rounded-xl border p-3 text-sm transition',
                'border-primary-300 bg-primary-50 text-primary-800 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-200'
                    => $this->selectedSession()?->id === $session->id,
                'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800'
                    => $this->selectedSession()?->id !== $session->id,
            ])
        >
            <div class="flex items-center justify-between gap-2">
                <span class="font-semibold">
                    {{ $session->session_date->format('M d, Y') }}
                </span>

                @if ($session->immichAlbum)
                    <span class="rounded-full bg-violet-600 px-2 py-1 text-[10px] font-bold text-white">
                        Immich
                    </span>
                @endif
            </div>

            @if ($sessionAttendance['marked'] > 0)
                <div class="mt-3 space-y-1 text-xs">
                    <p class="font-semibold text-emerald-700 dark:text-emerald-300">
                        {{ $sessionAttendance['present'] }} Present
                    </p>

                    <div class="flex flex-wrap gap-x-2 gap-y-1 text-gray-500 dark:text-gray-400">
                        @if ($sessionAttendance['immich'] > 0)
                            <span>
                                {{ $sessionAttendance['immich'] }} Immich
                            </span>
                        @endif

                        @if ($sessionAttendance['manual'] > 0)
                            <span>
                                {{ $sessionAttendance['manual'] }} Manual
                            </span>
                        @endif

                        @if ($sessionAttendance['immich_pending'] > 0)
                            <span class="font-semibold text-amber-600 dark:text-amber-300">
                                {{ $sessionAttendance['immich_pending'] }} pending
                            </span>
                        @endif
                    </div>
                </div>
            @else
                <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                    No attendance recorded
                </p>
            @endif
        </a>
    @endforeach
</div>

                        @if ($selectedSheet->sessions_count > 8)
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Showing first 8 dates only. Full attendance grid will be added in the next phase.
                            </p>
                        @endif

@php
    $selectedSession = $this->selectedSession();
    $immichAlbums = $this->immichAlbums();
    $selectedSessionAttendance = $selectedSession
        ? $this->sessionAttendanceSummary($selectedSession)
        : null;
@endphp

@php
    $selectedSession = $this->selectedSession();
    $immichAlbums = $this->immichAlbums();
@endphp

@if ($selectedSession)

<div class="mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Recorded Attendance
        </p>

        <h4 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
            {{ $selectedSession->session_date->format('M d, Y') }}
        </h4>
    </div>

    <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">
                Present
            </p>

            <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-200">
                {{ $selectedSessionAttendance['present'] }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Marked
            </p>

            <p class="mt-1 text-2xl font-bold text-gray-700 dark:text-gray-200">
                {{ $selectedSessionAttendance['marked'] }}
            </p>
        </div>

        <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950">
            <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                Immich
            </p>

            <p class="mt-1 text-2xl font-bold text-violet-700 dark:text-violet-200">
                {{ $selectedSessionAttendance['immich'] }}
            </p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300">
                Immich Pending
            </p>

            <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-200">
                {{ $selectedSessionAttendance['immich_pending'] }}
            </p>
        </div>
    </div>

    @if ($selectedSessionAttendance['immich_confirmed'] > 0)
        <div class="px-5 pb-5">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $selectedSessionAttendance['immich_confirmed'] }}
                Immich attendance record(s) confirmed by an administrator.
            </p>
        </div>
    @endif
</div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-violet-200 bg-violet-50 shadow-sm dark:border-violet-900 dark:bg-violet-950">
        <div class="border-b border-violet-200 px-5 py-4 dark:border-violet-900 sm:px-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                        Immich Attendance
                    </p>

                    <h4 class="mt-1 break-words text-lg font-bold text-violet-950 dark:text-white">
                        {{ $selectedSession->session_date->format('M d, Y') }}
                    </h4>

                    <p class="mt-1 text-xs text-violet-700 dark:text-violet-300">
                        This connects an Immich album to this attendance session.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6">

@if ($selectedSheet->immichAlbum)

    <div class="rounded-xl border border-violet-200 bg-white p-4 dark:border-violet-800 dark:bg-gray-950">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Linked Immich Album
                </p>

                <p class="mt-1 break-words text-lg font-bold text-gray-900 dark:text-white">
                    {{ $selectedSheet->immichAlbum->immich_album_name }}
                </p>

                <p class="mt-2 break-all text-xs text-gray-500 dark:text-gray-400">
                    {{ $selectedSheet->immichAlbum->immich_album_id }}
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Last sync:
                    {{ optional($selectedSheet->immichAlbum->last_synced_at)->format('M d, Y g:i A') ?? 'Never' }}
                </p>
            </div>

            <button
                type="button"
                wire:click="unlinkImmichAlbum({{ $selectedSheet->id }})"
                wire:confirm="Unlink this Immich album from the entire attendance sheet?"
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
            >
                Unlink Album
            </button>

        </div>

    </div>


            @else

                <div class="rounded-xl border border-violet-200 bg-white p-4 dark:border-violet-800 dark:bg-gray-950">

                    <div class="min-w-0">
                        <label
                            for="immich_album_id"
                            class="block text-sm font-bold text-violet-950 dark:text-violet-100"
                        >
                            Select Immich Album
                        </label>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Choose the album containing photographs from this specific meeting date.
                        </p>

                        @if (count($immichAlbums) > 0)

                            <select
                                id="immich_album_id"
                                wire:model="immichAlbumId"
                                class="mt-3 block w-full min-w-0 rounded-xl border border-violet-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-violet-900 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="">
                                    Select an Immich album...
                                </option>

                                @foreach ($immichAlbums as $album)
                                    <option value="{{ $album['id'] }}">
                                        {{ $album['albumName'] ?? 'Unnamed album' }}
                                        @if (isset($album['assetCount']))
                                            — {{ $album['assetCount'] }} photo(s)
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            <button
                                type="button"
                                wire:click="linkImmichAlbum({{ $selectedSession->id }}, @js($immichAlbumId))"
                                wire:loading.attr="disabled"
                                class="mt-4 w-full rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white hover:bg-violet-500 disabled:opacity-50"
                            >
                                <span wire:loading.remove>
                                    Link Album to This Session
                                </span>

                                <span wire:loading>
                                    Linking...
                                </span>
                            </button>

                        @else

                            <div class="mt-4 rounded-xl border border-dashed border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                                Unable to load Immich albums.
                            </div>

                        @endif

                    </div>
                </div>

            @endif

        </div>
    </div>
@endif
                        
                    </div>


                    <details class="min-w-0 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
                        <summary class="cursor-pointer px-4 py-4 text-lg font-bold text-emerald-900 hover:bg-emerald-100 dark:text-emerald-100 dark:hover:bg-emerald-900 sm:px-6">
                            Add Participants
                        </summary>

                        <div class="border-t border-emerald-200 p-4 dark:border-emerald-900 sm:p-6">
                            <p class="break-words text-sm text-emerald-700 dark:text-emerald-200">
                                Search and tap people to add them. Counting dates are optional.
                            </p>

                            <form
                            method="POST"
                            action="{{ route('quezonprovinceactivities.attendance-sheets.participants.store', ['sheet' => $selectedSheet]) }}"
                            class="mt-5 min-w-0 space-y-4"
                        >
                            @csrf

                            <div class="min-w-0">
                                <label for="participant_search" class="block break-words text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                    Search people
                                </label>

                                <input
                                    id="participant_search"
                                    type="search"
                                    placeholder="Search name, locality, status, or category..."
                                    oninput="
                                        const q = this.value.toLowerCase();
                                        this.closest('form').querySelectorAll('[data-person-card]').forEach((card) => {
                                            card.hidden = ! card.dataset.searchText.includes(q);
                                        });
                                    "
                                    class="mt-2 block w-full min-w-0 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-base text-gray-900 shadow-sm dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                                >

                                <div class="mt-3 flex min-w-0 flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onclick="this.closest('form').querySelectorAll('[data-person-card]:not([hidden]) input[type=checkbox]').forEach((box) => box.checked = true)"
                                        class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-600"
                                    >
                                        Select visible
                                    </button>

                                    <button
                                        type="button"
                                        onclick="this.closest('form').querySelectorAll('input[name=&quot;person_ids[]&quot;]').forEach((box) => box.checked = false)"
                                        class="rounded-lg border border-emerald-300 bg-white px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100"
                                    >
                                        Clear selected
                                    </button>
                                </div>

                                <div
                                    class="mt-3 min-w-0 space-y-2 rounded-xl border border-emerald-200 bg-white p-2 dark:border-emerald-900 dark:bg-gray-950"
                                    style="max-height: 22rem; overflow-y: auto; overflow-x: hidden;"
                                >
                                    @forelse ($availablePeople as $person)
                                        <label
                                            data-person-card
                                            data-search-text="{{ \Illuminate\Support\Str::lower(collect([
                                                $person->display_name,
                                                $person->locality,
                                                $person->churchProfile?->status,
                                                $person->churchProfile?->category,
                                                $person->contact_number,
                                            ])->filter()->implode(' ')) }}"
                                            class="flex w-full min-w-0 max-w-full cursor-pointer items-start gap-3 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm hover:bg-emerald-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-emerald-950"
                                        >
                                            <input
                                                type="checkbox"
                                                name="person_ids[]"
                                                value="{{ $person->id }}"
                                                class="mt-1 h-5 w-5 shrink-0 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            >

                                            <span class="block min-w-0 flex-1 overflow-hidden">
                                                <span class="block whitespace-normal break-words font-bold leading-snug text-gray-900 dark:text-white">
                                                    {{ $person->display_name }}
                                                </span>

                                                <span class="mt-0.5 block whitespace-normal break-words text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $person->locality ?: 'No locality' }}
                                                    @if ($person->churchProfile?->status)
                                                        · {{ $person->churchProfile->status }}
                                                    @endif
                                                    @if ($person->churchProfile?->category)
                                                        · {{ $person->churchProfile->category }}
                                                    @endif
                                                </span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="rounded-lg border border-dashed border-emerald-300 p-4 text-center text-sm text-emerald-700 dark:border-emerald-800 dark:text-emerald-200">
                                            All available people are already added to this sheet.
                                        </p>
                                    @endforelse
                                </div>

                                <p class="mt-2 break-words text-xs text-emerald-700 dark:text-emerald-200">
                                    Mobile tip: search first, then tap Select visible if needed.
                                </p>
                            </div>

                            <details class="min-w-0 overflow-hidden rounded-xl border border-emerald-200 bg-white p-4 dark:border-emerald-900 dark:bg-gray-950">
                                <summary class="cursor-pointer break-words text-sm font-bold text-emerald-900 dark:text-emerald-100">
                                    Counting dates optional
                                </summary>

                                <p class="mt-2 break-words text-xs text-emerald-700 dark:text-emerald-200">
                                    Use these only when a person should start or stop being counted on specific dates.
                                </p>

                                <div class="mt-4 grid min-w-0 gap-4 md:grid-cols-2">
                                    <div class="min-w-0">
                                        <label for="starts_on" class="block break-words text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                            Starts counting from
                                        </label>

                                        <input
                                            id="starts_on"
                                            name="starts_on"
                                            type="date"
                                            value="{{ optional($selectedSheet->start_date)->format('Y-m-d') }}"
                                            class="mt-2 block w-full min-w-0 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-base text-gray-900 shadow-sm dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                                        >
                                    </div>

                                    <div class="min-w-0">
                                        <label for="ends_on" class="block break-words text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                            Stops counting after
                                        </label>

                                        <input
                                            id="ends_on"
                                            name="ends_on"
                                            type="date"
                                            class="mt-2 block w-full min-w-0 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-base text-gray-900 shadow-sm dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                                        >
                                    </div>
                                </div>
                            </details>

                            <button
                                type="submit"
                                class="flex w-full justify-center rounded-xl bg-emerald-600 px-5 py-3 text-base font-bold text-white hover:bg-emerald-500"
                            >
                                Add Selected People
                            </button>
                            </form>
                        </div>
                    </details>


                    <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-6">
                        <h3 class="break-words text-lg font-bold text-gray-900 dark:text-white">
                            Participants
                        </h3>

                        <div class="mt-5 space-y-3 md:hidden">
                            @forelse ($participantRows as $participant)
                                <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                                    <p class="break-all text-base font-bold leading-snug text-gray-900 dark:text-white">
                                        {{ $participant->person?->display_name ?? 'Unknown person' }}
                                    </p>

                                    <div class="mt-3 grid gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <div class="min-w-0">
                                            <span class="font-bold text-gray-800 dark:text-gray-100">Locality:</span>
                                            <span class="break-all">{{ $participant->person?->locality ?: 'No locality' }}</span>
                                        </div>

                                        <div class="min-w-0">
                                            <span class="font-bold text-gray-800 dark:text-gray-100">Starts counting:</span>
                                            <span>{{ optional($participant->starts_on)->format('M d, Y') ?: 'Sheet start' }}</span>
                                        </div>

                                        <div class="min-w-0">
                                            <span class="font-bold text-gray-800 dark:text-gray-100">Stops after:</span>
                                            <span>{{ optional($participant->ends_on)->format('M d, Y') ?: 'No end' }}</span>
                                        </div>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('quezonprovinceactivities.attendance-sheets.participants.destroy', ['sheet' => $selectedSheet, 'participant' => $participant]) }}"
                                        onsubmit="return confirm('Remove this person from the attendance sheet?');"
                                        class="mt-4"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="w-full rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    No participants added yet.
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-5 hidden overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 md:block">
                            <table class="w-full table-fixed divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-950">
                                    <tr>
                                        <th class="w-[34%] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                                        <th class="w-[22%] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Locality</th>
                                        <th class="w-[16%] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Starts counting</th>
                                        <th class="w-[16%] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Stops after</th>
                                        <th class="w-[12%] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @forelse ($participantRows as $participant)
                                        <tr>
                                            <td class="break-all px-4 py-3 font-semibold leading-snug text-gray-900 dark:text-white">
                                                {{ $participant->person?->display_name ?? 'Unknown person' }}
                                            </td>

                                            <td class="break-all px-4 py-3 text-gray-500 dark:text-gray-400">
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
                                                    action="{{ route('quezonprovinceactivities.attendance-sheets.participants.destroy', ['sheet' => $selectedSheet, 'participant' => $participant]) }}"
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
    </div>
</x-filament-panels::page>
