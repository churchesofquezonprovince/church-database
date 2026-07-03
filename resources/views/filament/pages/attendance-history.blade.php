<x-filament-panels::page>
    @php
        $logs = $this->logs();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Attendance Audit Trail
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Attendance History
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Review attendance sheet creation, edits, archive/restore actions, deletions, participant changes, and saved attendance actions.
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-bold text-gray-900 dark:text-white">
                Action Filter
            </p>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($this->actionOptions() as $actionValue => $actionLabel)
                    <a
                        href="{{ $this->actionUrl($actionValue) }}"
                        class="rounded-full px-4 py-2 text-sm font-bold transition
                            {{ $this->selectedAction() === $actionValue
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        {{ $actionLabel }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ \App\Filament\Pages\AttendanceHistory::getUrl() }}" class="mt-5 grid gap-4 md:grid-cols-4">
                @if ($this->selectedAction() !== 'all')
                    <input type="hidden" name="log_action" value="{{ $this->selectedAction() }}">
                @endif

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Search
                    </label>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search action or description"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Date From
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        value="{{ $this->selectedDateFrom() }}"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Date To
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        value="{{ $this->selectedDateTo() }}"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="submit"
                        class="rounded-xl bg-primary-600 px-5 py-3 text-sm font-bold text-white hover:bg-primary-500"
                    >
                        Apply
                    </button>

                    <a
                        href="{{ $this->clearFiltersUrl() }}"
                        class="rounded-xl bg-gray-100 px-5 py-3 text-sm font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Latest Attendance Logs
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Showing latest {{ $logs->count() }} log(s).
                </p>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($logs as $log)
                    @php
                        $oldValues = $this->arrayValue($log->old_values ?? null);
                        $newValues = $this->arrayValue($log->new_values ?? null);
                    @endphp

                    <details class="group p-5">
                        <summary class="cursor-pointer list-none">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2 py-1 text-xs font-bold {{ $this->actionBadgeClass($log->action) }}">
                                            {{ $this->actionLabel($log->action) }}
                                        </span>

                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ optional($log->created_at)->format('M d, Y · g:i A') }}
                                        </span>
                                    </div>

                                    <h4 class="mt-2 text-base font-bold text-gray-900 dark:text-white">
                                        {{ $this->logTitle($log) }}
                                    </h4>

                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $this->logSummary($log) }}
                                    </p>
                                </div>

                                <p class="text-xs font-semibold text-gray-400 group-open:hidden">
                                    Click to view details
                                </p>
                            </div>
                        </summary>

                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                    Old Values
                                </p>

                                @if ($oldValues === [])
                                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                        No old values recorded.
                                    </p>
                                @else
                                    <dl class="mt-3 space-y-2 text-sm">
                                        @foreach ($oldValues as $key => $value)
                                            <div class="grid gap-1 sm:grid-cols-3">
                                                <dt class="font-semibold text-gray-500 dark:text-gray-400">
                                                    {{ str($key)->replace('_', ' ')->title() }}
                                                </dt>
                                                <dd class="sm:col-span-2 text-gray-900 dark:text-gray-100">
                                                    {{ $this->displayValue($value) }}
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                    New Values
                                </p>

                                @if ($newValues === [])
                                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                        No new values recorded.
                                    </p>
                                @else
                                    <dl class="mt-3 space-y-2 text-sm">
                                        @foreach ($newValues as $key => $value)
                                            <div class="grid gap-1 sm:grid-cols-3">
                                                <dt class="font-semibold text-gray-500 dark:text-gray-400">
                                                    {{ str($key)->replace('_', ' ')->title() }}
                                                </dt>
                                                <dd class="sm:col-span-2 text-gray-900 dark:text-gray-100">
                                                    {{ $this->displayValue($value) }}
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </div>
                        </div>
                    </details>
                @empty
                    <div class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        No attendance history logs found.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
