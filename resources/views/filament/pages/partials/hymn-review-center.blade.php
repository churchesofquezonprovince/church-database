@php
    $variantReview =
        $this->variantReviewEntries();

    $provisionalReview =
        $this->provisionalReviewEntries();

    $unresolved =
        $this->unresolvedEntries();

    $reviewedEntry =
        $this->reviewedEntry();

    $matches =
        $this->hymnalReviewMatches();

    $selectedHymn =
        $this->selectedHymnalReviewHymn();

    $classicCounterpart =
        $this->classicCounterpartForReviewedEntry();

    $variantReviewUndo =
        $this->variantReviewUndoSummary();
@endphp

<div
    class="space-y-6"
    x-on:scroll-to-hymnal-review.window="
        $nextTick(() => {
            setTimeout(() => {
                document
                    .getElementById(
                        'hymnal-review-form'
                    )
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
            }, 50);
        })
    "
    x-on:scroll-to-variant-review.window="
        $nextTick(() => {
            setTimeout(() => {
                document
                    .getElementById(
                        'variant-review-table'
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
                   border-gray-200
                   bg-white p-6
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
                    <h2
                        id="variant-review-table"
            class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        Variant Review
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        The canonical Hymn family is known,
                        but the correct tune or version still
                        needs an explicit variant assignment.
                    </p>
                </div>

                @if ($variantReviewUndo)
                    <div
                        class="flex flex-col items-start
                               gap-1 lg:items-end"
                    >
                        <x-filament::button
                            wire:click="rollbackLastVariantReviewSave"
                            wire:confirm="Rollback ONLY the most recent Variant Review save and restore its exact previous variant, source, and Hymnal.net assignments?"
                            icon="heroicon-m-arrow-uturn-left"
                            color="gray"
                            size="sm"
                        >
                            Rollback Last Save
                        </x-filament::button>

                        <div
                            class="max-w-xs text-xs
                                   text-gray-500
                                   dark:text-gray-400
                                   lg:text-right"
                        >
                            Last save:
                            {{
                                $variantReviewUndo[
                                    'label'
                                ]
                            }}
                        </div>
                    </div>
                @endif
            </div>

            <div
                class="overflow-x-auto"
                @if ($variantReview->count() >= 10)
                    style="
                        max-height: 34rem;
                        overflow-y: scroll;
                        scrollbar-gutter: stable;
                    "
                @endif
            >
                <table class="w-full text-left text-sm">
                    <thead style="position: sticky; top: 0; z-index: 20; background: #ffffff; color: #111827 !important;">
                        <tr
                            class="border-b
                                   border-gray-200
                                   dark:border-white/10"
                        >
                            <th class="px-4 py-3">
                                Source / Number
                            </th>

                            <th class="px-4 py-3">
                                Hymnal.net
                            </th>

                            <th class="px-4 py-3">
                                Canonical Hymn
                            </th>

                            <th class="px-4 py-3">
                                Variants
                            </th>

                            <th
                                class="px-4 py-3
                                       text-right"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($variantReview as $entry)
                            <tr
                                class="border-b
                                       border-gray-100
                                       dark:border-white/5"
                            >
                                <td
                                    class="px-4 py-3
                                           align-top
                                           font-bold"
                                >
                                    {{
                                        strtoupper(
                                            $entry->collection_code
                                        )
                                    }}{{ $entry->number }}

                                    @if ($entry->section_code)
                                        <div
                                            class="mt-1 text-xs
                                                   font-normal
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            {{
                                                str(
                                                    $entry->section_code
                                                )
                                                    ->replace(
                                                        '_',
                                                        ' '
                                                    )
                                                    ->title()
                                            }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div
                                        class="font-semibold
                                               text-gray-950
                                               dark:text-white"
                                    >
                                        {{ $entry->title }}
                                    </div>

                                    <a
                                        href="{{ $entry->source_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-1 inline-block
                                               text-xs
                                               font-semibold
                                               text-primary-600
                                               hover:underline
                                               dark:text-primary-400"
                                    >
                                        Hymnal.net
                                    </a>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{
                                        $entry
                                            ->matchedHymn
                                            ?->title
                                        ?? '—'
                                    }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    @php
                                        $entryVariants =
                                            $entry
                                                ->matchedHymn
                                                ?->variants
                                            ?? collect();

                                        if (
                                            $entry->section_code
                                                === 'new_tunes'
                                        ) {
                                            $entryVariants =
                                                $entryVariants
                                                    ->where(
                                                        'variant_type',
                                                        'tune'
                                                    )
                                                    ->values();
                                        }
                                    @endphp

                                    @forelse (
                                        $entryVariants
                                        as $variant
                                    )
                                        <div
                                            class="mb-1 text-xs
                                                   font-semibold"
                                        >
                                            {{ $variant->label }}
                                        </div>
                                    @empty
                                        <div
                                            class="text-xs
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            No tune variants yet
                                        </div>
                                    @endforelse
                                </td>

                                <td
                                    class="px-4 py-3
                                           text-right
                                           align-top"
                                >
                                    <button
                                        type="button"
                                        wire:click="beginReview({{ $entry->id }})"
                                        x-on:click="
                                            setTimeout(
                                                () =>
                                                    document
                                                        .getElementById(
                                                            'hymnal-review-form'
                                                        )
                                                        ?.scrollIntoView({
                                                            behavior: 'smooth',
                                                            block: 'start'
                                                        }),
                                                200
                                            )
                                        "
                                        class="rounded-lg
                                               border
                                               border-sky-300
                                               bg-sky-50
                                               px-3 py-1.5
                                               text-xs
                                               font-bold
                                               dark:border-sky-300
                                               dark:bg-sky-50
                                               dark:text-sky-950"
                                        style="
                                            color: #082f49 !important;
                                        "
                                    >
                                        Choose Variant
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-4 py-8
                                           text-center
                                           text-gray-500"
                                >
                                    No Hymns need variant review.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

<div
            class="rounded-2xl border
                   border-amber-300
                   bg-amber-50 p-6
                   text-amber-950
                   shadow-sm
                   dark:border-amber-300
                   dark:bg-amber-50
                   dark:text-amber-950"
        >
            <div class="mb-5">
                <h2
                    class="text-lg font-bold"
                    style="
                        color: #78350f !important;
                    "
                >
                    Provisional Review
                </h2>

                <p
                    class="mt-1 text-sm"
                    style="
                        color: #92400e !important;
                    "
                >
                    These links were intentionally made
                    provisionally and still need future
                    musician or developer verification.
                </p>
            </div>

            <div
                class="overflow-x-auto"
                @if ($provisionalReview->count() >= 10)
                    style="
                        max-height: 34rem;
                        overflow-y: scroll;
                        scrollbar-gutter: stable;
                    "
                @endif
            >
                <table
                    class="w-full text-left text-sm
                           text-amber-950
                           dark:text-amber-950"
                >
                    <thead style="position: sticky; top: 0; z-index: 20; background: #fffbeb;">
                        <tr
                            class="border-b
                                   border-amber-200
                                   dark:border-amber-300"
                        >
                            <th
                                class="px-4 py-3"
                                style="color: #451a03 !important;"
                            >
                                Hymnal #
                            </th>

                            <th
                                class="px-4 py-3"
                                style="color: #451a03 !important;"
                            >
                                Hymnal.net
                            </th>

                            <th
                                class="px-4 py-3"
                                style="color: #451a03 !important;"
                            >
                                Canonical Hymn
                            </th>

                            <th
                                class="px-4 py-3"
                                style="color: #451a03 !important;"
                            >
                                Current Variant
                            </th>

                            <th
                                class="px-4 py-3
                                       text-right"
                                style="color: #451a03 !important;"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse (
                            $provisionalReview
                            as $entry
                        )
                            <tr
                                class="border-b
                                       border-amber-100
                                       dark:border-amber-200"
                            >
                                <td
                                    class="px-4 py-3
                                           align-top
                                           font-bold"
                                    style="color: #451a03 !important;"
                                >
                                    {{ $entry->number }}
                                </td>

                                <td
                                    class="px-4 py-3
                                           align-top"
                                    style="color: #451a03 !important;"
                                >
                                    <div class="font-semibold">
                                        {{ $entry->title }}
                                    </div>

                                    <a
                                        href="{{ $entry->source_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-1 inline-block
                                               text-xs
                                               font-semibold
                                               underline"
                                        style="color: #78350f !important;"
                                    >
                                        Open Hymnal.net
                                    </a>
                                </td>

                                <td
                                    class="px-4 py-3
                                           align-top"
                                    style="color: #451a03 !important;"
                                >
                                    {{
                                        $entry
                                            ->matchedHymn
                                            ?->title
                                        ?? '—'
                                    }}
                                </td>

                                <td
                                    class="px-4 py-3
                                           align-top"
                                    style="color: #451a03 !important;"
                                >
                                    {{
                                        $entry
                                            ->matchedVariant
                                            ?->label
                                        ?? 'Not selected'
                                    }}

                                    <div
                                        class="mt-1 text-xs
                                               font-semibold"
                                        style="
                                            color: #b45309 !important;
                                        "
                                    >
                                        Needs review
                                    </div>
                                </td>

                                <td
                                    class="px-4 py-3
                                           text-right
                                           align-top"
                                    style="color: #451a03 !important;"
                                >
                                    <button
                                        type="button"
                                        wire:click="beginReview({{ $entry->id }})"
                                        x-on:click="
                                            setTimeout(
                                                () =>
                                                    document
                                                        .getElementById(
                                                            'hymnal-review-form'
                                                        )
                                                        ?.scrollIntoView({
                                                            behavior: 'smooth',
                                                            block: 'start'
                                                        }),
                                                200
                                            )
                                        "
                                        class="rounded-lg
                                               border
                                               border-amber-400
                                               bg-white
                                               px-3 py-1.5
                                               text-xs
                                               font-bold"
                                        style="
                                            color: #78350f !important;
                                        "
                                    >
                                        Review Again
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-4 py-8
                                           text-center"
                                >
                                    No provisional Hymn links
                                    need review.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                class="mb-5 flex flex-col gap-4
                       lg:flex-row
                       lg:items-end
                       lg:justify-between"
            >
                <div>
                    <h2
                        class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        Needs Review
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Unmatched, ambiguous, and conflicting
                        Hymnal.net entries from synchronized
                        collections appear here.
                    </p>

                    <p
                        class="mt-1 text-xs
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Showing
                        <strong>
                            {{ number_format($unresolved->count()) }}
                        </strong>
                        {{
                            str('entry')->plural(
                                $unresolved->count()
                            )
                        }}
                        for the selected section.
                    </p>
                </div>

                <div
                    class="w-full lg:w-64"
                >
                    <label
                        class="block text-xs font-bold
                               uppercase tracking-wide
                               text-gray-600
                               dark:text-gray-300"
                    >
                        Section
                    </label>

                    <select
                        wire:model.live="needsReviewSection"
                        class="mt-1 block w-full
                               rounded-xl border-gray-300
                               bg-white text-sm
                               text-gray-950 shadow-sm
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-white"
                    >
                        <option value="all">
                            All Sections
                        </option>

                        <option value="classic">
                            Classic Hymns
                        </option>

                        <option value="new_tunes">
                            New Tunes
                        </option>

                        <option value="alternate_tunes">
                            Alternate Tunes
                        </option>

                        <option value="new_songs">
                            New Songs
                        </option>

                        <option value="children">
                            Children's Songs
                        </option>
                    </select>
                </div>
            </div>

            <div
                class="overflow-x-auto"
                @if ($unresolved->count() >= 10)
                    style="
                        max-height: 34rem;
                        overflow-y: scroll;
                        scrollbar-gutter: stable;
                    "
                @endif
            >
                <table class="w-full text-left text-sm">
                    <thead style="position: sticky; top: 0; z-index: 20; background: #ffffff; color: #111827 !important;">
                        <tr
                            class="border-b
                                   border-gray-200
                                   dark:border-white/10"
                        >
                            <th class="px-4 py-3">
                                Source / Number
                            </th>

                            <th class="px-4 py-3">
                                Hymnal.net Title
                            </th>

                            <th class="px-4 py-3">
                                Status
                            </th>

                            <th
                                class="px-4 py-3
                                       text-right"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($unresolved as $entry)
                            <tr
                                class="border-b
                                       border-gray-100
                                       dark:border-white/5"
                            >
                                <td
                                    class="px-4 py-3
                                           align-top
                                           font-bold"
                                >
                                    {{
                                        strtoupper(
                                            $entry->collection_code
                                        )
                                    }}{{ $entry->number }}

                                    @if ($entry->section_code)
                                        <div
                                            class="mt-1 text-xs
                                                   font-normal
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            {{
                                                str(
                                                    $entry->section_code
                                                )
                                                    ->replace(
                                                        '_',
                                                        ' '
                                                    )
                                                    ->title()
                                            }}
                                        </div>
                                    @endif
                                </td>

                                <td
                                    class="px-4 py-3
                                           align-top"
                                >
                                    <div
                                        class="font-semibold
                                               text-gray-950
                                               dark:text-white"
                                    >
                                        {{ $entry->title }}
                                    </div>

                                    <a
                                        href="{{ $entry->source_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-1 inline-block
                                               text-xs
                                               font-semibold
                                               text-primary-600
                                               hover:underline
                                               dark:text-primary-400"
                                    >
                                        Hymnal.net
                                    </a>
                                </td>

                                <td
                                    class="px-4 py-3
                                           align-top"
                                >
                                    <span
                                        class="rounded-full
                                               bg-amber-100
                                               px-2.5 py-1
                                               text-xs
                                               font-bold
                                               dark:bg-amber-100
                                               dark:text-amber-950"
                                        style="
                                            color: #78350f !important;
                                        "
                                    >
                                        {{
                                            str(
                                                $entry->match_status
                                            )
                                                ->replace(
                                                    '_',
                                                    ' '
                                                )
                                                ->title()
                                        }}
                                    </span>
                                </td>

                                <td
                                    class="px-4 py-3
                                           text-right
                                           align-top"
                                >
                                    <button
                                        type="button"
                                        wire:click="beginReview({{ $entry->id }})"
                                        x-on:click="
                                            setTimeout(
                                                () =>
                                                    document
                                                        .getElementById(
                                                            'hymnal-review-form'
                                                        )
                                                        ?.scrollIntoView({
                                                            behavior: 'smooth',
                                                            block: 'start'
                                                        }),
                                                200
                                            )
                                        "
                                        class="rounded-lg
                                               border
                                               border-sky-300
                                               bg-sky-50
                                               px-3 py-1.5
                                               text-xs
                                               font-bold
                                               dark:border-sky-300
                                               dark:bg-sky-50
                                               dark:text-sky-950"
                                        style="
                                            color: #082f49 !important;
                                        "
                                    >
                                        Review
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-4 py-8
                                           text-center
                                           text-gray-500"
                                >
                                    No Hymnal.net entries currently
                                    require canonical review.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
</div>

<style>
    /*
     * These cards intentionally remain light even
     * when Filament is in dark mode.
     */
    .hymn-review-light-green,
    .hymn-review-light-green * {
        color: #064e3b !important;
    }

    .hymn-review-light-sky,
    .hymn-review-light-sky * {
        color: #082f49 !important;
    }

    .hymn-review-light-amber,
    .hymn-review-light-amber * {
        color: #78350f !important;
    }
</style>

@if ($reviewedEntry)
    <div
        id="hymnal-review-form"
        class="mt-6 rounded-2xl border
               border-gray-200 bg-white p-6
               shadow-sm
               dark:border-gray-700
               dark:bg-gray-900"
    >
        <div
            class="flex flex-col gap-3
                   lg:flex-row
                   lg:items-start
                   lg:justify-between"
        >
            <div>
                <p
                    class="text-xs font-bold uppercase
                           tracking-wide text-primary-600
                           dark:text-primary-300"
                >
                    Central Hymn Review
                </p>

                <h3
                    class="mt-1 text-lg font-bold
                           text-gray-950
                           dark:text-white"
                >
                    {{ $reviewedEntry->title ?? 'Untitled Hymn' }}
                </h3>

                <p
                    class="mt-1 text-sm
                           text-gray-500
                           dark:text-gray-400"
                >
                    Hymnal.net
                    {{
                        strtoupper(
                            $reviewedEntry
                                ->collection_code
                        )
                    }}{{ $reviewedEntry->number }}

                    @if ($reviewedEntry->section_code)
                        ·
                        {{
                            str(
                                $reviewedEntry
                                    ->section_code
                            )
                                ->replace('_', ' ')
                                ->title()
                        }}
                    @endif
                </p>
            </div>

            @if ($reviewedEntry->source_url)
                <a
                    href="{{ $reviewedEntry->source_url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hymn-review-light-sky
                           inline-flex rounded-lg border
                           border-sky-300 bg-sky-100
                           px-3 py-2 text-sm font-bold
                           text-sky-950
                           hover:bg-sky-200
                           dark:border-sky-300
                           dark:bg-sky-100
                           dark:text-sky-950"
                >
                    Open Hymnal.net
                </a>
            @endif
        </div>

        <div class="mt-6">
            <label
                class="block text-sm font-bold
                       text-gray-700
                       dark:text-gray-200"
            >
                Find Existing Canonical Hymn
            </label>

            <input
                type="search"
                wire:model.live.debounce.400ms="hymnalReviewSearch"
                placeholder="Search title, any source lyrics, source ID, or hymn number..."
                class="mt-2 block w-full rounded-xl
                       border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-950
                       shadow-sm
                       dark:border-gray-700
                       dark:bg-gray-950
                       dark:text-white"
            >
        </div>

        @if ($hymnalReviewSuggestions !== [])
            <div
                class="hymn-review-light-sky
                       mt-5 rounded-xl border
                       border-sky-300 bg-sky-50
                       p-4 text-sky-950
                       dark:border-sky-300
                       dark:bg-sky-50
                       dark:text-sky-950"
            >
                <div
                    class="flex flex-col gap-1
                           sm:flex-row
                           sm:items-start
                           sm:justify-between"
                >
                    <div>
                        <div class="font-bold">
                            Suggested Matches
                        </div>

                        <p class="mt-1 text-xs">
                            Title-based review aid only.
                            A suggestion never links an
                            entry automatically.
                        </p>
                    </div>

                    <div
                        class="text-xs font-semibold
                               text-sky-800"
                    >
                        Top {{
                            count(
                                $hymnalReviewSuggestions
                            )
                        }}
                    </div>
                </div>

                <div class="mt-4 grid gap-3">
                    @foreach (
                        $hymnalReviewSuggestions
                        as $suggestion
                    )
                        <div
                            class="block w-full rounded-xl
                                   border p-4 text-left
                                   transition
                                   {{
                                       $selectedHymnId
                                           === $suggestion['hymn_id']
                                               ? 'hymn-review-light-green border-emerald-400 bg-emerald-100'
                                               : 'border-sky-200 bg-white'
                                   }}"
                        >
                            <div
                                class="flex flex-col gap-2
                                       sm:flex-row
                                       sm:items-start
                                       sm:justify-between"
                            >
                                <div>
                                    <div class="font-bold">
                                        {{
                                            $suggestion[
                                                'title'
                                            ]
                                        }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs
                                               font-semibold"
                                    >
                                        Canonical Hymn
                                        #{{
                                            $suggestion[
                                                'hymn_id'
                                            ]
                                        }}

                                        @if (
                                            $suggestion[
                                                'language'
                                            ]
                                        )
                                            ·
                                            {{
                                                $suggestion[
                                                    'language'
                                                ]
                                            }}
                                        @endif
                                    </div>
                                </div>

                                <div
                                    class="shrink-0 rounded-full
                                           border border-sky-300
                                           bg-sky-100
                                           px-2.5 py-1
                                           text-xs font-bold"
                                >
                                    Title score:
                                    {{
                                        number_format(
                                            (float)
                                                $suggestion[
                                                    'score'
                                                ],
                                            1
                                        )
                                    }}%
                                </div>
                            </div>

                            <div
                                class="mt-2 text-xs
                                       text-sky-900"
                            >
                                {{
                                    $suggestion[
                                        'reason'
                                    ]
                                }}
                            </div>

                            @if (
                                $suggestion[
                                    'songbase_ids'
                                ] !== []
                            )
                                <div
                                    class="mt-2 flex flex-wrap
                                           items-center gap-x-2
                                           gap-y-1 text-xs
                                           text-sky-900"
                                >
                                    <strong>
                                        Songbase:
                                    </strong>

                                    @foreach (
                                        $suggestion[
                                            'songbase_ids'
                                        ]
                                        as $songbaseId
                                    )
                                        <a
                                            href="https://songbase.life/{{ $songbaseId }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-bold
                                                   text-sky-700
                                                   underline
                                                   decoration-sky-300
                                                   underline-offset-2
                                                   hover:text-sky-950"
                                            title="Open Songbase #{{ $songbaseId }}"
                                        >
                                            #{{ $songbaseId }}
                                        </a>

                                        @unless ($loop->last)
                                            <span
                                                class="text-sky-400"
                                            >
                                                ·
                                            </span>
                                        @endunless
                                    @endforeach
                                </div>
                            @endif

                            @if (
                                $suggestion[
                                    'books'
                                ] !== []
                            )
                                <div
                                    class="mt-1 text-xs
                                           text-sky-900"
                                >
                                    <strong>
                                        Books:
                                    </strong>

                                    {{
                                        implode(
                                            ' · ',
                                            $suggestion[
                                                'books'
                                            ]
                                        )
                                    }}
                                </div>
                            @endif

                            @if (
                                $suggestion[
                                    'active_variant_count'
                                ] > 0
                            )
                                <div
                                    class="mt-1 text-xs
                                           font-semibold
                                           text-sky-900"
                                >
                                    {{
                                        $suggestion[
                                            'active_variant_count'
                                        ]
                                    }}
                                    active
                                    {{
                                        str(
                                            'variant'
                                        )->plural(
                                            $suggestion[
                                                'active_variant_count'
                                            ]
                                        )
                                    }}
                                </div>
                            @endif

                            <div class="mt-3">
                                <button
                                    type="button"
                                    wire:click="selectHymnalReviewHymn({{ $suggestion['hymn_id'] }})"
                                    class="rounded-lg border
                                           px-3 py-1.5
                                           text-xs font-bold
                                           transition
                                           {{
                                               $selectedHymnId
                                                   === $suggestion['hymn_id']
                                                       ? 'border-emerald-500 bg-emerald-200 text-emerald-950'
                                                       : 'border-sky-300 bg-sky-100 text-sky-950 hover:bg-sky-200'
                                           }}"
                                >
                                    {{
                                        $selectedHymnId
                                            ===
                                            $suggestion[
                                                'hymn_id'
                                            ]
                                                ? 'Selected'
                                                : 'Use Candidate'
                                    }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($matches->isNotEmpty())
            <div class="mt-4 space-y-2">
                @foreach ($matches as $hymn)
                    <button
                        type="button"
                        wire:click="selectHymnalReviewHymn({{ $hymn->id }})"
                        class="block w-full rounded-xl border
                               p-4 text-left transition
                               {{
                                   $selectedHymnId === $hymn->id
                                       ? 'hymn-review-light-green border-emerald-400 bg-emerald-100 text-emerald-950 dark:border-emerald-300 dark:bg-emerald-100 dark:text-emerald-950'
                                       : 'border-gray-200 bg-white text-gray-950 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:hover:bg-gray-900'
                               }}"
                    >
                        <div class="font-bold">
                            {{ $hymn->title }}
                        </div>

                        <div
                            class="mt-1 text-xs
                                   {{
                                       $selectedHymnId === $hymn->id
                                           ? 'text-emerald-800 dark:text-emerald-800'
                                           : 'text-gray-500 dark:text-gray-400'
                                   }}"
                        >
                            Canonical Hymn #{{ $hymn->id }}

                            @if ($hymn->language)
                                · {{ $hymn->language }}
                            @endif
                        </div>

                        @if ($hymn->bookEntries->isNotEmpty())
                            <div
                                class="mt-1 text-xs
                                       {{
                                           $selectedHymnId === $hymn->id
                                               ? 'text-emerald-800 dark:text-emerald-800'
                                               : 'text-gray-500 dark:text-gray-400'
                                       }}"
                            >
                                @foreach (
                                    $hymn->bookEntries->take(4)
                                    as $entry
                                )
                                    {{ $entry->hymnBook?->name }}
                                    #{{ $entry->number }}
                                    @unless ($loop->last)
                                        ·
                                    @endunless
                                @endforeach
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        @if (
            $reviewedEntry->match_status
                === 'unmatched'
            && $reviewedEntry->section_code
                !== 'new_tunes'
        )
            <div
                class="hymn-review-light-sky
                       mt-6 rounded-xl border
                       border-sky-300 bg-sky-50
                       p-5
                       text-sky-950
                       dark:border-sky-300
                       dark:bg-sky-50
                       dark:text-sky-950"
            >
                <div
                    class="text-sm font-bold
                           text-sky-950
                           dark:text-sky-950"
                >
                    No correct canonical Hymn exists?
                </div>

                <p
                    class="mt-1 text-sm
                           text-sky-900
                           dark:text-sky-900"
                >
                    Create a new canonical Hymn only
                    when this Hymnal.net entry represents
                    a genuine Hymn that does not already
                    exist in the CoQP catalog.
                </p>

                <label
                    class="mt-4 block text-xs
                           font-bold uppercase
                           tracking-wide
                           text-sky-950
                           dark:text-sky-950"
                >
                    New Canonical Hymn Title
                </label>

                <input
                    type="text"
                    wire:model="newCanonicalTitle"
                    class="mt-2 block w-full
                           rounded-xl border
                           border-gray-300 bg-white
                           px-4 py-3 text-sm
                           text-gray-950 shadow-sm
                           dark:border-gray-300
                           dark:bg-white
                           dark:text-gray-950"
                >

                <div
                    class="mt-3 rounded-lg border
                           border-gray-200 bg-white p-3
                           text-xs text-gray-700
                           dark:border-gray-200
                           dark:bg-white
                           dark:text-gray-700"
                >
                    <strong>Language:</strong>
                    English

                    <span class="mx-1">·</span>

                    <strong>Provider:</strong>
                    Hymnal.net

                    <span class="mx-1">·</span>

                    <strong>Variant:</strong>
                    None initially
                </div>

                <p
                    class="mt-3 text-xs
                           text-sky-900
                           dark:text-sky-900"
                >
                    This creates the canonical identity
                    and attaches this Hymnal.net source.
                    It does not import Hymnal.net lyrics
                    yet.
                </p>

                <div class="mt-4">
                    <x-filament::button
                        wire:click="createCanonicalFromReviewedEntry"
                        wire:confirm="Create a new canonical Hymn from this unmatched Hymnal.net entry?"
                        icon="heroicon-m-plus"
                        color="success"
                        :disabled="trim($newCanonicalTitle) === ''"
                    >
                        Create New Canonical Hymn
                    </x-filament::button>
                </div>
            </div>
        @endif

        @if (
            $reviewedEntry->section_code
                === 'new_tunes'
        )
            <div
                class="hymn-review-light-amber
                       mt-6 rounded-xl border
                       border-amber-300
                       bg-amber-100 p-4
                       text-amber-950
                       dark:border-amber-300
                       dark:bg-amber-100
                       dark:text-amber-950"
                style="
                    color: #78350f !important;
                "
            >
                <div class="font-bold">
                    New Tune
                </div>

                <p class="mt-1 text-sm">
                    This Hymnal.net entry belongs to an
                    existing canonical Hymn family.
                    Select an explicit tune variant.
                    It must not create another canonical
                    Hymn or attach at canonical level.
                </p>
            </div>
        @endif

        @if ($selectedHymn)
            <div
                class="hymn-review-light-green
                       mt-6 rounded-xl border
                       border-emerald-300
                       bg-emerald-100 p-4
                       text-emerald-950
                       dark:border-emerald-300
                       dark:bg-emerald-100
                       dark:text-emerald-950"
            >
                <div
                    class="text-xs font-bold uppercase
                           tracking-wide"
                >
                    Selected Canonical Hymn
                </div>

                <div class="mt-1 font-bold">
                    {{ $selectedHymn->title }}
                </div>

                @php
                    $reviewVariants =
                        $selectedHymn
                            ->variants
                            ->where(
                                'is_active',
                                true
                            )
                            ->values();

                    $reviewTuneVariants =
                        $reviewVariants
                            ->where(
                                'variant_type',
                                'tune'
                            )
                            ->values();

                    $selectableReviewVariants =
                        in_array(
                            $reviewedEntry->section_code,
                            [
                                'new_tunes',
                                'alternate_tunes',
                            ],
                            true
                        )
                            ? $reviewTuneVariants
                            : $reviewVariants;
                @endphp

                @if ($selectableReviewVariants->isNotEmpty())
                    <div class="mt-4">
                        <div
                            class="text-xs font-bold
                                   uppercase tracking-wide"
                        >
                            {{
                                match (
                                    $reviewedEntry->section_code
                                ) {
                                    'new_tunes' =>
                                        'New Tune Variant',

                                    'alternate_tunes' =>
                                        'Alternate Tune Variant',

                                    default =>
                                        'Choose Variant',
                                }
                            }}
                        </div>

                        <div
                            class="mt-2 grid gap-2
                                   md:grid-cols-2"
                        >
                            @foreach (
                                $selectableReviewVariants
                                as $variant
                            )
                                <label
                                    class="hymn-review-light-green
                                           flex cursor-pointer
                                           items-start gap-3
                                           rounded-lg border
                                           border-emerald-300
                                           bg-white p-3
                                           text-emerald-950"
                                >
                                    <input
                                        type="radio"
                                        wire:model.live="selectedVariantId"
                                        value="{{ $variant->id }}"
                                        class="mt-1"
                                    >

                                    <span>
                                        <span class="block font-bold">
                                            {{ $variant->label }}
                                        </span>

                                        <span class="block text-xs">
                                            {{
                                                ucfirst(
                                                    $variant->variant_type
                                                )
                                            }}

                                            @if ($variant->title_override)
                                                ·
                                                {{ $variant->title_override }}
                                            @endif
                                        </span>

                                        @if (
                                            $variant
                                                ->sources
                                                ->isNotEmpty()
                                        )
                                            <span
                                                class="mt-1 block
                                                       space-y-1"
                                            >
                                                @foreach (
                                                    $variant->sources
                                                    as $variantSource
                                                )
                                                    <span
                                                        class="block text-xs"
                                                    >
                                                        @if (
                                                            $variantSource
                                                                ->source_url
                                                        )
                                                            <a
                                                                href="{{
                                                                    $variantSource
                                                                        ->source_url
                                                                }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="font-semibold
                                                                       underline"
                                                            >
                                                                {{
                                                                    ucfirst(
                                                                        str_replace(
                                                                            '_',
                                                                            ' ',
                                                                            $variantSource
                                                                                ->provider
                                                                        )
                                                                    )
                                                                }}
                                                                ·
                                                                {{
                                                                    $variantSource
                                                                        ->external_id
                                                                }}
                                                            </a>
                                                        @else
                                                            {{
                                                                $variantSource
                                                                    ->external_id
                                                            }}
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    @if (
                        $reviewedEntry->section_code
                            === 'new_tunes'
                    )
                        <p class="mt-3 text-sm">
                            This Hymn has no active variants
                            yet. A tune variant must be
                            created before this New Tune can
                            be resolved.
                        </p>
                    @else
                        <p class="mt-3 text-sm">
                            This Hymn has no active variants.
                            The Hymnal.net source will be
                            linked at the canonical Hymn
                            level.
                        </p>
                    @endif
                @endif

                @if (
                    $reviewedEntry->section_code
                        === 'new_tunes'
                    && $classicCounterpart
                    && $reviewTuneVariants->isNotEmpty()
                )
                    <div
                        class="mt-5 rounded-xl border
                               border-sky-300
                               bg-sky-50 p-4
                               text-sky-950
                               dark:border-sky-300
                               dark:bg-sky-50
                               dark:text-sky-950"
                        style="
                            color: #082f49 !important;
                        "
                    >
                        <div class="text-sm font-bold">
                            Classic Hymnal.net Tune
                        </div>

                        <p class="mt-1 text-sm">
                            Explicitly assign
                            <strong>
                                h/{{ $classicCounterpart->number }}
                            </strong>
                            to the Tune variant verified
                            musically. Songbase ordering
                            is not assumed.
                        </p>

                        @if (
                            $classicCounterpart->source_url
                        )
                            <a
                                href="{{
                                    $classicCounterpart
                                        ->source_url
                                }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-2 inline-block
                                       text-xs font-bold
                                       underline"
                            >
                                Open Hymnal.net
                                h/{{ $classicCounterpart->number }}
                            </a>
                        @endif

                        <div
                            class="mt-3 grid gap-2
                                   md:grid-cols-2"
                        >
                            @foreach (
                                $reviewTuneVariants
                                as $variant
                            )
                                <label
                                    class="flex cursor-pointer
                                           items-start gap-3
                                           rounded-lg border
                                           border-sky-300
                                           bg-white p-3"
                                >
                                    <input
                                        type="radio"
                                        wire:model.live="selectedClassicVariantId"
                                        value="{{ $variant->id }}"
                                        class="mt-1"
                                    >

                                    <span>
                                        <span
                                            class="block font-bold"
                                        >
                                            {{ $variant->label }}
                                        </span>

                                        @if (
                                            $variant
                                                ->sources
                                                ->isNotEmpty()
                                        )
                                            <span
                                                class="mt-1 block
                                                       space-y-1"
                                            >
                                                @foreach (
                                                    $variant->sources
                                                    as $variantSource
                                                )
                                                    <span
                                                        class="block text-xs"
                                                    >
                                                        @if (
                                                            $variantSource
                                                                ->source_url
                                                        )
                                                            <a
                                                                href="{{
                                                                    $variantSource
                                                                        ->source_url
                                                                }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="font-semibold
                                                                       underline"
                                                            >
                                                                {{
                                                                    ucfirst(
                                                                        str_replace(
                                                                            '_',
                                                                            ' ',
                                                                            $variantSource
                                                                                ->provider
                                                                        )
                                                                    )
                                                                }}
                                                                ·
                                                                {{
                                                                    $variantSource
                                                                        ->external_id
                                                                }}
                                                            </a>
                                                        @else
                                                            {{
                                                                $variantSource
                                                                    ->external_id
                                                            }}
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (
                in_array(
                    $reviewedEntry->section_code,
                    [
                        'new_tunes',
                        'alternate_tunes',
                    ],
                    true
                )
            )
                    @if ($reviewTuneVariants->isEmpty())
                        <div
                            class="mt-5 rounded-xl border
                                   border-emerald-300
                                   bg-white p-4
                                   text-emerald-950"
                        >
                            <div
                                class="text-sm font-bold"
                            >
                                Establish Tune Variants
                            </div>

                            <p class="mt-1 text-sm">
                                This canonical Hymn has no
                                Tune variants yet.
                            </p>

                            <p class="mt-2 text-sm">
                                CoQP will establish the
                                existing/default tune as
                                <strong>Tune 1</strong>
                                and create
                                <strong>Tune 2</strong>
                                for this Hymnal.net tune page.
                            </p>

                            <div class="mt-4">
                                <x-filament::button
                                    wire:click="createNewTuneVariantFromReviewedEntry"
                                    wire:confirm="This Hymn has no Tune variants yet. Create Tune 1 for the existing/default tune and Tune 2 for this Hymnal.net tune page?"
                                    icon="heroicon-m-plus"
                                    color="success"
                                >
                                    Establish Tune 1
                                    &amp; Create Tune 2
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        <details
                            class="mt-5 rounded-xl border
                                   border-amber-300
                                   bg-amber-50 p-4
                                   text-amber-950
                                   dark:border-amber-300
                                   dark:bg-amber-50
                                   dark:text-amber-950"
                            style="
                                color: #78350f !important;
                            "
                        >
                            <summary
                                class="cursor-pointer
                                       font-bold"
                            >
                                None of the existing Tunes
                                match — create a brand-new
                                Tune
                            </summary>

                            <div class="mt-3">
                                <p class="text-sm">
                                    Use this only after
                                    comparing the Hymnal.net
                                    New Tune with every
                                    existing Tune shown above
                                    and confirming that none
                                    of them is the same tune.
                                </p>

                                <p
                                    class="mt-2 text-sm
                                           font-semibold"
                                >
                                    If an existing Tune
                                    matches, close this section,
                                    select that Tune, and use
                                    <strong>
                                        Link Existing
                                        Canonical Hymn
                                    </strong>.
                                </p>

                                <p class="mt-2 text-xs">
                                    Creating a brand-new Tune
                                    adds another structural
                                    variant to this canonical
                                    Hymn family.
                                </p>

                                <div class="mt-4">
                                    <x-filament::button
                                        wire:click="createNewTuneVariantFromReviewedEntry"
                                        wire:confirm="Are you sure NONE of the existing Tune variants match this Hymnal.net tune page? This will create a BRAND-NEW Tune variant."
                                        icon="heroicon-m-plus"
                                        color="danger"
                                    >
                                        Yes — Create a
                                        Brand-New Tune
                                    </x-filament::button>
                                </div>
                            </div>
                        </details>
                    @endif
                @endif
            </div>
        @endif

        <div
            class="mt-6 flex flex-wrap
                   items-center gap-3"
        >
            <x-filament::button
                wire:click="resolveReviewedEntry"
                icon="heroicon-m-check"
                :disabled="
                    ! $selectedHymnId
                    || (
                        $reviewedEntry->section_code
                            === 'new_tunes'
                        && (
                            ! $selectedVariantId
                            || (
                                $classicCounterpart
                                && ! $selectedClassicVariantId
                            )
                        )
                    )
                "
            >
                Link Existing Canonical Hymn
            </x-filament::button>

            <x-filament::button
                wire:click="cancelReview"
                color="gray"
            >
                Cancel
            </x-filament::button>
        </div>
    </div>
@endif
