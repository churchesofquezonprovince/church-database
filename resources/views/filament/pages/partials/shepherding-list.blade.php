<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ $title }}
    </h3>

    <div class="mt-4 space-y-3">
        @forelse ($people as $person)
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <a
                    href="{{ $person['url'] }}"
                    class="font-medium text-primary-600 hover:underline dark:text-primary-400"
                >
                    {{ $person['name'] }}
                </a>

                <div class="mt-2 grid gap-1 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2">
                    <p>Status: {{ $person['status'] }}</p>
                    <p>Category: {{ $person['category'] }}</p>
                    <p>Service: {{ $person['service'] }}</p>
                    <p>Shepherd: {{ $person['shepherd'] }}</p>
                    <p>Locality: {{ $person['locality'] }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-600 dark:text-gray-300">
                No records found.
            </p>
        @endforelse
    </div>
</div>
