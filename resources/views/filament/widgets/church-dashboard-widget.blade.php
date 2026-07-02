<div class="space-y-6">
    <div class="overflow-hidden rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 to-white p-6 shadow-sm dark:border-primary-900 dark:from-gray-900 dark:to-gray-950">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                    Quezon Province Activities
                </p>

                <h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    Churches of Quezon Database Dashboard
                </h1>

                <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                    Manage people, households, family relationships, shepherding assignments, and follow-up needs.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ \App\Filament\Resources\People\PersonResource::getUrl('create') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                >
                    Add Person
                </a>

                <a
                    href="{{ \App\Filament\Pages\ShepherdingDashboard::getUrl() }}"
                    class="inline-flex items-center justify-center rounded-xl border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-50 dark:border-primary-900 dark:bg-gray-900 dark:text-primary-200 dark:hover:bg-primary-950"
                >
                    Shepherding Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            @php
                $tone = match ($stat['key']) {
                    'people' => 'border-primary-200 bg-primary-50 text-primary-800 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200',
                    'households' => 'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-900 dark:bg-purple-950 dark:text-purple-200',
                    'active' => 'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200',
                    'new_ones' => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200',
                    'gospel_friends' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',
                    'no_shepherd', 'no_group' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200',
                    default => 'border-gray-200 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200',
                };
            @endphp

            <a
                href="{{ $stat['url'] }}"
                class="rounded-2xl border p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $tone }}"
            >
                <p class="text-sm font-semibold opacity-75">
                    {{ $stat['label'] }}
                </p>

                <p class="mt-3 text-4xl font-bold">
                    {{ $stat['count'] }}
                </p>

                <p class="mt-3 text-xs font-semibold opacity-75">
                    View records →
                </p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Quick Actions
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Common places you will use while encoding and checking the database.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @foreach ($quickLinks as $link)
                    <a
                        href="{{ $link['url'] }}"
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-5 transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-950 dark:hover:border-primary-700 dark:hover:bg-gray-800"
                    >
                        <p class="font-bold text-gray-900 dark:text-white">
                            {{ $link['label'] }}
                        </p>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $link['description'] }}
                        </p>

                        <p class="mt-4 text-xs font-semibold text-primary-600 dark:text-primary-400">
                            Open →
                        </p>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                Care Alerts
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Items that may need attention.
            </p>

            <div class="mt-5 space-y-3">
                @foreach ($careAlerts as $alert)
                    <a
                        href="{{ $alert['url'] }}"
                        class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-950 dark:hover:border-primary-700 dark:hover:bg-gray-800"
                    >
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            {{ $alert['label'] }}
                        </span>

                        <span class="rounded-full bg-primary-600 px-2.5 py-1 text-xs font-bold text-white">
                            {{ $alert['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Recently Added People
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Latest encoded records.
                </p>
            </div>

            <a
                href="{{ \App\Filament\Resources\People\PersonResource::getUrl('index') }}"
                class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400"
            >
                View all →
            </a>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($recentPeople as $person)
                <a
                    href="{{ $person['url'] }}"
                    class="rounded-2xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-950 dark:hover:border-primary-700 dark:hover:bg-gray-800"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">
                            {{ $person['initials'] }}
                        </div>

                        <div class="min-w-0">
                            <p class="truncate font-bold text-gray-900 dark:text-white">
                                {{ $person['name'] }}
                            </p>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $person['status'] }} • {{ $person['category'] }} • {{ $person['locality'] }}
                            </p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No people encoded yet.
                </div>
            @endforelse
        </div>
    </div>
</div>
