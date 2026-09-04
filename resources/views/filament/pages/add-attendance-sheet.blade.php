<x-filament-panels::page>
    <div class="space-y-6">
        @if (session('attendance_sheet_created'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 shadow-sm dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Attendance sheet created.</p>
                <p class="mt-1 text-sm">
                    {{ session('attendance_sheet_title') }} was created with {{ session('attendance_sessions_created') }} session date(s).
                </p>

                <a
                    href="{{ \App\Filament\Pages\AttendanceSheets::getUrl() }}"
                    class="mt-4 inline-flex rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500"
                >
                    Manage Attendance Sheets
                </a>
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

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 xl:col-span-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Sheet Details
                </h3>

                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.attendance-sheets.store') }}"
                    class="mt-6 space-y-5"
                >
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Title
                            </label>

                            <input
                                id="title"
                                name="title"
                                type="text"
                                value="{{ old('title') }}"
                                required
                                placeholder="Campus Meeting"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label for="locality" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Locality
                            </label>

                            <input
                                id="locality"
                                name="locality"
                                type="text"
                                value="{{ old('locality') }}"
                                placeholder="Lucban, Lucena, Pagbilao"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                                <input
                                    type="checkbox"
                                    name="is_one_time"
                                    value="1"
                                    @checked(old('is_one_time'))
                                    class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                >

                                One-time attendance only
                            </label>

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                If checked, only the Start Date will be used as the meeting date. End Date and Meeting Day will be ignored.
                            </p>
                        </div>

{{-- =========================================================
     MEETING FORM
========================================================== --}}
<div class="md:col-span-2">

    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Meeting Form
    </label>

    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        Choose whether this attendance sheet should provide a public meeting response form.
    </p>


    <div class="mt-3 grid gap-3 md:grid-cols-3">

        {{-- Disabled --}}
        <label
            class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-800"
        >
            <input
                type="radio"
                name="meeting_form_type"
                value="disabled"
                @checked(
                    old(
                        'meeting_form_type',
                        \App\Models\AttendanceSheet::MEETING_FORM_DISABLED
                    )
                    === \App\Models\AttendanceSheet::MEETING_FORM_DISABLED
                )
                class="mt-1 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500"
            >

            <span>
                <span class="block font-bold text-gray-900 dark:text-white">
                    Disabled
                </span>

                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                    Do not create a public meeting form.
                </span>
            </span>
        </label>


        {{-- Normal Meeting Form --}}
        <label
            class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:hover:bg-emerald-900"
        >
            <input
                type="radio"
                name="meeting_form_type"
                value="normal"
                @checked(
                    old('meeting_form_type')
                    === \App\Models\AttendanceSheet::MEETING_FORM_NORMAL
                )
                class="mt-1 h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500"
            >

            <span>
                <span class="block font-bold text-emerald-900 dark:text-emerald-100">
                    Normal Meeting Form
                </span>

                <span class="mt-1 block text-xs text-emerald-700 dark:text-emerald-300">
                    Creates one public response URL for every meeting date.
                </span>
            </span>
        </label>


        {{-- Future option --}}
        <div
            class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-100 p-4 opacity-60 dark:border-gray-700 dark:bg-gray-800"
        >
            <input
                type="radio"
                disabled
                class="mt-1 h-4 w-4"
            >

            <span>
                <span class="block font-bold text-gray-600 dark:text-gray-300">
                    Google Form-like
                </span>

                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                    Future feature.
                </span>
            </span>
        </div>

    </div>


    <div class="mt-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200">
        Normal Meeting Form generates stable addresses such as
        <span class="font-mono font-semibold">
            /meeting/8-15-26-churchmeeting
        </span>.
        The public page itself will be activated in Phase 26C.
    </div>

</div>

                        <div>
                            <label for="meeting_time" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Time
                            </label>

                            <input
                                id="meeting_time"
                                name="meeting_time"
                                type="time"
                                value="{{ old('meeting_time') }}"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Optional. Example: 07:30 PM.
                            </p>
                        </div>

                        <div>
                            <label for="meeting_day" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Meeting Day
                            </label>

                            <select
                                id="meeting_day"
                                name="meeting_day"
                                required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
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
                                    <option value="{{ $value }}" @selected((string) old('meeting_day', '4') === (string) $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Start Date / Meeting Date
                            </label>

                            <input
                                id="start_date"
                                name="start_date"
                                type="date"
                                value="{{ old('start_date') }}"
                                required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                End Date
                            </label>

                            <input
                                id="end_date"
                                name="end_date"
                                type="date"
                                value="{{ old('end_date') }}"
                                required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label for="remarks" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Remarks
                            </label>

                            <textarea
                                id="remarks"
                                name="remarks"
                                rows="3"
                                placeholder="Optional notes"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                        >
                            Create Attendance Sheet
                        </button>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    <p class="font-bold">Example</p>
                    <p class="mt-2 text-sm">
                        Campus Meeting every Thursday from July 16, 2026 to November 12, 2026.
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="font-bold text-gray-900 dark:text-white">What happens after creating?</p>

                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-gray-500 dark:text-gray-400">
                        <li>The attendance sheet is saved.</li>
                        <li>Weekly meeting dates are generated automatically for recurring sheets.</li>
                        <li>One-time sheets create only one meeting date.</li>
                        <li>Participants will be added in the next phase.</li>
                        <li>Checkbox attendance will be added after participants.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
