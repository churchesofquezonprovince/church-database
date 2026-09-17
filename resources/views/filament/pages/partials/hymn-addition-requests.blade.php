@php
    $pendingHymnRequests =
        $this->pendingHymnRequests();
@endphp

<div
    class="rounded-2xl border border-gray-200
           bg-white p-6 shadow-sm
           dark:border-gray-700
           dark:bg-gray-900"
>
    <div
        class="flex flex-col gap-3
               sm:flex-row sm:items-center
               sm:justify-between"
    >
        <div>
            <h3
                class="text-lg font-bold
                       text-gray-950 dark:text-white"
            >
                External Hymn Request Review
            </h3>

            <p
                class="mt-1 text-sm text-gray-500
                       dark:text-gray-400"
            >
                Review external hymn requests,
                including YouTube, SoundCloud, and
                other submitted sources, before
                linking or adding them to the
                canonical Hymn Catalog.
            </p>
        </div>

        @if ($pendingHymnRequests->isNotEmpty())
            <span
                class="inline-flex w-fit
                       rounded-full
                       bg-sky-100
                       px-3 py-1
                       text-xs font-bold
                       text-sky-900
                       ring-1 ring-sky-200
                       dark:bg-sky-500/15
                       dark:text-sky-900
                       dark:ring-sky-500/30"
            >
                {{ $pendingHymnRequests->count() }}
                Pending
            </span>
        @endif
    </div>

    @if ($pendingHymnRequests->isEmpty())
        <div
            class="mt-5 rounded-xl border
                   border-dashed border-gray-300
                   px-5 py-8 text-center
                   text-sm text-gray-500
                   dark:border-gray-700
                   dark:text-gray-400"
        >
            No pending External Hymn Requests.
        </div>
    @else
        <div class="mt-5 space-y-4">
            @foreach (
                $pendingHymnRequests
                as $request
            )
                <div
                    wire:key="hymn-request-{{ $request->id }}"
                    class="rounded-xl border
                           border-gray-200 p-5
                           dark:border-gray-700
                           dark:bg-gray-950/40"
                >
                    <div
                        class="flex flex-col gap-4
                               lg:flex-row
                               lg:items-start
                               lg:justify-between"
                    >
                        <div class="min-w-0 flex-1">
                            <div
                                class="flex flex-wrap
                                       items-center gap-2"
                            >
                                <h4
                                    class="text-base
                                           font-bold
                                           text-gray-950
                                           dark:text-white"
                                >
                                    {{ $request->title }}
                                </h4>

                                <span
                                    class="rounded-full
                                           bg-sky-100
                                           px-2 py-0.5
                                           text-[10px]
                                           font-bold uppercase
                                           text-sky-900
                                           ring-1 ring-sky-200
                                           dark:bg-sky-500/15
                                           dark:text-sky-900
                                           dark:ring-sky-500/30"
                                >
                                    Pending
                                </span>
                            </div>

                            <p
                                class="mt-2 text-xs
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                Request #{{ $request->id }}

                                @if ($request->requester)
                                    &nbsp;·&nbsp;
                                    Requested by:
                                    {{ $request->requester->name }}
                                @endif

                                &nbsp;·&nbsp;
                                {{
                                    $request->created_at
                                        ?->format(
                                            'M j, Y g:i A'
                                        )
                                }}
                            </p>

                            <div
                                class="mt-4 grid gap-4
                                       md:grid-cols-2"
                            >
                                <div>
                                    <div
                                        class="text-xs
                                               font-bold uppercase
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Language
                                    </div>

                                    <div
                                        class="mt-1 text-sm
                                               text-gray-900
                                               dark:text-gray-200"
                                    >
                                        {{
                                            $request->language
                                            ?: 'Not provided'
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <div
                                        class="text-xs
                                               font-bold uppercase
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Book / Number
                                    </div>

                                    <div
                                        class="mt-1 text-sm
                                               text-gray-900
                                               dark:text-gray-200"
                                    >
                                        @if (
                                            filled(
                                                $request->book_name
                                            )
                                            || filled(
                                                $request->hymn_number
                                            )
                                        )
                                            {{
                                                $request->book_name
                                                ?: 'Book not provided'
                                            }}

                                            @if (
                                                filled(
                                                    $request->hymn_number
                                                )
                                            )
                                                #{{ $request->hymn_number }}
                                            @endif
                                        @else
                                            Not provided
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if (
                                filled(
                                    $request->source_url
                                )
                            )
                                <div class="mt-4">
                                    <div
                                        class="text-xs
                                               font-bold uppercase
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Source Link
                                    </div>

                                    <a
                                        href="{{ $request->source_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-1 block
                                               break-all
                                               text-sm
                                               font-semibold
                                               text-sky-800
                                               hover:text-sky-950
                                               hover:underline
                                               dark:text-sky-400
                                               dark:hover:text-sky-300"
                                    >
                                        {{ $request->source_url }}
                                    </a>
                                </div>
                            @endif

                            @if (
                                filled(
                                    $request->lyrics
                                )
                            )
                                <details class="mt-4">
                                    <summary
                                        class="cursor-pointer
                                               text-sm
                                               font-semibold
                                               text-primary-600
                                               dark:text-primary-400"
                                    >
                                        View Lyrics /
                                        Identifying Words
                                    </summary>

                                    <pre
                                        class="mt-2 max-h-64
                                               overflow-auto
                                               whitespace-pre-wrap
                                               rounded-lg
                                               bg-gray-50 p-3
                                               font-sans text-sm
                                               leading-6
                                               text-gray-800
                                               dark:bg-gray-900
                                               dark:text-gray-200"
                                    >{{ $request->lyrics }}</pre>
                                </details>
                            @endif
                        </div>

                        @php
                            $resolutionMode =
                                $hymnRequestModes[
                                    $request->id
                                ]
                                ?? 'link';

                            $selectedTargetId =
                                $hymnRequestTargetIds[
                                    $request->id
                                ]
                                ?? null;

                            $matches =
                                $resolutionMode === 'link'
                                    ? $this->hymnRequestMatches(
                                        $request->id
                                    )
                                    : collect();
                        @endphp

                        <div
                            class="w-full shrink-0
                                   lg:max-w-md"
                        >
                            <div
                                class="rounded-xl border
                                       border-gray-200
                                       bg-gray-50 p-4
                                       dark:border-gray-700
                                       dark:bg-gray-900"
                            >
                                <div
                                    class="text-xs font-bold
                                           uppercase tracking-wide
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Resolution
                                </div>

                                <div
                                    class="mt-3 grid gap-2
                                           sm:grid-cols-2"
                                >
                                    <button
                                        type="button"
                                        wire:click="setHymnRequestMode({{ $request->id }}, 'link')"
                                        class="rounded-lg border
                                               px-3 py-2
                                               text-sm font-bold
                                               {{ $resolutionMode === 'link'
                                                    ? 'border-sky-500 bg-sky-100 text-sky-900 ring-1 ring-sky-200 dark:border-sky-500 dark:bg-sky-500/15 dark:text-sky-900 dark:ring-sky-500/30'
                                                    : 'border-gray-300 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300' }}"
                                    >
                                        Link Existing Hymn
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="setHymnRequestMode({{ $request->id }}, 'new')"
                                        style="{{ $resolutionMode === 'new'
                                            ? 'color: #064e3b !important;'
                                            : ''
                                        }}"
                                        class="rounded-lg border
                                               px-3 py-2
                                               text-sm font-bold
                                               {{ $resolutionMode === 'new'
                                                    ? 'border-emerald-500 bg-emerald-100 text-emerald-950 dark:border-emerald-300 dark:bg-emerald-100 dark:text-emerald-950'
                                                    : 'border-gray-300 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300' }}"
                                    >
                                        Create New Hymn
                                    </button>
                                </div>

                                @if ($resolutionMode === 'link')
                                    <div class="mt-4">
                                        <label
                                            class="text-xs font-semibold
                                                   text-gray-700
                                                   dark:text-gray-300"
                                        >
                                            Search Existing Hymns
                                        </label>

                                        <input
                                            type="search"
                                            wire:model.live.debounce.350ms="hymnRequestSearches.{{ $request->id }}"
                                            placeholder="Title, lyrics, or hymn number..."
                                            class="mt-1 block w-full
                                                   rounded-lg border
                                                   border-gray-300
                                                   bg-white px-3 py-2
                                                   text-sm
                                                   text-gray-900
                                                   dark:border-gray-700
                                                   dark:bg-gray-950
                                                   dark:text-gray-100"
                                        >

                                        <p
                                            class="mt-1 text-xs
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            If left blank, matches use
                                            the requested hymn title.
                                        </p>

                                        <div
                                            class="mt-3 max-h-72
                                                   space-y-2
                                                   overflow-y-auto"
                                        >
                                            @forelse ($matches as $match)
                                                @php
                                                    $bookLabels =
                                                        $match
                                                            ->bookEntries
                                                            ->map(
                                                                fn ($entry) =>
                                                                    (
                                                                        $entry
                                                                            ->hymnBook
                                                                            ?->name
                                                                        ?? 'Hymn'
                                                                    )
                                                                    . ' #'
                                                                    . $entry
                                                                        ->number
                                                            )
                                                            ->implode(
                                                                ' · '
                                                            );

                                                    $sourceLabels =
                                                        $match
                                                            ->sources
                                                            ->pluck(
                                                                'label'
                                                            )
                                                            ->filter()
                                                            ->unique()
                                                            ->implode(
                                                                ' · '
                                                            );

                                                    $isSelected =
                                                        (int)
                                                        $selectedTargetId
                                                        ===
                                                        (int)
                                                        $match->id;
                                                @endphp

                                                <button
                                                    type="button"
                                                    wire:key="hymn-request-{{ $request->id }}-match-{{ $match->id }}"
                                                    wire:click="selectHymnRequestTarget({{ $request->id }}, {{ $match->id }})"
                                                    class="block w-full
                                                           rounded-lg border
                                                           px-3 py-3
                                                           text-left
                                                           {{ $isSelected
                                                                ? 'border-sky-500 bg-sky-100 dark:border-sky-500 dark:bg-sky-500/15'
                                                                : 'border-gray-200 bg-white hover:border-sky-300 hover:bg-sky-50 dark:border-gray-700 dark:bg-gray-950 dark:hover:border-sky-700 dark:hover:bg-sky-950/30' }}"
                                                >
                                                    <div
                                                        class="flex
                                                               items-start
                                                               gap-3"
                                                    >
                                                        <span
                                                            class="mt-1
                                                                   flex h-4 w-4
                                                                   shrink-0
                                                                   items-center
                                                                   justify-center
                                                                   rounded-full
                                                                   border
                                                                   {{ $isSelected
                                                                        ? 'border-sky-700 bg-sky-700 dark:border-sky-300 dark:bg-sky-300'
                                                                        : 'border-gray-400 dark:border-gray-600' }}"
                                                        >
                                                        </span>

                                                        <div class="min-w-0">
                                                            <div
                                                                class="font-bold
                                                                       text-gray-950
                                                                       dark:text-white"
                                                            >
                                                                {{ $match->title }}
                                                            </div>

                                                            <div
                                                                class="mt-1
                                                                       text-xs
                                                                       text-gray-500
                                                                       dark:text-gray-400"
                                                            >
                                                                @if (
                                                                    filled(
                                                                        $match->language
                                                                    )
                                                                )
                                                                    {{ ucfirst($match->language) }}
                                                                @endif

                                                                @if ($bookLabels !== '')
                                                                    @if (
                                                                        filled(
                                                                            $match->language
                                                                        )
                                                                    )
                                                                        ·
                                                                    @endif

                                                                    {{ $bookLabels }}
                                                                @endif

                                                                @if ($sourceLabels !== '')
                                                                    @if (
                                                                        filled(
                                                                            $match->language
                                                                        )
                                                                        || $bookLabels !== ''
                                                                    )
                                                                        ·
                                                                    @endif

                                                                    {{ $sourceLabels }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </button>
                                            @empty
                                                <div
                                                    class="rounded-lg
                                                           border
                                                           border-dashed
                                                           border-gray-300
                                                           px-3 py-5
                                                           text-center
                                                           text-sm
                                                           text-gray-500
                                                           dark:border-gray-700
                                                           dark:text-gray-400"
                                                >
                                                    No existing Hymns match
                                                    this search.

                                                    <div
                                                        class="mt-1 text-xs"
                                                    >
                                                        Choose
                                                        <strong>
                                                            Create New Hymn
                                                        </strong>
                                                        if this is genuinely
                                                        a new canonical song.
                                                    </div>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @else
                                    <div
                                        style="color: #064e3b !important;"
                                        class="mt-4 rounded-lg
                                               border
                                               border-emerald-200
                                               bg-emerald-50
                                               px-3 py-3
                                               text-sm
                                               text-emerald-950
                                               dark:border-emerald-900
                                               dark:bg-emerald-950/30
                                               dark:text-emerald-200"
                                    >
                                        A new canonical Hymn record
                                        will be created.

                                        @if (
                                            filled(
                                                $request->source_url
                                            )
                                        )
                                            Its source link will also
                                            be attached to the new Hymn.
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div
                                class="mt-3 flex
                                       flex-wrap justify-end
                                       gap-2"
                            >
                                <x-filament::button
                                    color="danger"
                                    outlined
                                    wire:click="rejectHymnRequest({{ $request->id }})"
                                    wire:confirm="Reject this Hymn Addition Request? The Hymn Catalog will remain unchanged."
                                >
                                    Reject
                                </x-filament::button>

                                <x-filament::button
                                    color="success"
                                    wire:click="approveHymnRequest({{ $request->id }})"
                                    wire:confirm="{{ $resolutionMode === 'new'
                                        ? 'Approve this request and create a new canonical Hymn?'
                                        : 'Approve this request and link its source to the selected existing Hymn?' }}"
                                >
                                    {{
                                        $resolutionMode === 'new'
                                            ? 'Approve & Create'
                                            : 'Approve & Link'
                                    }}
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
