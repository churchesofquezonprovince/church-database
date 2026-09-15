<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $books = $this->books();
        $languages = $this->languages();
    @endphp

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

                <x-filament::button
                    wire:click="syncSongbase"
                    wire:confirm="Synchronize the complete Songbase source catalog now?"
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
                    <div
                        class="rounded-xl border
                               border-gray-200
                               bg-gray-50 p-4
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                        <div
                            class="font-semibold
                                   text-gray-950
                                   dark:text-white"
                        >
                            {{ $book->name }}
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
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
                    </div>
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
                    <div
                        class="rounded-xl border
                               border-gray-200
                               bg-gray-50 p-4
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                        <div
                            class="font-semibold
                                   text-gray-950
                                   dark:text-white"
                        >
                            {{ $item->language }}
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            {{
                                number_format(
                                    $item->total
                                )
                            }}
                            Hymns
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
