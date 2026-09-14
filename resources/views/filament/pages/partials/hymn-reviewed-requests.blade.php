@php
    $reviewedHymnRequests =
        $this->reviewedHymnRequests();
@endphp

<div
    class="rounded-2xl border border-gray-200
           bg-white p-6 shadow-sm
           dark:border-gray-700
           dark:bg-gray-900"
>
    <div>
        <h3
            class="text-lg font-bold
                   text-gray-950 dark:text-white"
        >
            Reviewed Hymn Requests
        </h3>

        <p
            class="mt-1 text-sm
                   text-gray-500
                   dark:text-gray-400"
        >
            All approved and rejected Hymn Addition
            Requests are kept here as a permanent
            review history.
        </p>
    </div>

    <div
        class="mt-5 grid gap-3
               md:grid-cols-3"
    >
        <input
            type="search"
            wire:model.live.debounce.400ms="reviewedSearch"
            placeholder="Search requests or linked hymns..."
            class="block w-full rounded-xl
                   border border-gray-300
                   bg-white px-4 py-3
                   text-sm text-gray-900
                   dark:border-gray-700
                   dark:bg-gray-950
                   dark:text-gray-100"
        >

        <select
            wire:model.live="reviewedStatus"
            class="block w-full rounded-xl
                   border-gray-300 bg-white
                   text-sm text-gray-950
                   dark:border-gray-700
                   dark:bg-gray-950
                   dark:text-white"
        >
            <option value="all">
                All Reviewed
            </option>

            <option value="approved">
                Approved
            </option>

            <option value="rejected">
                Rejected
            </option>
        </select>

        <select
            wire:model.live="reviewedSource"
            class="block w-full rounded-xl
                   border-gray-300 bg-white
                   text-sm text-gray-950
                   dark:border-gray-700
                   dark:bg-gray-950
                   dark:text-white"
        >
            <option value="all">
                All Sources
            </option>

            <option value="soundcloud">
                SoundCloud
            </option>

            <option value="youtube">
                YouTube
            </option>

            <option value="other">
                Other
            </option>

            <option value="no_source">
                No Source Link
            </option>
        </select>
    </div>

    <div class="mt-5 space-y-4">
        @forelse (
            $reviewedHymnRequests
            as $request
        )
            @php
                $approved =
                    $request->status
                    ===
                    \App\Models\HymnAdditionRequest
                        ::STATUS_APPROVED;

                $sourceLabel =
                    $this
                        ->reviewedRequestSourceLabel(
                            $request
                        );
            @endphp

            <div
                wire:key="reviewed-hymn-request-{{ $request->id }}"
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
                                class="text-base font-bold
                                       text-gray-950
                                       dark:text-white"
                            >
                                {{ $request->title }}
                            </h4>

                            @if ($approved)
                                <span
                                    style="color: #022c22 !important;"
                                    class="rounded-full
                                           bg-emerald-100
                                           px-2 py-0.5
                                           text-[10px]
                                           font-bold uppercase
                                           text-emerald-950
                                           ring-1
                                           ring-emerald-200
                                           dark:bg-emerald-100
                                           dark:text-emerald-950"
                                >
                                    Approved
                                </span>
                            @else
                                <span
                                    style="color: #450a0a !important;"
                                    class="rounded-full
                                           bg-red-100
                                           px-2 py-0.5
                                           text-[10px]
                                           font-bold uppercase
                                           text-red-950
                                           ring-1 ring-red-200
                                           dark:bg-red-100
                                           dark:text-red-950"
                                >
                                    Rejected
                                </span>
                            @endif

                            <span
                                style="color: #082f49 !important;"
                                class="rounded-full
                                       bg-sky-100
                                       px-2 py-0.5
                                       text-[10px]
                                       font-bold
                                       text-sky-950
                                       ring-1 ring-sky-200
                                       dark:bg-sky-100
                                       dark:text-sky-950"
                            >
                                {{ $sourceLabel }}
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

                            @if ($request->reviewer)
                                &nbsp;·&nbsp;
                                Reviewed by:
                                {{ $request->reviewer->name }}
                            @endif

                            @if ($request->reviewed_at)
                                &nbsp;·&nbsp;
                                {{
                                    $request->reviewed_at
                                        ->format(
                                            'M j, Y g:i A'
                                        )
                                }}
                            @endif
                        </p>

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
                            $approved
                            && $request->createdHymn
                        )
                            <div
                                style="
                                    background-color: #ecfdf5 !important;
                                    border-color: #a7f3d0 !important;
                                    color: #111827 !important;
                                "
                                class="mt-4 rounded-lg
                                       border
                                       border-emerald-200
                                       bg-emerald-50
                                       p-3"
                            >
                                <div
                                    style="color: #064e3b !important;"
                                    class="text-xs
                                           font-bold uppercase"
                                >
                                    Canonical Hymn
                                </div>

                                <div
                                    style="color: #111827 !important;"
                                    class="mt-1 font-bold"
                                >
                                    {{
                                        $request
                                            ->createdHymn
                                            ->title
                                    }}
                                </div>

                                @if (
                                    $request
                                        ->createdHymn
                                        ->sources
                                        ->isNotEmpty()
                                )
                                    <div
                                        style="color: #4b5563 !important;"
                                        class="mt-1 text-xs"
                                    >
                                        Sources:
                                        {{
                                            $request
                                                ->createdHymn
                                                ->sources
                                                ->pluck(
                                                    'label'
                                                )
                                                ->filter()
                                                ->unique()
                                                ->implode(
                                                    ' · '
                                                )
                                        }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div
                        class="flex shrink-0
                               flex-wrap gap-2"
                    >
                        @php
                        $requestCreatedOwnHymn =
                            $approved
                            && $request->createdHymn
                            && $request
                                ->createdHymn
                                ->source === 'manual'
                            && $request
                                ->createdHymn
                                ->source_id
                                ===
                                'request:' . $request->id;

                        $deleteLabel =
                            $requestCreatedOwnHymn
                                ? 'Delete Record & Hymn'
                                : 'Delete Record';

                        $deleteConfirm =
                            $requestCreatedOwnHymn
                                ? 'Delete this Reviewed Request AND the Hymn it created? Its attached source links will also be deleted.'
                                : 'Delete this Reviewed Request record? Any existing canonical Hymn it was linked to will remain unchanged.';
                    @endphp

                    <div
                        class="flex shrink-0
                               flex-wrap gap-2"
                    >
                        @if (
                            $approved
                            && $request->createdHymn
                        )
                            <button
                                type="button"
                                style="color: #082f49 !important;"
                                wire:click="showReviewedHymn({{ $request->id }})"
                                x-on:click="
                                    setTimeout(
                                        () => document
                                            .getElementById(
                                                'hymn-catalog'
                                            )
                                            ?.scrollIntoView({
                                                behavior: 'smooth'
                                            }),
                                        300
                                    )
                                "
                                class="rounded-lg border
                                       border-sky-300
                                       bg-sky-100
                                       px-4 py-2
                                       text-sm font-bold
                                       hover:bg-sky-200"
                            >
                                View Hymn
                            </button>
                        @endif

                        <button
                            type="button"
                            style="color: #450a0a !important;"
                            wire:click="deleteReviewedHymnRequest({{ $request->id }})"
                            wire:confirm="{{ $deleteConfirm }}"
                            class="rounded-lg border
                                   border-red-300
                                   bg-red-50
                                   px-4 py-2
                                   text-sm font-bold
                                   hover:bg-red-100"
                        >
                            {{ $deleteLabel }}
                        </button>
                    </div>

                        <button
                            type="button"
                            wire:click="deleteReviewedHymnRequest({{ $request->id }})"
                            wire:confirm="Delete this reviewed Hymn Request record? Any canonical Hymn and linked sources will remain unchanged."
                            class="rounded-lg border
                                   border-red-300
                                   bg-red-50
                                   px-4 py-2
                                   text-sm font-bold
                                   text-red-900
                                   hover:bg-red-100
                                   dark:border-red-300
                                   dark:bg-red-50
                                   dark:text-red-900"
                            style="color: #450a0a !important;"
                        >
                            Delete Record
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div
                class="rounded-xl border
                       border-dashed
                       border-gray-300
                       px-5 py-8
                       text-center text-sm
                       text-gray-500
                       dark:border-gray-700
                       dark:text-gray-400"
            >
                No Reviewed Hymn Requests
                match these filters.
            </div>
        @endforelse
    </div>
</div>
