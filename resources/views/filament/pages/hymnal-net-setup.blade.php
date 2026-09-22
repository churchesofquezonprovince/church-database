<x-filament-panels::page>
    @php
        $matches = $this->hymnMatches();
        $selectedHymn = $this->selectedHymn();
        $linkedSources = $this->linkedSources();
        $summary = $this->catalogSummary();
        $collectionSummaries =
            $this->collectionSummaries();

        $linkedSectionFilter =
            $this->linkedSectionFilter;

        $linkedSectionLabel =
            $linkedSectionFilter !== ''
                ? (
                    \App\Support\HymnalNetCollectionCatalog
                        ::sections()[
                            $linkedSectionFilter
                        ]['label']
                    ?? null
                )
                : null;
    @endphp

    <style>
        /*
         * These semantic controls intentionally remain
         * pale in dark mode, so their text must remain
         * dark too.
         */
        .hymnal-light-emerald,
        .hymnal-light-emerald * {
            color: #064e3b !important;
        }

        .hymnal-light-amber,
        .hymnal-light-amber * {
            color: #78350f !important;
        }

        .hymnal-light-sky,
        .hymnal-light-sky * {
            color: #082f49 !important;
        }

        .hymnal-light-red,
        .hymnal-light-red * {
            color: #450a0a !important;
        }
    </style>

    <div
        class="space-y-6"
        x-on:scroll-to-linked-hymnal-sources.window="
            $nextTick(() => {
                setTimeout(() => {
                    document
                        .getElementById(
                            'linked-hymnal-sources'
                        )
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start',
                        });
                }, 50);
            })
        "
    >
        <div
            class="rounded-2xl border
                   border-primary-200
                   bg-primary-50 p-6
                   shadow-sm
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
                        class="text-sm font-semibold
                               uppercase tracking-wide
                               text-primary-600
                           dark:text-primary-300"
                >
                    Hymn Source Provider
                </p>

                <h2
                    class="mt-2 text-3xl font-bold
                           text-gray-950
                           dark:text-white"
                >
                    Hymnal.net Setup
                </h2>

                <p
                    class="mt-2 max-w-3xl
                           text-sm text-gray-600
                           dark:text-gray-300"
                >
                    Discover, synchronize, inspect,
                    and link all supported Hymnal.net
                    collections. Hymnal.net remains a
                    provider attached to canonical
                    Hymns; it is not the canonical
                    identity.
                </p>

                <p
                    class="mt-2 text-xs
                           text-gray-500
                           dark:text-gray-400"
                >
                    Classic sequential sync remains
                    safely limited to
                    <strong>h/1–1360</strong>.
                    Multi-collection imports use the
                    official Hymnal.net song indexes and
                    synchronize only discovered links.
                </p>
                </div>

                <a
                    href="{{ \App\Filament\Pages\HymnsSetup::getUrl() }}"
                    class="inline-flex shrink-0
                           items-center justify-center
                           rounded-lg bg-primary-600
                           px-4 py-2.5
                           text-sm font-bold text-white
                           shadow-sm
                           hover:bg-primary-500"
                >
                    Open Hymns Setup
                </a>
            </div>
        </div>

        <div
            class="grid gap-4 sm:grid-cols-2
                   lg:grid-cols-4 xl:grid-cols-8"
        >
            @foreach ([
                'Total' => $summary['total'],
                'Linked' => $summary['linked'],
                'Unmatched' => $summary['unmatched'],
                'Ambiguous' => $summary['ambiguous'],
                'Conflicts' => $summary['conflict'],
                'Invalid' => $summary['invalid'],
                'Variant Review' =>
                    $summary['variant_review'],
                'Failed' => $summary['failed'],
            ] as $label => $count)
                <div
                    class="rounded-2xl border
                           border-gray-200
                           bg-white p-5
                           shadow-sm
                           dark:border-gray-700
                           dark:bg-gray-900"
                >
                    <div
                        class="text-xs font-bold
                               uppercase tracking-wide
                               text-gray-500
                               dark:text-gray-400"
                    >
                        {{ $label }}
                    </div>

                    <div
                        class="mt-2 text-2xl
                               font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        {{ number_format($count) }}
                    </div>
                </div>
            @endforeach
        </div>

        <div
            id="linked-hymnal-sources"
            class="rounded-2xl border
                   border-gray-200 bg-white p-6
                   shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div
                class="mb-5 flex flex-col gap-3
                       lg:flex-row lg:items-start
                       lg:justify-between"
            >
                <div>
                    <h3
                        class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        Hymnal.net Collections
                    </h3>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Click a collection to filter
                        Linked Hymnal.net Sources.
                        Logical collections remain
                        separate from physical provider
                        routes such as
                        <strong>h</strong>,
                        <strong>nt</strong>,
                        <strong>ns</strong>, and
                        <strong>lb</strong>.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="clearLinkedSourcesSectionFilter"
                    class="rounded-lg border
                           border-gray-300 bg-white
                           px-3 py-2 text-sm font-bold
                           text-gray-700
                           hover:bg-gray-50
                           dark:border-gray-600
                           dark:bg-gray-950
                           dark:text-gray-200"
                >
                    All Collections
                </button>
            </div>

            <div
                class="grid gap-4
                       md:grid-cols-2
                       xl:grid-cols-4"
            >
                @foreach (
                    $collectionSummaries
                    as $collection
                )
                    <button
                        type="button"
                        wire:click="filterLinkedSourcesBySection('{{ $collection['code'] }}')"
                        class="w-full rounded-xl border
                               border-gray-200
                               bg-gray-50 p-5
                               text-left
                               transition
                               hover:border-primary-400
                               hover:bg-primary-50
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:hover:border-primary-500
                               {{
                                   $linkedSectionFilter
                                       === $collection['code']
                                           ? 'ring-2 ring-primary-500'
                                           : ''
                               }}"
                    >
                        <div
                            class="text-base font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            {{ $collection['label'] }}
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Index:
                            <strong>
                                {{ $collection['index_code'] }}
                            </strong>

                            · Routes:
                            <strong>
                                {{
                                    $collection['routes']
                                        ->isNotEmpty()
                                            ? $collection['routes']
                                                ->join(', ')
                                            : $collection[
                                                'primary_route'
                                            ]
                                }}
                            </strong>
                        </div>

                        <div
                            class="mt-4 grid grid-cols-2
                                   gap-3 text-sm"
                        >
                            <div>
                                <div
                                    class="text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Discovered
                                </div>

                                <div
                                    class="font-bold
                                           text-gray-950
                                           dark:text-white"
                                >
                                    {{
                                        number_format(
                                            $collection['total']
                                        )
                                    }}
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Linked
                                </div>

                                <div
                                    class="font-bold
                                           text-gray-950
                                           dark:text-white"
                                >
                                    {{
                                        number_format(
                                            $collection['linked']
                                        )
                                    }}
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Pending Sync
                                </div>

                                <div
                                    class="font-bold
                                           text-gray-950
                                           dark:text-white"
                                >
                                    {{
                                        number_format(
                                            $collection['pending']
                                        )
                                    }}
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Needs Review
                                </div>

                                <div
                                    class="font-bold
                                           text-gray-950
                                           dark:text-white"
                                >
                                    {{
                                        number_format(
                                            $collection['review']
                                        )
                                    }}
                                </div>
                            </div>
                        </div>

                        @if ($collection['failed'] > 0)
                            <div
                                class="mt-3 text-xs
                                       font-semibold
                                       text-red-600
                                       dark:text-red-400"
                            >
                                {{
                                    number_format(
                                        $collection['failed']
                                    )
                                }}
                                failed request(s)
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <div
            class="rounded-2xl border
                   border-gray-200
                   bg-white p-6
                   shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div
                id="hymnal-link-form"
                class="mb-5"
            >
                <h3
                    class="text-lg font-bold
                           text-gray-950
                           dark:text-white"
                >
                    Link Hymnal.net
                </h3>

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
                        Find Existing Canonical Hymn
                    </label>

                    <input
                        type="search"
                        wire:model.live.debounce.300ms="hymnSearch"
                        placeholder="Search title, any source lyrics, hymn number, or source ID..."
                        class="block w-full rounded-xl
                               border border-gray-300
                               bg-white px-4 py-3
                               text-sm text-gray-950
                               shadow-sm
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-white"
                    >

                    @if (trim($hymnSearch) !== '')
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
                                           rounded-xl border
                                           px-4 py-3
                                           text-left
                                           transition
                                           {{ $selectedHymnId === $hymn->id
                                               ? 'hymnal-light-emerald border-emerald-300 bg-emerald-100 dark:border-emerald-300 dark:bg-emerald-100'
                                               : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-white/5'
                                           }}"
                                >
                                    <div
                                        class="font-bold
                                               text-gray-950
                                               dark:text-white"
                                        @if (
                                            $selectedHymnId
                                                === $hymn->id
                                        )
                                            style="
                                                color: #064e3b !important;
                                            "
                                        @endif
                                    >
                                        {{ $hymn->title }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                        @if (
                                            $selectedHymnId
                                                === $hymn->id
                                        )
                                            style="
                                                color: #047857 !important;
                                            "
                                        @endif
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
                                    class="rounded-xl border
                                           border-gray-200
                                           bg-gray-50
                                           p-4 text-sm
                                           text-gray-500
                                           dark:border-gray-700
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
                        class="block w-full rounded-xl
                               border border-gray-300
                               bg-white px-4 py-3
                               text-sm text-gray-950
                               shadow-sm
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-white"
                    >

                    @if ($selectedHymn)
                        <div
                            class="hymnal-light-emerald
                                   mt-4 rounded-xl
                                   border border-emerald-300
                                   bg-emerald-100 p-4
                                   dark:border-emerald-300
                                   dark:bg-emerald-100"
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
                            class="hymnal-light-amber
                                   mt-4 rounded-xl
                                   border border-amber-300
                                   bg-amber-100 p-4
                                   dark:border-amber-300
                                   dark:bg-amber-100"
                        >
                            Select the canonical Hymn first.
                        </div>
                    @endif

                    @error('selectedHymnId')
                        <p data-coqp-field-error class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('hymnalUrl')
                        <p data-coqp-field-error class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    <div
                        class="mt-4 flex
                               flex-wrap gap-2"
                    >
                        <button
                                type="button"
                                wire:click="attachHymnalNetSource"
                                wire:loading.attr="disabled"
                                class="rounded-xl
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
        </div>

        <div
            class="rounded-2xl border
                   border-gray-200
                   bg-white p-6
                   shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div
                class="mb-5 flex flex-wrap
                       items-end justify-between
                       gap-4"
            >
                <div>
                    <h3
                        class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        Linked Hymnal.net Sources
                    </h3>

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

                <div class="w-full sm:w-96">
                    @if ($linkedSectionLabel)
                        <div
                            class="mb-2 flex items-center
                                   justify-between gap-2
                                   text-xs font-semibold
                                   text-primary-700
                                   dark:text-primary-300"
                        >
                            <span>
                                Filtering:
                                {{ $linkedSectionLabel }}
                            </span>

                            <button
                                type="button"
                                wire:click="clearLinkedSourcesSectionFilter"
                                class="underline"
                            >
                                Clear
                            </button>
                        </div>
                    @endif
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="linkedSearch"
                        placeholder="Filter existing Hymnal.net links by title, URL, or Hymnal.net ID..."
                        class="block w-full rounded-xl
                               border border-gray-300
                               bg-white px-4 py-3
                               text-sm text-gray-950
                               shadow-sm
                               dark:border-gray-700
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
                                   dark:border-gray-700"
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
                                       dark:border-gray-800"
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
                                            class="hymnal-light-sky
                                                   inline-flex
                                                   rounded-full
                                                   bg-sky-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-bold
                                                   ring-1
                                                   ring-sky-200
                                                   hover:underline
                                                   dark:bg-sky-100
                                                   dark:text-sky-950
                                                   dark:ring-sky-300"
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
                                        class="hymnal-light-red
                                               rounded-xl
                                               border border-red-300
                                               bg-red-50
                                               px-3 py-1.5
                                               text-xs
                                               font-bold
                                               hover:bg-red-100
                                               dark:border-red-300
                                               dark:bg-red-50
                                               dark:text-red-950
                                               dark:hover:bg-red-100"
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
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    No linked Hymnal.net sources match this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
