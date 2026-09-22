<x-filament-panels::page>
    <div class="space-y-6">
        @if (session('attendance_sheet_created'))
            <div data-coqp-flash="success" data-coqp-flash-id="971599c0c1066dee" data-coqp-keep="true" class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 shadow-sm dark:border-green-900 dark:bg-green-950 dark:text-green-100">
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
            <div data-coqp-flash="danger" data-coqp-flash-id="d5e4e13f6670c933" data-coqp-keep="true" class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
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
                    x-data="{
                        scheduleType: @js(
                            old(
                                'schedule_type',
                                \App\Models\AttendanceSheet::SCHEDULE_RECURRING
                            )
                        ),

                        manualDate: '',

                        manualDates: @js(
                            array_values(
                                old('manual_dates', [])
                            )
                        ),

                        addManualDate() {
                            if (! this.manualDate) {
                                return;
                            }

                            if (! this.manualDates.includes(this.manualDate)) {
                                this.manualDates.push(this.manualDate);
                                this.manualDates.sort();
                            }

                            this.manualDate = '';
                        },

                        removeManualDate(index) {
                            this.manualDates.splice(index, 1);
                        }
                    }"
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
                            <label for="locality_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Locality
                            </label>

                            <select
                                id="locality_id"
                                name="locality_id"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >
                                <option value="">No Locality / Not locality-specific</option>

                                @foreach ($this->localityOptions() as $group => $options)
                                    <optgroup label="{{ $group }}">
                                        @foreach ($options as $localityId => $locality)
                                            <option
                                                value="{{ $localityId }}"
                                                @selected((string) old('locality_id') === (string) $localityId)
                                            >
                                                {{ $locality }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Scheduling Mode
                            </label>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Choose how attendance session dates will be generated.
                            </p>

                            <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-900 dark:bg-primary-950">
                                    <input
                                        type="radio"
                                        name="schedule_type"
                                        value="recurring"
                                        x-model="scheduleType"
                                        @checked(old('schedule_type', 'recurring') === 'recurring')
                                        class="mt-1 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500"
                                    >

                                    <span>
                                        <span class="block font-bold text-gray-900 dark:text-white">
                                            Recurring Weekly
                                        </span>

                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                            One selected weekday between Start and End Date.
                                        </span>
                                    </span>
                                </label>

                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                                    <input
                                        type="radio"
                                        name="schedule_type"
                                        value="one_time"
                                        x-model="scheduleType"
                                        x-on:change="
                                            if ($el.checked) {
                                                document.getElementById('end_date').value = '';
                                            }
                                        "
                                        @checked(old('schedule_type') === 'one_time')
                                        class="mt-1 h-4 w-4 border-gray-300 text-amber-600 focus:ring-amber-500"
                                    >

                                    <span>
                                        <span class="block font-bold text-amber-900 dark:text-amber-100">
                                            One-time Attendance
                                        </span>

                                        <span class="mt-1 block text-xs text-amber-700 dark:text-amber-300">
                                            Creates exactly one Session from Start Date.
                                        </span>
                                    </span>
                                </label>

                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
                                    <input
                                        type="radio"
                                        name="schedule_type"
                                        value="consecutive"
                                        x-model="scheduleType"
                                        @checked(old('schedule_type') === 'consecutive')
                                        class="mt-1 h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    >

                                    <span>
                                        <span class="block font-bold text-emerald-900 dark:text-emerald-100">
                                            Consecutive Days
                                        </span>

                                        <span class="mt-1 block text-xs text-emerald-700 dark:text-emerald-300">
                                            Creates one Session for every calendar day in the range.
                                        </span>
                                    </span>
                                </label>

                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950">
                                    <input
                                        type="radio"
                                        name="schedule_type"
                                        value="manual"
                                        x-model="scheduleType"
                                        @checked(old('schedule_type') === 'manual')
                                        class="mt-1 h-4 w-4 border-gray-300 text-violet-600 focus:ring-violet-500"
                                    >

                                    <span>
                                        <span class="block font-bold text-violet-900 dark:text-violet-100">
                                            Manual Dates
                                        </span>

                                        <span class="mt-1 block text-xs text-violet-700 dark:text-violet-300">
                                            Create the Sheet now and add Session dates manually later.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div
                            x-show="scheduleType === 'manual'"
                            x-cloak
                            class="md:col-span-2 rounded-2xl border border-violet-200 bg-violet-50 p-5 dark:border-violet-900 dark:bg-violet-950"
                        >
                            <div>
                                <p class="text-sm font-bold text-violet-950 dark:text-violet-100">
                                    Manual Session Dates
                                </p>

                                <p class="mt-1 text-xs text-violet-700 dark:text-violet-300">
                                    Click the date field to open the calendar,
                                    then add each Session date individually.
                                </p>
                            </div>

                            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div class="flex-1">
                                    <label
                                        for="manual_session_date"
                                        class="block text-xs font-semibold text-violet-800 dark:text-violet-200"
                                    >
                                        Select Session Date
                                    </label>

                                    <input
                                        id="manual_session_date"
                                        type="date"
                                        x-model="manualDate"
                                        x-on:keydown.enter.prevent="addManualDate()"
                                        class="mt-2 block w-full rounded-xl border border-violet-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-violet-800 dark:bg-gray-950 dark:text-gray-100"
                                    >
                                </div>

                                <button
                                    type="button"
                                    x-on:click="addManualDate()"
                                    class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-violet-500"
                                >
                                    + Add Date
                                </button>
                            </div>

                            <div
                                x-show="manualDates.length > 0"
                                class="mt-4 space-y-2"
                            >
                                <template
                                    x-for="(date, index) in manualDates"
                                    :key="date"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3 rounded-xl border border-violet-200 bg-white px-4 py-3 dark:border-violet-800 dark:bg-gray-950"
                                    >
                                        <div>
                                            <input
                                                type="hidden"
                                                name="manual_dates[]"
                                                :value="date"
                                            >

                                            <span
                                                class="text-sm font-semibold text-gray-900 dark:text-white"
                                                x-text="
                                                    new Date(
                                                        date + 'T00:00:00'
                                                    ).toLocaleDateString(
                                                        undefined,
                                                        {
                                                            year: 'numeric',
                                                            month: 'short',
                                                            day: 'numeric'
                                                        }
                                                    )
                                                "
                                            ></span>
                                        </div>

                                        <button
                                            type="button"
                                            x-on:click="removeManualDate(index)"
                                            class="text-xs font-bold text-red-600 hover:text-red-500 dark:text-red-400"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div
                                x-show="manualDates.length === 0"
                                class="mt-4 rounded-xl border border-dashed border-violet-300 px-4 py-3 text-center text-xs text-violet-700 dark:border-violet-800 dark:text-violet-300"
                            >
                                No Manual Session Dates added yet.
                            </div>
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
                                Start Time
                            </label>

                            <input
                                id="meeting_time"
                                name="meeting_time"
                                type="time"
                                value="{{ old('meeting_time') }}"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Optional. For Manual Dates, this becomes the default Start Time.
                            </p>
                        </div>

                        <div>
                            <label for="end_time" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                End Time
                            </label>

                            <input
                                id="end_time"
                                name="end_time"
                                type="time"
                                value="{{ old('end_time') }}"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Optional. Must be later than Start Time.
                            </p>
                        </div>

                        <div
                            class="md:col-span-2"
                            x-show="scheduleType === 'recurring'"
                        >
                            <label for="meeting_day" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Meeting Day
                            </label>

                            <select
                                id="meeting_day"
                                name="meeting_day"
                                x-bind:disabled="scheduleType !== 'recurring'"
                                x-bind:required="scheduleType === 'recurring'"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
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
                                            (string) old('meeting_day', '4')
                                            === (string) $value
                                        )
                                    >
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
                                x-bind:disabled="scheduleType === 'manual'"
                                x-bind:required="scheduleType !== 'manual'"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
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
                                x-bind:disabled="
                                    scheduleType === 'one_time'
                                    || scheduleType === 'manual'
                                "
                                x-bind:required="
                                    scheduleType === 'recurring'
                                    || scheduleType === 'consecutive'
                                "
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
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
