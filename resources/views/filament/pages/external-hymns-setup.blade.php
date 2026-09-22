<x-filament-panels::page>
    @php
        $summary =
            $this->summary();

        $linkedExternalSources =
            $this->linkedExternalSources();
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
                        External Hymn Sources
                    </p>

                    <h2
                        class="mt-2 text-3xl
                               font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        External Hymns Setup
                    </h2>

                    <p
                        class="mt-2 max-w-3xl
                               text-sm
                               text-gray-600
                               dark:text-gray-300"
                    >
                        Inspect YouTube, SoundCloud,
                        and other external sources
                        linked to canonical Hymns.
                    </p>

                    <p
                        class="mt-2 max-w-3xl
                               text-xs
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Review decisions remain in
                        Hymns Setup. External submissions
                        do not become canonical Hymns
                        until they are approved there.
                    </p>
                </div>

                <a
                    href="{{
                        \App\Filament\Pages\HymnsSetup
                            ::getUrl()
                    }}"
                    class="inline-flex items-center
                           justify-center rounded-lg
                           bg-primary-600 px-4 py-2.5
                           text-sm font-bold text-white
                           shadow-sm
                           hover:bg-primary-500"
                >
                    Open Hymns Setup
                </a>
            </div>
        </div>

        <div
            class="grid gap-4
                   sm:grid-cols-2
                   xl:grid-cols-4"
        >
            @foreach ([
                'Pending External Requests' =>
                    $summary['pending_external'],

                'YouTube Sources' =>
                    $summary['youtube'],

                'SoundCloud Sources' =>
                    $summary['soundcloud'],

                'Other External Sources' =>
                    $summary['other'],
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
                    Linked External Sources
                </h3>

                <p
                    class="mt-1 text-sm
                           text-gray-500
                           dark:text-gray-400"
                >
                    YouTube, SoundCloud, and other
                    external sources already attached
                    to canonical Hymns.
                </p>
            </div>

            <div
                class="mt-5 flex flex-col gap-2
                       sm:flex-row
                       sm:items-center"
            >
                <div class="min-w-0 flex-1">
                    <input
                        type="search"
                        wire:model.defer="externalSourceSearchInput"
                        wire:keydown.enter="searchExternalSources"
                        placeholder="Search external hymn, album, track, provider, URL, or source ID..."
                        class="block w-full rounded-xl
                               border border-gray-300
                               bg-white px-4 py-2.5
                               text-sm text-gray-900
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-gray-100"
                    >
                </div>

                <button
                    type="button"
                    wire:click="searchExternalSources"
                    class="inline-flex items-center
                           justify-center rounded-lg
                           bg-sky-600 px-4 py-2.5
                           text-sm font-bold
                           text-white
                           hover:bg-sky-500"
                >
                    Search
                </button>

                @if (
                    $externalSourceSearch !== ''
                    || $externalSourceSearchInput !== ''
                )
                    <button
                        type="button"
                        wire:click="clearExternalSourceSearch"
                        class="inline-flex items-center
                               justify-center rounded-lg
                               border border-gray-300
                               bg-white px-4 py-2.5
                               text-sm font-bold
                               text-gray-700
                               hover:bg-gray-50
                               dark:border-gray-600
                               dark:bg-gray-800
                               dark:text-gray-100
                               dark:hover:bg-gray-700"
                    >
                        Clear
                    </button>
                @endif
            </div>

            @if ($externalSourceSearch !== '')
                <div
                    class="mt-2 text-xs
                           text-gray-500
                           dark:text-gray-400"
                >
                    Showing external sources matching:
                    <strong>
                        {{ $externalSourceSearch }}
                    </strong>
                </div>
            @endif

            @if ($linkedExternalSources->isEmpty())
                <div
                    class="mt-5 rounded-xl
                           border border-dashed
                           border-gray-300
                           px-5 py-8
                           text-center text-sm
                           text-gray-500
                           dark:border-gray-700
                           dark:text-gray-400"
                >
                    @if ($externalSourceSearch !== '')
                        No linked external sources
                        match this search.
                    @else
                        No linked external hymn sources yet.
                    @endif
                </div>
            @else
                <div
                    class="mt-5 overflow-x-auto
                           rounded-xl border
                           border-gray-200
                           dark:border-gray-700"
                >
                    <table
                        class="w-full min-w-[980px]
                               text-left text-sm"
                    >
                        <thead
                            class="bg-gray-50
                                   dark:bg-gray-950"
                        >
                            <tr
                                class="border-b
                                       border-gray-200
                                       dark:border-gray-700"
                            >
                                <th
                                    class="px-4 py-3
                                           text-xs font-bold
                                           uppercase tracking-wide
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Hymn
                                </th>

                                <th
                                    class="px-4 py-3
                                           text-xs font-bold
                                           uppercase tracking-wide
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Provider
                                </th>

                                <th
                                    class="px-4 py-3
                                           text-xs font-bold
                                           uppercase tracking-wide
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Collection /
                                    Album / Book
                                </th>

                                <th
                                    class="px-4 py-3
                                           text-xs font-bold
                                           uppercase tracking-wide
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Track /
                                    Hymn No.
                                </th>

                                <th
                                    class="px-4 py-3
                                           text-xs font-bold
                                           uppercase tracking-wide
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Source
                                </th>

                                <th
                                    class="px-4 py-3
                                           text-right
                                           text-xs font-bold
                                           uppercase tracking-wide
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $linkedExternalSources
                                as $source
                            )
                                @php
                                    $sourceMetadata =
                                        is_array(
                                            $source->metadata
                                        )
                                            ? $source->metadata
                                            : [];

                                    $collectionName =
                                        $sourceMetadata[
                                            'collection_name'
                                        ]
                                        ?? null;

                                    $trackNumber =
                                        $sourceMetadata[
                                            'track_number'
                                        ]
                                        ?? null;
                                @endphp

                                <tr
                                    wire:key="external-source-{{ $source->id }}"
                                    class="border-b
                                           border-gray-100
                                           align-top
                                           last:border-b-0
                                           dark:border-gray-800"
                                >
                                    <td class="px-4 py-4">
                                        <div
                                            class="font-bold
                                                   text-gray-950
                                                   dark:text-white"
                                        >
                                            {{
                                                $source
                                                    ->hymn
                                                    ?->title
                                                ?? 'Missing canonical Hymn'
                                            }}
                                        </div>

                                        <div
                                            class="mt-1 text-xs
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            Source #{{ $source->id }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <span
                                            style="
                                                background-color:#e0f2fe !important;
                                                color:#082f49 !important;
                                            "
                                            class="inline-flex
                                                   rounded-full
                                                   px-2.5 py-1
                                                   text-xs font-bold
                                                   ring-1
                                                   ring-sky-200"
                                        >
                                            {{
                                                $this
                                                    ->sourceLabel(
                                                        $source
                                                    )
                                            }}
                                        </span>

                                        <div
                                            class="mt-1 text-xs
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            {{ $source->source_type }}
                                        </div>
                                    </td>

                                    <td
                                        class="px-4 py-4
                                               text-gray-900
                                               dark:text-gray-200"
                                    >
                                        {{
                                            filled(
                                                $collectionName
                                            )
                                                ? $collectionName
                                                : '—'
                                        }}
                                    </td>

                                    <td
                                        class="px-4 py-4
                                               text-gray-900
                                               dark:text-gray-200"
                                    >
                                        {{
                                            filled(
                                                $trackNumber
                                            )
                                                ? $trackNumber
                                                : '—'
                                        }}
                                    </td>

                                    <td class="px-4 py-4">
                                        @if (
                                            filled(
                                                $source->source_url
                                            )
                                        )
                                            <a
                                                href="{{ $source->source_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="font-semibold
                                                       text-sky-700
                                                       hover:underline
                                                       dark:text-sky-400"
                                            >
                                                Open Source
                                            </a>

                                            <div
                                                class="mt-1
                                                       max-w-xs
                                                       break-all
                                                       text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                {{
                                                    $source->source_url
                                                }}
                                            </div>
                                        @else
                                            <span
                                                class="text-gray-400"
                                            >
                                                —
                                            </span>
                                        @endif
                                    </td>

                                    <td
                                        class="px-4 py-4
                                               text-right"
                                    >
                                        <button
                                            type="button"
                                            wire:click="editExternalSource({{ $source->id }})"
                                            class="inline-flex
                                                   items-center
                                                   justify-center
                                                   rounded-lg
                                                   border
                                                   border-gray-300
                                                   bg-white
                                                   px-3 py-2
                                                   text-xs
                                                   font-bold
                                                   text-gray-700
                                                   hover:bg-gray-50
                                                   dark:border-gray-600
                                                   dark:bg-gray-800
                                                   dark:text-gray-100
                                                   dark:hover:bg-gray-700"
                                        >
                                            Edit
                                        </button>
                                    </td>
                                </tr>

                                @if (
                                    $editingExternalSourceId
                                    === $source->id
                                )
                                    <tr
                                        wire:key="external-source-editor-{{ $source->id }}"
                                        class="border-b
                                               border-gray-100
                                               bg-gray-50
                                               dark:border-gray-800
                                               dark:bg-gray-950"
                                    >
                                        <td
                                            colspan="6"
                                            class="px-4 py-4"
                                        >
                                            <div
                                                class="rounded-xl
                                                       border
                                                       border-gray-200
                                                       bg-white p-4
                                                       dark:border-gray-700
                                                       dark:bg-gray-900"
                                            >
                                                <div
                                                    class="grid gap-4
                                                           md:grid-cols-2"
                                                >
                                                    <div>
                                                        <label
                                                            class="text-xs
                                                                   font-bold
                                                                   uppercase
                                                                   text-gray-500
                                                                   dark:text-gray-400"
                                                        >
                                                            Collection /
                                                            Album / Book
                                                        </label>

                                                        <input
                                                            type="text"
                                                            maxlength="255"
                                                            wire:model="externalSourceForm.collection_name"
                                                            class="mt-1
                                                                   block w-full
                                                                   rounded-lg
                                                                   border
                                                                   border-gray-300
                                                                   bg-white
                                                                   px-3 py-2
                                                                   text-sm
                                                                   text-gray-900
                                                                   dark:border-gray-700
                                                                   dark:bg-gray-950
                                                                   dark:text-gray-100"
                                                        >

                                                        @error(
                                                            'externalSourceForm.collection_name'
                                                        )
                                                            <p data-coqp-field-error
                                                                class="mt-1
                                                                       text-xs
                                                                       text-red-600"
                                                            >
                                                                {{ $message }}
                                                            </p>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label
                                                            class="text-xs
                                                                   font-bold
                                                                   uppercase
                                                                   text-gray-500
                                                                   dark:text-gray-400"
                                                        >
                                                            Track /
                                                            Hymn No.
                                                        </label>

                                                        <input
                                                            type="text"
                                                            maxlength="100"
                                                            wire:model="externalSourceForm.track_number"
                                                            class="mt-1
                                                                   block w-full
                                                                   rounded-lg
                                                                   border
                                                                   border-gray-300
                                                                   bg-white
                                                                   px-3 py-2
                                                                   text-sm
                                                                   text-gray-900
                                                                   dark:border-gray-700
                                                                   dark:bg-gray-950
                                                                   dark:text-gray-100"
                                                        >

                                                        @error(
                                                            'externalSourceForm.track_number'
                                                        )
                                                            <p data-coqp-field-error
                                                                class="mt-1
                                                                       text-xs
                                                                       text-red-600"
                                                            >
                                                                {{ $message }}
                                                            </p>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="mt-4">
                                                    <label
                                                        class="text-xs
                                                               font-bold
                                                               uppercase
                                                               text-gray-500
                                                               dark:text-gray-400"
                                                    >
                                                        Source URL
                                                    </label>

                                                    <input
                                                        type="url"
                                                        maxlength="2048"
                                                        wire:model="externalSourceForm.source_url"
                                                        class="mt-1
                                                               block w-full
                                                               rounded-lg
                                                               border
                                                               border-gray-300
                                                               bg-white
                                                               px-3 py-2
                                                               text-sm
                                                               text-gray-900
                                                               dark:border-gray-700
                                                               dark:bg-gray-950
                                                               dark:text-gray-100"
                                                    >

                                                    @error(
                                                        'externalSourceForm.source_url'
                                                    )
                                                        <p data-coqp-field-error
                                                            class="mt-1
                                                                   text-xs
                                                                   text-red-600"
                                                        >
                                                            {{ $message }}
                                                        </p>
                                                    @enderror
                                                </div>

                                                <div
                                                    class="mt-4 flex
                                                           flex-wrap
                                                           justify-end
                                                           gap-2"
                                                >
                                                    <button
                                                        type="button"
                                                        wire:click="cancelExternalSourceEdit"
                                                        class="rounded-lg
                                                               border
                                                               border-gray-300
                                                               bg-white
                                                               px-3 py-2
                                                               text-xs
                                                               font-bold
                                                               text-gray-700
                                                               dark:border-gray-600
                                                               dark:bg-gray-800
                                                               dark:text-gray-200"
                                                    >
                                                        Cancel
                                                    </button>

                                                    <button
                                                        type="button"
                                                        wire:click="saveExternalSource"
                                                        wire:loading.attr="disabled"
                                                        class="rounded-lg
                                                               bg-sky-600
                                                               px-3 py-2
                                                               text-xs
                                                               font-bold
                                                               text-white
                                                               hover:bg-sky-500"
                                                    >
                                                        Save Source Details
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</x-filament-panels::page>
