<x-filament-panels::page>
    @php
        $matches = $this->hymnMatches();
        $selectedHymn = $this->selectedHymn();
        $linkedSources = $this->linkedSources();
    @endphp

    <div class="space-y-6">
        <div
            class="rounded-xl border
                   border-gray-200
                   bg-white p-6
                   shadow-sm
                   dark:border-white/10
                   dark:bg-gray-900"
        >
            <div class="mb-5">
                <h2
                    class="text-lg font-bold
                           text-gray-950
                           dark:text-white"
                >
                    Link Hymnal.net
                </h2>

                <p
                    class="mt-1 text-sm
                           text-gray-500
                           dark:text-gray-400"
                >
                    Link a Hymnal.net hymn page to an
                    existing canonical Hymn in CoQP.
                    This does not create a duplicate Hymn.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label
                        class="mb-2 block text-sm
                               font-semibold
                               text-gray-700
                               dark:text-gray-200"
                    >
                        Find Canonical Hymn
                    </label>

                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search title, lyrics, hymn number..."
                        class="w-full rounded-lg
                               border-gray-300
                               dark:border-white/10
                               dark:bg-gray-950
                               dark:text-white"
                    >

                    @if (trim($search) !== '')
                        <div
                            class="mt-3 max-h-80
                                   space-y-2
                                   overflow-y-auto"
                        >
                            @forelse ($matches as $hymn)
                                <button
                                    type="button"
                                    wire:click="selectHymn({{ $hymn->id }})"
                                    class="block w-full
                                           rounded-lg border
                                           px-4 py-3
                                           text-left
                                           transition
                                           {{ $selectedHymnId === $hymn->id
                                               ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-500 dark:bg-emerald-500/10'
                                               : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-white/10 dark:bg-gray-950 dark:hover:bg-white/5'
                                           }}"
                                >
                                    <div
                                        class="font-bold
                                               text-gray-950
                                               dark:text-white"
                                    >
                                        {{ $hymn->title }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        {{
                                            $hymn->language
                                                ?: 'Language not specified'
                                        }}

                                        @if (
                                            $hymn
                                                ->bookEntries
                                                ->isNotEmpty()
                                        )
                                            ·
                                            {{
                                                $hymn
                                                    ->bookEntries
                                                    ->map(
                                                        fn ($entry) =>
                                                            ($entry
                                                                ->hymnBook
                                                                ?->name
                                                                ?? 'Book')
                                                            . ' '
                                                            . $entry->number
                                                    )
                                                    ->join(', ')
                                            }}
                                        @endif
                                    </div>
                                </button>
                            @empty
                                <div
                                    class="rounded-lg border
                                           border-gray-200
                                           p-4 text-sm
                                           text-gray-500
                                           dark:border-white/10
                                           dark:text-gray-400"
                                >
                                    No matching canonical Hymns.
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>

                <div>
                    <label
                        class="mb-2 block text-sm
                               font-semibold
                               text-gray-700
                               dark:text-gray-200"
                    >
                        Hymnal.net Hymn URL
                    </label>

                    <input
                        type="url"
                        wire:model="hymnalUrl"
                        placeholder="https://www.hymnal.net/en/hymn/..."
                        class="w-full rounded-lg
                               border-gray-300
                               dark:border-white/10
                               dark:bg-gray-950
                               dark:text-white"
                    >

                    @if ($selectedHymn)
                        <div
                            class="mt-4 rounded-lg
                                   border border-emerald-200
                                   bg-emerald-50 p-4"
                            style="color: #064e3b !important;"
                        >
                            <div
                                class="text-xs font-bold
                                       uppercase tracking-wide"
                            >
                                Selected Canonical Hymn
                            </div>

                            <div
                                class="mt-1 text-base
                                       font-bold"
                            >
                                {{ $selectedHymn->title }}
                            </div>

                            <div
                                class="mt-2 text-xs"
                            >
                                Existing sources:
                                {{ $selectedHymn->sources->count() }}
                            </div>
                        </div>
                    @else
                        <div
                            class="mt-4 rounded-lg
                                   border border-amber-200
                                   bg-amber-50 p-4"
                            style="color: #78350f !important;"
                        >
                            Select the canonical Hymn first.
                        </div>
                    @endif

                    @error('selectedHymnId')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('hymnalUrl')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    <button
                        type="button"
                        wire:click="attachHymnalNetSource"
                        wire:loading.attr="disabled"
                        class="mt-4 rounded-lg
                               bg-primary-600
                               px-4 py-2
                               text-sm font-bold
                               text-white
                               hover:bg-primary-500
                               disabled:opacity-50"
                    >
                        Link Hymnal.net Source
                    </button>
                </div>
            </div>
        </div>

        <div
            class="rounded-xl border
                   border-gray-200
                   bg-white p-6
                   shadow-sm
                   dark:border-white/10
                   dark:bg-gray-900"
        >
            <div
                class="mb-5 flex flex-wrap
                       items-end justify-between
                       gap-4"
            >
                <div>
                    <h2
                        class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        Linked Hymnal.net Sources
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        {{
                            $linkedSources->count()
                        }}
                        matching link(s)
                    </p>
                </div>

                <div class="w-full sm:w-80">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="linkedSearch"
                        placeholder="Search linked Hymns..."
                        class="w-full rounded-lg
                               border-gray-300
                               dark:border-white/10
                               dark:bg-gray-950
                               dark:text-white"
                    >
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr
                            class="border-b border-gray-200
                                   dark:border-white/10"
                        >
                            <th class="px-4 py-3">
                                Canonical Hymn
                            </th>
                            <th class="px-4 py-3">
                                Hymnal.net
                            </th>
                            <th class="px-4 py-3 text-right">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($linkedSources as $source)
                            <tr
                                class="border-b
                                       border-gray-100
                                       dark:border-white/5"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div
                                        class="font-bold
                                               text-gray-950
                                               dark:text-white"
                                    >
                                        {{
                                            $source
                                                ->hymn
                                                ?->title
                                            ?? 'Missing Hymn'
                                        }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        {{
                                            $source->external_id
                                                ?: 'No external ID'
                                        }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    @if ($source->source_url)
                                        <a
                                            href="{{ $source->source_url }}"
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
                                            Hymnal.net
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td
                                    class="px-4 py-3
                                           text-right
                                           align-top"
                                >
                                    <button
                                        type="button"
                                        wire:click="deleteHymnalNetSource({{ $source->id }})"
                                        wire:confirm="Remove this Hymnal.net source link? The canonical Hymn will remain."
                                        style="color: #450a0a !important;"
                                        class="rounded-lg
                                               border border-red-300
                                               bg-red-50
                                               px-3 py-1.5
                                               text-xs
                                               font-bold
                                               hover:bg-red-100"
                                    >
                                        Remove Link
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="3"
                                    class="px-4 py-8
                                           text-center
                                           text-gray-500"
                                >
                                    No Hymnal.net links found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
