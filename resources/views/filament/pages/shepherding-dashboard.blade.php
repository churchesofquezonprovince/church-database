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
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total People</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Active</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['active'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">New Ones</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['new_ones'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Gospel Friends</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['gospel_friends'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Dormant</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['dormant'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">No Shepherd</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['without_shepherd'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">No Service</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['without_service'] ?? 0 }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @include('filament.pages.partials.shepherding-list', [
                'title' => 'People Without Shepherd',
                'people' => $peopleWithoutShepherd,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'Dormant People',
                'people' => $dormantPeople,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'New Ones',
                'people' => $newOnes,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'Gospel Friends',
                'people' => $gospelFriends,
            ])

            @include('filament.pages.partials.shepherding-list', [
                'title' => 'People Without Shepherding Service',
                'people' => $peopleWithoutService,
            ])
        </div>

    </div>
</x-filament-panels::page>
