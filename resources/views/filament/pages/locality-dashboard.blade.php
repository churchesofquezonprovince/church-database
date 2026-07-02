<x-filament-panels::page>
    <div class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 to-white p-6 shadow-sm dark:border-primary-900 dark:from-gray-900 dark:to-gray-950">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                        Churches of Quezon Database
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        Locality Dashboard
                    </h2>

                    <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                        View people and households grouped by locality.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ \App\Filament\Resources\People\PersonResource::getUrl('index') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                    >
                        Open People
                    </a>

                    <a
                        href="{{ \App\Filament\Resources\Households\HouseholdResource::getUrl('index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-50 dark:border-primary-900 dark:bg-gray-900 dark:text-primary-200 dark:hover:bg-primary-950"
                    >
                        Open Households
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 text-primary-800 shadow-sm dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">
                <p class="text-sm font-semibold opacity-75">Total People</p>
                <p class="mt-3 text-4xl font-bold">{{ $summary['total_people'] }}</p>
            </div>

            <div class="rounded-2xl border border-purple-200 bg-purple-50 p-5 text-purple-800 shadow-sm dark:border-purple-900 dark:bg-purple-950 dark:text-purple-200">
                <p class="text-sm font-semibold opacity-75">Households</p>
                <p class="mt-3 text-4xl font-bold">{{ $summary['total_households'] }}</p>
            </div>

            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sky-800 shadow-sm dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200">
                <p class="text-sm font-semibold opacity-75">Localities</p>
                <p class="mt-3 text-4xl font-bold">{{ $summary['total_localities'] }}</p>
            </div>

            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                <p class="text-sm font-semibold opacity-75">People No Locality</p>
                <p class="mt-3 text-4xl font-bold">{{ $summary['people_without_locality'] }}</p>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                <p class="text-sm font-semibold opacity-75">Households No Locality</p>
                <p class="mt-3 text-4xl font-bold">{{ $summary['households_without_locality'] }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Locality Breakdown
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Sorted by the combined number of people and households.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                @forelse ($localities as $locality)
                    @php
                        $isMissing = $locality['name'] === 'No Locality';
                    @endphp

                    <div
                        @class([
                            'rounded-2xl border p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md',
                            'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950' => $isMissing,
                            'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-950' => ! $isMissing,
                        ])
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    @class([
                                        'text-lg font-bold',
                                        'text-red-800 dark:text-red-200' => $isMissing,
                                        'text-gray-900 dark:text-white' => ! $isMissing,
                                    ])
                                >
                                    {{ $locality['name'] }}
                                </p>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $locality['total_count'] }} total record(s)
                                </p>
                            </div>

                            <span class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white">
                                {{ $locality['total_count'] }}
                            </span>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <a
                                href="{{ $locality['people_url'] }}"
                                class="rounded-xl border border-primary-200 bg-white p-4 text-center transition hover:bg-primary-50 dark:border-primary-900 dark:bg-gray-900 dark:hover:bg-primary-950"
                            >
                                <p class="text-2xl font-bold text-primary-700 dark:text-primary-200">
                                    {{ $locality['people_count'] }}
                                </p>

                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    People
                                </p>
                            </a>

                            <a
                                href="{{ $locality['households_url'] }}"
                                class="rounded-xl border border-purple-200 bg-white p-4 text-center transition hover:bg-purple-50 dark:border-purple-900 dark:bg-gray-900 dark:hover:bg-purple-950"
                            >
                                <p class="text-2xl font-bold text-purple-700 dark:text-purple-200">
                                    {{ $locality['household_count'] }}
                                </p>

                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Households
                                </p>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No locality data recorded yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
