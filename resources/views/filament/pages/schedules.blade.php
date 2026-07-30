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

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Calendar
                </h2>

                <button
                    type="button"
                    wire:click="syncGoogleCalendar"
                    wire:loading.attr="disabled"
                    wire:target="syncGoogleCalendar"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="syncGoogleCalendar">
                        Sync Google Calendar Now
                    </span>

                    <span wire:loading wire:target="syncGoogleCalendar">
                        Syncing...
                    </span>
                </button>
            </div>

            <div class="p-6">
                @livewire(\App\Filament\Widgets\SchedulesCalendarWidget::class)
            </div>
        </div>

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
                    <div
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs shadow-sm"
                        style="background-color: #1f2937; border: 1px solid #374151; color: #f9fafb;"
                    >
                        <span
                            class="h-3 w-3 shrink-0 rounded-full"
                            style="background-color: {{ $calendar['color'] }}"
                        ></span>

                        <span class="font-medium">
                            {{ $calendar['name'] }}
                        </span>

                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                            style="background-color: #111827; color: #f9fafb;"
                        >
                            {{ $calendar['count'] }}
                        </span>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        No Google Calendars are configured yet.
                    </div>
                @endforelse

                @if ($localScheduleCount > 0)
                    <div
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs shadow-sm"
                        style="background-color: #1f2937; border: 1px solid #374151; color: #f9fafb;"
                    >
                        <span
                            class="h-3 w-3 shrink-0 rounded-full"
                            style="background-color: #3b82f6"
                        ></span>

                        <span class="font-medium">
                            Local / Unsynced
                        </span>

                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                            style="background-color: #111827; color: #f9fafb;"
                        >
                            {{ $localScheduleCount }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
