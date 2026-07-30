<x-filament-panels::page>
    {{-- Phase 20H: Google Calendar Legend --}}
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

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-3">
            <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                Google Calendar Legend
            </h2>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                These colors match the configured Google Calendars used by the Schedules module.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @forelse ($configuredCalendars as $calendar)
                <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs dark:border-gray-700">
                    <span
                        class="h-3 w-3 rounded-full"
                        style="background-color: {{ $calendar['color'] }}"
                    ></span>

                    <span class="font-medium text-gray-800 dark:text-gray-100">
                        {{ $calendar['name'] }}
                    </span>

                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ $calendar['count'] }}
                    </span>
                </div>
            @empty
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    No Google Calendars are configured yet.
                </div>
            @endforelse

            @if ($localScheduleCount > 0)
                <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs dark:border-gray-700">
                    <span
                        class="h-3 w-3 rounded-full"
                        style="background-color: #3b82f6"
                    ></span>

                    <span class="font-medium text-gray-800 dark:text-gray-100">
                        Local / Unsynced
                    </span>

                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ $localScheduleCount }}
                    </span>
                </div>
            @endif
        </div>
    </div>


    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-bold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Posts
            </p>

            <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                Schedules
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Phase 20E: Multiple Google Calendars are supported. Add/Edit can choose the target calendar, and Sync Now pulls from all configured calendars.
            </p>

            <div class="mt-4">
                <button
                    type="button"
                    wire:click="syncGoogleCalendar"
                    wire:loading.attr="disabled"
                    wire:target="syncGoogleCalendar"
                    class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-60"
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

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @livewire(\App\Filament\Widgets\SchedulesCalendarWidget::class)
        </div>
    </div>
</x-filament-panels::page>
