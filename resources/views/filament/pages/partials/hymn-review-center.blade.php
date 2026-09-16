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
@endphp

<div class="space-y-6">
<div
            class="rounded-2xl border
                   border-gray-200
                   bg-white p-6
                   shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div class="mb-5">
                <h2
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

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
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

            <div class="overflow-x-auto">
                <table
                    class="w-full text-left text-sm
                           text-amber-950
                           dark:text-amber-950"
                >
                    <thead>
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
            <div class="mb-5">
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
                    Hymnal.net entries from all synchronized
                    collections appear here.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
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
                        $reviewedEntry->section_code
                            === 'new_tunes'
                                ? $reviewTuneVariants
                                : $reviewVariants;
                @endphp

                @if ($selectableReviewVariants->isNotEmpty())
                    <div class="mt-4">
                        <div
                            class="text-xs font-bold
                                   uppercase tracking-wide"
                        >
                            Choose Variant
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
                )
                    <div
                        class="mt-5 rounded-xl border
                               border-emerald-300
                               bg-white p-4
                               text-emerald-950"
                    >
                        <div
                            class="text-sm font-bold"
                        >
                            Create New Tune Variant
                        </div>

                        @if ($reviewTuneVariants->isEmpty())
                            <p class="mt-1 text-sm">
                                This canonical Hymn has no
                                Tune variants yet. CoQP will
                                establish the existing/default
                                tune as <strong>Tune 1</strong>
                                and create
                                <strong>Tune 2</strong> for
                                this Hymnal.net New Tune.
                            </p>
                        @else
                            <p class="mt-1 text-sm">
                                Use this only when the
                                Hymnal.net page represents a
                                genuinely different tune.
                                CoQP will create the next
                                Tune variant and attach this
                                NT source to it.
                            </p>
                        @endif

                        <p
                            class="mt-2 text-xs
                                   text-emerald-800"
                        >
                            If Hymnal.net is actually using
                            one of the Tune variants already
                            shown above, select that variant
                            instead and use
                            <strong>
                                Link Existing Canonical Hymn
                            </strong>.
                        </p>

                        <div class="mt-4">
                            <x-filament::button
                                wire:click="createNewTuneVariantFromReviewedEntry"
                                wire:confirm="Create a new Tune variant for this Hymnal.net New Tune and link the source to it?"
                                icon="heroicon-m-plus"
                                color="success"
                            >
                                Create New Tune Variant & Link
                            </x-filament::button>
                        </div>
                    </div>
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
                        && ! $selectedVariantId
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
