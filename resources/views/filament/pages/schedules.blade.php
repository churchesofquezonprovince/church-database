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
    @endphp

    <div class="space-y-3">
        <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                            Calendar
                        </h2>

                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            |
                        </span>

                        <div class="flex flex-wrap items-center gap-1.5">
                            @forelse ($configuredCalendars as $calendar)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-2 py-1 text-[11px] leading-none dark:border-gray-700">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full"
                                        style="background-color: {{ $calendar['color'] }}"
                                    ></span>

                                    <span class="font-medium text-gray-700 dark:text-gray-200">
                                        {{ $calendar['name'] }}
                                    </span>

                                    <span class="text-gray-400 dark:text-gray-500">
                                        {{ $calendar['count'] }}
                                    </span>
                                </span>
                            @empty
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    No Google Calendars configured.
                                </span>
                            @endforelse

                            @if ($localScheduleCount > 0)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-2 py-1 text-[11px] leading-none dark:border-gray-700">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full"
                                        style="background-color: #3b82f6"
                                    ></span>

                                    <span class="font-medium text-gray-700 dark:text-gray-200">
                                        Local
                                    </span>

                                    <span class="text-gray-400 dark:text-gray-500">
                                        {{ $localScheduleCount }}
                                    </span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Colors follow the configured Google Calendars.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="syncGoogleCalendar"
                    wire:loading.attr="disabled"
                    wire:target="syncGoogleCalendar"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="syncGoogleCalendar">
                        Sync Google Calendar Now
                    </span>

                    <span wire:loading wire:target="syncGoogleCalendar">
                        Syncing...
                    </span>
                </button>
            </div>
        </div>

        @livewire(\App\Filament\Widgets\SchedulesCalendarWidget::class)
    </div>
</x-filament-panels::page>
