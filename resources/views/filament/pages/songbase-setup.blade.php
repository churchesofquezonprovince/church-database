<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $books = $this->books();
        $languages = $this->languages();
        $songbaseHymns = $this->songbaseHymns();

        $hasSongbaseFilters =
            trim($songbaseSearch) !== ''
            || $songbaseBookId !== null
            || $songbaseLanguage !== '';

        $selectedSongbaseBook =
            $songbaseBookId
                ? $books->firstWhere(
                    'id',
                    $songbaseBookId
                )
                : null;
    @endphp

    <style>
        /*
         * Pale-blue controls intentionally remain
         * light in both light and dark mode.
         */
        .songbase-light-sky,
        .songbase-light-sky * {
            color: #082f49 !important;
        }
    </style>

    <div class="space-y-6">
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
                        Songbase Setup
                    </h2>

                    <p
                        class="mt-2 max-w-3xl
                               text-sm text-gray-600
                               dark:text-gray-300"
                    >
                        Synchronize and inspect the
                        Songbase source catalog.
                        Songbase is a provider attached
                        to canonical Hymns; it is not
                        the canonical identity.
                    </p>

                    <p
                        class="mt-2 text-xs
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Last synchronized:
                        {{
                            $summary['last_synced_at']
                                ?? 'Never'
                        }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ \App\Filament\Pages\HymnsSetup::getUrl() }}"
                        class="inline-flex items-center
                               justify-center rounded-lg
                               bg-primary-600 px-4 py-2.5
                               text-sm font-bold text-white
                               shadow-sm
                               hover:bg-primary-500"
                    >
                        Open Hymns Setup
                    </a>

                    <x-filament::button
                        wire:click="syncSongbase"
                        wire:confirm="Synchronize the complete Songbase source catalog now?"
                        icon="heroicon-m-arrow-path"
                    >
                        Sync Songbase
                    </x-filament::button>
                </div>
            </div>
        </div>

        <div
            class="grid gap-4 sm:grid-cols-2
                   lg:grid-cols-3 xl:grid-cols-6"
        >
            @foreach ([
                'Canonical Hymns' =>
                    $summary['canonical_hymns'],

                'Variant Sources' =>
                    $summary['variant_sources'],

                'Source Links' =>
                    $summary['source_links'],

                'Languages' =>
                    $summary['languages'],

                'Books' =>
                    $summary['books'],

                'Book Entries' =>
                    $summary['book_entries'],
            ] as $label => $value)
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
                        {{ number_format($value) }}
                    </div>
                </div>
            @endforeach
        </div>



        <div
            class="rounded-2xl border
                   border-gray-200
                   bg-white p-6
                   shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div>
                <h3
                    class="text-lg font-bold
                           text-gray-950
                           dark:text-white"
                >
                    Songbase Books
                </h3>

                <p
                    class="mt-1 text-sm
                           text-gray-500
                           dark:text-gray-400"
                >
                    Numbered hymn books supplied by
                    the Songbase provider.
                </p>
            </div>

            <div
                class="mt-5 grid gap-3
                       md:grid-cols-2
                       xl:grid-cols-3"
            >
                @forelse ($books as $book)
                    <button
                        type="button"
                        wire:click="toggleSongbaseBook({{ $book->id }})"
                        class="rounded-xl border
                               p-4 text-left
                               transition
                               {{
                                   $songbaseBookId === $book->id
                                       ? 'songbase-light-sky border-sky-300 bg-sky-100 dark:border-sky-300 dark:bg-sky-100'
                                       : 'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-900'
                               }}"
                    >
                        <div
                            class="font-semibold
                                   {{
                                       $songbaseBookId === $book->id
                                           ? 'text-sky-950 dark:text-sky-950'
                                           : 'text-gray-950 dark:text-white'
                                   }}"
                        >
                            {{ $book->name }}
                        </div>

                        <div
                            class="mt-1 text-xs
                                   {{
                                       $songbaseBookId === $book->id
                                           ? 'text-sky-800 dark:text-sky-900'
                                           : 'text-gray-500 dark:text-gray-400'
                                   }}"
                        >
                            {{
                                $book->language
                                    ?? 'Language not detected'
                            }}
                            ·
                            {{
                                number_format(
                                    $book->entries_count
                                )
                            }}
                            entries
                        </div>
                    </button>
                @empty
                    <div
                        class="text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        No Songbase books are available.
                    </div>
                @endforelse
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
            <div>
                <h3
                    class="text-lg font-bold
                           text-gray-950
                           dark:text-white"
                >
                    Languages
                </h3>

                <p
                    class="mt-1 text-sm
                           text-gray-500
                           dark:text-gray-400"
                >
                    Languages represented by canonical
                    Hymns currently connected to
                    Songbase.
                </p>
            </div>

            <div
                class="mt-5 grid gap-3
                       sm:grid-cols-2
                       lg:grid-cols-4"
            >
                @foreach ($languages as $item)
                    <button
                        type="button"
                        wire:click="toggleSongbaseLanguage(@js($item->language))"
                        class="rounded-xl border
                               p-4 text-left
                               transition
                               {{
                                   $songbaseLanguage === $item->language
                                       ? 'songbase-light-sky border-sky-300 bg-sky-100 dark:border-sky-300 dark:bg-sky-100'
                                       : 'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-900'
                               }}"
                    >
                        <div
                            class="font-semibold
                                   {{
                                       $songbaseLanguage === $item->language
                                           ? 'text-sky-950 dark:text-sky-950'
                                           : 'text-gray-950 dark:text-white'
                                   }}"
                        >
                            {{ $item->language }}
                        </div>

                        <div
                            class="mt-1 text-xs
                                   {{
                                       $songbaseLanguage === $item->language
                                           ? 'text-sky-800 dark:text-sky-900'
                                           : 'text-gray-500 dark:text-gray-400'
                                   }}"
                        >
                            {{
                                number_format(
                                    $item->total
                                )
                            }}
                            Hymns
                        </div>
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
                class="flex flex-col gap-4
                       lg:flex-row
                       lg:items-end
                       lg:justify-between"
            >
                <div class="flex-1">
                    <h3
                        class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        Browse Songbase Catalog
                    </h3>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Search only Hymns connected to
                        Songbase. Book and language cards
                        below act as additional filters.
                    </p>

                    <input
                        type="search"
                        wire:model.live.debounce.350ms="songbaseSearch"
                        placeholder="Search title, first line, lyrics, Songbase ID, or hymn number..."
                        class="mt-4 block w-full
                               rounded-xl border
                               border-gray-300 bg-white
                               px-4 py-3 text-sm
                               text-gray-950 shadow-sm
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-white"
                    >
                </div>

                @if ($hasSongbaseFilters)
                    <button
                        type="button"
                        wire:click="clearSongbaseFilters"
                        class="rounded-xl border
                               border-gray-300
                               bg-white px-4 py-3
                               text-sm font-bold
                               text-gray-700
                               hover:bg-gray-50
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-gray-200
                               dark:hover:bg-gray-900"
                    >
                        Clear Filters
                    </button>
                @endif
            </div>

            @if ($hasSongbaseFilters)
                <div
                    class="mt-4 flex flex-wrap
                           items-center gap-2"
                >
                    @if (trim($songbaseSearch) !== '')
                        <span
                            class="songbase-light-sky
                                   rounded-full
                                   bg-sky-100
                                   px-3 py-1
                                   text-xs font-bold
                                   text-sky-950
                                   ring-1 ring-sky-200
                                   dark:bg-sky-100
                                   dark:text-sky-950
                                   dark:ring-sky-300"
                        >
                            Search:
                            {{ $songbaseSearch }}
                        </span>
                    @endif

                    @if ($selectedSongbaseBook)
                        <span
                            class="songbase-light-sky
                                   rounded-full
                                   bg-sky-100
                                   px-3 py-1
                                   text-xs font-bold
                                   text-sky-950
                                   ring-1 ring-sky-200
                                   dark:bg-sky-100
                                   dark:text-sky-950
                                   dark:ring-sky-300"
                        >
                            Book:
                            {{ $selectedSongbaseBook->name }}
                        </span>
                    @endif

                    @if ($songbaseLanguage !== '')
                        <span
                            class="songbase-light-sky
                                   rounded-full
                                   bg-sky-100
                                   px-3 py-1
                                   text-xs font-bold
                                   text-sky-950
                                   ring-1 ring-sky-200
                                   dark:bg-sky-100
                                   dark:text-sky-950
                                   dark:ring-sky-300"
                        >
                            Language:
                            {{ $songbaseLanguage }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
        @if ($hasSongbaseFilters)
            <div
                class="rounded-2xl border
                       border-gray-200
                       bg-white p-6
                       shadow-sm
                       dark:border-gray-700
                       dark:bg-gray-900"
            >
                <div class="mb-5">
                    <h3
                        class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        Songbase Results
                    </h3>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Showing
                        {{ number_format($songbaseHymns->count()) }}
                        matching Hymn(s), up to 100.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table
                        class="w-full text-left text-sm"
                    >
                        <thead>
                            <tr
                                class="border-b
                                       border-gray-200
                                       dark:border-gray-700"
                            >
                                <th class="px-4 py-3">
                                    Canonical Hymn
                                </th>

                                <th class="px-4 py-3">
                                    Songbase
                                </th>

                                <th class="px-4 py-3">
                                    Language / Book
                                </th>

                                <th class="px-4 py-3">
                                    Variants
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse (
                                $songbaseHymns
                                as $hymn
                            )
                                @php
                                    $songbaseSources =
                                        $hymn
                                            ->sources
                                            ->where(
                                                'provider',
                                                'songbase'
                                            );

                                    $activeVariants =
                                        $hymn
                                            ->variants
                                            ->where(
                                                'is_active',
                                                true
                                            );
                                @endphp

                                <tr
                                    class="border-b
                                           border-gray-100
                                           dark:border-gray-800"
                                >
                                    <td
                                        class="px-4 py-4
                                               align-top"
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
                                            Canonical Hymn
                                            #{{ $hymn->id }}
                                        </div>

                                        @if ($hymn->first_line_search)
                                            <div
                                                class="mt-2 max-w-xl
                                                       text-xs
                                                       text-gray-600
                                                       dark:text-gray-300"
                                            >
                                                First line:
                                                {{
                                                    $hymn
                                                        ->first_line_search
                                                }}
                                            </div>
                                        @endif
                                    </td>

                                    <td
                                        class="px-4 py-4
                                               align-top"
                                    >
                                        <div
                                            class="flex flex-wrap
                                                   gap-2"
                                        >
                                            @foreach (
                                                $songbaseSources
                                                as $source
                                            )
                                                @if ($source->source_url)
                                                    <a
                                                        href="{{ $source->source_url }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="songbase-light-sky
                                                               rounded-full
                                                               bg-sky-100
                                                               px-2.5 py-1
                                                               text-xs
                                                               font-bold
                                                               text-sky-950
                                                               ring-1
                                                               ring-sky-200
                                                               hover:bg-sky-200
                                                               dark:bg-sky-100
                                                               dark:text-sky-950
                                                               dark:ring-sky-300"
                                                    >
                                                        {{
                                                            $source
                                                                ->external_id
                                                            ?: 'Songbase'
                                                        }}
                                                    </a>
                                                @else
                                                    <span
                                                        class="songbase-light-sky
                                                               rounded-full
                                                               bg-sky-100
                                                               px-2.5 py-1
                                                               text-xs
                                                               font-bold
                                                               text-sky-950
                                                               ring-1
                                                               ring-sky-200
                                                               dark:bg-sky-100
                                                               dark:text-sky-950
                                                               dark:ring-sky-300"
                                                    >
                                                        {{
                                                            $source
                                                                ->external_id
                                                            ?: 'Songbase'
                                                        }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>

                                    <td
                                        class="px-4 py-4
                                               align-top"
                                    >
                                        <div
                                            class="font-semibold
                                                   text-gray-950
                                                   dark:text-white"
                                        >
                                            {{
                                                $hymn->language
                                                    ?: 'Not specified'
                                            }}
                                        </div>

                                        @if (
                                            $hymn
                                                ->bookEntries
                                                ->isNotEmpty()
                                        )
                                            <div
                                                class="mt-2 flex
                                                       flex-wrap
                                                       gap-1.5"
                                            >
                                                @foreach (
                                                    $hymn
                                                        ->bookEntries
                                                    as $entry
                                                )
                                                    @if (
                                                        $entry
                                                            ->hymnBook
                                                            ?->source
                                                        === 'songbase'
                                                    )
                                                        <span
                                                            class="rounded-full
                                                                   bg-gray-100
                                                                   px-2 py-1
                                                                   text-xs
                                                                   text-gray-700
                                                                   dark:bg-gray-800
                                                                   dark:text-gray-200"
                                                        >
                                                            {{
                                                                $entry
                                                                    ->hymnBook
                                                                    ?->name
                                                            }}
                                                            #{{ $entry->number }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td
                                        class="px-4 py-4
                                               align-top"
                                    >
                                        @forelse (
                                            $activeVariants
                                            as $variant
                                        )
                                            <div
                                                class="mb-1 text-xs
                                                       font-semibold
                                                       text-gray-700
                                                       dark:text-gray-200"
                                            >
                                                {{ $variant->label }}
                                            </div>
                                        @empty
                                            <span
                                                class="text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                No variants
                                            </span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-4 py-10
                                               text-center
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        No Songbase Hymns match
                                        the current search and
                                        filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
