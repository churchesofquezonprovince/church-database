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
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
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

                    <details class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                        <summary class="cursor-pointer text-sm font-bold text-gray-900 dark:text-white">
                            Edit sheet details
                        </summary>

<form
    method="POST"
    action="{{ route('quezonprovinceactivities.attendance-sheets.sheets.update', $sheet) }}"
    class="mt-5 grid gap-4 md:grid-cols-2"
    x-data="{
        oneTime:
            {{ $sheet->is_one_time ? 'true' : 'false' }}
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

                                <input
                                    type="text"
                                    name="locality"
                                    value="{{ $sheet->locality }}"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                >
                            </div>

<div class="md:col-span-2">
    <label
        class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100"
    >
        <input
            type="checkbox"
            name="is_one_time"
            value="1"
            x-model="oneTime"
            @checked($sheet->is_one_time)
            class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
        >

        One-time attendance only
    </label>

    <p
        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
    >
        When enabled, Start Date becomes the single
        meeting date. Empty extra dates can be removed,
        but dates containing attendance, pre-listed,
        or Immich history will never be deleted.
    </p>
</div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    Time
                                </label>

                                <input
                                    type="time"
                                    name="meeting_time"
                                    value="{{ substr((string) ($sheet->meeting_time ?? ''), 0, 5) }}"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                >
                            </div>

<div x-show="! oneTime">
    <label
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
    >
        Meeting Day
    </label>

    <select
        name="meeting_day"
        :disabled="oneTime"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
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
                    (int) $sheet->meeting_day
                    === $value
                )
            >
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
    >
        <span x-show="! oneTime">
            Start Date
        </span>

        <span x-show="oneTime">
            Meeting Date
        </span>
    </label>

    <input
        type="date"
        name="start_date"
        value="{{
            $sheet->start_date?->format('Y-m-d')
            ??
            $sheet->sessions
                ->min('session_date')
                ?->format('Y-m-d')
        }}"
        required
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
    >
</div>

<div x-show="! oneTime">
    <label
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
    >
        End Date
    </label>

    <input
        type="date"
        name="end_date"
        :disabled="oneTime"
        value="{{
            $sheet->end_date?->format('Y-m-d')
            ??
            $sheet->sessions
                ->max('session_date')
                ?->format('Y-m-d')
        }}"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
    >
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


        {{-- Future --}}
        <div
            class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-100 p-4 opacity-60 dark:border-gray-700 dark:bg-gray-800"
        >
            <input
                type="radio"
                disabled
                class="mt-1 h-4 w-4"
            >

            <span class="min-w-0">
                <span class="block font-bold text-gray-600 dark:text-gray-300">
                    Google Form-like
                </span>

                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                    Future feature.
                </span>
            </span>
        </div>

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

@if ($sheet->meetingFormEnabled())
    @php
        $meetingFormSessions = $sheet->sessions;
    @endphp

    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">

        <div>
            <p class="text-sm font-bold text-emerald-900 dark:text-emerald-100">
                Public Meeting Forms
            </p>

            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                Each meeting date has its own stable public response link.
            </p>
        </div>


        <div class="mt-4 space-y-2">

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
                        <a
                            href="{{ $session->publicMeetingUrl() }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="shrink-0 rounded-lg bg-emerald-600 px-3 py-2 text-center text-xs font-bold text-white hover:bg-emerald-500"
                        >
                            Open Form
                        </a>
                    @endif

                </div>

            @empty

                <div class="rounded-lg border border-dashed border-emerald-300 p-4 text-center text-sm text-emerald-700 dark:border-emerald-800 dark:text-emerald-200">
                    This attendance sheet has no meeting dates.
                </div>

            @endforelse

        </div>

        <p class="mt-3 text-xs text-emerald-700 dark:text-emerald-300">
            The links are generated now. The public page will become functional in Phase 26C.
        </p>

    </div>
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
