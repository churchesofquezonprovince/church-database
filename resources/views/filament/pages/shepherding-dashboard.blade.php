<x-filament-panels::page>
    <div class="space-y-6">

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Shepherding Overview
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Monitor church care, shepherding assignments, and people needing follow-up.
                    </p>
                </div>

                <div class="w-full md:max-w-sm">
                    <label for="locality" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Locality
                    </label>

                    <select
                        id="locality"
                        wire:model.live="locality"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="" style="color: #111827; background-color: #ffffff;">
                            All Localities
                        </option>

                        @foreach ($localities as $localityOption)
                            <option
                                value="{{ $localityOption }}"
                                style="color: #111827; background-color: #ffffff;"
                            >
                                {{ $localityOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                Showing data for:
                <span class="font-semibold text-gray-900 dark:text-white">
                    {{ filled($locality) ? $locality : 'All Localities' }}
                </span>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $key => $stat)
                @php
                    $tone = match ($key) {
                        'active' => 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300',
                        'new_ones' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',
                        'gospel_friends' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
                        'dormant' => 'border-gray-300 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
                        'without_shepherd', 'without_service' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
                        default => 'border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-300',
                    };
                @endphp

                <a
                    href="{{ $stat['url'] }}"
                    class="rounded-2xl border p-5 shadow-sm transition hover:scale-[1.01] hover:shadow-md {{ $tone }}"
                >
                    <p class="text-sm font-medium opacity-80">
                        {{ $stat['label'] }}
                    </p>

                    <p class="mt-3 text-4xl font-bold">
                        {{ $stat['count'] }}
                    </p>

                    <p class="mt-3 text-xs font-medium opacity-80">
                        View filtered list →
                    </p>
                </a>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @include('filament.pages.partials.shepherding-list', [
                'title' => 'People Without Shepherd',
                'people' => $peopleWithoutShepherd,
                'viewAllUrl' => $listUrls['without_shepherd'] ?? null,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'Dormant People',
                'people' => $dormantPeople,
                'viewAllUrl' => $listUrls['dormant'] ?? null,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'New Ones',
                'people' => $newOnes,
                'viewAllUrl' => $listUrls['new_ones'] ?? null,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'Gospel Friends',
                'people' => $gospelFriends,
                'viewAllUrl' => $listUrls['gospel_friends'] ?? null,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'People Without Shepherding Group',
                'people' => $peopleWithoutService,
                'viewAllUrl' => $listUrls['without_service'] ?? null,
            ])
        </div>

    </div>
</x-filament-panels::page>
