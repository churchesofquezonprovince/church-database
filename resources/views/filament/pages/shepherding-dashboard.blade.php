<x-filament-panels::page>
    <div class="space-y-6">

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
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

            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Showing data for:
                <span class="font-medium">
                    {{ filled($locality) ? $locality : 'All Localities' }}
                </span>
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <a
                    href="{{ $stat['url'] }}"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-400 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-600 dark:hover:bg-gray-800"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $stat['label'] }}
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $stat['count'] }}
                    </p>

                    <p class="mt-2 text-xs text-primary-600 dark:text-primary-400">
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
