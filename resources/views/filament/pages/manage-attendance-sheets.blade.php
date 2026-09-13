<x-filament-panels::page>
    @php
        $sheets = $this->sheets();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Attendance Controls
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Manage Attendance Sheets
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Edit safe sheet details or archive wrong sheets without deleting attendance records. Archived sheets are hidden from normal attendance pages but remain available here and in reports.
            </p>
        </div>

        @if (session('attendance_sheet_updated'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                Attendance sheet updated.
            </div>
        @endif

        @if (session('attendance_sheet_archived'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                Attendance sheet archived.
            </div>
        @endif

        @if (session('attendance_sheet_restored'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                Attendance sheet restored.
            </div>
        @endif

        @if (session('attendance_sheet_deleted'))
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                Attendance sheet deleted permanently.
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Please fix the following:</p>

                <ul class="mt-2 list-inside list-disc text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-bold text-gray-900 dark:text-white">
                Status Filter
            </p>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($this->statusOptions() as $statusValue => $statusLabel)
                    <a
                        href="{{ $this->statusUrl($statusValue) }}"
                        class="rounded-full px-4 py-2 text-sm font-bold transition
                            {{ $this->selectedStatus() === $statusValue
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        {{ $statusLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="grid gap-5">
            @forelse ($sheets as $sheet)
                <div
                    @if (! $sheet->is_active)
                        tabindex="0"
                        onclick="
                            if (
                                event.target.closest(
                                    'button, a, form, input, select, textarea, summary, details, dialog'
                                )
                            ) {
                                return;
                            }

                            document
                                .getElementById(
                                    'archived-sheet-details-{{ $sheet->id }}'
                                )
                                ?.showModal();
                        "
                        onkeydown="
                            if (
                                event.key === 'Enter'
                                && event.target === this
                            ) {
                                document
                                    .getElementById(
                                        'archived-sheet-details-{{ $sheet->id }}'
                                    )
                                    ?.showModal();
                            }
                        "
                    @endif

                    @class([
                        'rounded-2xl border border-gray-200 bg-white p-6 shadow-sm',
                        'dark:border-gray-700 dark:bg-gray-900',
                        'cursor-pointer transition hover:border-primary-300 hover:shadow-md dark:hover:border-primary-800'
                            => ! $sheet->is_active,
                    ])
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $sheet->title }}
                                </h3>

                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $sheet->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100' : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                    {{ $sheet->is_active ? 'Active' : 'Archived' }}
                                </span>

                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $sheet->attendanceModeBadgeClass() }}">
                                    {{ $sheet->attendanceModeLabel() }}
                                </span>

                                <span
    @class([
        'rounded-full px-2 py-1 text-xs font-bold',
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100'
            => $sheet->meetingFormEnabled(),
        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
            => ! $sheet->meetingFormEnabled(),
    ])
>
    {{ $sheet->meetingFormLabel() }}
</span>
                            </div>

                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $sheet->locality ?: 'No Locality' }}
                                · {{ $sheet->meetingTimeLabel() }}
                                · {{ $sheet->dateRangeLabel() }}
                            </p>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $sheet->sessions_count }} meeting date(s)
                                · {{ $sheet->participants_count }} participant(s)
                            </p>

                            @if (! $sheet->is_active)
                                <p
                                    class="mt-2 text-xs font-semibold
                                           text-primary-600
                                           dark:text-primary-300"
                                >
                                    Click this archived sheet to view details.
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2">
<form
    method="POST"
    action="{{ route(
        'quezonprovinceactivities.attendance-sheets.sheets.toggle-active',
        $sheet
    ) }}"
>
    @csrf

    <button
        type="submit"
        onclick="return confirm('{{ $sheet->is_active ? 'Archive this sheet?' : 'Restore this sheet?' }}')"
        class="rounded-xl px-4 py-2 text-sm font-bold text-white {{ $sheet->is_active ? 'bg-amber-600 hover:bg-amber-500' : 'bg-emerald-600 hover:bg-emerald-500' }}"
    >
        {{ $sheet->is_active ? 'Archive' : 'Restore' }}
    </button>
</form>

                            @if (auth()->user()?->canDeleteRecords())
                                <form
                                    method="POST"
                                    action="{{ route('quezonprovinceactivities.attendance-sheets.sheets.destroy', $sheet) }}"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        onclick="return confirm('Delete this attendance sheet permanently? This will also delete all meeting dates, participants, and attendance records under this sheet.')"
                                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-500"
                                    >
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if (! $sheet->is_active)
                        <dialog
                            id="archived-sheet-details-{{ $sheet->id }}"
                            onclick="
                                if (event.target === this) {
                                    this.close();
                                }
                            "
                            class="m-auto max-h-[90vh] w-[calc(100%-2rem)]
                                   max-w-3xl overflow-y-auto rounded-2xl
                                   border border-gray-200 bg-white p-0
                                   shadow-2xl backdrop:bg-black/60
                                   dark:border-gray-700 dark:bg-gray-900"
                        >
                            <div
                                class="relative w-full"
                            >
                                <div
                                    class="sticky top-0 z-10 flex
                                           items-start justify-between
                                           gap-4 border-b
                                           border-gray-200 bg-white
                                           p-5
                                           dark:border-gray-700
                                           dark:bg-gray-900"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap
                                                   items-center gap-2"
                                        >
                                            <h2
                                                class="break-words
                                                       text-xl font-bold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $sheet->title }}
                                            </h2>

                                            <span
                                                class="rounded-full
                                                       bg-gray-200
                                                       px-2.5 py-1
                                                       text-xs font-bold
                                                       text-gray-700
                                                       dark:bg-gray-800
                                                       dark:text-gray-200"
                                            >
                                                Archived
                                            </span>

                                            <span
                                                class="rounded-full
                                                       px-2.5 py-1
                                                       text-xs font-bold
                                                       {{ $sheet->attendanceModeBadgeClass() }}"
                                            >
                                                {{ $sheet->attendanceModeLabel() }}
                                            </span>
                                        </div>

                                        <p
                                            class="mt-1 text-sm
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            Read-only Attendance Sheet details
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onclick="
                                            event.stopPropagation();
                                            this.closest('dialog').close();
                                        "
                                        class="shrink-0 rounded-lg
                                               border border-gray-300
                                               bg-white px-3 py-2
                                               text-sm font-bold
                                               text-gray-700
                                               hover:bg-gray-100
                                               dark:border-gray-700
                                               dark:bg-gray-800
                                               dark:text-gray-200
                                               dark:hover:bg-gray-700"
                                    >
                                        Close
                                    </button>
                                </div>

                                <div class="space-y-6 p-5">
                                    <div
                                        class="grid gap-4
                                               sm:grid-cols-2"
                                    >
                                        <div
                                            class="rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50 p-4
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <p
                                                class="text-xs font-bold
                                                       uppercase tracking-wide
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Locality
                                            </p>

                                            <p
                                                class="mt-1 font-semibold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $sheet->locality ?: 'No Locality' }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50 p-4
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <p
                                                class="text-xs font-bold
                                                       uppercase tracking-wide
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Scheduling Mode
                                            </p>

                                            <p
                                                class="mt-1 font-semibold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $sheet->attendanceModeLabel() }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50 p-4
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <p
                                                class="text-xs font-bold
                                                       uppercase tracking-wide
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Date Range
                                            </p>

                                            <p
                                                class="mt-1 font-semibold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $sheet->dateRangeLabel() }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50 p-4
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <p
                                                class="text-xs font-bold
                                                       uppercase tracking-wide
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Meeting Time
                                            </p>

                                            <p
                                                class="mt-1 font-semibold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $sheet->meetingTimeLabel() }}

                                                @if (filled($sheet->end_time))
                                                    –
                                                    {{
                                                        \Carbon\Carbon::parse(
                                                            $sheet->end_time
                                                        )->format('g:i A')
                                                    }}
                                                @endif
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50 p-4
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <p
                                                class="text-xs font-bold
                                                       uppercase tracking-wide
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Meeting Dates
                                            </p>

                                            <p
                                                class="mt-1 font-semibold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $sheet->sessions_count }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50 p-4
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <p
                                                class="text-xs font-bold
                                                       uppercase tracking-wide
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Participant Records
                                            </p>

                                            <p
                                                class="mt-1 font-semibold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $sheet->participants_count }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50 p-4
                                                   dark:border-gray-700
                                                   dark:bg-gray-950
                                                   sm:col-span-2"
                                        >
                                            <p
                                                class="text-xs font-bold
                                                       uppercase tracking-wide
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Meeting Form
                                            </p>

                                            <p
                                                class="mt-1 font-semibold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                {{ $sheet->meetingFormLabel() }}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <div
                                            class="flex items-center
                                                   justify-between gap-3"
                                        >
                                            <h3
                                                class="text-sm font-bold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                Session Dates
                                            </h3>

                                            <span
                                                class="rounded-full
                                                       bg-gray-100
                                                       px-2.5 py-1
                                                       text-xs font-bold
                                                       text-gray-600
                                                       dark:bg-gray-800
                                                       dark:text-gray-300"
                                            >
                                                {{ $sheet->sessions->count() }}
                                            </span>
                                        </div>

                                        <div class="mt-3 space-y-2">
                                            @forelse ($sheet->sessions as $session)
                                                <div
                                                    class="rounded-lg border
                                                           border-gray-200
                                                           bg-gray-50
                                                           px-4 py-3
                                                           dark:border-gray-700
                                                           dark:bg-gray-950"
                                                >
                                                    <p
                                                        class="font-semibold
                                                               text-gray-900
                                                               dark:text-white"
                                                    >
                                                        {{ $session->dateTimeLabel() }}
                                                    </p>
                                                </div>
                                            @empty
                                                <div
                                                    class="rounded-lg border
                                                           border-dashed
                                                           border-gray-300
                                                           p-4 text-center
                                                           text-sm
                                                           text-gray-500
                                                           dark:border-gray-700
                                                           dark:text-gray-400"
                                                >
                                                    No Session dates recorded.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div>
                                        <h3
                                            class="text-sm font-bold
                                                   text-gray-900
                                                   dark:text-white"
                                        >
                                            Remarks
                                        </h3>

                                        <div
                                            class="mt-3 rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50 p-4
                                                   text-sm text-gray-700
                                                   dark:border-gray-700
                                                   dark:bg-gray-950
                                                   dark:text-gray-300"
                                        >
                                            {{
                                                filled($sheet->remarks)
                                                    ? $sheet->remarks
                                                    : 'No remarks.'
                                            }}
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-xl border
                                               border-amber-200
                                               bg-amber-50 p-4
                                               text-sm text-amber-800
                                               dark:border-amber-900
                                               dark:bg-amber-950
                                               dark:text-amber-200"
                                    >
                                        This Attendance Sheet is archived
                                        and shown in read-only mode.
                                        Restore it to manage its settings
                                        and meeting forms again.
                                    </div>
                                </div>
                            </div>
                        </dialog>
                    @endif

                    @if ($sheet->is_active)
<details class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                        <summary class="cursor-pointer text-sm font-bold text-gray-900 dark:text-white">
                            Edit sheet details
                        </summary>

<form
    method="POST"
    action="{{ route('quezonprovinceactivities.attendance-sheets.sheets.update', $sheet) }}"
    class="mt-5 grid gap-4 md:grid-cols-2"
    x-data="{
        scheduleType: @js(
            old(
                'schedule_type',
                $sheet->schedule_type
                ?: (
                    $sheet->is_one_time
                        ? \App\Models\AttendanceSheet::SCHEDULE_ONE_TIME
                        : \App\Models\AttendanceSheet::SCHEDULE_RECURRING
                )
            )
        )
    }"
>
                            @csrf
                            @method('PATCH')

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    Title
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    value="{{ $sheet->title }}"
                                    required
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    Locality
                                </label>

                                <select
                                    name="locality_id"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                >
                                    <option value="">No Locality / Not locality-specific</option>

                                    @foreach ($this->localityOptions() as $group => $options)
                                        <optgroup label="{{ $group }}">
                                            @foreach ($options as $localityId => $locality)
                                                <option
                                                    value="{{ $localityId }}"
                                                    @selected(
                                                        (int) $sheet->locality_id
                                                        === (int) $localityId
                                                    )
                                                >
                                                    {{ $locality }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

<div class="md:col-span-2">
    <label
        class="block text-sm font-semibold
               text-gray-700 dark:text-gray-200"
    >
        Scheduling Mode
    </label>

    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        Changing the schedule preserves Sessions containing
        attendance, pre-listed responses, Immich history,
        participant preparation, or linked activities.
    </p>

    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label
            class="flex cursor-pointer items-start gap-3 rounded-xl
                   border border-primary-200 bg-primary-50 p-4
                   dark:border-primary-900 dark:bg-primary-950"
        >
            <input
                type="radio"
                name="schedule_type"
                value="recurring"
                x-model="scheduleType"
                @checked(
                    old('schedule_type', $sheet->schedule_type)
                    === \App\Models\AttendanceSheet::SCHEDULE_RECURRING
                )
                class="mt-1 h-4 w-4"
            >

            <span>
                <span class="block font-bold">
                    Recurring Weekly
                </span>

                <span class="mt-1 block text-xs">
                    One selected weekday within a date range.
                </span>
            </span>
        </label>

        <label
            class="flex cursor-pointer items-start gap-3 rounded-xl
                   border border-amber-200 bg-amber-50 p-4
                   dark:border-amber-900 dark:bg-amber-950"
        >
            <input
                type="radio"
                name="schedule_type"
                value="one_time"
                x-model="scheduleType"
                @checked(
                    old('schedule_type', $sheet->schedule_type)
                    === \App\Models\AttendanceSheet::SCHEDULE_ONE_TIME
                )
                class="mt-1 h-4 w-4"
            >

            <span>
                <span class="block font-bold">
                    One-time Attendance
                </span>

                <span class="mt-1 block text-xs">
                    Exactly one Session from Start Date.
                </span>
            </span>
        </label>

        <label
            class="flex cursor-pointer items-start gap-3 rounded-xl
                   border border-emerald-200 bg-emerald-50 p-4
                   dark:border-emerald-900 dark:bg-emerald-950"
        >
            <input
                type="radio"
                name="schedule_type"
                value="consecutive"
                x-model="scheduleType"
                @checked(
                    old('schedule_type', $sheet->schedule_type)
                    === \App\Models\AttendanceSheet::SCHEDULE_CONSECUTIVE
                )
                class="mt-1 h-4 w-4"
            >

            <span>
                <span class="block font-bold">
                    Consecutive Days
                </span>

                <span class="mt-1 block text-xs">
                    Every calendar day from Start through End Date.
                </span>
            </span>
        </label>

        <label
            class="flex cursor-pointer items-start gap-3 rounded-xl
                   border border-violet-200 bg-violet-50 p-4
                   dark:border-violet-900 dark:bg-violet-950"
        >
            <input
                type="radio"
                name="schedule_type"
                value="manual"
                x-model="scheduleType"
                @checked(
                    old('schedule_type', $sheet->schedule_type)
                    === \App\Models\AttendanceSheet::SCHEDULE_MANUAL
                )
                class="mt-1 h-4 w-4"
            >

            <span>
                <span class="block font-bold">
                    Manual Dates
                </span>

                <span class="mt-1 block text-xs">
                    Dates are managed individually in Attendance Sheets.
                </span>
            </span>
        </label>
    </div>
</div>

                            <div>
                                <label
                                    class="block text-sm font-semibold
                                           text-gray-700 dark:text-gray-200"
                                >
                                    Start Time
                                </label>

                                <input
                                    type="time"
                                    name="meeting_time"
                                    value="{{
                                        old(
                                            'meeting_time',
                                            substr(
                                                (string) ($sheet->meeting_time ?? ''),
                                                0,
                                                5
                                            )
                                        )
                                    }}"
                                    class="mt-2 block w-full rounded-xl
                                           border border-gray-300 bg-white
                                           px-4 py-3 text-sm text-gray-900
                                           dark:border-gray-700
                                           dark:bg-gray-900
                                           dark:text-gray-100"
                                >
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold
                                           text-gray-700 dark:text-gray-200"
                                >
                                    End Time
                                </label>

                                <input
                                    type="time"
                                    name="end_time"
                                    value="{{
                                        old(
                                            'end_time',
                                            substr(
                                                (string) ($sheet->end_time ?? ''),
                                                0,
                                                5
                                            )
                                        )
                                    }}"
                                    class="mt-2 block w-full rounded-xl
                                           border border-gray-300 bg-white
                                           px-4 py-3 text-sm text-gray-900
                                           dark:border-gray-700
                                           dark:bg-gray-900
                                           dark:text-gray-100"
                                >
                            </div>

                            <div
                                class="md:col-span-2"
                                x-show="scheduleType === 'recurring'"
                            >
                                <label
                                    class="block text-sm font-semibold
                                           text-gray-700 dark:text-gray-200"
                                >
                                    Meeting Day
                                </label>

                                <select
                                    name="meeting_day"
                                    x-bind:disabled="
                                        scheduleType !== 'recurring'
                                    "
                                    x-bind:required="
                                        scheduleType === 'recurring'
                                    "
                                    class="mt-2 block w-full rounded-xl
                                           border border-gray-300 bg-white
                                           px-4 py-3 text-sm text-gray-900
                                           disabled:opacity-50
                                           dark:border-gray-700
                                           dark:bg-gray-900
                                           dark:text-gray-100"
                                >
                                    @php
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

                                    @foreach ($days as $value => $label)
                                        <option
                                            value="{{ $value }}"
                                            @selected(
                                                (string) old(
                                                    'meeting_day',
                                                    $sheet->meeting_day
                                                )
                                                === (string) $value
                                            )
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div
                                x-show="scheduleType !== 'manual'"
                            >
                                <label
                                    class="block text-sm font-semibold
                                           text-gray-700 dark:text-gray-200"
                                >
                                    <span
                                        x-show="
                                            scheduleType !== 'one_time'
                                        "
                                    >
                                        Start Date
                                    </span>

                                    <span
                                        x-show="
                                            scheduleType === 'one_time'
                                        "
                                    >
                                        Meeting Date
                                    </span>
                                </label>

                                <input
                                    type="date"
                                    name="start_date"
                                    value="{{
                                        old(
                                            'start_date',
                                            $sheet->start_date?->format('Y-m-d')
                                            ??
                                            $sheet->sessions
                                                ->min('session_date')
                                                ?->format('Y-m-d')
                                        )
                                    }}"
                                    x-bind:disabled="
                                        scheduleType === 'manual'
                                    "
                                    x-bind:required="
                                        scheduleType !== 'manual'
                                    "
                                    class="mt-2 block w-full rounded-xl
                                           border border-gray-300 bg-white
                                           px-4 py-3 text-sm text-gray-900
                                           disabled:opacity-50
                                           dark:border-gray-700
                                           dark:bg-gray-900
                                           dark:text-gray-100"
                                >
                            </div>

                            <div
                                x-show="
                                    scheduleType === 'recurring'
                                    || scheduleType === 'consecutive'
                                "
                            >
                                <label
                                    class="block text-sm font-semibold
                                           text-gray-700 dark:text-gray-200"
                                >
                                    End Date
                                </label>

                                <input
                                    type="date"
                                    name="end_date"
                                    value="{{
                                        old(
                                            'end_date',
                                            $sheet->end_date?->format('Y-m-d')
                                            ??
                                            $sheet->sessions
                                                ->max('session_date')
                                                ?->format('Y-m-d')
                                        )
                                    }}"
                                    x-bind:disabled="
                                        scheduleType === 'one_time'
                                        || scheduleType === 'manual'
                                    "
                                    x-bind:required="
                                        scheduleType === 'recurring'
                                        || scheduleType === 'consecutive'
                                    "
                                    class="mt-2 block w-full rounded-xl
                                           border border-gray-300 bg-white
                                           px-4 py-3 text-sm text-gray-900
                                           disabled:opacity-50
                                           dark:border-gray-700
                                           dark:bg-gray-900
                                           dark:text-gray-100"
                                >
                            </div>

                            <div
                                x-show="scheduleType === 'manual'"
                                class="md:col-span-2 rounded-xl
                                       border border-violet-200
                                       bg-violet-50 p-4 text-sm
                                       text-violet-800
                                       dark:border-violet-900
                                       dark:bg-violet-950
                                       dark:text-violet-200"
                            >
                                Manual Session dates are managed from
                                Attendance Sheets using
                                <strong>+ Add Session Date</strong>.
                                Saving this form does not remove or
                                regenerate Manual Dates.
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    Remarks
                                </label>

                                <textarea
                                    name="remarks"
                                    rows="3"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                >{{ $sheet->remarks }}</textarea>
                            </div>

<div class="md:col-span-2">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Meeting Form
    </label>

    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        Enable or disable the public response form for this attendance sheet.
    </p>

    <div class="mt-3 grid gap-3 md:grid-cols-3">

        {{-- Disabled --}}
        <label
            class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800"
        >
            <input
                type="radio"
                name="meeting_form_type"
                value="{{ \App\Models\AttendanceSheet::MEETING_FORM_DISABLED }}"
                @checked(
                    $sheet->meeting_form_type
                    === \App\Models\AttendanceSheet::MEETING_FORM_DISABLED
                )
                class="mt-1 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500"
            >

            <span class="min-w-0">
                <span class="block font-bold text-gray-900 dark:text-white">
                    Disabled
                </span>

                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                    Public meeting responses are disabled.
                </span>
            </span>
        </label>


        {{-- Normal --}}
        <label
            class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:hover:bg-emerald-900"
        >
            <input
                type="radio"
                name="meeting_form_type"
                value="{{ \App\Models\AttendanceSheet::MEETING_FORM_NORMAL }}"
                @checked(
                    $sheet->meeting_form_type
                    === \App\Models\AttendanceSheet::MEETING_FORM_NORMAL
                )
                class="mt-1 h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500"
            >

            <span class="min-w-0">
                <span class="block font-bold text-emerald-900 dark:text-emerald-100">
                    Normal Meeting Form
                </span>

                <span class="mt-1 block text-xs text-emerald-700 dark:text-emerald-300">
                    Allows public YES / NO responses without login.
                </span>
            </span>
        </label>


        {{-- Google Form-like --}}
        <label
            class="flex cursor-pointer items-start gap-3
                   rounded-xl border border-violet-200
                   bg-violet-50 p-4
                   hover:bg-violet-100
                   dark:border-violet-900
                   dark:bg-violet-950
                   dark:hover:bg-violet-900"
        >
            <input
                type="radio"
                name="meeting_form_type"
                value="{{ \App\Models\AttendanceSheet::MEETING_FORM_GOOGLE }}"
                @checked(
                    $sheet->meeting_form_type
                    === \App\Models\AttendanceSheet::MEETING_FORM_GOOGLE
                )
                class="mt-1 h-4 w-4 border-gray-300
                       text-violet-600 focus:ring-violet-500"
            >

            <span class="min-w-0">
                <span
                    class="block font-bold
                           text-violet-900
                           dark:text-violet-100"
                >
                    Google Form-like
                </span>

                <span
                    class="mt-1 block text-xs
                           text-violet-700
                           dark:text-violet-300"
                >
                    Build a custom public response form
                    with configurable questions.
                </span>
            </span>
        </label>

    </div>
</div>

                            <div class="md:col-span-2 flex justify-end">
                                <button
                                    type="submit"
                                    class="rounded-xl bg-primary-600 px-5 py-2 text-sm font-bold text-white hover:bg-primary-500"
                                >
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </details>
@endif


@if (
    $sheet->is_active
    &&
    $sheet->meeting_form_type
        === \App\Models\AttendanceSheet::MEETING_FORM_GOOGLE
)
    <details
        class="mt-5 overflow-hidden rounded-xl
               border border-violet-200 bg-violet-50
               dark:border-violet-900 dark:bg-violet-950"
        open
    >
        <summary
            class="flex cursor-pointer list-none
                   items-center justify-between gap-4 p-4"
        >
            <span>
                <span
                    class="block text-sm font-bold
                           text-violet-900
                           dark:text-violet-100"
                >
                    Google Form-like Builder
                </span>

                <span
                    class="mt-1 block text-xs
                           text-violet-700
                           dark:text-violet-300"
                >
                    Configure the questions for this
                    Attendance Sheet.
                </span>
            </span>

            <span
                class="rounded-full bg-violet-100
                       px-2.5 py-1 text-xs font-bold
                       text-violet-800
                       dark:bg-violet-900
                       dark:text-violet-100"
            >
                {{ $sheet->meetingFormQuestions->count() }}
                form item(s)
            </span>
        </summary>

        <div
            class="space-y-5 border-t border-violet-200
                   p-4 dark:border-violet-900"
        >
            {{-- Add Question --}}
            <div
                class="rounded-xl border border-violet-200
                       bg-white p-4
                       dark:border-violet-800
                       dark:bg-gray-950"
            >
                <h4
                    class="font-bold text-gray-900
                           dark:text-white"
                >
                    Add Form Item
                </h4>

                <div
                    class="mt-4 grid gap-4 md:grid-cols-2"
                >
                    <div>
                        <label
                            class="block text-sm font-semibold
                                   text-gray-700
                                   dark:text-gray-200"
                        >
                            Question Type
                        </label>

                        <select
                            wire:model.live="newMeetingFormQuestionTypes.{{ $sheet->id }}"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-900"
                        >
                            @foreach (
                                $this->meetingFormQuestionTypeOptions()
                                as $type => $label
                            )
                                <option value="{{ $type }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @php
                        $newBuilderType =
                            $this->newMeetingFormQuestionTypes[
                                $sheet->id
                            ]
                            ??
                            \App\Models\AttendanceMeetingFormQuestion::TYPE_SHORT_ANSWER;

                        $newBuilderIsNotice =
                            $newBuilderType
                            ===
                            \App\Models\AttendanceMeetingFormQuestion::TYPE_NOTICE;
                    @endphp

                    @if (! $newBuilderIsNotice)
                    <div
                        class="flex items-end"
                    >
                        <label
                            class="inline-flex items-center
                                   gap-2 rounded-xl border
                                   border-gray-200 px-4 py-3
                                   text-sm font-semibold
                                   dark:border-gray-700"
                        >
                            <input
                                type="checkbox"
                                wire:model="newMeetingFormQuestionRequired.{{ $sheet->id }}"
                                class="rounded border-gray-300
                                       text-violet-600
                                       focus:ring-violet-500"
                            >

                            Required question
                        </label>
                    </div>
                    @endif

                    <div class="md:col-span-2">
                        <label
                            class="block text-sm font-semibold
                                   text-gray-700
                                   dark:text-gray-200"
                        >
                            {{ $newBuilderIsNotice
                                ? 'Notice Title'
                                : 'Question' }}
                        </label>

                        <input
                            type="text"
                            wire:model="newMeetingFormQuestionTexts.{{ $sheet->id }}"
                            placeholder="{{ $newBuilderIsNotice
                                ? 'Enter notice title...'
                                : 'Enter the question...' }}"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-900"
                        >

                        @error(
                            'newMeetingFormQuestionTexts.'
                            . $sheet->id
                        )
                            <p
                                class="mt-1 text-xs
                                       text-red-600"
                            >
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="block text-sm font-semibold
                                   text-gray-700
                                   dark:text-gray-200"
                        >
                            {{ $newBuilderIsNotice
                                ? 'Notice / Message'
                                : 'Description' }}
                            <span class="font-normal">
                                (optional)
                            </span>
                        </label>

                        <textarea
                            wire:model="newMeetingFormQuestionDescriptions.{{ $sheet->id }}"
                            rows="2"
                            placeholder="Optional help text..."
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-900"
                        ></textarea>
                    </div>

                    @php
                        $newQuestionType =
                            $this->newMeetingFormQuestionTypes[
                                $sheet->id
                            ]
                            ??
                            \App\Models\AttendanceMeetingFormQuestion::TYPE_SHORT_ANSWER;

                        $newQuestionIsDatabaseField =
                            $newQuestionType
                            ===
                            \App\Models\AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD;

                        $newQuestionNeedsOptions =
                            in_array(
                                $newQuestionType,
                                [
                                    \App\Models\AttendanceMeetingFormQuestion::TYPE_MULTIPLE_CHOICE,
                                    \App\Models\AttendanceMeetingFormQuestion::TYPE_CHECKBOXES,
                                    \App\Models\AttendanceMeetingFormQuestion::TYPE_DROPDOWN,
                                ],
                                true
                            );

                        $newQuestionOptions =
                            array_values(
                                $this->newMeetingFormQuestionOptions[
                                    $sheet->id
                                ]
                                ?? []
                            );

                        while (
                            count($newQuestionOptions) < 2
                        ) {
                            $newQuestionOptions[] = '';
                        }
                    @endphp

                    @if ($newBuilderIsNotice)
                        <div
                            class="md:col-span-2 rounded-xl
                                   border border-teal-200
                                   bg-teal-50 p-4
                                   text-sm text-teal-900
                                   dark:border-teal-900
                                   dark:bg-teal-950
                                   dark:text-teal-100"
                        >
                            This form item displays information only.
                            Participants will not be asked to answer it,
                            and nothing will be stored as an answer.
                        </div>

                    @elseif ($newQuestionIsDatabaseField)
                        <div
                            class="md:col-span-2 rounded-xl
                                   border border-sky-200
                                   bg-sky-50 p-4
                                   dark:border-sky-900
                                   dark:bg-sky-950"
                        >
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label
                                        class="block text-sm font-semibold
                                               text-sky-900
                                               dark:text-sky-100"
                                    >
                                        Database Field
                                    </label>

                                    <select
                                        wire:model.live="newMeetingFormQuestionDatabaseFields.{{ $sheet->id }}"
                                        wire:change="selectNewMeetingFormDatabaseField({{ $sheet->id }}, $event.target.value)"
                                        class="mt-2 block w-full rounded-xl
                                               border border-sky-300
                                               bg-white px-4 py-3 text-sm
                                               dark:border-sky-800
                                               dark:bg-gray-900"
                                    >
                                        @foreach (
                                            $this->meetingFormDatabaseFieldGroups()
                                            as $group => $fields
                                        )
                                            <optgroup label="{{ $group }}">
                                                @foreach (
                                                    $fields
                                                    as $field => $label
                                                )
                                                    <option value="{{ $field }}">
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>

                                    @error(
                                        'newMeetingFormQuestionDatabaseFields.'
                                        . $sheet->id
                                    )
                                        <p class="mt-1 text-xs text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="flex items-end">
                                    <label
                                        class="inline-flex items-center gap-2
                                               rounded-xl border
                                               border-sky-200 bg-white
                                               px-4 py-3 text-sm
                                               font-semibold
                                               text-sky-900
                                               dark:border-sky-800
                                               dark:bg-gray-900
                                               dark:text-sky-100"
                                    >
                                        <input
                                            type="checkbox"
                                            wire:model="newMeetingFormQuestionAllowCorrection.{{ $sheet->id }}"
                                            @checked(
                                                $this
                                                    ->newMeetingFormQuestionAllowCorrection[
                                                        $sheet->id
                                                    ]
                                                ?? true
                                            )
                                            class="rounded border-gray-300
                                                   text-sky-600
                                                   focus:ring-sky-500"
                                        >

                                        Allow correction request
                                    </label>
                                </div>
                            </div>

                            @php
                                $databaseField =
                                    $this
                                        ->newMeetingFormQuestionDatabaseFields[
                                            $sheet->id
                                        ]
                                    ??
                                    \App\Models\AttendanceMeetingFormQuestion::DATABASE_FIELD_BIRTHDATE;

                                $databaseFieldDefinition =
                                    $this
                                        ->meetingFormDatabaseFieldDefinition(
                                            $databaseField
                                        );

                                $databaseFieldInput =
                                    $databaseFieldDefinition[
                                        'input'
                                    ]
                                    ?? 'text';

                                $databaseFieldLabel =
                                    $databaseFieldDefinition[
                                        'label'
                                    ]
                                    ?? 'Database Field';
                            @endphp

                            <div
                                class="mt-4 rounded-xl
                                       border border-sky-100
                                       bg-white p-4
                                       dark:border-sky-900
                                       dark:bg-gray-900"
                            >
                                <p
                                    class="text-xs font-bold uppercase
                                           tracking-wide text-sky-700
                                           dark:text-sky-300"
                                >
                                    Public Form Preview
                                </p>

                                @switch($databaseFieldInput)
                                    @case('date')
                                        <input
                                            type="date"
                                            disabled
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                        @break

                                    @case('sex')
                                        <select
                                            disabled
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <option>Auto-filled Sex</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                        </select>
                                        @break

                                    @case('locality')
                                        <select
                                            disabled
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <option>
                                                Auto-filled Locality
                                            </option>
                                        </select>
                                        @break

                                    @case('school')
                                        <select
                                            disabled
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <option>
                                                Auto-filled School / Campus
                                            </option>
                                        </select>
                                        @break

                                    @case('grade_level')
                                        <select
                                            disabled
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            <option>
                                                Select grade / year level
                                            </option>

                                            @foreach (
                                                \App\Support\MeetingFormDatabaseFieldRegistry::options(
                                                    'grade_level'
                                                )
                                                as $value => $label
                                            )
                                                <option value="{{ $value }}">
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('textarea')
                                        <textarea
                                            disabled
                                            rows="3"
                                            placeholder="Auto-filled {{ $databaseFieldLabel }}"
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        ></textarea>
                                        @break

                                    @case('email')
                                        <input
                                            type="email"
                                            disabled
                                            placeholder="Auto-filled email address"
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                        @break

                                    @case('tel')
                                        <input
                                            type="tel"
                                            disabled
                                            placeholder="Auto-filled contact number"
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                        @break

                                    @case('multi_value')
                                        <div
                                            class="mt-2 rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                            Auto-filled selections
                                        </div>
                                        @break

                                    @default
                                        <input
                                            type="text"
                                            disabled
                                            placeholder="Auto-filled {{ $databaseFieldLabel }}"
                                            class="mt-2 block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-300 bg-gray-50
                                                   px-4 py-3 text-sm
                                                   text-gray-500
                                                   dark:border-gray-700
                                                   dark:bg-gray-950"
                                        >
                                @endswitch

                                <p
                                    class="mt-2 text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Existing People Database data will
                                    be auto-filled on the public form.
                                    Corrections will not directly
                                    overwrite the database.
                                </p>
                            </div>
                        </div>

                    @elseif ($newQuestionNeedsOptions)
                        <div class="md:col-span-2">
                            <label
                                class="block text-sm font-semibold
                                       text-gray-700
                                       dark:text-gray-200"
                            >
                                Options
                            </label>

                            <div class="mt-3 space-y-2">
                                @foreach (
                                    $newQuestionOptions
                                    as $optionIndex => $option
                                )
                                    <div
                                        class="flex items-center gap-2"
                                        wire:key="new-question-option-{{ $sheet->id }}-{{ $optionIndex }}"
                                    >
                                        <span
                                            class="flex h-7 w-7 shrink-0
                                                   items-center justify-center
                                                   text-sm text-gray-400"
                                        >
                                            @if (
                                                $newQuestionType
                                                ===
                                                \App\Models\AttendanceMeetingFormQuestion::TYPE_MULTIPLE_CHOICE
                                            )
                                                ○
                                            @elseif (
                                                $newQuestionType
                                                ===
                                                \App\Models\AttendanceMeetingFormQuestion::TYPE_CHECKBOXES
                                            )
                                                □
                                            @else
                                                {{ $optionIndex + 1 }}.
                                            @endif
                                        </span>

                                        <input
                                            type="text"
                                            wire:model="newMeetingFormQuestionOptions.{{ $sheet->id }}.{{ $optionIndex }}"
                                            placeholder="Option {{ $optionIndex + 1 }}"
                                            class="block min-w-0 flex-1
                                                   rounded-xl border
                                                   border-gray-300 bg-white
                                                   px-4 py-2.5 text-sm
                                                   dark:border-gray-700
                                                   dark:bg-gray-900"
                                        >

                                        @if (count($newQuestionOptions) > 2)
                                            <button
                                                type="button"
                                                wire:click="removeNewMeetingFormQuestionOption({{ $sheet->id }}, {{ $optionIndex }})"
                                                class="rounded-lg px-2 py-2
                                                       text-sm font-bold
                                                       text-red-600
                                                       hover:bg-red-50
                                                       dark:hover:bg-red-950"
                                                title="Remove option"
                                            >
                                                ×
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <button
                                type="button"
                                wire:click="addNewMeetingFormQuestionOption({{ $sheet->id }})"
                                class="mt-3 inline-flex items-center
                                       rounded-lg px-3 py-2
                                       text-sm font-bold
                                       text-violet-700
                                       hover:bg-violet-100
                                       dark:text-violet-300
                                       dark:hover:bg-violet-900"
                            >
                                + Add Option
                            </button>

                            @error(
                                'newMeetingFormQuestionOptions.'
                                . $sheet->id
                            )
                                <p class="mt-2 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    @elseif (
                        $newQuestionType
                        ===
                        \App\Models\AttendanceMeetingFormQuestion::TYPE_PARAGRAPH
                    )
                        <div class="md:col-span-2">
                            <textarea
                                disabled
                                rows="3"
                                placeholder="Long answer text"
                                class="block w-full cursor-not-allowed
                                       rounded-xl border
                                       border-gray-200 bg-gray-50
                                       px-4 py-3 text-sm
                                       text-gray-400
                                       dark:border-gray-800
                                       dark:bg-gray-900"
                            ></textarea>
                        </div>

                    @else
                        <div class="md:col-span-2">
                            <input
                                type="text"
                                disabled
                                placeholder="Short answer text"
                                class="block w-full cursor-not-allowed
                                       border-0 border-b
                                       border-gray-300 bg-transparent
                                       px-1 py-2 text-sm
                                       text-gray-400
                                       focus:ring-0
                                       dark:border-gray-700"
                            >
                        </div>
                    @endif
                </div>

                <button
                    type="button"
                    wire:click="addMeetingFormQuestion({{ $sheet->id }})"
                    wire:loading.attr="disabled"
                    wire:target="addMeetingFormQuestion"
                    class="mt-4 rounded-xl
                           bg-violet-600 px-4 py-2.5
                           text-sm font-bold text-white
                           hover:bg-violet-500
                           disabled:opacity-50"
                >
                    {{ $newBuilderIsNotice
                        ? '+ Add Notice'
                        : '+ Add Question' }}
                </button>
            </div>


            {{-- Existing Questions --}}
            <div class="space-y-4">
                @forelse (
                    $sheet->meetingFormQuestions
                    as $question
                )
                    @php
                        $builderQuestionType =
                            $this->meetingFormQuestionTypes[
                                $question->id
                            ]
                            ?? $question->question_type;

                        $builderQuestionIsNotice =
                            $builderQuestionType
                            ===
                            \App\Models\AttendanceMeetingFormQuestion::TYPE_NOTICE;
                    @endphp

                    <details
                        class="overflow-hidden rounded-xl
                               border border-gray-200 bg-white
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                        <summary
                            class="flex cursor-pointer list-none
                                   items-start justify-between
                                   gap-4 p-4"
                        >
                            <span class="min-w-0">
                                <span
                                    class="block font-bold
                                           text-gray-900
                                           dark:text-white"
                                >
                                    {{ $loop->iteration }}.
                                    {{ $question->question_text }}
                                </span>

                                <span
                                    class="mt-1 block text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    {{ $question->typeLabel() }}

                                    @if (! $question->isNotice())
                                        ·

                                        {{
                                            $question->is_required
                                                ? 'Required'
                                                : 'Optional'
                                        }}
                                    @endif
                                </span>
                            </span>

                            <span
                                class="shrink-0 rounded-full
                                       bg-gray-100 px-2 py-1
                                       text-xs font-bold
                                       text-gray-600
                                       dark:bg-gray-800
                                       dark:text-gray-300"
                            >
                                Edit
                            </span>
                        </summary>

                        <div
                            class="border-t border-gray-200
                                   p-4 dark:border-gray-700"
                        >
                            <div
                                class="grid gap-4
                                       md:grid-cols-2"
                            >
                                <div>
                                    <label
                                        class="block text-sm
                                               font-semibold"
                                    >
                                        Question Type
                                    </label>

                                    <select
                                        wire:model.live="meetingFormQuestionTypes.{{ $question->id }}"
                                        class="mt-2 block w-full
                                               rounded-xl border
                                               border-gray-300 bg-white
                                               px-4 py-3 text-sm
                                               dark:border-gray-700
                                               dark:bg-gray-900"
                                    >
                                        @foreach (
                                            $this->meetingFormQuestionTypeOptions()
                                            as $type => $label
                                        )
                                            <option
                                                value="{{ $type }}"
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if (! $builderQuestionIsNotice)
                                <div
                                    class="flex items-end"
                                >
                                    <label
                                        class="inline-flex
                                               items-center gap-2
                                               rounded-xl border
                                               border-gray-200
                                               px-4 py-3 text-sm
                                               font-semibold
                                               dark:border-gray-700"
                                    >
                                        <input
                                            type="checkbox"
                                            wire:model="meetingFormQuestionRequired.{{ $question->id }}"
                                            class="rounded
                                                   border-gray-300
                                                   text-violet-600"
                                        >

                                        Required
                                    </label>
                                </div>
                                @endif

                                <div class="md:col-span-2">
                                    <label
                                        class="block text-sm
                                               font-semibold"
                                    >
                                        {{ $builderQuestionIsNotice
                                            ? 'Notice Title'
                                            : 'Question' }}
                                    </label>

                                    <input
                                        type="text"
                                        wire:model="meetingFormQuestionTexts.{{ $question->id }}"
                                        class="mt-2 block w-full
                                               rounded-xl border
                                               border-gray-300 bg-white
                                               px-4 py-3 text-sm
                                               dark:border-gray-700
                                               dark:bg-gray-900"
                                    >
                                </div>

                                <div class="md:col-span-2">
                                    <label
                                        class="block text-sm
                                               font-semibold"
                                    >
                                        {{ $builderQuestionIsNotice
                                            ? 'Notice / Message'
                                            : 'Description' }}
                                    </label>

                                    <textarea
                                        wire:model="meetingFormQuestionDescriptions.{{ $question->id }}"
                                        rows="2"
                                        class="mt-2 block w-full
                                               rounded-xl border
                                               border-gray-300 bg-white
                                               px-4 py-3 text-sm
                                               dark:border-gray-700
                                               dark:bg-gray-900"
                                    ></textarea>
                                </div>

                                @php
                                    $questionType =
                                        $this->meetingFormQuestionTypes[
                                            $question->id
                                        ]
                                        ?? $question->question_type;

                                    $questionIsDatabaseField =
                                        $questionType
                                        ===
                                        \App\Models\AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD;

                                    $questionNeedsOptions =
                                        in_array(
                                            $questionType,
                                            [
                                                \App\Models\AttendanceMeetingFormQuestion::TYPE_MULTIPLE_CHOICE,
                                                \App\Models\AttendanceMeetingFormQuestion::TYPE_CHECKBOXES,
                                                \App\Models\AttendanceMeetingFormQuestion::TYPE_DROPDOWN,
                                            ],
                                            true
                                        );

                                    $questionOptions =
                                        array_values(
                                            $this->meetingFormQuestionOptions[
                                                $question->id
                                            ]
                                            ?? []
                                        );

                                    while (
                                        $questionNeedsOptions
                                        &&
                                        count($questionOptions) < 2
                                    ) {
                                        $questionOptions[] = '';
                                    }
                                @endphp

                                @if ($builderQuestionIsNotice)
                                    <div
                                        class="md:col-span-2 rounded-xl
                                               border border-teal-200
                                               bg-teal-50 p-4
                                               text-sm text-teal-900
                                               dark:border-teal-900
                                               dark:bg-teal-950
                                               dark:text-teal-100"
                                    >
                                        Notice items display information only
                                        and do not collect or store answers.
                                    </div>

                                @elseif ($questionIsDatabaseField)
                                    <div
                                        class="md:col-span-2
                                               rounded-xl border
                                               border-sky-200
                                               bg-sky-50 p-4
                                               dark:border-sky-900
                                               dark:bg-sky-950"
                                    >
                                        <div
                                            class="grid gap-4
                                                   md:grid-cols-2"
                                        >
                                            <div>
                                                <label
                                                    class="block text-sm
                                                           font-semibold
                                                           text-sky-900
                                                           dark:text-sky-100"
                                                >
                                                    Database Field
                                                </label>

                                                <select
                                                    wire:model.live="meetingFormQuestionDatabaseFields.{{ $question->id }}"
                                                    wire:change="selectMeetingFormDatabaseField({{ $question->id }}, $event.target.value)"
                                                    class="mt-2 block w-full
                                                           rounded-xl border
                                                           border-sky-300
                                                           bg-white px-4 py-3
                                                           text-sm
                                                           dark:border-sky-800
                                                           dark:bg-gray-900"
                                                >
                                                    @foreach (
                                                        $this->meetingFormDatabaseFieldGroups()
                                                        as $group => $fields
                                                    )
                                                        <optgroup label="{{ $group }}">
                                                            @foreach (
                                                                $fields
                                                                as $field => $label
                                                            )
                                                                <option value="{{ $field }}">
                                                                    {{ $label }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="flex items-end">
                                                <label
                                                    class="inline-flex
                                                           items-center gap-2
                                                           rounded-xl border
                                                           border-sky-200
                                                           bg-white px-4 py-3
                                                           text-sm font-semibold
                                                           text-sky-900
                                                           dark:border-sky-800
                                                           dark:bg-gray-900
                                                           dark:text-sky-100"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        wire:model="meetingFormQuestionAllowCorrection.{{ $question->id }}"
                                                        class="rounded
                                                               border-gray-300
                                                               text-sky-600"
                                                    >

                                                    Allow correction request
                                                </label>
                                            </div>
                                        </div>

                                        @php
                                            $databaseField =
                                                $this
                                                    ->meetingFormQuestionDatabaseFields[
                                                        $question->id
                                                    ]
                                                ??
                                                $question->database_field;

                                            $databaseFieldDefinition =
                                                $this
                                                    ->meetingFormDatabaseFieldDefinition(
                                                        $databaseField
                                                    );

                                            $databaseFieldInput =
                                                $databaseFieldDefinition[
                                                    'input'
                                                ]
                                                ?? 'text';

                                            $databaseFieldLabel =
                                                $databaseFieldDefinition[
                                                    'label'
                                                ]
                                                ?? 'Database Field';
                                        @endphp

                                        <div
                                            class="mt-4 rounded-xl
                                                   border border-sky-100
                                                   bg-white p-4
                                                   dark:border-sky-900
                                                   dark:bg-gray-900"
                                        >
                                            <p
                                                class="text-xs font-bold
                                                       uppercase tracking-wide
                                                       text-sky-700
                                                       dark:text-sky-300"
                                            >
                                                Public Form Preview
                                            </p>

                                            @switch($databaseFieldInput)
                                                @case('date')
                                                    <input
                                                        type="date"
                                                        disabled
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                                    @break

                                                @case('sex')
                                                    <select
                                                        disabled
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                                        <option>Auto-filled Sex</option>
                                                        <option>Male</option>
                                                        <option>Female</option>
                                                    </select>
                                                    @break

                                                @case('locality')
                                                    <select
                                                        disabled
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                                        <option>
                                                            Auto-filled Locality
                                                        </option>
                                                    </select>
                                                    @break

                                                @case('school')
                                                    <select
                                                        disabled
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                                        <option>
                                                            Auto-filled School / Campus
                                                        </option>
                                                    </select>
                                                    @break

                                                @case('grade_level')
                                                    <select
                                                        disabled
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                                        <option>
                                                            Select grade / year level
                                                        </option>

                                                        @foreach (
                                                            \App\Support\MeetingFormDatabaseFieldRegistry::options(
                                                                'grade_level'
                                                            )
                                                            as $value => $label
                                                        )
                                                            <option value="{{ $value }}">
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @break

                                                @case('textarea')
                                                    <textarea
                                                        disabled
                                                        rows="3"
                                                        placeholder="Auto-filled {{ $databaseFieldLabel }}"
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    ></textarea>
                                                    @break

                                                @case('email')
                                                    <input
                                                        type="email"
                                                        disabled
                                                        placeholder="Auto-filled email address"
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                                    @break

                                                @case('tel')
                                                    <input
                                                        type="tel"
                                                        disabled
                                                        placeholder="Auto-filled contact number"
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                                    @break

                                                @case('multi_value')
                                                    <div
                                                        class="mt-2 rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               text-gray-500
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                                        Auto-filled selections
                                                    </div>
                                                    @break

                                                @default
                                                    <input
                                                        type="text"
                                                        disabled
                                                        placeholder="Auto-filled {{ $databaseFieldLabel }}"
                                                        class="mt-2 block w-full
                                                               cursor-not-allowed
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-gray-50
                                                               px-4 py-3 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-950"
                                                    >
                                            @endswitch

                                            <p
                                                class="mt-2 text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Existing database data
                                                will be auto-filled when
                                                the public form is enabled.
                                            </p>
                                        </div>
                                    </div>

                                @elseif ($questionNeedsOptions)
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-sm
                                                   font-semibold"
                                        >
                                            Options
                                        </label>

                                        <div class="mt-3 space-y-2">
                                            @foreach (
                                                $questionOptions
                                                as $optionIndex => $option
                                            )
                                                <div
                                                    class="flex items-center gap-2"
                                                    wire:key="question-option-{{ $question->id }}-{{ $optionIndex }}"
                                                >
                                                    <span
                                                        class="flex h-7 w-7
                                                               shrink-0 items-center
                                                               justify-center
                                                               text-sm
                                                               text-gray-400"
                                                    >
                                                        @if (
                                                            $questionType
                                                            ===
                                                            \App\Models\AttendanceMeetingFormQuestion::TYPE_MULTIPLE_CHOICE
                                                        )
                                                            ○
                                                        @elseif (
                                                            $questionType
                                                            ===
                                                            \App\Models\AttendanceMeetingFormQuestion::TYPE_CHECKBOXES
                                                        )
                                                            □
                                                        @else
                                                            {{ $optionIndex + 1 }}.
                                                        @endif
                                                    </span>

                                                    <input
                                                        type="text"
                                                        wire:model="meetingFormQuestionOptions.{{ $question->id }}.{{ $optionIndex }}"
                                                        placeholder="Option {{ $optionIndex + 1 }}"
                                                        class="block min-w-0
                                                               flex-1 rounded-xl
                                                               border
                                                               border-gray-300
                                                               bg-white px-4
                                                               py-2.5 text-sm
                                                               dark:border-gray-700
                                                               dark:bg-gray-900"
                                                    >

                                                    @if (count($questionOptions) > 2)
                                                        <button
                                                            type="button"
                                                            wire:click="removeMeetingFormQuestionOption({{ $question->id }}, {{ $optionIndex }})"
                                                            class="rounded-lg
                                                                   px-2 py-2
                                                                   text-sm font-bold
                                                                   text-red-600
                                                                   hover:bg-red-50
                                                                   dark:hover:bg-red-950"
                                                            title="Remove option"
                                                        >
                                                            ×
                                                        </button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="addMeetingFormQuestionOption({{ $question->id }})"
                                            class="mt-3 rounded-lg
                                                   px-3 py-2
                                                   text-sm font-bold
                                                   text-violet-700
                                                   hover:bg-violet-100
                                                   dark:text-violet-300
                                                   dark:hover:bg-violet-900"
                                        >
                                            + Add Option
                                        </button>

                                        @error(
                                            'meetingFormQuestionOptions.'
                                            . $question->id
                                        )
                                            <p
                                                class="mt-2 text-xs
                                                       text-red-600"
                                            >
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                @elseif (
                                    $questionType
                                    ===
                                    \App\Models\AttendanceMeetingFormQuestion::TYPE_PARAGRAPH
                                )
                                    <div class="md:col-span-2">
                                        <textarea
                                            disabled
                                            rows="3"
                                            placeholder="Long answer text"
                                            class="block w-full
                                                   cursor-not-allowed
                                                   rounded-xl border
                                                   border-gray-200
                                                   bg-gray-50
                                                   px-4 py-3
                                                   text-sm text-gray-400
                                                   dark:border-gray-800
                                                   dark:bg-gray-900"
                                        ></textarea>
                                    </div>

                                @else
                                    <div class="md:col-span-2">
                                        <input
                                            type="text"
                                            disabled
                                            placeholder="Short answer text"
                                            class="block w-full
                                                   cursor-not-allowed
                                                   border-0 border-b
                                                   border-gray-300
                                                   bg-transparent
                                                   px-1 py-2 text-sm
                                                   text-gray-400
                                                   focus:ring-0
                                                   dark:border-gray-700"
                                        >
                                    </div>
                                @endif
                            </div>

                            <div
                                class="mt-4 flex flex-wrap
                                       gap-2"
                            >
                                <button
                                    type="button"
                                    wire:click="saveMeetingFormQuestion({{ $question->id }})"
                                    class="rounded-lg
                                           bg-violet-600
                                           px-3 py-2
                                           text-xs font-bold
                                           text-white
                                           hover:bg-violet-500"
                                >
                                    Save Question
                                </button>

                                <button
                                    type="button"
                                    wire:click="moveMeetingFormQuestion({{ $question->id }}, 'up')"
                                    @disabled($loop->first)
                                    class="rounded-lg border
                                           border-gray-300
                                           px-3 py-2
                                           text-xs font-bold
                                           disabled:opacity-40
                                           dark:border-gray-700"
                                >
                                    Move Up
                                </button>

                                <button
                                    type="button"
                                    wire:click="moveMeetingFormQuestion({{ $question->id }}, 'down')"
                                    @disabled($loop->last)
                                    class="rounded-lg border
                                           border-gray-300
                                           px-3 py-2
                                           text-xs font-bold
                                           disabled:opacity-40
                                           dark:border-gray-700"
                                >
                                    Move Down
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteMeetingFormQuestion({{ $question->id }})"
                                    wire:confirm="Delete this question and all of its saved answers? This cannot be undone."
                                    class="rounded-lg
                                           border border-red-200
                                           bg-red-50 px-3 py-2
                                           text-xs font-bold
                                           text-red-700
                                           hover:bg-red-100
                                           dark:border-red-900
                                           dark:bg-red-950
                                           dark:text-red-200"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </details>
                @empty
                    <div
                        class="rounded-xl border
                               border-dashed border-violet-300
                               p-5 text-center text-sm
                               text-violet-700
                               dark:border-violet-800
                               dark:text-violet-300"
                    >
                        No custom questions yet.
                        Add the first question above.
                    </div>
                @endforelse
            </div>
        </div>
    </details>
@endif


@php
    $meetingSeries =
        $sheet->meetingSeries;

    $meetingSeriesStatus =
        $meetingSeries
            ? $this->meetingSeriesStatus(
                $meetingSeries
            )
            : null;

    $resolvedSession =
        $meetingSeriesStatus[
            'session'
        ] ?? null;

    $seriesOptions =
        $this->meetingSeriesOptions();
@endphp

@if ($sheet->is_active)
<details class="mt-5 overflow-hidden rounded-xl border border-sky-200 bg-sky-50 dark:border-sky-900 dark:bg-sky-950">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4">
        <span class="min-w-0">
            <span class="block text-sm font-bold text-sky-900 dark:text-sky-100">
                Permanent Meeting Link
            </span>

        </span>

        @if ($meetingSeries)
            <span class="shrink-0 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-800 dark:bg-sky-900 dark:text-sky-100">
                Linked
            </span>
        @endif
    </summary>

    <div class="border-t border-sky-200 p-4 dark:border-sky-900">

    @if ($meetingSeries)
        <div class="mt-4 rounded-xl border border-sky-200 bg-white p-4 dark:border-sky-900 dark:bg-gray-950">
            <div class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="break-words font-bold text-gray-900 dark:text-white">
                        {{ $meetingSeries->name }}
                    </p>

                    <p class="mt-1 break-all font-mono text-xs text-gray-500 dark:text-gray-400">
                        {{ $meetingSeries->publicUrl() }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($meetingSeriesStatus['status'] === 'today')
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                                Resolves to today's Session
                            </span>
                        @elseif ($meetingSeriesStatus['status'] === 'upcoming')
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-800 dark:bg-sky-900 dark:text-sky-100">
                                Next scheduled Session
                            </span>
                        @elseif ($meetingSeriesStatus['status'] === 'ambiguous')
                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800 dark:bg-red-900 dark:text-red-100">
                                Needs attention
                            </span>
                        @else
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                No upcoming Session
                            </span>
                        @endif

                        @if ($resolvedSession)
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ $resolvedSession->dateTimeLabel() }}
                            </span>

                            @if (
                                $resolvedSession->attendance_sheet_id
                                !== $sheet->id
                            )
                                <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-900 dark:text-violet-200">
                                    {{ $resolvedSession->sheet?->title }}
                                </span>
                            @endif
                        @endif
                    </div>

                    @if ($meetingSeriesStatus['status'] === 'ambiguous')
                        <p class="mt-3 text-xs font-semibold text-red-700 dark:text-red-300">
                            {{ $meetingSeriesStatus['message'] }}
                        </p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <button
                        type="button"
                        data-short-url="{{ $meetingSeries->shortPublicUrl() }}"
                        onclick="
                            navigator.clipboard
                                .writeText(this.dataset.shortUrl)
                                .then(() => {
                                    const button = this;
                                    const originalText =
                                        button.textContent.trim();

                                    button.textContent =
                                        'Copied!';

                                    setTimeout(() => {
                                        button.textContent =
                                            originalText;
                                    }, 1500);
                                });
                        "
                        class="rounded-lg bg-sky-600 px-3 py-2 text-xs font-bold text-white hover:bg-sky-500"
                    >
                        Shorten Link
                    </button>

                    <a
                        href="{{ $meetingSeries->publicUrl() }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-500"
                    >
                        Open Permanent Link
                    </a>
                </div>
            </div>
        </div>

        <details class="mt-4 rounded-xl border border-sky-200 bg-white p-4 dark:border-sky-900 dark:bg-gray-950">
            <summary class="cursor-pointer text-sm font-bold text-sky-900 dark:text-sky-100">
                Change Meeting Series
            </summary>

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Use this when a Sheet should belong to another
                existing permanent meeting identity.
            </p>

            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                <select
                    wire:model.defer="meetingSeriesSelections.{{ $sheet->id }}"
                    class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                >
                    <option value="">
                        Select existing Meeting Series
                    </option>

                    @foreach ($seriesOptions as $option)
                        <option value="{{ $option->id }}">
                            {{ $option->name }}
                            — {{ $option->public_slug }}
                            — {{ $option->sheets_count }}
                            {{ $option->sheets_count === 1 ? 'Sheet' : 'Sheets' }}
                        </option>
                    @endforeach
                </select>

                <button
                    type="button"
                    wire:click="attachMeetingSeries({{ $sheet->id }})"
                    wire:loading.attr="disabled"
                    class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-500 disabled:opacity-50"
                >
                    Attach Existing
                </button>
            </div>

            <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                <button
                    type="button"
                    wire:click="detachMeetingSeries({{ $sheet->id }})"
                    wire:confirm="Detach this Attendance Sheet from the permanent Meeting Series? The permanent URL and other linked semesters will be preserved."
                    wire:loading.attr="disabled"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                >
                    Detach This Sheet
                </button>
            </div>
        </details>
    @else
        <div class="mt-4 grid gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-sky-200 bg-white p-4 dark:border-sky-900 dark:bg-gray-950">
                <p class="text-sm font-bold text-gray-900 dark:text-white">
                    Attach Existing Meeting Series
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Best choice for Semester 2 or a new academic
                    year of an existing recurring meeting.
                </p>

                <select
                    wire:model.defer="meetingSeriesSelections.{{ $sheet->id }}"
                    class="mt-4 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                >
                    <option value="">
                        Select existing Meeting Series
                    </option>

                    @foreach ($seriesOptions as $option)
                        <option value="{{ $option->id }}">
                            {{ $option->name }}
                            — {{ $option->public_slug }}
                            — {{ $option->sheets_count }}
                            {{ $option->sheets_count === 1 ? 'Sheet' : 'Sheets' }}
                        </option>
                    @endforeach
                </select>

                <button
                    type="button"
                    wire:click="attachMeetingSeries({{ $sheet->id }})"
                    wire:loading.attr="disabled"
                    class="mt-3 w-full rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-500 disabled:opacity-50"
                >
                    Attach Existing Series
                </button>
            </div>

            <div class="rounded-xl border border-violet-200 bg-white p-4 dark:border-violet-900 dark:bg-gray-950">
                <p class="text-sm font-bold text-gray-900 dark:text-white">
                    Create New Permanent Meeting
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Use this only when this is a genuinely new
                    recurring meeting identity.
                </p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                            Meeting Series Name
                        </label>

                        <input
                            type="text"
                            wire:model.defer="newMeetingSeriesNames.{{ $sheet->id }}"
                            class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                            Permanent Slug
                        </label>

                        <input
                            type="text"
                            wire:model.defer="newMeetingSeriesSlugs.{{ $sheet->id }}"
                            class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 font-mono text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >

                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            m.overcomers.win/&lt;permanent-slug&gt;
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="createMeetingSeries({{ $sheet->id }})"
                        wire:loading.attr="disabled"
                        class="w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-500 disabled:opacity-50"
                    >
                        Create Permanent Link
                    </button>
                </div>
            </div>
        </div>
    @endif
    </div>
</details>
@endif

@if ($sheet->meetingFormEnabled())
    @php
        $meetingFormSessions = $sheet->sessions;
    @endphp

    <details
        @if ($meetingFormSessions->count() <= 4)
            open
        @endif
        class="mt-5 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950"
    >
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4">
            <span class="min-w-0">
                <span class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                    Session-Specific Meeting Forms
                </span>

                <span class="mt-1 block text-xs text-emerald-700 dark:text-emerald-300">
                    Exact public links for individual meeting dates.
                </span>
            </span>

            <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                {{ $meetingFormSessions->count() }}
                {{ $meetingFormSessions->count() === 1 ? 'Form' : 'Forms' }}
            </span>
        </summary>

        <div class="border-t border-emerald-200 p-4 dark:border-emerald-900">
            @if (! $sheet->is_active)
                <div
                    class="mb-4 rounded-xl border border-amber-200
                           bg-amber-50 p-4 text-sm font-semibold
                           text-amber-800
                           dark:border-amber-900
                           dark:bg-amber-950
                           dark:text-amber-200"
                >
                    Archived sheet — restore this Attendance Sheet
                    to enable the Session-Specific Meeting Forms.
                </div>
            @endif

            <div class="space-y-2">

            @forelse ($meetingFormSessions as $session)

                <div
                    class="flex min-w-0 flex-col gap-3 rounded-lg border border-emerald-200 bg-white p-3 dark:border-emerald-900 dark:bg-gray-950 lg:flex-row lg:items-center lg:justify-between"
                >

                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $session->dateTimeLabel() }}
                        </p>

                        @if ($session->publicMeetingUrl())
                            <p class="mt-1 break-all font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $session->publicMeetingUrl() }}
                            </p>
                        @else
                            <p class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-300">
                                Public URL has not been generated.
                            </p>
                        @endif
                    </div>


@if ($session->publicMeetingUrl())
    @if ($sheet->is_active)
        <div class="flex shrink-0 flex-wrap gap-2">
            <button
                type="button"
                data-short-url="https://m.overcomers.win/{{ $session->public_slug }}"
                onclick="
                    navigator.clipboard
                        .writeText(this.dataset.shortUrl)
                        .then(() => {
                            const button = this;
                            const originalText =
                                button.textContent.trim();

                            button.textContent =
                                'Copied!';

                            setTimeout(() => {
                                button.textContent =
                                    originalText;
                            }, 1500);
                        });
                "
                class="rounded-lg bg-sky-600 px-3 py-2
                       text-center text-xs font-bold text-white
                       hover:bg-sky-500"
            >
                Shorten Link
            </button>

            <a
                href="{{ $session->publicMeetingUrl() }}"
                target="_blank"
                rel="noopener noreferrer"
                class="rounded-lg bg-emerald-600 px-3 py-2
                       text-center text-xs font-bold text-white
                       hover:bg-emerald-500"
            >
                Open Form
            </a>
        </div>
    @else
        <div class="flex shrink-0 flex-wrap gap-2">
            <button
                type="button"
                disabled
                class="cursor-not-allowed rounded-lg
                       bg-gray-300 px-3 py-2 text-center
                       text-xs font-bold text-gray-500
                       opacity-70
                       dark:bg-gray-800
                       dark:text-gray-500"
            >
                Shorten Link
            </button>

            <span
                class="cursor-not-allowed rounded-lg
                       bg-gray-300 px-3 py-2 text-center
                       text-xs font-bold text-gray-500
                       opacity-70
                       dark:bg-gray-800
                       dark:text-gray-500"
            >
                Open Form
            </span>
        </div>
    @endif
@endif

                </div>

            @empty

                <div class="rounded-lg border border-dashed border-emerald-300 p-4 text-center text-sm text-emerald-700 dark:border-emerald-800 dark:text-emerald-200">
                    This attendance sheet has no meeting dates.
                </div>

            @endforelse

            </div>

            <p class="mt-3 text-xs text-emerald-700 dark:text-emerald-300">
                These links open one exact meeting date. The Permanent Meeting Link follows today's or the next scheduled Session.
            </p>
        </div>
    </details>
@endif

                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    No attendance sheets found.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
