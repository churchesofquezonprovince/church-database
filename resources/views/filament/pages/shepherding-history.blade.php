<x-filament-panels::page>
    @php
        $contacts =
            $this->historyContacts();

        $people =
            $this->people();

        $localities =
            $this->localities();

        $activityTypes =
            $this->activityTypes();
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <div
            class="rounded-2xl border border-primary-200
                   bg-primary-50 p-6 shadow-sm
                   dark:border-primary-900
                   dark:bg-primary-950"
        >
            <div
                class="flex flex-col gap-4
                       md:flex-row md:items-start
                       md:justify-between"
            >
                <div>
                    <p
                        class="text-sm font-semibold uppercase
                               tracking-wide text-primary-600
                               dark:text-primary-300"
                    >
                        Shepherding
                    </p>

                    <h2
                        class="mt-1 text-2xl font-bold
                               text-gray-900 dark:text-white"
                    >
                        Shepherding History
                    </h2>

                    <p
                        class="mt-2 max-w-3xl text-sm
                               text-gray-600
                               dark:text-gray-300"
                    >
                        Full Shepherding Record history with
                        search, activity, Locality, outcome,
                        Person, and date filters.
                    </p>
                </div>

                <a
                    href="{{ \App\Filament\Pages\ShepherdingContacts::getUrl() }}"
                    class="shrink-0 rounded-xl
                           bg-primary-600 px-4 py-2.5
                           text-sm font-semibold text-white
                           hover:bg-primary-500"
                >
                    Record Shepherding Activity
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div
            class="rounded-2xl border border-gray-200
                   bg-white p-5 shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div
                class="flex items-center justify-between
                       gap-4"
            >
                <div>
                    <h3
                        class="font-semibold text-gray-900
                               dark:text-white"
                    >
                        History Filters
                    </h3>

                    <p
                        class="text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Showing
                        {{ $contacts->firstItem() ?? 0 }}
                        –
                        {{ $contacts->lastItem() ?? 0 }}
                        of
                        {{ $contacts->total() }}
                        record(s).
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="rounded-xl border
                           border-gray-300 px-4 py-2
                           text-sm font-semibold
                           text-gray-700
                           hover:bg-gray-50
                           dark:border-gray-700
                           dark:text-gray-200
                           dark:hover:bg-gray-800"
                >
                    Clear filters
                </button>
            </div>

            @if ($mode === 'follow-up')
                <div
                    class="mt-4 rounded-xl border
                           border-amber-200 bg-amber-50
                           px-4 py-3 text-sm
                           text-amber-900
                           dark:border-amber-900
                           dark:bg-amber-950
                           dark:text-amber-100"
                >
                    Showing follow-up outcomes:
                    Out / Unavailable, Reschedule,
                    and Declined.
                </div>
            @endif

            @if (filled($recordId))
                <div
                    class="mt-4 rounded-xl border
                           border-blue-200 bg-blue-50
                           px-4 py-3 text-sm
                           text-blue-900
                           dark:border-blue-900
                           dark:bg-blue-950
                           dark:text-blue-100"
                >
                    Showing Shepherding Record
                    #{{ $recordId }}.
                </div>
            @endif

            <div
                class="mt-5 grid gap-3
                       md:grid-cols-2
                       xl:grid-cols-4"
            >
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search history..."
                    class="rounded-xl border
                           border-gray-300 bg-white
                           px-4 py-3 text-sm
                           dark:border-gray-700
                           dark:bg-gray-950
                           dark:text-gray-100"
                >

                <select
                    wire:model.live="personId"
                    class="rounded-xl border
                           border-gray-300 bg-white
                           px-4 py-3 text-sm
                           dark:border-gray-700
                           dark:bg-gray-950
                           dark:text-gray-100"
                >
                    <option value="">
                        All People
                    </option>

                    @foreach ($people as $person)
                        <option value="{{ $person->id }}">
                            {{ $person->display_name }}
                        </option>
                    @endforeach
                </select>

                <select
                    wire:model.live="outcome"
                    class="rounded-xl border
                           border-gray-300 bg-white
                           px-4 py-3 text-sm
                           dark:border-gray-700
                           dark:bg-gray-950
                           dark:text-gray-100"
                >
                    <option value="all">
                        All Outcomes
                    </option>

                    @foreach ($this->outcomeOptions() as $value => $label)
                        <option value="{{ $value }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <select
                    wire:model.live="activity"
                    class="rounded-xl border
                           border-gray-300 bg-white
                           px-4 py-3 text-sm
                           dark:border-gray-700
                           dark:bg-gray-950
                           dark:text-gray-100"
                >
                    <option value="">
                        All Activities
                    </option>

                    @foreach ($activityTypes as $activityType)
                        <option value="{{ $activityType->code }}">
                            {{ $activityType->code }}
                            — {{ $activityType->name }}
                        </option>
                    @endforeach
                </select>

                <select
                    wire:model.live="localityId"
                    class="rounded-xl border
                           border-gray-300 bg-white
                           px-4 py-3 text-sm
                           dark:border-gray-700
                           dark:bg-gray-950
                           dark:text-gray-100"
                >
                    <option value="">
                        All Localities
                    </option>

                    @foreach ($localities as $locality)
                        <option value="{{ $locality->id }}">
                            {{ $locality->name }}
                        </option>
                    @endforeach
                </select>

                <label
                    class="rounded-xl border
                           border-gray-200 p-2
                           dark:border-gray-700"
                >
                    <span
                        class="block px-2 text-xs
                               font-medium text-gray-500
                               dark:text-gray-400"
                    >
                        From
                    </span>

                    <input
                        type="date"
                        wire:model.live="from"
                        class="mt-1 block w-full border-0
                               bg-transparent px-2 py-1
                               text-sm text-gray-900
                               focus:ring-0
                               dark:text-gray-100"
                    >
                </label>

                <label
                    class="rounded-xl border
                           border-gray-200 p-2
                           dark:border-gray-700"
                >
                    <span
                        class="block px-2 text-xs
                               font-medium text-gray-500
                               dark:text-gray-400"
                    >
                        To
                    </span>

                    <input
                        type="date"
                        wire:model.live="to"
                        class="mt-1 block w-full border-0
                               bg-transparent px-2 py-1
                               text-sm text-gray-900
                               focus:ring-0
                               dark:text-gray-100"
                    >
                </label>

                <select
                    wire:model.live="perPage"
                    class="rounded-xl border
                           border-gray-300 bg-white
                           px-4 py-3 text-sm
                           dark:border-gray-700
                           dark:bg-gray-950
                           dark:text-gray-100"
                >
                    <option value="50">
                        50 per page
                    </option>

                    <option value="100">
                        100 per page
                    </option>

                    <option value="200">
                        200 per page
                    </option>

                    <option value="500">
                        500 per page
                    </option>
                </select>
            </div>
        </div>

        {{-- History --}}
        <div class="space-y-3">
            @forelse ($contacts as $contact)
                @php
                    $presentHouseholdMembers =
                        $contact
                            ->householdMembers
                            ->filter(
                                fn ($member) =>
                                    (bool) $member
                                        ->pivot
                                        ->was_present
                            );

                    $absentHouseholdMembers =
                        $contact
                            ->householdMembers
                            ->reject(
                                fn ($member) =>
                                    (bool) $member
                                        ->pivot
                                        ->was_present
                            );
                @endphp

                <div
                    wire:key="history-contact-{{ $contact->id }}"
                    class="rounded-2xl border
                           border-gray-200 bg-white p-5
                           shadow-sm
                           dark:border-gray-700
                           dark:bg-gray-900"
                >
                    <div
                        class="flex flex-col gap-4
                               lg:flex-row
                               lg:items-start
                               lg:justify-between"
                    >
                        <div class="min-w-0 flex-1">

                            {{-- Targets --}}
                            <div
                                class="flex flex-wrap
                                       items-center gap-2"
                            >
                                @foreach ($contact->contactedPeople as $person)
                                    <span
                                        class="rounded-full
                                               bg-primary-50
                                               px-2.5 py-1
                                               text-xs font-bold
                                               text-primary-700
                                               dark:bg-primary-950
                                               dark:text-primary-300"
                                    >
                                        {{ $person->display_name }}
                                    </span>
                                @endforeach

                                @foreach ($contact->contactedHouseholds as $household)
                                    <span
                                        class="rounded-full
                                               bg-violet-50
                                               px-2.5 py-1
                                               text-xs font-bold
                                               text-violet-700
                                               dark:bg-violet-950
                                               dark:text-violet-300"
                                    >
                                        Household ·
                                        {{ $household->display_name }}
                                    </span>
                                @endforeach

                                @foreach ($contact->contactedCampusContacts as $campusContact)
                                    <span
                                        class="rounded-full
                                               bg-cyan-50
                                               px-2.5 py-1
                                               text-xs font-bold
                                               text-cyan-700
                                               dark:bg-cyan-950
                                               dark:text-cyan-300"
                                    >
                                        Campus ·
                                        {{ $campusContact->display_name }}
                                    </span>
                                @endforeach

                                @foreach ($contact->contactedGospelContacts as $gospelContact)
                                    <span
                                        class="rounded-full
                                               bg-emerald-50
                                               px-2.5 py-1
                                               text-xs font-bold
                                               text-emerald-700
                                               dark:bg-emerald-950
                                               dark:text-emerald-300"
                                    >
                                        Gospel ·
                                        {{ $gospelContact->display_name }}
                                    </span>
                                @endforeach
                            </div>

                            {{-- Date / Locality / Outcome --}}
                            <div
                                class="mt-3 flex flex-wrap
                                       items-center gap-2"
                            >
                                <span
                                    class="rounded-full
                                           bg-gray-100
                                           px-2.5 py-1
                                           text-xs font-bold
                                           text-gray-700
                                           dark:bg-gray-800
                                           dark:text-gray-200"
                                >
                                    {{ $contact->contact_date?->format('M j, Y') }}

                                    @if ($contact->contact_time)
                                        ·
                                        {{ \Carbon\Carbon::parse(
                                            $contact->contact_time
                                        )->format('g:i A') }}
                                    @endif
                                </span>

                                @if ($contact->locality)
                                    <span
                                        class="rounded-full
                                               bg-blue-50
                                               px-2.5 py-1
                                               text-xs font-bold
                                               text-blue-700
                                               dark:bg-blue-950
                                               dark:text-blue-300"
                                    >
                                        {{ $contact->locality->name }}
                                    </span>
                                @endif

                                <span
                                    class="rounded-full
                                           bg-gray-100
                                           px-2.5 py-1
                                           text-xs font-bold
                                           text-gray-700
                                           dark:bg-gray-800
                                           dark:text-gray-200"
                                >
                                    {{ $contact->outcome }}
                                </span>
                            </div>

                            @if ($presentHouseholdMembers->isNotEmpty())
                                <p
                                    class="mt-3 text-xs
                                           text-gray-600
                                           dark:text-gray-300"
                                >
                                    <strong>
                                        Household members present:
                                    </strong>

                                    {{ $presentHouseholdMembers
                                        ->pluck('display_name')
                                        ->join(', ') }}
                                </p>
                            @endif

                            @if ($absentHouseholdMembers->isNotEmpty())
                                <p
                                    class="mt-1 text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    <strong>
                                        Not present:
                                    </strong>

                                    {{ $absentHouseholdMembers
                                        ->pluck('display_name')
                                        ->join(', ') }}
                                </p>
                            @endif

                            @if ($contact->activityTypes->isNotEmpty())
                                <div
                                    class="mt-3 flex
                                           flex-wrap gap-2"
                                >
                                    @foreach ($contact->activityTypes as $activityType)
                                        <span
                                            class="rounded-lg
                                                   bg-primary-50
                                                   px-2.5 py-1
                                                   text-xs font-bold
                                                   text-primary-700
                                                   dark:bg-primary-950
                                                   dark:text-primary-300"
                                        >
                                            {{ $activityType->code }}
                                            ·
                                            {{ $activityType->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($contact->ministryLessons->isNotEmpty())
                                <div
                                    class="mt-2 flex
                                           flex-wrap gap-2"
                                >
                                    @foreach ($contact->ministryLessons as $lesson)
                                        <span
                                            class="rounded-lg
                                                   bg-violet-50
                                                   px-2.5 py-1
                                                   text-xs font-bold
                                                   text-violet-700
                                                   dark:bg-violet-950
                                                   dark:text-violet-300"
                                        >
                                            {{ $lesson->code }}

                                            @if ($lesson->title)
                                                · {{ $lesson->title }}
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($contact->participants->isNotEmpty())
                                <p
                                    class="mt-3 text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    <strong>
                                        Serving saints:
                                    </strong>

                                    {{ $contact
                                        ->participants
                                        ->pluck('display_name')
                                        ->join(', ') }}
                                </p>
                            @endif

                            @if ($contact->notes)
                                <p
                                    class="mt-3 whitespace-pre-line
                                           text-sm text-gray-600
                                           dark:text-gray-300"
                                >
                                    {{ $contact->notes }}
                                </p>
                            @endif
                        </div>

                        <div class="shrink-0">
                            <a
                                href="{{ \App\Filament\Pages\ShepherdingContacts::getUrl([
                                    'record' => $contact->id,
                                ]) }}#shepherding-contact-form"
                                class="rounded-xl border
                                       border-gray-300
                                       px-3 py-2 text-xs
                                       font-semibold text-gray-700
                                       hover:bg-gray-50
                                       dark:border-gray-700
                                       dark:text-gray-200
                                       dark:hover:bg-gray-800"
                            >
                                Edit Record
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div
                    class="rounded-2xl border
                           border-dashed border-gray-300
                           bg-white p-10 text-center
                           dark:border-gray-700
                           dark:bg-gray-900"
                >
                    <p
                        class="font-medium text-gray-700
                               dark:text-gray-300"
                    >
                        No Shepherding Records found.
                    </p>

                    <p
                        class="mt-1 text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Try clearing or changing the history filters.
                    </p>
                </div>
            @endforelse
        </div>

        @if ($contacts->hasPages())
            <div
                class="rounded-2xl border
                       border-gray-200 bg-white p-4
                       dark:border-gray-700
                       dark:bg-gray-900"
            >
                {{ $contacts->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
