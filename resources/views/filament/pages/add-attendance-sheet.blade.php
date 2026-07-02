<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Attendance Module
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Add Attendance Sheet
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Create recurring attendance sheets such as Campus Meeting, Young People Meeting, or other weekly gatherings.
            </p>
        </div>

        @if (session('attendance_sheet_created'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 shadow-sm dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Attendance sheet created.</p>
                <p class="mt-1 text-sm">
                    {{ session('attendance_sheet_title') }} was created with {{ session('attendance_sessions_created') }} session date(s).
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

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 xl:col-span-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Sheet Details
                </h3>

                <form
                    method="POST"
                    action="{{ route('church-database.attendance-sheets.store') }}"
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
                                Start Date
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
                        <li>Weekly session dates are generated automatically.</li>
                        <li>Participants will be added in the next phase.</li>
                        <li>Checkbox attendance will be added after participants.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
