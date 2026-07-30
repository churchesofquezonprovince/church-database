<x-filament-panels::page>
    @php
        $configuredCalendars = collect(config('services.google_calendar.calendars', []))
            ->filter(fn ($calendar) => filled($calendar['id'] ?? null))
            ->map(fn ($calendar) => [
                'name' => $calendar['name'] ?? ($calendar['id'] ?? 'Calendar'),
                'id' => $calendar['id'] ?? null,
                'color' => $calendar['color'] ?? '#3b82f6',
                'count' => \App\Models\Schedule::query()
                    ->where('google_calendar_id', $calendar['id'] ?? null)
                    ->count(),
            ])
            ->values();

        $localScheduleCount = \App\Models\Schedule::query()
            ->where(function ($query) {
                $query
                    ->whereNull('google_calendar_id')
                    ->orWhere('google_calendar_id', '');
            })
            ->count();

        $calendarColorsById = $configuredCalendars
            ->mapWithKeys(fn ($calendar) => [$calendar['id'] => $calendar['color']])
            ->all();

        $calendarNamesById = $configuredCalendars
            ->mapWithKeys(fn ($calendar) => [$calendar['id'] => $calendar['name']])
            ->all();

        $upcomingScheduleRange = $this->upcomingScheduleRange ?? '30_days';

        $upcomingSchedulesQuery = \App\Models\Schedule::query()
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at');

        if ($upcomingScheduleRange === 'today') {
            $upcomingSchedulesQuery->where('starts_at', '<=', now()->endOfDay());
        } elseif ($upcomingScheduleRange === '7_days') {
            $upcomingSchedulesQuery->where('starts_at', '<=', now()->addDays(7)->endOfDay());
        } elseif ($upcomingScheduleRange === '30_days') {
            $upcomingSchedulesQuery->where('starts_at', '<=', now()->addDays(30)->endOfDay());
        }

        $upcomingSchedules = $upcomingSchedulesQuery
            ->limit($upcomingScheduleRange === 'all' ? 50 : 15)
            ->get();

        $upcomingRangeLabels = [
            'today' => 'Today',
            '7_days' => 'Next 7 Days',
            '30_days' => 'Next 30 Days',
            'all' => 'All Upcoming',
        ];
    @endphp

    <div class="space-y-4">
        @livewire(\App\Filament\Widgets\SchedulesCalendarWidget::class)

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-3">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Legends
                </h2>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Colors follow the configured Google Calendars.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @forelse ($configuredCalendars as $calendar)
                    <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <span
                            class="h-3 w-3 shrink-0 rounded-full"
                            style="background-color: {{ $calendar['color'] }}"
                        ></span>

                        <span class="font-medium text-gray-800 dark:text-gray-100">
                            {{ $calendar['name'] }}
                        </span>

                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-semibold text-gray-700 dark:bg-gray-950 dark:text-gray-200">
                            {{ $calendar['count'] }}
                        </span>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        No Google Calendars are configured yet.
                    </div>
                @endforelse

                @if ($localScheduleCount > 0)
                    <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <span
                            class="h-3 w-3 shrink-0 rounded-full"
                            style="background-color: #3b82f6"
                        ></span>

                        <span class="font-medium text-gray-800 dark:text-gray-100">
                            Local / Unsynced
                        </span>

                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-semibold text-gray-700 dark:bg-gray-950 dark:text-gray-200">
                            {{ $localScheduleCount }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Upcoming Schedules
                    </h2>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Showing {{ $upcomingRangeLabels[$upcomingScheduleRange] ?? 'Next 30 Days' }}.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($upcomingRangeLabels as $rangeKey => $rangeLabel)
                        <button
                            type="button"
                            wire:click="setUpcomingScheduleRange('{{ $rangeKey }}')"
                            class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition
                                {{ $upcomingScheduleRange === $rangeKey
                                    ? 'border-primary-500 bg-primary-50 text-primary-700 dark:border-primary-400 dark:bg-primary-950/40 dark:text-primary-300'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                        >
                            {{ $rangeLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                @forelse ($upcomingSchedules as $schedule)
                    @php
                        $calendarColor = $calendarColorsById[$schedule->google_calendar_id] ?? '#3b82f6';
                        $calendarName = $calendarNamesById[$schedule->google_calendar_id] ?? 'Local / Unsynced';
                    @endphp

                    <div class="flex flex-col gap-2 bg-white p-3 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-3 w-3 shrink-0 rounded-full"
                                    style="background-color: {{ $calendarColor }}"
                                ></span>

                                <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $schedule->title }}
                                </p>
                            </div>

                            @php
                                $scheduleStart = $schedule->starts_at?->timezone(config('app.timezone'));
                                $scheduleEnd = $schedule->ends_at?->timezone(config('app.timezone'));

                                if ($scheduleStart && $scheduleEnd && $scheduleStart->isSameDay($scheduleEnd)) {
                                    $scheduleDateText = $scheduleStart->format('M d, Y') . ' · ' .
                                        $scheduleStart->format('h:i A') . ' - ' .
                                        $scheduleEnd->format('h:i A');
                                } elseif ($scheduleStart && $scheduleEnd) {
                                    $scheduleDateText = $scheduleStart->format('M d, Y h:i A') . ' - ' .
                                        $scheduleEnd->format('M d, Y h:i A');
                                } elseif ($scheduleStart) {
                                    $scheduleDateText = $scheduleStart->format('M d, Y h:i A');
                                } else {
                                    $scheduleDateText = 'No schedule date';
                                }
                            @endphp

                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                <span>
                                    {{ $scheduleDateText }}
                                </span>

                                @if ($schedule->location)
                                    <span>
                                        {{ $schedule->location }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ $calendarName }}
                            </span>

                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ $schedule->google_sync_status ?: 'pending' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-4 text-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                        No upcoming schedules found.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</x-filament-panels::page>
