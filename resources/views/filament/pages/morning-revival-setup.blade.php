<x-filament-panels::page>
    <div
        x-data
        x-on:scroll-to-morning-revival-weeks.window="
            $nextTick(() => {
                document
                    .getElementById(
                        'morning-revival-weeks-editor'
                    )
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
            })
        "
    >
    @php
        $publications =
            $this->publications();

        $todayReading =
            $this->todayReading();
    @endphp

    <div class="space-y-6">

        {{-- ========================================= --}}
        {{-- Header --}}
        {{-- ========================================= --}}
        <div
            class="rounded-2xl border
                   border-amber-200
                   bg-amber-50 p-6
                   shadow-sm
                   dark:!border-amber-800
                   dark:!bg-gray-900"
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
                               text-amber-700
                               dark:!text-amber-300"
                    >
                        Setup & Reference Data
                    </p>

                    <h2
                        class="mt-2 text-3xl
                               font-bold
                               text-gray-950
                               dark:!text-white"
                    >
                        Morning Revival Setup
                    </h2>

                    <p
                        class="mt-2 max-w-3xl
                               text-sm
                               text-gray-600
                               dark:!text-gray-300"
                    >
                        Maintain Morning Revival
                        publications and their weekly
                        messages. Day 1 through Day 6
                        are generated from each week's
                        schedule.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="newPublication"
                    class="inline-flex items-center
                           justify-center rounded-lg
                           bg-amber-600 px-4 py-2.5
                           text-sm font-bold
                           text-white shadow-sm
                           hover:bg-amber-500"
                >
                    + New Publication
                </button>
            </div>
        </div>


        {{-- ========================================= --}}
        {{-- Today's resolved reading --}}
        {{-- ========================================= --}}
        <div
            class="rounded-2xl border
                   border-gray-200 bg-white p-5
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
                Today's Scheduled Reading
            </div>

            @if ($todayReading)
                @php
                    $todayWeek =
                        $todayReading['week'];

                    $todayPublication =
                        $todayWeek->publication;
                @endphp

                <div
                    class="mt-2 text-lg font-bold
                           text-gray-950
                           dark:text-white"
                >
                    Week {{ $todayWeek->week_number }}:
                    {{ $todayWeek->title }}
                    · Day {{ $todayReading['day'] }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-gray-600
                           dark:text-gray-300"
                >
                    {{
                        $todayPublication
                            ->general_subject
                    }}
                </div>

                <div
                    class="mt-1 text-xs
                           text-gray-500
                           dark:text-gray-400"
                >
                    {{
                        $todayPublication
                            ->source_title
                    }}
                </div>
            @else
                <div
                    class="mt-2 text-sm
                           text-gray-500
                           dark:text-gray-400"
                >
                    No Day 1–6 Morning Revival
                    reading is scheduled for today.
                </div>
            @endif
        </div>


        {{-- ========================================= --}}
        {{-- Publication editor --}}
        {{-- ========================================= --}}
        <div
            class="rounded-2xl border
                   border-gray-200 bg-white p-6
                   shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-2
                       sm:flex-row
                       sm:items-start
                       sm:justify-between"
            >
                <div>
                    <h3
                        class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        @if ($editingPublicationId)
                            Edit Morning Revival Publication
                        @else
                            Add Morning Revival Publication
                        @endif
                    </h3>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Enter Week 1 Day 1 as the start
                        date. Later week dates are
                        calculated automatically.
                    </p>
                </div>

                @if ($editingPublicationId)
                    <div
                        class="text-xs font-semibold
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Publication
                        #{{ $editingPublicationId }}
                    </div>
                @endif
            </div>

            <div
                class="mt-5 grid gap-4
                       lg:grid-cols-2"
            >
                <div>
                    <label
                        class="text-xs font-bold
                               uppercase tracking-wide
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Source / Conference
                    </label>

                    <input
                        type="text"
                        wire:model="sourceTitle"
                        maxlength="255"
                        placeholder="e.g. 2026 International Memorial Day Blending Conference"
                        class="mt-1 block w-full
                               rounded-xl border
                               border-gray-300
                               bg-white px-4 py-3
                               text-sm text-gray-900
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-gray-100"
                    >

                    @error('sourceTitle')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        class="text-xs font-bold
                               uppercase tracking-wide
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Week 1 Day 1
                    </label>

                    <input
                        type="date"
                        wire:model.live="startDate"
                        class="mt-1 block w-full
                               rounded-xl border
                               border-gray-300
                               bg-white px-4 py-3
                               text-sm text-gray-900
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-gray-100"
                    >

                    <p
                        class="mt-1 text-xs
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Must be Monday.
                    </p>

                    @error('startDate')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <details
                class="mt-5 overflow-hidden
                       rounded-xl border
                       border-amber-200
                       bg-amber-50
                       dark:!border-amber-900
                       dark:!bg-gray-950"
            >
                <summary
                    class="cursor-pointer
                           px-4 py-3
                           text-sm font-bold
                           text-amber-900
                           hover:bg-amber-100
                           dark:!text-amber-200
                           dark:hover:!bg-gray-900"
                >
                    Paste Morning Revival Outline
                </summary>

                <div
                    class="border-t
                           border-amber-200 p-4
                           dark:border-amber-900"
                >
                    <p
                        style="
                            color:#111827 !important;
                            font-weight:600 !important;
                        "
                        class="text-xs"
                    >
                        Paste the General Subject followed
                        by Week 1:, Week 2:, and so on.
                        The parser fills the Subject and
                        Week / Message fields for review.
                        It does not save automatically.
                    </p>

                    <textarea
                        rows="8"
                        wire:model="outlinePaste"
                        placeholder="The Great Need for a New Revival

Week 1: Cooperating with the Lord...
Week 2: Arriving at the Highest Peak...
Week 3: The God-man Living..."
                        class="mt-3 block w-full
                               rounded-xl border
                               border-amber-200
                               bg-white px-4 py-3
                               text-sm text-gray-900
                               shadow-sm
                               dark:border-amber-900
                               dark:bg-gray-900
                               dark:text-gray-100"
                    ></textarea>

                    <div
                        class="mt-3 flex
                               flex-wrap gap-2"
                    >
                        <button
                            type="button"
                            wire:click="parseOutlinePaste"
                            wire:loading.attr="disabled"
                            class="rounded-lg
                                   bg-amber-600
                                   px-3 py-2
                                   text-xs font-bold
                                   text-white
                                   hover:bg-amber-500"
                        >
                            Parse Outline
                        </button>

                        <button
                            type="button"
                            wire:click="clearOutlinePaste"
                            class="rounded-lg border
                                   border-amber-300
                                   bg-white
                                   px-3 py-2
                                   text-xs font-bold
                                   text-amber-800
                                   hover:bg-amber-100
                                   dark:border-amber-800
                                   dark:bg-gray-900
                                   dark:text-amber-200"
                        >
                            Clear Paste
                        </button>
                    </div>
                </div>
            </details>

            <div class="mt-4">
                <label
                    class="text-xs font-bold
                           uppercase tracking-wide
                           text-gray-500
                           dark:text-gray-400"
                >
                    General Subject
                </label>

                <input
                    type="text"
                    wire:model="generalSubject"
                    maxlength="500"
                    placeholder="General subject of the Morning Revival"
                    class="mt-1 block w-full
                           rounded-xl border
                           border-gray-300
                           bg-white px-4 py-3
                           text-sm text-gray-900
                           dark:border-gray-700
                           dark:bg-gray-950
                           dark:text-gray-100"
                >

                @error('generalSubject')
                    <p class="mt-1 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <label
                class="mt-4 inline-flex
                       cursor-pointer items-center
                       gap-3"
            >
                <input
                    type="checkbox"
                    wire:model="isActive"
                    class="rounded border-gray-300"
                >

                <span
                    class="text-sm font-semibold
                           text-gray-700
                           dark:text-gray-200"
                >
                    Active publication
                </span>
            </label>


            {{-- ===================================== --}}
            {{-- Weeks --}}
            {{-- ===================================== --}}
            <div
                id="morning-revival-weeks-editor"
                class="mt-6 scroll-mt-6
                       rounded-xl border
                       border-gray-200 p-4
                       dark:border-gray-700"
            >
                <div
                    class="flex flex-col gap-3
                           sm:flex-row
                           sm:items-center
                           sm:justify-between"
                >
                    <div>
                        <h4
                            class="font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            Weeks / Messages
                        </h4>

                        <p
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Each week automatically
                            contains Day 1 through Day 6.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="addWeek"
                        class="rounded-lg border
                               border-amber-300
                               bg-amber-50
                               px-3 py-2
                               text-xs font-bold
                               text-amber-800
                               hover:bg-amber-100
                               dark:border-amber-800
                               dark:bg-amber-950/40
                               dark:text-amber-200"
                    >
                        + Week
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach (
                        $weekTitles
                        as $weekIndex => $weekTitle
                    )
                        @php
                            $weekStart =
                                $this->weekStartDate(
                                    $weekIndex
                                );
                        @endphp

                        <div
                            wire:key="morning-revival-week-{{ $weekIndex }}"
                            class="rounded-xl border
                                   border-gray-200
                                   bg-gray-50 p-4
                                   dark:border-gray-700
                                   dark:bg-gray-950"
                        >
                            <div
                                class="flex items-start
                                       gap-3"
                            >
                                <div
                                    class="flex h-9 w-9
                                           shrink-0 items-center
                                           justify-center
                                           rounded-full
                                           bg-amber-100
                                           text-xs font-bold
                                           text-amber-800
                                           dark:bg-amber-900
                                           dark:text-amber-200"
                                >
                                    {{ $weekIndex + 1 }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex flex-col
                                               gap-1 sm:flex-row
                                               sm:items-center
                                               sm:justify-between"
                                    >
                                        <label
                                            class="text-xs
                                                   font-bold
                                                   uppercase
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            Week
                                            {{ $weekIndex + 1 }}
                                        </label>

                                        @if ($weekStart)
                                            <div
                                                class="text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                {{
                                                    $weekStart
                                                        ->format(
                                                            'M j, Y'
                                                        )
                                                }}
                                                –
                                                {{
                                                    $weekStart
                                                        ->copy()
                                                        ->addDays(5)
                                                        ->format(
                                                            'M j, Y'
                                                        )
                                                }}
                                            </div>
                                        @endif
                                    </div>

                                    <textarea
                                        rows="2"
                                        maxlength="1000"
                                        wire:model="weekTitles.{{ $weekIndex }}"
                                        placeholder="Week {{ $weekIndex + 1 }} message title"
                                        class="mt-1 block w-full
                                               rounded-xl border
                                               border-gray-300
                                               bg-white px-4 py-3
                                               text-sm text-gray-900
                                               dark:border-gray-700
                                               dark:bg-gray-900
                                               dark:text-gray-100"
                                    ></textarea>

                                    @error(
                                        'weekTitles.'
                                        . $weekIndex
                                    )
                                        <p
                                            class="mt-1 text-xs
                                                   text-red-600"
                                        >
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <button
                                    type="button"
                                    wire:click="removeWeek({{ $weekIndex }})"
                                    class="shrink-0
                                           rounded-lg px-2 py-2
                                           text-xs font-semibold
                                           text-gray-400
                                           hover:bg-red-50
                                           hover:text-red-600
                                           dark:hover:bg-red-950/30"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @error('weekTitles')
                    <p class="mt-2 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div
                class="mt-5 flex flex-wrap
                       justify-end gap-2"
            >
                @if ($editingPublicationId)
                    <button
                        type="button"
                        wire:click="newPublication"
                        class="rounded-lg border
                               border-gray-300
                               bg-white px-4 py-2.5
                               text-sm font-bold
                               text-gray-700
                               hover:bg-gray-50
                               dark:border-gray-600
                               dark:bg-gray-800
                               dark:text-gray-200"
                    >
                        Cancel Edit
                    </button>
                @endif

                <button
                    type="button"
                    wire:click="savePublication"
                    wire:loading.attr="disabled"
                    class="rounded-lg bg-amber-600
                           px-4 py-2.5
                           text-sm font-bold
                           text-white
                           hover:bg-amber-500"
                >
                    Save Publication
                </button>
            </div>
        </div>


        {{-- ========================================= --}}
        {{-- Existing publications --}}
        {{-- ========================================= --}}
        <div
            class="rounded-2xl border
                   border-gray-200 bg-white p-6
                   shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <h3
                class="text-lg font-bold
                       text-gray-950
                       dark:text-white"
            >
                Morning Revival Publications
            </h3>

            <p
                class="mt-1 text-sm
                       text-gray-500
                       dark:text-gray-400"
            >
                Current and historical Morning Revival
                reference data.
            </p>

            @if ($publications->isEmpty())
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
                    No Morning Revival publications yet.
                </div>
            @else
                <div class="mt-5 space-y-4">
                    @foreach (
                        $publications
                        as $publication
                    )
                        <div
                            wire:key="morning-revival-publication-{{ $publication->id }}"
                            class="rounded-xl border
                                   border-gray-200 p-4
                                   dark:border-gray-700"
                        >
                            <div
                                class="flex flex-col gap-3
                                       lg:flex-row
                                       lg:items-start
                                       lg:justify-between"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap
                                               items-center gap-2"
                                    >
                                        <h4
                                            class="font-bold
                                                   text-gray-950
                                                   dark:text-white"
                                        >
                                            {{
                                                $publication
                                                    ->general_subject
                                            }}
                                        </h4>

                                        <span
                                            class="rounded-full
                                                   px-2 py-0.5
                                                   text-[10px]
                                                   font-bold
                                                   {{
                                                       $publication
                                                           ->is_active
                                                           ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
                                                           : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
                                                   }}"
                                        >
                                            {{
                                                $publication
                                                    ->is_active
                                                    ? 'Active'
                                                    : 'Inactive'
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-1 text-sm
                                               text-gray-600
                                               dark:text-gray-300"
                                    >
                                        {{
                                            $publication
                                                ->source_title
                                        }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Starts
                                        {{
                                            $publication
                                                ->start_date
                                                ->format(
                                                    'M j, Y'
                                                )
                                        }}
                                        ·
                                        {{
                                            $publication
                                                ->weeks
                                                ->count()
                                        }}
                                        week(s)
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="editPublication({{ $publication->id }})"
                                    class="shrink-0 rounded-lg
                                           border border-gray-300
                                           bg-white px-3 py-2
                                           text-xs font-bold
                                           text-gray-700
                                           hover:bg-gray-50
                                           dark:border-gray-600
                                           dark:bg-gray-800
                                           dark:text-gray-100"
                                >
                                    Edit
                                </button>
                            </div>

                            <div
                                class="mt-4 overflow-x-auto
                                       rounded-lg border
                                       border-gray-200
                                       dark:border-gray-700"
                            >
                                <table
                                    class="w-full
                                           min-w-[760px]
                                           text-left text-sm"
                                >
                                    <thead
                                        class="bg-gray-50
                                               dark:bg-gray-950"
                                    >
                                        <tr>
                                            <th
                                                class="px-3 py-2
                                                       text-xs font-bold
                                                       uppercase
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Week
                                            </th>

                                            <th
                                                class="px-3 py-2
                                                       text-xs font-bold
                                                       uppercase
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Dates
                                            </th>

                                            <th
                                                class="px-3 py-2
                                                       text-xs font-bold
                                                       uppercase
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Message
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach (
                                            $publication->weeks
                                            as $week
                                        )
                                            <tr
                                                class="border-t
                                                       border-gray-100
                                                       dark:border-gray-800"
                                            >
                                                <td
                                                    class="px-3 py-3
                                                           font-bold
                                                           text-gray-900
                                                           dark:text-gray-100"
                                                >
                                                    Week
                                                    {{
                                                        $week
                                                            ->week_number
                                                    }}
                                                </td>

                                                <td
                                                    class="whitespace-nowrap
                                                           px-3 py-3
                                                           text-xs
                                                           text-gray-500
                                                           dark:text-gray-400"
                                                >
                                                    {{
                                                        $week
                                                            ->start_date
                                                            ->format(
                                                                'M j'
                                                            )
                                                    }}
                                                    –
                                                    {{
                                                        $week
                                                            ->start_date
                                                            ->copy()
                                                            ->addDays(5)
                                                            ->format(
                                                                'M j, Y'
                                                            )
                                                    }}
                                                </td>

                                                <td
                                                    class="px-3 py-3
                                                           text-gray-800
                                                           dark:text-gray-200"
                                                >
                                                    {{ $week->title }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    </div>
</x-filament-panels::page>
