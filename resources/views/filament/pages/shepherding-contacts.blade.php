<x-filament-panels::page>
    @php
        $people = $this->people();

        $localityGroups = $this->localityGroups();

        $contactPeople =
            $this->contactPeople();

        $selectedContactPeople =
            $this->selectedContactPeople();

        $households =
            $this->households();

        $selectedContactedHouseholds =
            $this->selectedContactedHouseholds();

        $campusContacts =
            $this->campusContacts();

        $selectedCampusContacts =
            $this->selectedCampusContacts();

        $householdMembers =
            $this->householdMembers();

        $participantPeople =
            $this->participantPeople();

        $selectedParticipants =
            $this->selectedParticipants();

        $activityTypes =
            $this->activityTypes();

        $ministryBooks =
            $this->ministryBooks();

        $contacts =
            $this->recentContacts();
    @endphp

    <div class="space-y-6">

        {{-- ===================================== --}}
        {{-- Header --}}
        {{-- ===================================== --}}
        <div
            class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm
                   dark:border-primary-900 dark:bg-primary-950"
        >
            <p
                class="text-sm font-semibold uppercase tracking-wide
                       text-primary-600 dark:text-primary-300"
            >
                Shepherding
            </p>

            <h2
                class="mt-2 text-3xl font-bold
                       text-gray-900 dark:text-white"
            >
                Shepherding Records
            </h2>

            <p
                class="mt-2 max-w-4xl text-sm
                       text-gray-600 dark:text-gray-300"
            >
                Record shepherding contacts involving individual
                People, Households, ministry progress, activities,
                and serving saints. Meeting attendance remains
                under Attendance.
            </p>
        </div>


        {{-- ===================================== --}}
        {{-- Record / Edit Contact --}}
        {{-- ===================================== --}}
        <div
            id="shepherding-contact-form"
            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="flex flex-wrap items-center
                       justify-between gap-3"
            >
                <h3
                    class="text-lg font-bold
                           text-gray-950 dark:text-white"
                >
                    {{ $editingContactId
                        ? 'Edit Contact'
                        : 'Record Contact' }}
                </h3>

                @if ($editingContactId)
                    <span
                        class="rounded-full bg-amber-100 px-3 py-1
                               text-xs font-bold text-amber-800
                               dark:bg-amber-900 dark:text-amber-100"
                    >
                        Editing Contact #{{ $editingContactId }}
                    </span>
                @endif
            </div>

            <form
                wire:submit="saveContact"
                class="mt-5 space-y-6"
            >

                {{-- ================================= --}}
                {{-- Contact Targets --}}
                {{-- ================================= --}}
                <section>
                    <div>
                        <h4
                            class="text-sm font-bold
                                   text-gray-900 dark:text-white"
                        >
                            Contacted Targets *
                        </h4>

                        <p
                            class="mt-1 text-xs
                                   text-gray-500 dark:text-gray-400"
                        >
                            Select individual People,
                            Households, or both.
                        </p>
                    </div>

                    <div class="mt-4">
                        <label
                            class="block text-sm font-semibold
                                   text-gray-700 dark:text-gray-200"
                        >
                            Search Contact Targets
                        </label>

                        <input
                            type="search"
                            wire:model.live.debounce.300ms="targetSearch"
                            placeholder="Search People, Households, or Campus Contacts..."
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-950
                                   dark:text-gray-100"
                        >

                        @if (filled($targetSearch))
                            <p
                                class="mt-1 text-xs
                                       text-gray-500 dark:text-gray-400"
                            >
                                Showing matching results across
                                all Contact Target databases.
                            </p>
                        @endif
                    </div>

                    <div
                        class="mt-4 grid gap-4
                               lg:grid-cols-3"
                    >

                        {{-- People Contacted --}}
                        <div
                            class="rounded-xl border border-gray-200 p-4
                                   dark:border-gray-700"
                        >
                            <label
                                class="block text-sm font-semibold
                                       text-gray-700 dark:text-gray-200"
                            >
                                People Contacted
                            </label>

                            <p
                                class="mt-1 text-xs
                                       text-gray-500 dark:text-gray-400"
                            >
                                Members of selected Households
                                are automatically hidden here.
                            </p>

                            @if ($selectedContactPeople->isNotEmpty())
                                <div
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    @foreach ($selectedContactPeople as $person)
                                        <button
                                            type="button"
                                            wire:click="removeContactedPerson({{ $person->id }})"
                                            title="Remove {{ $person->display_name }}"
                                            class="rounded-full bg-primary-50
                                                   px-3 py-1.5 text-xs font-bold
                                                   text-primary-700
                                                   hover:bg-primary-100
                                                   dark:bg-primary-950
                                                   dark:text-primary-300"
                                        >
                                            {{ $person->display_name }}
                                            ×
                                        </button>
                                    @endforeach
                                </div>
                            @endif
<div
                                class="mt-2 space-y-1 overflow-y-auto
                                       rounded-xl border border-gray-200
                                       p-2 dark:border-gray-700"
                                style="max-height: 12rem;"
                            >
                                @forelse ($contactPeople as $person)
                                    <label
                                        class="flex cursor-pointer
                                               items-center gap-3
                                               rounded-lg px-2 py-2
                                               hover:bg-gray-50
                                               dark:hover:bg-gray-800"
                                    >
                                        <input
                                            type="checkbox"
                                            value="{{ $person->id }}"
                                            wire:model.live="contactedPersonIds"
                                            class="rounded border-gray-300"
                                        >

                                        <span
                                            class="text-sm
                                                   text-gray-800
                                                   dark:text-gray-200"
                                        >
                                            {{ $person->display_name }}
                                        </span>
                                    </label>
                                @empty
                                    <p
                                        class="px-2 py-3 text-sm
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        No matching People found.
                                    </p>
                                @endforelse
                            </div>
                        </div>


                        {{-- Households Contacted --}}
                        <div
                            class="rounded-xl border border-gray-200 p-4
                                   dark:border-gray-700"
                        >
                            <label
                                class="block text-sm font-semibold
                                       text-gray-700 dark:text-gray-200"
                            >
                                Households Contacted
                            </label>

                            <p
                                class="mt-1 text-xs
                                       text-gray-500 dark:text-gray-400"
                            >
                                Household members will appear
                                below after selecting a Household.
                            </p>

                            @if ($selectedContactedHouseholds->isNotEmpty())
                                <div
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    @foreach ($selectedContactedHouseholds as $household)
                                        <button
                                            type="button"
                                            wire:click="removeContactedHousehold({{ $household->id }})"
                                            title="Remove {{ $household->display_name }}"
                                            class="rounded-full bg-violet-50
                                                   px-3 py-1.5 text-xs font-bold
                                                   text-violet-700
                                                   hover:bg-violet-100
                                                   dark:bg-violet-950
                                                   dark:text-violet-300"
                                        >
                                            {{ $household->display_name }}
                                            ×
                                        </button>
                                    @endforeach
                                </div>
                            @endif
<div
                                class="mt-2 space-y-1 overflow-y-auto
                                       rounded-xl border border-gray-200
                                       p-2 dark:border-gray-700"
                                style="max-height: 12rem;"
                            >
                                @forelse ($households as $household)
                                    <label
                                        class="flex cursor-pointer
                                               items-start gap-3
                                               rounded-lg px-2 py-2
                                               hover:bg-gray-50
                                               dark:hover:bg-gray-800"
                                    >
                                        <input
                                            type="checkbox"
                                            value="{{ $household->id }}"
                                            wire:model.live="contactedHouseholdIds"
                                            class="mt-1 rounded
                                                   border-gray-300"
                                        >

                                        <span class="min-w-0 text-sm">
                                            <strong
                                                class="block
                                                       text-gray-900
                                                       dark:text-gray-100"
                                            >
                                                {{ $household->display_name }}
                                            </strong>

                                            @if ($household->localityRecord)
                                                <span
                                                    class="block text-xs
                                                           text-gray-500
                                                           dark:text-gray-400"
                                                >
                                                    {{ $household->localityRecord->name }}
                                                </span>
                                            @endif
                                        </span>
                                    </label>
                                @empty
                                    <p
                                        class="px-2 py-3 text-sm
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        No matching Households found.
                                    </p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Campus Contacts --}}
                        <div
                            class="rounded-xl border border-gray-200 p-4
                                   dark:border-gray-700"
                        >
                            <label
                                class="block text-sm font-semibold
                                       text-gray-700 dark:text-gray-200"
                            >
                                Campus Contacts
                            </label>

                            <p
                                class="mt-1 text-xs
                                       text-gray-500 dark:text-gray-400"
                            >
                                Unlinked Campus Contacts only.
                                Once linked to the People Database,
                                use People Contacted instead.
                            </p>

                            @if ($selectedCampusContacts->isNotEmpty())
                                <div
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    @foreach ($selectedCampusContacts as $campusContact)
                                        <button
                                            type="button"
                                            wire:click="removeContactedCampusContact({{ $campusContact->id }})"
                                            title="Remove {{ $campusContact->display_name }}"
                                            class="rounded-full
                                                   bg-cyan-50
                                                   px-3 py-1.5
                                                   text-xs font-bold
                                                   text-cyan-700
                                                   dark:bg-cyan-950
                                                   dark:text-cyan-300"
                                        >
                                            {{ $campusContact->display_name }}
                                            ×
                                        </button>
                                    @endforeach
                                </div>
                            @endif
<div
                                class="mt-2 space-y-1
                                       overflow-y-auto
                                       rounded-xl border
                                       border-gray-200 p-2
                                       dark:border-gray-700"
                                style="max-height: 12rem;"
                            >
                                @forelse ($campusContacts as $campusContact)
                                    <label
                                        class="flex cursor-pointer
                                               items-start gap-3
                                               rounded-lg px-2 py-2
                                               hover:bg-gray-50
                                               dark:hover:bg-gray-800"
                                    >
                                        <input
                                            type="checkbox"
                                            value="{{ $campusContact->id }}"
                                            wire:model.live="contactedCampusContactIds"
                                            class="mt-1 rounded
                                                   border-gray-300"
                                        >

                                        <span class="min-w-0">
                                            <strong
                                                class="block text-sm
                                                       text-gray-900
                                                       dark:text-gray-100"
                                            >
                                                {{ $campusContact->display_name }}
                                            </strong>

                                            @if (
                                                $campusContact->school
                                                || $campusContact->school_campus
                                                || $campusContact->localityRecord
                                            )
                                                <span
                                                    class="block text-xs
                                                           text-gray-500
                                                           dark:text-gray-400"
                                                >
                                                    @if ($campusContact->school)
                                                        {{ $campusContact->school->name }}
                                                    @elseif ($campusContact->school_campus)
                                                        {{ $campusContact->school_campus }}
                                                    @endif

                                                    @if (
                                                        ($campusContact->school
                                                            || $campusContact->school_campus)
                                                        && $campusContact->localityRecord
                                                    )
                                                        ·
                                                    @endif

                                                    @if ($campusContact->localityRecord)
                                                        {{ $campusContact->localityRecord->name }}
                                                    @endif
                                                </span>
                                            @endif
                                        </span>
                                    </label>
                                @empty
                                    <p
                                        class="px-2 py-3 text-sm
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        No matching unlinked
                                        Campus Contacts.
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    @error('contactedPersonIds')
                        <p class="mt-2 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </section>


                {{-- ================================= --}}
                {{-- Household Member Presence --}}
                {{-- ================================= --}}
                @if ($selectedContactedHouseholds->isNotEmpty())
                    <section
                        class="rounded-xl border border-violet-200
                               bg-violet-50/50 p-4
                               dark:border-violet-900
                               dark:bg-violet-950/20"
                    >
                        <div>
                            <h4
                                class="text-sm font-bold
                                       text-gray-900 dark:text-white"
                            >
                                Household Members Present
                            </h4>

                            <p
                                class="mt-1 text-xs
                                       text-gray-600 dark:text-gray-400"
                            >
                                Current Household members are
                                selected as Present by default.
                                Uncheck anyone who was not there.
                                This list is saved historically
                                with the contact.
                            </p>
                        </div>

                        <input
                            type="search"
                            wire:model.live.debounce.300ms="householdMemberSearch"
                            placeholder="Search selected Household members..."
                            class="mt-3 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-2.5 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-950
                                   dark:text-gray-100"
                        >

                        <div
                            class="mt-2 space-y-1 overflow-y-auto
                                   rounded-xl border border-gray-200
                                   bg-white p-2
                                   dark:border-gray-700
                                   dark:bg-gray-950"
                            style="max-height: 12rem;"
                        >
                            @forelse ($householdMembers as $person)
                                @php
                                    $snapshotHouseholdId =
                                        (int) (
                                            $householdMemberHouseholdIds[
                                                $person->id
                                            ] ?? 0
                                        );

                                    $snapshotHousehold =
                                        $selectedContactedHouseholds
                                            ->firstWhere(
                                                'id',
                                                $snapshotHouseholdId
                                            );

                                    $isPresent =
                                        (bool) (
                                            $householdMemberPresence[
                                                $person->id
                                            ] ?? false
                                        );
                                @endphp

                                <label
                                    class="flex cursor-pointer
                                           items-center justify-between
                                           gap-3 rounded-lg px-2 py-2
                                           hover:bg-gray-50
                                           dark:hover:bg-gray-800"
                                >
                                    <span
                                        class="flex min-w-0
                                               items-center gap-3"
                                    >
                                        <input
                                            type="checkbox"
                                            wire:model.live="householdMemberPresence.{{ $person->id }}"
                                            class="rounded border-gray-300"
                                        >

                                        <span class="min-w-0">
                                            <strong
                                                class="block text-sm
                                                       text-gray-900
                                                       dark:text-gray-100"
                                            >
                                                {{ $person->display_name }}
                                            </strong>

                                            @if ($snapshotHousehold)
                                                <span
                                                    class="block text-xs
                                                           text-gray-500
                                                           dark:text-gray-400"
                                                >
                                                    {{ $snapshotHousehold->display_name }}
                                                </span>
                                            @endif
                                        </span>
                                    </span>

                                    @if ($isPresent)
                                        <span
                                            class="shrink-0 rounded-full
                                                   bg-emerald-100
                                                   px-2.5 py-1 text-xs
                                                   font-bold
                                                   text-emerald-800
                                                   dark:bg-emerald-900
                                                   dark:text-emerald-100"
                                        >
                                            Present
                                        </span>
                                    @else
                                        <span
                                            class="shrink-0 rounded-full
                                                   bg-gray-100
                                                   px-2.5 py-1 text-xs
                                                   font-bold
                                                   text-gray-600
                                                   dark:bg-gray-800
                                                   dark:text-gray-300"
                                        >
                                            Not Present
                                        </span>
                                    @endif
                                </label>
                            @empty
                                <p
                                    class="px-2 py-4 text-sm
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    No Household members found
                                    for the selected Household.
                                </p>
                            @endforelse
                        </div>
                    </section>
                @endif


                {{-- ================================= --}}
                {{-- Locality / Date / Time --}}
                {{-- ================================= --}}
                <section
                    class="grid gap-4 md:grid-cols-4"
                >
                    <div class="md:col-span-2">
                        <label
                            class="block text-sm font-semibold
                                   text-gray-700 dark:text-gray-200"
                        >
                            Locality
                        </label>

                        <select
                            wire:model.live="localityId"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-950
                                   dark:text-gray-100"
                        >
                            <option value="">
                                No Locality / Select manually
                            </option>

                            @foreach ($localityGroups as $groupLabel => $options)
                                <optgroup
                                    label="{{ $groupLabel }}"
                                >
                                    @foreach ($options as $localityId => $localityLabel)
                                        <option
                                            value="{{ $localityId }}"
                                        >
                                            {{ $localityLabel }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>

                        @if ($localitySource)
                            <p
                                class="mt-1 text-xs
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                {{ $localitySource }}
                            </p>
                        @endif

                        @error('localityId')
                            <p
                                class="mt-1 text-xs
                                       text-red-600"
                            >
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            class="block text-sm font-semibold
                                   text-gray-700 dark:text-gray-200"
                        >
                            Date *
                        </label>

                        <input
                            type="date"
                            wire:model="contactDate"
                            max="{{ now()->toDateString() }}"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-950
                                   dark:text-gray-100"
                        >

                        @error('contactDate')
                            <p
                                class="mt-1 text-xs
                                       text-red-600"
                            >
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            class="block text-sm font-semibold
                                   text-gray-700 dark:text-gray-200"
                        >
                            Time
                        </label>

                        <input
                            type="time"
                            wire:model="contactTime"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-950
                                   dark:text-gray-100"
                        >

                        <p
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Optional
                        </p>
                    </div>
                </section>


                {{-- ================================= --}}
                {{-- Outcome --}}
                {{-- ================================= --}}
                <section>
                    <label
                        class="block text-sm font-semibold
                               text-gray-700 dark:text-gray-200"
                    >
                        Outcome *
                    </label>

                    <select
                        wire:model.live="outcome"
                        class="mt-2 block w-full rounded-xl
                               border border-gray-300 bg-white
                               px-4 py-3 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-gray-100"
                    >
                        @foreach ($this->outcomeOptions() as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @if (
                        $outcome
                        === \App\Models\ShepherdingContact::OUTCOME_UNAVAILABLE
                    )
                        <p
                            class="mt-2 text-xs font-semibold
                                   text-amber-700
                                   dark:text-amber-300"
                        >
                            Out / Unavailable records an
                            attempted contact. Activities and
                            Ministry Lessons are not counted.
                        </p>
                    @endif
                </section>


                {{-- ================================= --}}
                {{-- Positive Activities --}}
                {{-- ================================= --}}
                @if (
                    $outcome
                    !== \App\Models\ShepherdingContact::OUTCOME_UNAVAILABLE
                )
                    <section>
                        <h4
                            class="text-sm font-bold
                                   text-gray-900 dark:text-white"
                        >
                            Activities
                        </h4>

                        <p
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Select everything that actually
                            happened during the contact.
                        </p>

                        <div
                            class="mt-3 grid gap-3
                                   sm:grid-cols-2
                                   lg:grid-cols-3"
                        >
                            @foreach ($activityTypes as $activity)
                                <label
                                    class="flex cursor-pointer
                                           items-start gap-3
                                           rounded-xl border
                                           border-gray-200 p-3
                                           dark:border-gray-700"
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $activity->id }}"
                                        wire:model="activityTypeIds"
                                        class="mt-1 rounded
                                               border-gray-300"
                                    >

                                    <span>
                                        <span
                                            class="block text-sm
                                                   font-bold
                                                   text-gray-900
                                                   dark:text-white"
                                        >
                                            {{ $activity->code }}
                                            ·
                                            {{ $activity->name }}
                                        </span>

                                        <span
                                            class="block text-xs
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            {{ $activity->category }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>


                    {{-- ============================= --}}
                    {{-- Ministry Lessons --}}
                    {{-- ============================= --}}
                    <section>
                        <h4
                            class="text-sm font-bold
                                   text-gray-900 dark:text-white"
                        >
                            Ministry Lessons / Messages
                        </h4>

                        <p
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Optional. Select Ministry Lessons
                            covered during this contact.
                        </p>

                        @forelse ($ministryBooks as $book)
                            <div
                                class="mt-4 rounded-xl
                                       border border-gray-200
                                       p-4 dark:border-gray-700"
                            >
                                <div
                                    class="flex items-center gap-2"
                                >
                                    <span
                                        class="rounded-lg
                                               bg-gray-100
                                               px-2.5 py-1
                                               font-mono text-xs
                                               font-bold
                                               dark:bg-gray-800"
                                    >
                                        {{ $book->code }}
                                    </span>

                                    <h5
                                        class="text-sm font-bold
                                               text-gray-900
                                               dark:text-white"
                                    >
                                        {{ $book->title }}
                                    </h5>
                                </div>

                                <div
                                    class="mt-3 grid gap-2
                                           sm:grid-cols-2
                                           lg:grid-cols-3"
                                >
                                    @foreach ($book->lessons as $lesson)
                                        <label
                                            class="flex cursor-pointer
                                                   items-center gap-2
                                                   rounded-lg border
                                                   border-gray-200
                                                   px-3 py-2
                                                   dark:border-gray-700"
                                        >
                                            <input
                                                type="checkbox"
                                                value="{{ $lesson->id }}"
                                                wire:model="ministryLessonIds"
                                                class="rounded
                                                       border-gray-300"
                                            >

                                            <span class="text-sm">
                                                <strong
                                                    class="font-mono"
                                                >
                                                    {{ $lesson->code }}
                                                </strong>

                                                @if ($lesson->title)
                                                    · {{ $lesson->title }}
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div
                                class="mt-3 rounded-xl
                                       border border-dashed
                                       border-gray-300 p-4
                                       text-sm text-gray-500
                                       dark:border-gray-700
                                       dark:text-gray-400"
                            >
                                No active Ministry Lessons are
                                configured yet. Add them under
                                Administration → Ministry Books.
                            </div>
                        @endforelse
                    </section>
                @endif


                {{-- ================================= --}}
                {{-- Serving Saints --}}
                {{-- ================================= --}}
                <section>
                    <label
                        class="block text-sm font-semibold
                               text-gray-700 dark:text-gray-200"
                    >
                        Serving Saints (Optional)
                    </label>

                    <p
                        class="mt-1 text-xs
                               text-gray-500 dark:text-gray-400"
                    >
                        Add serving saints only when someone
                        outside the contacted Person or Household
                        served in this activity. Leave this blank
                        for family activities such as Household
                        Morning Revival.
                    </p>

                    @if ($selectedParticipants->isNotEmpty())
                        <div
                            class="mt-3 flex flex-wrap gap-2"
                        >
                            @foreach ($selectedParticipants as $participant)
                                <button
                                    type="button"
                                    wire:click="removeParticipant({{ $participant->id }})"
                                    title="Remove {{ $participant->display_name }}"
                                    class="rounded-full
                                           bg-primary-50
                                           px-3 py-1.5
                                           text-xs font-bold
                                           text-primary-700
                                           hover:bg-primary-100
                                           dark:bg-primary-950
                                           dark:text-primary-300"
                                >
                                    {{ $participant->display_name }}
                                    ×
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <input
                        type="search"
                        wire:model.live.debounce.300ms="participantSearch"
                        placeholder="Search Serving Saint..."
                        class="mt-3 block w-full rounded-xl
                               border border-gray-300 bg-white
                               px-4 py-2.5 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-gray-100"
                    >

                    <div
                        class="mt-2 space-y-1 overflow-y-auto
                               rounded-xl border border-gray-200
                               p-2 dark:border-gray-700"
                        style="max-height: 12rem;"
                    >
                        @forelse ($participantPeople as $person)
                            <label
                                class="flex cursor-pointer
                                       items-center gap-3
                                       rounded-lg px-2 py-2
                                       hover:bg-gray-50
                                       dark:hover:bg-gray-800"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $person->id }}"
                                    wire:model="participantIds"
                                    class="rounded
                                           border-gray-300"
                                >

                                <span
                                    class="text-sm
                                           text-gray-800
                                           dark:text-gray-200"
                                >
                                    {{ $person->display_name }}
                                </span>
                            </label>
                        @empty
                            <p
                                class="px-2 py-3 text-sm
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                No matching People found.
                            </p>
                        @endforelse
                    </div>
                </section>


                {{-- ================================= --}}
                {{-- Notes --}}
                {{-- ================================= --}}
                <section>
                    <label
                        class="block text-sm font-semibold
                               text-gray-700 dark:text-gray-200"
                    >
                        Notes
                    </label>

                    <textarea
                        wire:model="notes"
                        rows="4"
                        placeholder="Shepherding notes, response, follow-up needs, prayer burden, etc."
                        class="mt-2 block w-full rounded-xl
                               border border-gray-300 bg-white
                               px-4 py-3 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-gray-100"
                    ></textarea>
                </section>


                {{-- ================================= --}}
                {{-- Save Buttons --}}
                {{-- ================================= --}}
                <div
                    class="flex flex-wrap
                           justify-end gap-2"
                >
                    @if ($editingContactId)
                        <button
                            type="button"
                            wire:click="cancelEditing"
                            class="rounded-xl border
                                   border-gray-300
                                   px-5 py-2.5
                                   text-sm font-semibold
                                   text-gray-700
                                   dark:border-gray-700
                                   dark:text-gray-200"
                        >
                            Cancel Edit
                        </button>
                    @endif

                    <button
                        type="submit"
                        class="rounded-xl
                               bg-primary-600
                               px-5 py-2.5
                               text-sm font-bold
                               text-white
                               hover:bg-primary-500"
                    >
                        {{ $editingContactId
                            ? 'Update Shepherding Record'
                            : 'Record Shepherding Record' }}
                    </button>
                </div>
            </form>
        </div>


        {{-- ===================================== --}}
        {{-- Recent Contact History --}}
        {{-- ===================================== --}}
        <div
            class="rounded-2xl border border-gray-200
                   bg-white p-6 shadow-sm
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-3
                       lg:flex-row lg:items-end
                       lg:justify-between"
            >
                <div>
                    <h3
                        class="text-lg font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        Recent Contact History
                    </h3>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Most recent 50 Shepherding
                        Contact records.
                    </p>
                </div>

                <div
                    class="grid gap-3
                           sm:grid-cols-3"
                >
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="historySearch"
                        placeholder="Search history..."
                        class="rounded-xl border
                               border-gray-300 bg-white
                               px-3 py-2 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-gray-100"
                    >

                    <select
                        wire:model.live="historyPersonId"
                        class="rounded-xl border
                               border-gray-300 bg-white
                               px-3 py-2 text-sm
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
                        wire:model.live="historyOutcome"
                        class="rounded-xl border
                               border-gray-300 bg-white
                               px-3 py-2 text-sm
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
                </div>
            </div>


            <div class="mt-5 space-y-3">
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
                        wire:key="shepherding-contact-{{ $contact->id }}"
                        class="rounded-xl border
                               border-gray-200 p-4
                               dark:border-gray-700"
                    >
                        <div
                            class="flex flex-col gap-4
                                   lg:flex-row
                                   lg:items-start
                                   lg:justify-between"
                        >
                            <div class="min-w-0">

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

                                    @if (
                                        $contact->contactedPeople->isEmpty()
                                        && $contact->contactedHouseholds->isEmpty()
                                        && $contact->contactedCampusContacts->isEmpty()
                                    )
                                        <span
                                            class="text-sm font-bold
                                                   text-gray-500"
                                        >
                                            No Contact Target
                                        </span>
                                    @endif
                                </div>


                                {{-- Date / Locality / Outcome --}}
                                <div
                                    class="mt-2 flex flex-wrap
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

                                    @if (
                                        $contact->outcome
                                        === \App\Models\ShepherdingContact::OUTCOME_COMPLETED
                                    )
                                        <span
                                            class="rounded-full
                                                   bg-emerald-100
                                                   px-2.5 py-1
                                                   text-xs font-bold
                                                   text-emerald-800
                                                   dark:bg-emerald-900
                                                   dark:text-emerald-100"
                                        >
                                            {{ $contact->outcome }}
                                        </span>
                                    @elseif (
                                        $contact->outcome
                                        === \App\Models\ShepherdingContact::OUTCOME_UNAVAILABLE
                                    )
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
                                    @else
                                        <span
                                            class="rounded-full
                                                   bg-amber-100
                                                   px-2.5 py-1
                                                   text-xs font-bold
                                                   text-amber-800
                                                   dark:bg-amber-900
                                                   dark:text-amber-100"
                                        >
                                            {{ $contact->outcome }}
                                        </span>
                                    @endif
                                </div>


                                {{-- Household presence --}}
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


                                {{-- Activities --}}
                                @if ($contact->activityTypes->isNotEmpty())
                                    <div
                                        class="mt-3 flex
                                               flex-wrap gap-2"
                                    >
                                        @foreach ($contact->activityTypes as $activity)
                                            <span
                                                class="rounded-lg
                                                       bg-primary-50
                                                       px-2.5 py-1
                                                       text-xs font-bold
                                                       text-primary-700
                                                       dark:bg-primary-950
                                                       dark:text-primary-300"
                                            >
                                                {{ $activity->code }}
                                                ·
                                                {{ $activity->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif


                                {{-- Ministry --}}
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
                                                    ·
                                                    {{ $lesson->title }}
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif


                                {{-- Serving Saints --}}
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


                                {{-- Notes --}}
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


                            {{-- Actions --}}
                            <div
                                class="flex shrink-0 gap-2"
                            >
                                <button
                                    type="button"
                                    wire:click="editContact({{ $contact->id }})"
                                    onclick="document.getElementById('shepherding-contact-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                    class="rounded-xl border
                                           border-gray-300
                                           px-3 py-2
                                           text-xs font-semibold
                                           text-gray-700
                                           dark:border-gray-700
                                           dark:text-gray-200"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteContact({{ $contact->id }})"
                                    wire:confirm="Delete this Shepherding Contact?"
                                    class="rounded-xl border
                                           border-red-300
                                           px-3 py-2
                                           text-xs font-semibold
                                           text-red-700
                                           dark:border-red-900
                                           dark:text-red-300"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border
                               border-dashed border-gray-300
                               p-8 text-center text-sm
                               text-gray-500
                               dark:border-gray-700
                               dark:text-gray-400"
                    >
                        No Shepherding Records found.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
