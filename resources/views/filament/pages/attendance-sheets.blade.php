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

@if (session('attendance_participant_removed'))
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
        <p class="font-bold">
            Participant removed from the attendance sheet.
        </p>

        <p class="mt-1 text-sm">
            @if (session('attendance_participant_removed_record'))
    The selected Session's non-present attendance record was also removed.
    Attendance from other Sessions and Immich detection history were preserved.
@else
    Attendance from other Sessions and Immich detection history were preserved.
@endif
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
                                        'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950'
    => $selectedSheet?->id === $sheet->id,
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
    href="{{
        \App\Filament\Pages\CheckAttendance::getUrl()
        . '?'
        . http_build_query([
            'sheetId' =>
                $selectedSheet->id,

            'sessionId' =>
                $this->selectedSession()?->id,
        ])
    }}"
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

@if (
    $selectedSheet->schedule_type
    === \App\Models\AttendanceSheet::SCHEDULE_MANUAL
)
    <div class="mt-5">
        @if (session('attendance_session_added'))
            <div
                class="mb-3 rounded-xl border border-emerald-200
                       bg-emerald-50 px-4 py-3 text-sm
                       font-semibold text-emerald-800
                       dark:border-emerald-900
                       dark:bg-emerald-950
                       dark:text-emerald-200"
            >
                Session added:
                {{ session('attendance_session_added_date') }}
            </div>
        @endif

        @php
            $manualSelectedSession =
                $this->selectedSession();
        @endphp

        <details
            @if ($errors->has('manual_session_date'))
                open
            @endif
            class="overflow-hidden rounded-xl
                   border border-violet-200
                   bg-violet-50
                   dark:border-violet-900
                   dark:bg-violet-950"
        >
            <summary
                class="cursor-pointer px-4 py-3
                       text-sm font-bold
                       text-violet-900
                       hover:bg-violet-100
                       dark:text-violet-100
                       dark:hover:bg-violet-900"
            >
                + Add Session Date
            </summary>

            <form
                method="POST"
                action="{{
                    route(
                        'quezonprovinceactivities.attendance-sheets.sessions.store',
                        $selectedSheet
                    )
                }}"
                class="border-t border-violet-200
                       p-4 dark:border-violet-900"
            >
                @csrf

                <div
                    class="flex flex-col gap-3
                           sm:flex-row sm:items-end"
                >
                    <div class="flex-1">
                        <label
                            for="manual_session_date_{{ $selectedSheet->id }}"
                            class="block text-xs font-semibold
                                   text-violet-800
                                   dark:text-violet-200"
                        >
                            Session Date
                        </label>

                        <input
                            id="manual_session_date_{{ $selectedSheet->id }}"
                            name="manual_session_date"
                            type="date"
                            value="{{
                                old('manual_session_date')
                            }}"
                            required
                            class="mt-2 block w-full rounded-xl
                                   border border-violet-300
                                   bg-white px-4 py-3 text-sm
                                   text-gray-900 shadow-sm
                                   dark:border-violet-800
                                   dark:bg-gray-950
                                   dark:text-gray-100"
                        >

                        @error('manual_session_date')
                            <p
                                class="mt-2 text-xs font-semibold
                                       text-red-600
                                       dark:text-red-400"
                            >
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center
                               justify-center rounded-xl
                               bg-violet-600 px-4 py-3
                               text-sm font-semibold
                               text-white shadow-sm
                               hover:bg-violet-500"
                    >
                        Add Session Date
                    </button>
                </div>

                <p
                    class="mt-3 text-xs
                           text-violet-700
                           dark:text-violet-300"
                >
                    The new Session inherits this Sheet's
                    default Start Time and End Time.
                </p>
            </form>

            @if ($manualSelectedSession)
                <div
                    class="mt-4 border-t border-violet-200
                           px-4 pb-4 pt-4
                           dark:border-violet-900"
                >
                    <p
                        class="mb-3 text-xs text-violet-700
                               dark:text-violet-300"
                    >
                        Remove the currently selected Manual Session
                        only when it has no attendance or related history.
                    </p>

                <form
                method="POST"
                action="{{
                    route(
                        'quezonprovinceactivities.attendance-sheets.sessions.destroy',
                        [
                            'sheet' => $selectedSheet,
                            'session' => $manualSelectedSession,
                        ]
                    )
                }}"
                class="mt-3"
                onsubmit="
                    return confirm(
                        'Remove the selected Session Date {{ $manualSelectedSession->session_date->format('M d, Y') }}? This is only allowed when the Session has no attendance or related history.'
                    );
                "
            >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="inline-flex items-center
                           justify-center rounded-xl
                           border border-red-300
                           px-4 py-2 text-xs font-bold
                           text-red-700
                           hover:bg-red-50
                           dark:border-red-800
                           dark:text-red-300
                           dark:hover:bg-red-950"
                >
                    Remove Selected Session Date
                    ·
                    {{
                        $manualSelectedSession
                            ->session_date
                            ->format('M d, Y')
                    }}
                </button>
                </form>
                </div>
            @endif

        </details>

        @if (session('attendance_session_removed'))
            <div
                class="mt-3 rounded-xl border border-emerald-200
                       bg-emerald-50 px-4 py-3 text-sm
                       font-semibold text-emerald-800
                       dark:border-emerald-900
                       dark:bg-emerald-950
                       dark:text-emerald-200"
            >
                Session removed:
                {{ session('attendance_session_removed_date') }}
            </div>
        @endif

        @error('manual_session_delete')
            <div
                class="mt-3 rounded-xl border border-red-200
                       bg-red-50 px-4 py-3 text-sm
                       font-semibold text-red-800
                       dark:border-red-900
                       dark:bg-red-950
                       dark:text-red-200"
            >
                {{ $message }}
            </div>
        @enderror

    </div>
@endif

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

                @if ($session->immichAssets->isNotEmpty())
                    <span
                        class="rounded-full bg-violet-600
                               px-2 py-1 text-[10px]
                               font-bold text-white"
                    >
                        Photo
                    </span>
                @elseif ($selectedSheet->immichAlbum)
                    <span
                        class="rounded-full bg-violet-600
                               px-2 py-1 text-[10px]
                               font-bold text-white"
                    >
                        Album
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

    $immichAlbums =
        $selectedSheet->immichAlbum
            ? []
            : $this->immichAlbums();

    $selectedSessionAttendance =
        $selectedSession
            ? $this->sessionAttendanceSummary(
                $selectedSession
            )
            : null;
@endphp

@if ($selectedSession)

<div class="mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
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

    <details
        class="mt-6 overflow-hidden rounded-2xl
               border border-violet-200
               bg-violet-50 shadow-sm
               dark:border-violet-900
               dark:bg-violet-950"
    >
        <summary
            class="cursor-pointer list-none
                   px-5 py-4
                   hover:bg-violet-100
                   dark:hover:bg-violet-900
                   sm:px-6"
        >
            <span
                class="block text-xs font-bold uppercase
                       tracking-wide text-violet-600
                       dark:text-violet-300"
            >
                Immich Attendance
            </span>

            <span
                class="mt-1 block text-lg font-bold
                       text-violet-950 dark:text-white"
            >
                {{ $selectedSession->session_date->format('M d, Y') }}
            </span>
        </summary>

        <div
            class="space-y-5 border-t
                   border-violet-200 p-5
                   dark:border-violet-900
                   sm:p-6"
        >
            <p
                class="text-xs text-violet-700
                       dark:text-violet-300"
            >
                Exact Session photos override the
                Attendance Sheet album.
            </p>

            {{-- Exact Session photos --}}
            <div
                class="rounded-xl border border-violet-200
                       bg-white p-4
                       dark:border-violet-800
                       dark:bg-gray-950"
            >
                <div>
                    <p
                        class="text-xs font-bold uppercase
                               tracking-wide text-gray-500
                               dark:text-gray-400"
                    >
                        Exact Immich Photos
                    </p>

                    <p
                        class="mt-1 text-sm text-gray-600
                               dark:text-gray-300"
                    >
                        Use this when the meeting has only
                        one photo, or when you want to select
                        specific photos instead of an album.
                    </p>
                </div>

                @if ($selectedSession->immichAssets->isNotEmpty())
                    <div
                        class="mt-4 rounded-xl
                               bg-violet-50 p-3
                               text-xs font-semibold
                               text-violet-700
                               dark:bg-violet-950
                               dark:text-violet-300"
                    >
                        Exact-photo mode is active.
                        The Sheet album will not be scanned
                        for this Session.
                    </div>

                    <div class="mt-3 space-y-2">
                        @foreach (
                            $selectedSession->immichAssets
                            as $assetLink
                        )
                            <div
                                class="flex flex-col gap-3
                                       rounded-xl border
                                       border-gray-200 p-3
                                       dark:border-gray-800
                                       sm:flex-row
                                       sm:items-center
                                       sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="break-words
                                               text-sm font-bold
                                               text-gray-900
                                               dark:text-white"
                                    >
                                        {{
                                            $assetLink
                                                ->immich_asset_name
                                            ?: 'Immich Photo'
                                        }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        @if ($assetLink->asset_taken_at)
                                            {{
                                                $assetLink
                                                    ->asset_taken_at
                                                    ->format(
                                                        'M d, Y g:i A'
                                                    )
                                            }}
                                            ·
                                        @endif

                                        <span class="break-all">
                                            {{
                                                $assetLink
                                                    ->immich_asset_id
                                            }}
                                        </span>
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    wire:click="unlinkImmichAsset({{ $assetLink->id }})"
                                    wire:confirm="Unlink this exact Immich photo? Existing attendance and detection history will be preserved."
                                    class="shrink-0 rounded-lg
                                           border border-red-200
                                           bg-red-50 px-3 py-2
                                           text-xs font-bold
                                           text-red-700
                                           hover:bg-red-100
                                           dark:border-red-900
                                           dark:bg-red-950
                                           dark:text-red-200"
                                >
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p
                        class="mt-4 text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        No exact photos linked to this Session.
                    </p>
                @endif

                <div class="mt-4">
                    <label
                        for="immich_asset_input"
                        class="block text-sm font-bold
                               text-gray-900 dark:text-white"
                    >
                        Add Immich Photo
                    </label>

                    <p
                        class="mt-1 text-xs text-gray-500
                               dark:text-gray-400"
                    >
                        Paste the Immich photo URL or
                        its asset UUID.
                    </p>

                    <input
                        id="immich_asset_input"
                        type="text"
                        wire:model="immichAssetInput"
                        placeholder="Immich photo URL or asset UUID..."
                        class="mt-3 block w-full rounded-xl
                               border border-violet-200
                               bg-white px-4 py-3
                               text-sm text-gray-900
                               dark:border-violet-900
                               dark:bg-gray-950
                               dark:text-white"
                    >

                    <button
                        type="button"
                        wire:click="linkImmichAsset({{ $selectedSession->id }})"
                        wire:loading.attr="disabled"
                        wire:target="linkImmichAsset"
                        class="mt-3 rounded-xl
                               bg-violet-600
                               px-4 py-2.5
                               text-sm font-bold text-white
                               hover:bg-violet-500
                               disabled:opacity-50"
                    >
                        <span
                            wire:loading.remove
                            wire:target="linkImmichAsset"
                        >
                            Add Immich Photo
                        </span>

                        <span
                            wire:loading
                            wire:target="linkImmichAsset"
                        >
                            Checking Photo...
                        </span>
                    </button>
                </div>
            </div>


            {{-- Sheet album fallback --}}
            <div
                class="rounded-xl border border-violet-200
                       bg-white p-4
                       dark:border-violet-800
                       dark:bg-gray-950"
            >
                <p
                    class="text-xs font-bold uppercase
                           tracking-wide text-gray-500
                           dark:text-gray-400"
                >
                    Attendance Sheet Album
                </p>

                @if ($selectedSheet->immichAlbum)
                    <div
                        class="mt-3 flex flex-col gap-4
                               lg:flex-row lg:items-center
                               lg:justify-between"
                    >
                        <div class="min-w-0">
                            <p
                                class="break-words text-lg
                                       font-bold text-gray-900
                                       dark:text-white"
                            >
                                {{
                                    $selectedSheet
                                        ->immichAlbum
                                        ->immich_album_name
                                }}
                            </p>

                            <p
                                class="mt-1 break-all
                                       text-xs text-gray-500
                                       dark:text-gray-400"
                            >
                                {{
                                    $selectedSheet
                                        ->immichAlbum
                                        ->immich_album_id
                                }}
                            </p>

                            <p
                                class="mt-2 text-xs
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                Used only when this Session
                                has no exact photos.
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="unlinkImmichAlbum({{ $selectedSheet->id }})"
                            wire:confirm="Unlink this Immich album from the entire attendance sheet?"
                            class="rounded-xl border
                                   border-red-200 bg-red-50
                                   px-4 py-2.5 text-sm
                                   font-bold text-red-700
                                   hover:bg-red-100
                                   dark:border-red-900
                                   dark:bg-red-950
                                   dark:text-red-200"
                        >
                            Unlink Album
                        </button>
                    </div>
                @else
                    <p
                        class="mt-2 text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Optional fallback for recurring
                        Attendance Sessions.
                    </p>

                    @if (count($immichAlbums) > 0)
                        <select
                            id="immich_album_id"
                            wire:model.change="immichAlbumId"
                            class="mt-3 block w-full
                                   rounded-xl border
                                   border-violet-200
                                   bg-white px-4 py-3
                                   text-sm text-gray-900
                                   dark:border-violet-900
                                   dark:bg-gray-950
                                   dark:text-white"
                        >
                            <option value="">
                                Select an Immich album...
                            </option>

                            @foreach ($immichAlbums as $album)
                                <option value="{{ $album['id'] }}">
                                    {{
                                        $album['albumName']
                                        ?? 'Unnamed album'
                                    }}

                                    @if (isset($album['assetCount']))
                                        —
                                        {{ $album['assetCount'] }}
                                        photo(s)
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        <button
                            type="button"
                            wire:click="linkImmichAlbum({{ $selectedSheet->id }})"
                            wire:loading.attr="disabled"
                            class="mt-3 rounded-xl
                                   bg-violet-600
                                   px-4 py-2.5
                                   text-sm font-bold
                                   text-white
                                   hover:bg-violet-500
                                   disabled:opacity-50"
                        >
                            Link Album to This Sheet
                        </button>
                    @else
                        <div
                            class="mt-4 rounded-xl border
                                   border-dashed border-red-300
                                   bg-red-50 p-4 text-sm
                                   text-red-700
                                   dark:border-red-900
                                   dark:bg-red-950
                                   dark:text-red-200"
                        >
                            Unable to load Immich albums.
                        </div>
                    @endif
                @endif
            </div>


            @if (
                $selectedSession->immichAssets->isNotEmpty()
                || $selectedSheet->immichAlbum
            )
                <div
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <button
                        type="button"
                        wire:click="syncImmich({{ $selectedSession->id }})"
                        wire:loading.attr="disabled"
                        wire:target="syncImmich"
                        class="w-full rounded-xl
                               bg-violet-600 px-5 py-3
                               text-sm font-bold text-white
                               hover:bg-violet-500
                               disabled:opacity-50"
                    >
                        <span
                            wire:loading.remove
                            wire:target="syncImmich"
                        >
                            Sync Immich Attendance
                        </span>

                        <span
                            wire:loading
                            wire:target="syncImmich"
                        >
                            Synchronizing...
                        </span>
                    </button>

                    <a
                        href="{{
                            \App\Filament\Pages\ImmichPeopleLinking::getUrl()
                            . '?'
                            . http_build_query([
                                'sheetId' =>
                                    $selectedSheet->id,

                                'sessionId' =>
                                    $selectedSession->id,
                            ])
                        }}"
                        class="inline-flex w-full
                               items-center justify-center
                               rounded-xl border
                               border-violet-300
                               bg-white px-5 py-3
                               text-sm font-bold
                               text-violet-700
                               hover:bg-violet-50
                               dark:border-violet-800
                               dark:bg-gray-950
                               dark:text-violet-200
                               dark:hover:bg-violet-950"
                    >
                        Immich People Linking
                    </a>
                </div>
            @endif

            @include('filament.pages.partials.attendance-sheets-immich-history')
        </div>
    </details>
@endif
                        
                    </div>


                    @include('filament.pages.partials.attendance-sheets-meeting-responses')

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

                            <details
                                class="min-w-0 overflow-hidden rounded-xl
                                       border border-sky-200 bg-sky-50
                                       dark:border-sky-900 dark:bg-sky-950"
                            >
                                <summary
                                    class="cursor-pointer px-4 py-3
                                           text-sm font-bold text-sky-900
                                           hover:bg-sky-100
                                           dark:text-sky-100
                                           dark:hover:bg-sky-900"
                                >
                                    Paste Participant List
                                </summary>

                                <div
                                    class="border-t border-sky-200 p-4
                                           dark:border-sky-900"
                                >
                                    <p
                                        class="text-xs text-sky-700
                                               dark:text-sky-300"
                                    >
                                        Paste a numbered or plain list of names.
                                        High-confidence matches will be checked
                                        below for you to review before adding.
                                    </p>

                                    <textarea
                                        data-pasted-participants
                                        data-storage-key="attendance-participant-list-{{ $selectedSheet->id }}"
                                        oninput="
                                            localStorage.setItem(
                                                this.dataset.storageKey,
                                                this.value
                                            )
                                        "
                                        rows="8"
                                        placeholder="1. Zedric Dalde&#10;2. Jhyrnol Cuaton&#10;3. Johnny Guyo"
                                        class="mt-3 block w-full rounded-xl
                                               border border-sky-200 bg-white
                                               px-4 py-3 text-sm text-gray-900
                                               shadow-sm
                                               dark:border-sky-900
                                               dark:bg-gray-950
                                               dark:text-gray-100"
                                    ></textarea>

                                    <div
                                        class="mt-3 flex flex-wrap gap-2"
                                    >
                                        <button
                                            type="button"
                                            onclick="window.matchPastedParticipantList(this)"
                                            class="rounded-lg bg-sky-600
                                                   px-3 py-2 text-xs
                                                   font-bold text-white
                                                   hover:bg-sky-500"
                                        >
                                            Parse & Match
                                        </button>

                                        <button
                                            type="button"
                                            onclick="window.clearPastedParticipantMatches(this)"
                                            class="rounded-lg border
                                                   border-sky-300 bg-white
                                                   px-3 py-2 text-xs font-bold
                                                   text-sky-800
                                                   hover:bg-sky-100
                                                   dark:border-sky-800
                                                   dark:bg-sky-950
                                                   dark:text-sky-100"
                                        >
                                            Clear Parsed Selections
                                        </button>
                                    </div>

                                    <div
                                        data-pasted-participant-results
                                        class="mt-3 hidden"
                                    ></div>
                                </div>
                            </details>

                            <div class="mt-4 min-w-0">
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
                                            data-person-name="{{ $person->display_name }}"
                                            data-person-id="{{ $person->id }}"
                                            data-person-nickname="{{ data_get($person, 'nickname', '') }}"
                                            data-search-text="{{ \Illuminate\Support\Str::lower(collect([
                                                $person->display_name,
                                                data_get($person, 'nickname'),
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

                    <div
                        data-existing-participants
                        class="hidden"
                        aria-hidden="true"
                    >
                        @foreach ($participantRows as $participant)
                            @if ($participant->person)
                                <span
                                    data-existing-participant
                                    data-person-id="{{ $participant->person->id }}"
                                    data-person-name="{{ $participant->person->display_name }}"
                                    data-person-nickname="{{ data_get($participant->person, 'nickname', '') }}"
                                ></span>
                            @endif
                        @endforeach
                    </div>

                    <script>
                        (() => {
                            const pastedParticipantTextarea =
                                document.querySelector(
                                    '[data-pasted-participants]'
                                );

                            if (
                                pastedParticipantTextarea
                                &&
                                pastedParticipantTextarea
                                    .dataset.storageKey
                            ) {
                                const savedList =
                                    localStorage.getItem(
                                        pastedParticipantTextarea
                                            .dataset.storageKey
                                    );

                                if (
                                    savedList !== null
                                    &&
                                    ! pastedParticipantTextarea.value
                                ) {
                                    pastedParticipantTextarea.value =
                                        savedList;
                                }
                            }

                            const normalizeParticipantName = (value) => {
                                return String(value ?? '')
                                    .toLowerCase()
                                    .normalize('NFD')
                                    .replace(/[\u0300-\u036f]/g, '')
                                    .replace(/\b(jr|sr|ii|iii|iv)\b/g, ' ')
                                    .replace(/[^a-z0-9]+/g, ' ')
                                    .replace(/\s+/g, ' ')
                                    .trim();
                            };

                            const participantNameTokens = (value) => {
                                return normalizeParticipantName(value)
                                    .split(' ')
                                    .filter(Boolean);
                            };

                            const participantEditDistance = (a, b) => {
                                if (a === b) {
                                    return 0;
                                }

                                if (! a.length) {
                                    return b.length;
                                }

                                if (! b.length) {
                                    return a.length;
                                }

                                const previous =
                                    Array.from(
                                        { length: b.length + 1 },
                                        (_, index) => index
                                    );

                                for (
                                    let i = 1;
                                    i <= a.length;
                                    i++
                                ) {
                                    const current = [i];

                                    for (
                                        let j = 1;
                                        j <= b.length;
                                        j++
                                    ) {
                                        const cost =
                                            a[i - 1] === b[j - 1]
                                                ? 0
                                                : 1;

                                        current[j] =
                                            Math.min(
                                                current[j - 1] + 1,
                                                previous[j] + 1,
                                                previous[j - 1] + cost
                                            );
                                    }

                                    previous.splice(
                                        0,
                                        previous.length,
                                        ...current
                                    );
                                }

                                return previous[b.length];
                            };

                            const participantTokenMatches = (
                                inputToken,
                                candidateToken
                            ) => {
                                if (
                                    inputToken === candidateToken
                                ) {
                                    return true;
                                }

                                /*
                                 * Initials such as "Roberto C."
                                 * may match "Roberto Caalaman".
                                 */
                                if (
                                    inputToken.length === 1
                                    &&
                                    candidateToken.startsWith(
                                        inputToken
                                    )
                                ) {
                                    return true;
                                }

                                /*
                                 * Allow one small spelling difference for
                                 * longer names, e.g. Virgilo / Virgilio.
                                 */
                                if (
                                    inputToken.length >= 5
                                    &&
                                    candidateToken.length >= 5
                                    &&
                                    Math.abs(
                                        inputToken.length
                                        - candidateToken.length
                                    ) <= 1
                                    &&
                                    participantEditDistance(
                                        inputToken,
                                        candidateToken
                                    ) <= 1
                                ) {
                                    return true;
                                }

                                return false;
                            };

                            const participantMatchScore = (
                                inputName,
                                candidateName
                            ) => {
                                const inputTokens =
                                    participantNameTokens(
                                        inputName
                                    );

                                const candidateTokens =
                                    participantNameTokens(
                                        candidateName
                                    );

                                if (
                                    ! inputTokens.length
                                    ||
                                    ! candidateTokens.length
                                ) {
                                    return 0;
                                }

                                if (
                                    normalizeParticipantName(
                                        inputName
                                    )
                                    ===
                                    normalizeParticipantName(
                                        candidateName
                                    )
                                ) {
                                    return 1;
                                }

                                const usedCandidateTokens =
                                    new Set();

                                let matched = 0;

                                for (
                                    const inputToken
                                    of inputTokens
                                ) {
                                    const candidateIndex =
                                        candidateTokens.findIndex(
                                            (
                                                candidateToken,
                                                index
                                            ) =>
                                                ! usedCandidateTokens.has(
                                                    index
                                                )
                                                &&
                                                participantTokenMatches(
                                                    inputToken,
                                                    candidateToken
                                                )
                                        );

                                    if (
                                        candidateIndex !== -1
                                    ) {
                                        usedCandidateTokens.add(
                                            candidateIndex
                                        );

                                        matched++;
                                    }
                                }

                                const coverage =
                                    matched
                                    /
                                    inputTokens.length;

                                if (coverage === 1) {
                                    return Math.min(
                                        0.99,
                                        0.90
                                        +
                                        (
                                            0.09
                                            *
                                            Math.min(
                                                1,
                                                inputTokens.length
                                                /
                                                candidateTokens.length
                                            )
                                        )
                                    );
                                }

                                return coverage * 0.80;
                            };

                            const escapeParticipantHtml = (value) => {
                                const div =
                                    document.createElement(
                                        'div'
                                    );

                                div.textContent =
                                    String(value ?? '');

                                return div.innerHTML;
                            };

                            window.clearPastedParticipantMatches =
                                (button) => {
                                    const form =
                                        button.closest('form');

                                    if (! form) {
                                        return;
                                    }

                                    form
                                        .querySelectorAll(
                                            'input[data-paste-selected="1"]'
                                        )
                                        .forEach(
                                            (checkbox) => {
                                                checkbox.checked =
                                                    false;

                                                delete checkbox.dataset
                                                    .pasteSelected;
                                            }
                                        );

                                    const results =
                                        form.querySelector(
                                            '[data-pasted-participant-results]'
                                        );

                                    if (results) {
                                        results.innerHTML = '';
                                        results.classList.add(
                                            'hidden'
                                        );
                                    }
                                };

                            window.matchPastedParticipantList =
                                (button) => {
                                    const form =
                                        button.closest('form');

                                    if (! form) {
                                        return;
                                    }

                                    const textarea =
                                        form.querySelector(
                                            '[data-pasted-participants]'
                                        );

                                    const results =
                                        form.querySelector(
                                            '[data-pasted-participant-results]'
                                        );

                                    if (
                                        ! textarea
                                        ||
                                        ! results
                                    ) {
                                        return;
                                    }

                                    /*
                                     * Clear only selections made by the
                                     * previous pasted-list run. Manually
                                     * checked People remain untouched.
                                     */
                                    form
                                        .querySelectorAll(
                                            'input[data-paste-selected="1"]'
                                        )
                                        .forEach(
                                            (checkbox) => {
                                                checkbox.checked =
                                                    false;

                                                delete checkbox.dataset
                                                    .pasteSelected;
                                            }
                                        );

                                    const names =
                                        textarea.value
                                            .split(/\r?\n/)
                                            .map(
                                                (line) =>
                                                    line
                                                        .replace(
                                                            /^\s*\d+\s*[\.\)\-:]?\s*/,
                                                            ''
                                                        )
                                                        .trim()
                                            )
                                            .filter(Boolean);

                                    if (! names.length) {
                                        results.innerHTML =
                                            '<div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">Paste at least one name first.</div>';

                                        results.classList.remove(
                                            'hidden'
                                        );

                                        return;
                                    }

                                    const people =
                                        Array.from(
                                            form.querySelectorAll(
                                                '[data-person-card]'
                                            )
                                        )
                                            .map(
                                                (card) => ({
                                                    card,
                                                    name:
                                                        card.dataset
                                                            .personName
                                                        ?? '',
                                                    nickname:
                                                        card.dataset
                                                            .personNickname
                                                        ?? '',
                                                    checkbox:
                                                        card.querySelector(
                                                            'input[name="person_ids[]"]'
                                                        ),
                                                })
                                            )
                                            .filter(
                                                (person) =>
                                                    person.checkbox
                                            );

                                    const existingParticipants =
                                        Array.from(
                                            document.querySelectorAll(
                                                '[data-existing-participant]'
                                            )
                                        )
                                            .map(
                                                (element) => ({
                                                    name:
                                                        element.dataset
                                                            .personName
                                                        ?? '',
                                                    nickname:
                                                        element.dataset
                                                            .personNickname
                                                        ?? '',
                                                })
                                            );

                                    const bestScoreForPerson = (
                                        inputName,
                                        person
                                    ) => {
                                        const nameScore =
                                            participantMatchScore(
                                                inputName,
                                                person.name
                                            );

                                        const nicknameScore =
                                            person.nickname
                                                ? participantMatchScore(
                                                    inputName,
                                                    person.nickname
                                                )
                                                : 0;

                                        /*
                                         * Also allow a pasted value such as
                                         * "Gilbert Jun Aguila" to benefit from
                                         * the nickname being present between
                                         * the normal name tokens.
                                         */
                                        const combinedScore =
                                            person.nickname
                                                ? participantMatchScore(
                                                    inputName,
                                                    `${person.name} ${person.nickname}`
                                                )
                                                : 0;

                                        return Math.max(
                                            nameScore,
                                            nicknameScore,
                                            combinedScore
                                        );
                                    };

                                    const matched = [];
                                    const alreadyParticipant = [];
                                    const review = [];
                                    const unmatched = [];

                                    for (
                                        const inputName
                                        of names
                                    ) {
                                        const existingRanked =
                                            existingParticipants
                                                .map(
                                                    (person) => ({
                                                        ...person,
                                                        score:
                                                            bestScoreForPerson(
                                                                inputName,
                                                                person
                                                            ),
                                                    })
                                                )
                                                .filter(
                                                    (person) =>
                                                        person.score >= 0.88
                                                )
                                                .sort(
                                                    (a, b) =>
                                                        b.score - a.score
                                                );

                                        if (
                                            existingRanked.length
                                            &&
                                            (
                                                ! existingRanked[1]
                                                ||
                                                existingRanked[0].score
                                                - existingRanked[1].score
                                                >= 0.08
                                            )
                                        ) {
                                            alreadyParticipant.push({
                                                inputName,
                                                candidate:
                                                    existingRanked[0].name,
                                            });

                                            continue;
                                        }

                                        const ranked =
                                            people
                                                .map(
                                                    (person) => ({
                                                        ...person,
                                                        score:
                                                            bestScoreForPerson(
                                                                inputName,
                                                                person
                                                            ),
                                                    })
                                                )
                                                .filter(
                                                    (person) =>
                                                        person.score
                                                        >= 0.55
                                                )
                                                .sort(
                                                    (a, b) =>
                                                        b.score
                                                        - a.score
                                                );

                                        const best =
                                            ranked[0];

                                        const second =
                                            ranked[1];

                                        if (! best) {
                                            unmatched.push(
                                                {
                                                    inputName,
                                                }
                                            );

                                            continue;
                                        }

                                        /*
                                         * One-word names are deliberately
                                         * never auto-selected.
                                         *
                                         * They are too easy to confuse
                                         * with another Person.
                                         */
                                        const tokenCount =
                                            participantNameTokens(
                                                inputName
                                            ).length;

                                        const safelyUnique =
                                            ! second
                                            ||
                                            (
                                                best.score
                                                - second.score
                                            ) >= 0.08;

                                        if (
                                            tokenCount >= 2
                                            &&
                                            best.score >= 0.88
                                            &&
                                            safelyUnique
                                        ) {
                                            best.checkbox.checked =
                                                true;

                                            best.checkbox.dataset
                                                .pasteSelected =
                                                '1';

                                            matched.push(
                                                {
                                                    inputName,
                                                    candidate:
                                                        best.name,
                                                }
                                            );

                                            continue;
                                        }

                                        review.push(
                                            {
                                                inputName,
                                                suggestions:
                                                    ranked
                                                        .slice(
                                                            0,
                                                            3
                                                        )
                                                        .map(
                                                            (
                                                                candidate
                                                            ) =>
                                                                candidate.name
                                                        ),
                                            }
                                        );
                                    }

                                    const matchedHtml =
                                        matched.length
                                            ? `
                                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900 dark:bg-emerald-950">
                                                    <p class="text-xs font-bold text-emerald-800 dark:text-emerald-200">
                                                        Matched and checked: ${matched.length}
                                                    </p>

                                                    <div class="mt-2 space-y-1 text-xs text-emerald-700 dark:text-emerald-300">
                                                        ${matched
                                                            .map(
                                                                (item) =>
                                                                    `<p>✓ ${escapeParticipantHtml(item.inputName)} → <strong>${escapeParticipantHtml(item.candidate)}</strong></p>`
                                                            )
                                                            .join('')}
                                                    </div>
                                                </div>
                                            `
                                            : '';

                                    const alreadyParticipantHtml =
                                        alreadyParticipant.length
                                            ? `
                                                <div class="rounded-lg border border-sky-200 bg-sky-50 p-3 dark:border-sky-900 dark:bg-sky-950">
                                                    <p class="text-xs font-bold text-sky-800 dark:text-sky-200">
                                                        Already Participant: ${alreadyParticipant.length}
                                                    </p>

                                                    <div class="mt-2 space-y-1 text-xs text-sky-700 dark:text-sky-300">
                                                        ${alreadyParticipant
                                                            .map(
                                                                (item) =>
                                                                    `<p>✓ ${escapeParticipantHtml(item.inputName)} → <strong>${escapeParticipantHtml(item.candidate)}</strong></p>`
                                                            )
                                                            .join('')}
                                                    </div>
                                                </div>
                                            `
                                            : '';

                                    const reviewHtml =
                                        review.length
                                            ? `
                                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950">
                                                    <p class="text-xs font-bold text-amber-800 dark:text-amber-200">
                                                        Needs your review: ${review.length}
                                                    </p>

                                                    <div class="mt-2 space-y-2 text-xs text-amber-700 dark:text-amber-300">
                                                        ${review
                                                            .map(
                                                                (item) => `
                                                                    <div>
                                                                        <strong>? ${escapeParticipantHtml(item.inputName)}</strong>

                                                                        <div class="mt-0.5">
                                                                            Suggested:
                                                                            ${
                                                                                item.suggestions.length
                                                                                    ? item.suggestions
                                                                                        .map(
                                                                                            escapeParticipantHtml
                                                                                        )
                                                                                        .join(' · ')
                                                                                    : 'No safe suggestion'
                                                                            }
                                                                        </div>
                                                                    </div>
                                                                `
                                                            )
                                                            .join('')}
                                                    </div>
                                                </div>
                                            `
                                            : '';

                                    const unmatchedHtml =
                                        unmatched.length
                                            ? `
                                                <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950">
                                                    <p class="text-xs font-bold text-red-800 dark:text-red-200">
                                                        No safe match: ${unmatched.length}
                                                    </p>

                                                    <div class="mt-2 space-y-1 text-xs text-red-700 dark:text-red-300">
                                                        ${unmatched
                                                            .map(
                                                                (item) =>
                                                                    `<p>! ${escapeParticipantHtml(item.inputName)}</p>`
                                                            )
                                                            .join('')}
                                                    </div>
                                                </div>
                                            `
                                            : '';

                                    results.innerHTML = `
                                        <div class="space-y-2">
                                            <div class="text-xs font-semibold text-sky-800 dark:text-sky-200">
                                                Parsed ${names.length} name(s).
                                                Review the checked People below,
                                                then use Add Selected People.
                                            </div>

                                            ${matchedHtml}
                                            ${alreadyParticipantHtml}
                                            ${reviewHtml}
                                            ${unmatchedHtml}
                                        </div>
                                    `;

                                    results.classList.remove(
                                        'hidden'
                                    );

                                    /*
                                     * Scroll the existing People checklist
                                     * into view after matching.
                                     */
                                    const firstMatched =
                                        form.querySelector(
                                            'input[data-paste-selected="1"]'
                                        );

                                    if (firstMatched) {
                                        firstMatched
                                            .closest(
                                                '[data-person-card]'
                                            )
                                            ?.scrollIntoView({
                                                behavior: 'smooth',
                                                block: 'center',
                                            });
                                    }
                                };
                        })();
                    </script>


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

                                        @if ($selectedSession)
                                            <input
                                                type="hidden"
                                                name="attendance_session_id"
                                                value="{{ $selectedSession->id }}"
                                            >
                                        @endif

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

                                                    @if ($selectedSession)
                                                        <input
                                                            type="hidden"
                                                            name="attendance_session_id"
                                                            value="{{ $selectedSession->id }}"
                                                        >
                                                    @endif

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
