<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header / Locality Filter --}}
        <div
            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-4 md:flex-row
                       md:items-end md:justify-between"
            >
                <div>
                    <h2
                        class="text-xl font-bold text-gray-900
                               dark:text-white"
                    >
                        Shepherding Overview
                    </h2>

                    <p
                        class="mt-1 text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Shepherding activity, contact follow-up,
                        ministry use, and people needing care.
                    </p>
                </div>

                <div class="w-full md:max-w-sm">
                    <label
                        for="locality"
                        class="mb-2 block text-sm font-medium
                               text-gray-700 dark:text-gray-200"
                    >
                        Locality
                    </label>

                    <select
                        id="locality"
                        wire:model.live="locality"
                        class="block w-full rounded-lg
                               border-gray-300 shadow-sm
                               focus:border-primary-500
                               focus:ring-primary-500
                               dark:border-gray-700
                               dark:bg-gray-800
                               dark:text-white"
                    >
                        <option value="">
                            All Localities
                        </option>

                        @foreach ($localities as $localityOption)
                            <option value="{{ $localityOption }}">
                                {{ $localityOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div
                class="mt-4 rounded-xl bg-gray-50 px-4 py-3
                       text-sm text-gray-600
                       dark:bg-gray-800 dark:text-gray-300"
            >
                Showing:
                <span
                    class="font-semibold text-gray-900
                           dark:text-white"
                >
                    {{ filled($locality) ? $locality : 'All Localities' }}
                </span>

                · {{ now()->format('F Y') }}
            </div>
        </div>

        {{-- Operational Overview --}}
        <div>
            <div class="mb-3">
                <h3
                    class="text-lg font-semibold text-gray-900
                           dark:text-white"
                >
                    Shepherding Operations
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Current Shepherding workload and contact databases.
                </p>
            </div>

            <div
                class="grid gap-4 sm:grid-cols-2
                       lg:grid-cols-4"
            >
                @foreach ($operationalStats as $key => $stat)
                    @php
                        $tone = match ($key) {
                            'records_this_month' =>
                                'border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-300',

                            'follow_up_outcomes' =>
                                'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',

                            'gospel_contacts',
                            'unlinked_gospel' =>
                                'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900 dark:bg-orange-950 dark:text-orange-300',

                            'campus_contacts',
                            'unlinked_campus' =>
                                'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',

                            default =>
                                'border-gray-200 bg-white text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200',
                        };

                        $tag = filled($stat['url'])
                            ? 'a'
                            : 'div';
                    @endphp

                    <{{ $tag }}
                        @if (filled($stat['url']))
                            href="{{ $stat['url'] }}"
                        @endif
                        class="rounded-2xl border p-5 shadow-sm
                               {{ $tone }}
                               @if (filled($stat['url']))
                                   transition hover:scale-[1.01]
                                   hover:shadow-md
                               @endif"
                    >
                        <p class="text-sm font-medium opacity-80">
                            {{ $stat['label'] }}
                        </p>

                        <p class="mt-3 text-4xl font-bold">
                            {{ $stat['count'] }}
                        </p>

                        @if (filled($stat['url']))
                            <p class="mt-3 text-xs font-medium opacity-80">
                                Open →
                            </p>
                        @endif
                    </{{ $tag }}>
                @endforeach
            </div>
        </div>

        {{-- Recent Shepherding + Activity Summary --}}
        <div class="grid gap-6 xl:grid-cols-3">

            <div
                class="rounded-2xl border border-gray-200
                       bg-white p-5 shadow-sm
                       dark:border-gray-700 dark:bg-gray-900
                       xl:col-span-2"
            >
                <div
                    class="flex items-center justify-between gap-4"
                >
                    <div>
                        <h3
                            class="text-lg font-semibold
                                   text-gray-900 dark:text-white"
                        >
                            Recent Shepherding
                        </h3>

                        <p
                            class="text-sm text-gray-500
                                   dark:text-gray-400"
                        >
                            Latest Shepherding Records.
                        </p>
                    </div>

                    <a
                        href="{{ \App\Filament\Pages\ShepherdingHistory::getUrl() }}"
                        class="text-sm font-medium text-primary-600
                               hover:text-primary-500
                               dark:text-primary-400"
                    >
                        View history →
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($recentShepherding as $record)
                        @php
                            $targets = collect(
                                $record['targets']
                            )
                                ->flatten()
                                ->filter()
                                ->values();

                            $codes = collect(
                                $record['shepherding_codes']
                            )
                                ->filter()
                                ->values();
                        @endphp

                        <div
                            class="rounded-xl border border-gray-200
                                   p-4 dark:border-gray-700"
                        >
                            <div
                                class="flex flex-col gap-3
                                       md:flex-row
                                       md:items-start
                                       md:justify-between"
                            >
                                <div>
                                    <p
                                        class="font-semibold
                                               text-gray-900
                                               dark:text-white"
                                    >
                                        {{ $targets->isNotEmpty()
                                            ? $targets->join(', ')
                                            : 'No target recorded' }}
                                    </p>

                                    <p
                                        class="mt-1 text-sm
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        {{ $record['date'] }}

                                        @if (filled($record['time']))
                                            · {{ $record['time'] }}
                                        @endif

                                        ·
                                        {{ $record['locality']
                                            ?? 'No Locality' }}
                                    </p>
                                </div>

                                <span
                                    class="inline-flex self-start
                                           rounded-full bg-gray-100
                                           px-3 py-1 text-xs
                                           font-medium text-gray-700
                                           dark:bg-gray-800
                                           dark:text-gray-300"
                                >
                                    {{ $record['outcome']
                                        ?? 'No outcome' }}
                                </span>
                            </div>

                            <div class="mt-3">
                                <a
                                    href="{{ $record['url'] }}"
                                    class="text-xs font-semibold
                                           text-primary-600
                                           hover:text-primary-500
                                           dark:text-primary-400"
                                >
                                    Open record →
                                </a>
                            </div>

                            @if ($codes->isNotEmpty())
                                <div
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    @foreach ($codes as $code)
                                        <span
                                            class="rounded-lg
                                                   bg-primary-50
                                                   px-2.5 py-1
                                                   text-xs font-semibold
                                                   text-primary-700
                                                   dark:bg-primary-950
                                                   dark:text-primary-300"
                                        >
                                            {{ $code }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed
                                   border-gray-300 px-5 py-10
                                   text-center
                                   dark:border-gray-700"
                        >
                            <p
                                class="font-medium text-gray-700
                                       dark:text-gray-300"
                            >
                                No Shepherding Records yet.
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-500
                                       dark:text-gray-400"
                            >
                                Recorded visits, prayer, fellowship,
                                ministry use, and other activities
                                will appear here.
                            </p>

                            <a
                                href="{{ \App\Filament\Pages\ShepherdingHistory::getUrl() }}"
                                class="mt-4 inline-flex text-sm
                                       font-medium text-primary-600
                                       hover:text-primary-500
                                       dark:text-primary-400"
                            >
                                Open Shepherding History →
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <div
                class="rounded-2xl border border-gray-200
                       bg-white p-5 shadow-sm
                       dark:border-gray-700 dark:bg-gray-900"
            >
                <h3
                    class="text-lg font-semibold text-gray-900
                           dark:text-white"
                >
                    Activities This Month
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Recorded Shepherding activity codes.
                </p>

                <div class="mt-4 space-y-2">
                    @foreach ($activitySummary as $activity)
                        <a
                            href="{{ $activity['url'] }}"
                            class="flex items-center justify-between
                                   rounded-xl bg-gray-50 px-3 py-2
                                   transition hover:bg-gray-100
                                   dark:bg-gray-800
                                   dark:hover:bg-gray-700"
                        >
                            <div class="min-w-0">
                                <span
                                    class="font-semibold
                                           text-primary-700
                                           dark:text-primary-300"
                                >
                                    {{ $activity['code'] }}
                                </span>

                                <span
                                    class="ml-2 text-sm
                                           text-gray-600
                                           dark:text-gray-300"
                                >
                                    {{ $activity['name'] }}
                                </span>
                            </div>

                            <div
                                class="ml-3 flex items-center gap-2"
                            >
                                <span
                                    class="font-bold text-gray-900
                                           dark:text-white"
                                >
                                    {{ $activity['count'] }}
                                </span>

                                <span
                                    class="text-xs text-gray-400"
                                >
                                    →
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Needs Follow-up --}}
        <div
            class="rounded-2xl border border-amber-200
                   bg-white p-5 shadow-sm
                   dark:border-amber-900 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-3
                       sm:flex-row sm:items-start
                       sm:justify-between"
            >
                <div>
                    <h3
                        class="text-lg font-semibold text-gray-900
                               dark:text-white"
                    >
                        Needs Follow-up
                    </h3>

                    <p
                        class="text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Out / Unavailable, Reschedule, and Declined
                        Shepherding Records during
                        {{ now()->format('F Y') }}.
                    </p>
                </div>

                <a
                    href="{{ $operationalStats['follow_up_outcomes']['url'] }}"
                    class="shrink-0 text-sm font-medium
                           text-amber-700 hover:text-amber-600
                           dark:text-amber-300
                           dark:hover:text-amber-200"
                >
                    View follow-up records →
                </a>
            </div>

            {{-- Outcome Summary --}}
            <div
                class="mt-4 grid gap-3
                       sm:grid-cols-3"
            >
                @foreach ($followUpSummary as $key => $summary)
                    @php
                        $tone = match ($key) {
                            'unavailable' =>
                                'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',

                            'reschedule' =>
                                'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200',

                            'declined' =>
                                'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200',

                            default =>
                                'border-gray-200 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200',
                        };
                    @endphp

                    <div
                        class="rounded-xl border px-4 py-3
                               {{ $tone }}"
                    >
                        <p class="text-xs font-medium opacity-80">
                            {{ $summary['label'] }}
                        </p>

                        <p class="mt-1 text-2xl font-bold">
                            {{ $summary['count'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Follow-up Records --}}
            <div class="mt-5 space-y-3">
                @forelse ($followUpItems as $record)
                    @php
                        $targetNames = collect(
                            $record['targets']
                        )
                            ->flatten()
                            ->filter()
                            ->values();

                        $codes = collect(
                            $record['shepherding_codes']
                        )
                            ->filter()
                            ->values();
                    @endphp

                    <div
                        class="rounded-xl border border-gray-200
                               p-4 dark:border-gray-700"
                    >
                        <div
                            class="flex flex-col gap-3
                                   md:flex-row md:items-start
                                   md:justify-between"
                        >
                            <div class="min-w-0">
                                <p
                                    class="font-semibold text-gray-900
                                           dark:text-white"
                                >
                                    {{ $targetNames->isNotEmpty()
                                        ? $targetNames->join(', ')
                                        : 'No target recorded' }}
                                </p>

                                <p
                                    class="mt-1 text-sm text-gray-500
                                           dark:text-gray-400"
                                >
                                    {{ $record['date'] }}

                                    @if (filled($record['time']))
                                        · {{ $record['time'] }}
                                    @endif

                                    ·
                                    {{ $record['locality']
                                        ?? 'No Locality' }}
                                </p>
                            </div>

                            <span
                                class="inline-flex self-start
                                       rounded-full bg-amber-100
                                       px-3 py-1 text-xs font-semibold
                                       text-amber-800
                                       dark:bg-amber-950
                                       dark:text-amber-200"
                            >
                                {{ $record['outcome'] }}
                            </span>
                        </div>

                        @if ($codes->isNotEmpty())
                            <div
                                class="mt-3 flex flex-wrap gap-2"
                            >
                                @foreach ($codes as $code)
                                    <span
                                        class="rounded-lg bg-gray-100
                                               px-2.5 py-1 text-xs
                                               font-semibold text-gray-700
                                               dark:bg-gray-800
                                               dark:text-gray-300"
                                    >
                                        {{ $code }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if (filled($record['notes']))
                            <p
                                class="mt-3 text-sm text-gray-600
                                       dark:text-gray-300"
                            >
                                {{ \Illuminate\Support\Str::limit(
                                    $record['notes'],
                                    180
                                ) }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed
                               border-gray-300 px-5 py-8
                               text-center
                               dark:border-gray-700"
                    >
                        <p
                            class="font-medium text-gray-700
                                   dark:text-gray-300"
                        >
                            No follow-up outcomes this month.
                        </p>

                        <p
                            class="mt-1 text-sm text-gray-500
                                   dark:text-gray-400"
                        >
                            Out / Unavailable, Reschedule, and
                            Declined records will appear here.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Locality Activity --}}
        <div
            class="rounded-2xl border border-gray-200
                   bg-white p-5 shadow-sm
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <h3
                class="text-lg font-semibold text-gray-900
                       dark:text-white"
            >
                Shepherding by Locality
            </h3>

            <p
                class="text-sm text-gray-500
                       dark:text-gray-400"
            >
                Shepherding Records during {{ now()->format('F Y') }}.
            </p>

            <div class="mt-4">
                @forelse ($localitySummary as $row)
                    @if (filled($row['url']))
                        <a
                            href="{{ $row['url'] }}"
                            class="flex items-center justify-between
                                   border-b border-gray-100 py-3
                                   transition hover:bg-gray-50
                                   last:border-b-0
                                   dark:border-gray-800
                                   dark:hover:bg-gray-800"
                        >
                            <span
                                class="font-medium text-gray-800
                                       dark:text-gray-200"
                            >
                                {{ $row['locality'] }}
                            </span>

                            <div
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="rounded-full bg-gray-100
                                           px-3 py-1 text-sm font-semibold
                                           text-gray-700
                                           dark:bg-gray-800
                                           dark:text-gray-300"
                                >
                                    {{ $row['count'] }}
                                </span>

                                <span class="text-xs text-gray-400">
                                    →
                                </span>
                            </div>
                        </a>
                    @else
                        <div
                            class="flex items-center justify-between
                                   border-b border-gray-100 py-3
                                   last:border-b-0
                                   dark:border-gray-800"
                        >
                            <span
                                class="font-medium text-gray-800
                                       dark:text-gray-200"
                            >
                                {{ $row['locality'] }}
                            </span>

                            <span
                                class="rounded-full bg-gray-100
                                       px-3 py-1 text-sm font-semibold
                                       text-gray-700
                                       dark:bg-gray-800
                                       dark:text-gray-300"
                            >
                                {{ $row['count'] }}
                            </span>
                        </div>
                    @endif
                @empty
                    <p
                        class="py-6 text-center text-sm
                               text-gray-500 dark:text-gray-400"
                    >
                        No Shepherding activity recorded this month.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- People Shepherding Needs --}}
        <div>
            <div class="mb-3">
                <h3
                    class="text-lg font-semibold text-gray-900
                           dark:text-white"
                >
                    People Shepherding Needs
                </h3>

                <p
                    class="text-sm text-gray-500
                           dark:text-gray-400"
                >
                    Church status, shepherd assignments,
                    and Shepherding Group coverage.
                </p>
            </div>

            <div
                class="grid gap-4 sm:grid-cols-2
                       lg:grid-cols-3 xl:grid-cols-6"
            >
                @foreach ($peopleStats as $key => $stat)
                    @php
                        $tone = match ($key) {
                            'active' =>
                                'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300',

                            'new_ones' =>
                                'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',

                            'gospel_friends' =>
                                'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',

                            'without_shepherd',
                            'without_service' =>
                                'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',

                            default =>
                                'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
                        };
                    @endphp

                    <a
                        href="{{ $stat['url'] }}"
                        class="rounded-2xl border p-4 shadow-sm
                               transition hover:scale-[1.01]
                               hover:shadow-md {{ $tone }}"
                    >
                        <p class="text-xs font-medium opacity-80">
                            {{ $stat['label'] }}
                        </p>

                        <p class="mt-2 text-3xl font-bold">
                            {{ $stat['count'] }}
                        </p>
                    </a>
                @endforeach
            </div>
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
