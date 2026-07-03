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
                                action="{{ route('church-database.attendance-sheets.sheets.toggle-active', $sheet) }}"
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
                                    action="{{ route('church-database.attendance-sheets.sheets.destroy', $sheet) }}"
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
                            action="{{ route('church-database.attendance-sheets.sheets.update', $sheet) }}"
                            class="mt-5 grid gap-4 md:grid-cols-2"
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
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    No attendance sheets found.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
