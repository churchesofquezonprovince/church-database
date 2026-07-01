<div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                {{ $title }}
            </h3>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Showing first {{ count($people) }} records
            </p>
        </div>

        @if (! empty($viewAllUrl))
            <a
                href="{{ $viewAllUrl }}"
                class="rounded-lg bg-primary-600 px-3 py-2 text-xs font-medium text-white hover:bg-primary-500"
            >
                View all →
            </a>
        @endif
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($people as $person)
            <div class="rounded-xl border border-gray-200 p-4 transition hover:border-primary-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-primary-700 dark:hover:bg-gray-800">
                <a
                    href="{{ $person['url'] }}"
                    class="font-semibold text-primary-600 hover:underline dark:text-primary-400"
                >
                    {{ $person['name'] }}
                </a>

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Status: {{ $person['status'] }}
                    </span>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Category: {{ $person['category'] }}
                    </span>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Group: {{ $person['service'] }}
                    </span>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Shepherd: {{ $person['shepherd'] }}
                    </span>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Locality: {{ $person['locality'] }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    No records found.
                </p>
            </div>
        @endforelse
    </div>
</div>
