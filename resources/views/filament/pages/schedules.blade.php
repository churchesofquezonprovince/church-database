<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-bold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Posts
            </p>

            <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                Schedules
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Phase 12A: Local schedule calendar display. Google Calendar sync and Add/Edit actions will be added in the next phases.
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @livewire(\App\Filament\Widgets\SchedulesCalendarWidget::class)
        </div>
    </div>
</x-filament-panels::page>
