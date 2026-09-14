<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $languages = $this->languages();
        $books = $this->books();
        $hymns = $this->hymns();
    @endphp

    <div class="space-y-6">
        <div
            class="rounded-2xl border border-primary-200
                   bg-primary-50 p-6 shadow-sm
                   dark:border-primary-900
                   dark:bg-primary-950"
        >
            <div
                class="flex flex-col gap-4
                       lg:flex-row
                       lg:items-start
                       lg:justify-between"
            >
                <div>
                    <p
                        class="text-sm font-semibold uppercase
                               tracking-wide text-primary-600
                               dark:text-primary-300"
                    >
                        Administration
                    </p>

                    <h2
                        class="mt-2 text-3xl font-bold
                               text-gray-900 dark:text-white"
                    >
                        Hymns Setup
                    </h2>

                    <p
                        class="mt-2 max-w-3xl text-sm
                               text-gray-600 dark:text-gray-300"
                    >
                        Browse the synchronized multilingual
                        Songbase hymn catalog used by CoQP.
                    </p>

                    <p
                        class="mt-2 text-xs text-gray-500
                               dark:text-gray-400"
                    >
                        Last synchronized:
                        {{ $summary['last_synced_at'] ?? 'Never' }}
                    </p>
                </div>

                <x-filament::button
                    wire:click="syncSongbase"
                    wire:confirm="Synchronize the complete Songbase hymn catalog now?"
                    icon="heroicon-m-arrow-path"
                >
                    Sync Songbase
                </x-filament::button>
            </div>
        </div>

        <div
            class="grid gap-4 sm:grid-cols-2
                   lg:grid-cols-3 xl:grid-cols-6"
        >
            @foreach ([
                'Songs' => $summary['songs'],
                'Active' => $summary['active'],
                'Languages' => $summary['languages'],
                'Books' => $summary['books'],
                'Book Entries' => $summary['book_entries'],
                'Pending Requests' => $summary['pending_requests'],
            ] as $label => $value)
                <div
                    class="rounded-2xl border border-gray-200
                           bg-white p-5 shadow-sm
                           dark:border-gray-700
                           dark:bg-gray-900"
                >
                    <div
                        class="text-xs font-bold uppercase
                               text-gray-500 dark:text-gray-400"
                    >
                        {{ $label }}
                    </div>

                    <div
                        class="mt-2 text-2xl font-bold
                               text-gray-950 dark:text-white"
                    >
                        {{ number_format($value) }}
                    </div>
                </div>
            @endforeach
        </div>

        @include(
            'filament.pages.partials.hymn-addition-requests'
        )

        @include(
            'filament.pages.partials.hymn-reviewed-requests'
        )

        <div
            class="rounded-2xl border border-gray-200
                   bg-white p-6 shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <h3
                class="text-lg font-bold
                       text-gray-950 dark:text-white"
            >
                Hymn Books
            </h3>

            <div
                class="mt-4 grid gap-3
                       md:grid-cols-2 xl:grid-cols-3"
            >
                @foreach ($books as $book)
                    <div
                        class="rounded-xl border border-gray-200
                               p-4 dark:border-gray-700"
                    >
                        <div
                            class="font-semibold text-gray-950
                                   dark:text-white"
                        >
                            {{ $book->name }}
                        </div>

                        <div
                            class="mt-1 text-xs text-gray-500
                                   dark:text-gray-400"
                        >
                            {{ $book->language ?? 'Language not detected' }}
                            ·
                            {{ number_format($book->entries_count) }}
                            entries
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div
            id="hymn-catalog"
            class="rounded-2xl border border-gray-200
                   bg-white p-6 shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div
                class="grid gap-4 md:grid-cols-[1fr_260px]"
            >
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search title, lyrics, Songbase ID, or hymn number..."
                    class="block w-full rounded-xl
                           border border-gray-300 bg-white
                           px-4 py-3 text-sm text-gray-900
                           shadow-sm dark:border-gray-700
                           dark:bg-gray-950 dark:text-gray-100"
                >

                <select
                    wire:model.live="language"
                    class="block w-full rounded-xl
                           border-gray-300 bg-white text-sm
                           text-gray-950 shadow-sm
                           dark:border-gray-700
                           dark:bg-gray-950 dark:text-white"
                >
                    <option value="">
                        All Languages
                    </option>

                    @foreach ($languages as $item)
                        <option
                            value="{{ $item->language }}"
                        >
                            {{ $item->language }}
                            ({{ number_format($item->total) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <p
                class="mt-3 text-xs text-gray-500
                       dark:text-gray-400"
            >
                Showing up to 100 matching active hymns.
                All Languages is the default filter.
            </p>

            <div
                class="mt-5 overflow-x-auto
                       rounded-xl border border-gray-200
                       dark:border-gray-700"
            >
                <table
                    class="min-w-full divide-y divide-gray-200
                           text-sm dark:divide-gray-700"
                >
                    <colgroup>
                        <col style="width: 18%;">
                        <col style="width: 9%;">
                        <col style="width: 13%;">
                        <col style="width: auto;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead
                        class="bg-gray-50 dark:bg-gray-800"
                    >
                        <tr>
                            <th class="px-4 py-3 text-left">
                                Title
                            </th>

                            <th class="px-4 py-3 text-left">
                                Language
                            </th>

                            <th class="px-4 py-3 text-left">
                                Book / Number
                            </th>

                            <th class="px-4 py-3 text-left">
                                Lyrics
                            </th>

                            <th class="px-4 py-3 text-left">
                                Source
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-200
                               dark:divide-gray-700"
                    >
                        @forelse ($hymns as $hymn)
                            <tr>
                                <td
                                    class="px-4 py-3
                                           text-gray-950
                                           dark:text-white"
                                >
                                    <div class="font-semibold">
                                        {{ $hymn->title }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Songbase ID:
                                        {{ $hymn->source_id }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ $hymn->language ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    @forelse (
                                        $hymn->bookEntries
                                        as $entry
                                    )
                                        <div>
                                            {{ $entry->hymnBook?->name }}
                                            #{{ $entry->number }}
                                        </div>
                                    @empty
                                        <span
                                            class="text-gray-400"
                                        >
                                            Not in a numbered book
                                        </span>
                                    @endforelse
                                </td>

                                <td class="px-4 py-3 align-top">
                                    @if (filled($hymn->lyrics))
                                        <details
                                            class="min-w-[280px]
                                                   max-w-xl"
                                        >
                                            <summary
                                                class="cursor-pointer
                                                       font-semibold
                                                       text-primary-600
                                                       hover:underline
                                                       dark:text-primary-400"
                                            >
                                                View Lyrics
                                            </summary>

                                            <pre
                                                class="mt-3 max-h-96
                                                       overflow-auto
                                                       whitespace-pre-wrap
                                                       rounded-lg
                                                       bg-gray-50 p-4
                                                       font-sans text-sm
                                                       leading-6
                                                       text-gray-800
                                                       dark:bg-gray-950
                                                       dark:text-gray-200"
                                            >{{ $hymn->lyrics }}</pre>
                                        </details>
                                    @else
                                        <span
                                            class="text-gray-400"
                                        >
                                            No lyrics available
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top">
                                    @php
                                        $sources =
                                            $hymn
                                                ->sources
                                                ->sortBy(
                                                    fn ($source) =>
                                                        match (
                                                            $source->provider
                                                        ) {
                                                            'songbase' => 10,
                                                            'soundcloud' => 20,
                                                            'youtube' => 30,
                                                            'hymnal_net' => 40,
                                                            default => 90,
                                                        }
                                                )
                                                ->values();

                                        $providerTotals =
                                            $sources
                                                ->groupBy(
                                                    'provider'
                                                )
                                                ->map
                                                ->count();

                                        $providerSeen = [];
                                    @endphp

                                    @forelse ($sources as $source)
                                        @php
                                            $provider =
                                                $source->provider
                                                ?: 'other';

                                            $providerSeen[
                                                $provider
                                            ] =
                                                (
                                                    $providerSeen[
                                                        $provider
                                                    ]
                                                    ?? 0
                                                )
                                                + 1;

                                            $label =
                                                $source->label
                                                ?: match ($provider) {
                                                    'songbase' =>
                                                        'Songbase',

                                                    'soundcloud' =>
                                                        'SoundCloud',

                                                    'youtube' =>
                                                        'YouTube',

                                                    'hymnal_net' =>
                                                        'Hymnal.net',

                                                    default =>
                                                        'Other Source',
                                                };

                                            if (
                                                (
                                                    $providerTotals[
                                                        $provider
                                                    ]
                                                    ?? 0
                                                ) > 1
                                            ) {
                                                $label .=
                                                    ' '
                                                    . $providerSeen[
                                                        $provider
                                                    ];
                                            }
                                        @endphp

                                        <div
                                            class="mb-2 flex
                                                   min-w-[190px]
                                                   items-center
                                                   gap-2
                                                   last:mb-0"
                                        >
                                            @if ($source->source_url)
                                                <a
                                                    href="{{ $source->source_url }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    title="Open {{ $label }}"
                                                    style="color: #082f49 !important;"
                                                    class="inline-flex
                                                           rounded-full
                                                           bg-sky-100
                                                           px-2.5 py-1
                                                           text-xs
                                                           font-bold
                                                           ring-1
                                                           ring-sky-200
                                                           hover:underline"
                                                >
                                                    {{ $label }}
                                                </a>
                                            @else
                                                <span
                                                    title="No source link available"
                                                    style="color: #4b5563 !important;"
                                                    class="inline-flex
                                                           rounded-full
                                                           bg-gray-100
                                                           px-2.5 py-1
                                                           text-xs
                                                           font-bold
                                                           ring-1
                                                           ring-gray-200"
                                                >
                                                    {{ $label }}
                                                </span>
                                            @endif
                                        </div>
                                    @empty
                                        @if ($hymn->source_url)
                                            <a
                                                href="{{ $hymn->source_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                style="color: #082f49 !important;"
                                                class="inline-flex
                                                       rounded-full
                                                       bg-sky-100
                                                       px-2.5 py-1
                                                       text-xs
                                                       font-bold
                                                       ring-1
                                                       ring-sky-200
                                                       hover:underline"
                                            >
                                                {{
                                                    $hymn->source === 'songbase'
                                                        ? 'Songbase'
                                                        : 'Source'
                                                }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-4 py-8 text-center
                                           text-gray-500"
                                >
                                    No matching hymns found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
