<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">Posts Roadmap</p>
            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">Prayer Meeting Items</h2>
            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Weekly changing prayer items by locality. If a locality has no specific items, it will automatically use the Lucena items.
            </p>
        </div>

        <div class="rounded-2xl border border-dashed border-primary-300 bg-white p-8 text-center shadow-sm dark:border-primary-800 dark:bg-gray-900">
            <x-heroicon-o-clipboard-document-list class="mx-auto h-14 w-14 text-primary-600 dark:text-primary-300" />
            <h3 class="mt-5 text-xl font-bold text-gray-900 dark:text-white">Coming Soon</h3>
            <p class="mx-auto mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Planned fields: week date, locality, prayer burden, responsible one, and Lucena fallback content.
            </p>
        </div>
    </div>
</x-filament-panels::page>
