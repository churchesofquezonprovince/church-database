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

        $gospelContacts =
            $this->gospelContacts();

        $selectedGospelContacts =
            $this->selectedGospelContacts();

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
                            Select People, Households,
                            Campus Contacts, or Gospel Contacts.
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
                            placeholder="Search People, Households, Campus Contacts, or Gospel Contacts..."
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
                               lg:grid-cols-4"
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
                                        wire:key="shepherding-checkbox-contactedPersonIds-{{ $person->id }}"
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
                                        wire:key="shepherding-checkbox-contactedHouseholdIds-{{ $household->id }}"
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
                                        wire:key="shepherding-checkbox-contactedCampusContactIds-{{ $campusContact->id }}"
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

                        {{-- Gospel Contacts --}}
                        <div
                            class="rounded-xl border border-gray-200 p-4
                                   dark:border-gray-700"
                        >
                            <label
                                class="block text-sm font-semibold
                                       text-gray-700 dark:text-gray-200"
                            >
                                Gospel Contacts
                            </label>

                            <p
                                class="mt-1 text-xs
                                       text-gray-500 dark:text-gray-400"
                            >
                                Unlinked Gospel Contacts only.
                                Once linked to the People Database,
                                use People Contacted instead.
                            </p>

                            @if ($selectedGospelContacts->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($selectedGospelContacts as $gospelContact)
                                        <button
                                            type="button"
                                            wire:click="removeContactedGospelContact({{ $gospelContact->id }})"
                                            title="Remove {{ $gospelContact->display_name }}"
                                            class="rounded-full
                                                   bg-emerald-50
                                                   px-3 py-1.5
                                                   text-xs font-bold
                                                   text-emerald-700
                                                   dark:bg-emerald-950
                                                   dark:text-emerald-300"
                                        >
                                            {{ $gospelContact->display_name }}
                                            ×
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <div
                                class="mt-3 space-y-1
                                       overflow-y-auto
                                       rounded-xl border
                                       border-gray-200 p-2
                                       dark:border-gray-700"
                                style="max-height: 12rem;"
                            >
                                
                                @if ($newGospelContactNames !== [])
                                    <div
                                        class="mb-2 flex flex-wrap gap-2"
                                    >
                                        @foreach (
                                            $newGospelContactNames
                                            as $newGospelIndex => $newGospelName
                                        )
                                            <button
                                                type="button"
                                                wire:click="removeNewGospelContactCandidate({{ $newGospelIndex }})"
                                                title="Remove {{ $newGospelName }}"
                                                class="rounded-full
                                                       border
                                                       border-emerald-200
                                                       bg-emerald-100
                                                       px-3 py-1.5
                                                       text-xs font-bold
                                                       text-emerald-800
                                                       hover:bg-emerald-200
                                                       dark:border-emerald-800
                                                       dark:bg-gray-950
                                                       dark:text-emerald-300
                                                       dark:hover:bg-gray-900"
                                            >
                                                New Gospel Contact ·
                                                {{ $newGospelName }}
                                                ×
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

@forelse ($gospelContacts as $gospelContact)
                                    <label
                                        wire:key="shepherding-checkbox-contactedGospelContactIds-{{ $gospelContact->id }}"
                                        class="flex cursor-pointer
                                               items-start gap-3
                                               rounded-lg px-2 py-2
                                               hover:bg-gray-50
                                               dark:hover:bg-gray-800"
                                    >
                                        <input
                                            type="checkbox"
                                            value="{{ $gospelContact->id }}"
                                            wire:model.live="contactedGospelContactIds"
                                            class="mt-1 rounded
                                                   border-gray-300"
                                        >

                                        <span class="min-w-0">
                                            <strong
                                                class="block text-sm
                                                       text-gray-900
                                                       dark:text-gray-100"
                                            >
                                                {{ $gospelContact->display_name }}
                                            </strong>

                                            @if (
                                                $gospelContact->localityRecord
                                                || $gospelContact->contact_place
                                            )
                                                <span
                                                    class="block text-xs
                                                           text-gray-500
                                                           dark:text-gray-400"
                                                >
                                                    @if ($gospelContact->localityRecord)
                                                        {{ $gospelContact->localityRecord->name }}
                                                    @endif

                                                    @if (
                                                        $gospelContact->localityRecord
                                                        && $gospelContact->contact_place
                                                    )
                                                        ·
                                                    @endif

                                                    @if ($gospelContact->contact_place)
                                                        {{ $gospelContact->contact_place }}
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
                                        Gospel Contacts.
                                    </p>
                                @endforelse
                                @php
                                    $gospelCreationCandidate =
                                        $this
                                            ->gospelContactCreationCandidate();

                                    $gospelCandidateSelected =
                                        $gospelCreationCandidate['valid']
                                        &&
                                        collect(
                                            $newGospelContactNames
                                        )->contains(
                                            fn ($name) =>
                                                mb_strtolower(
                                                    trim(
                                                        (string) $name
                                                    )
                                                )
                                                ===
                                                mb_strtolower(
                                                    $gospelCreationCandidate[
                                                        'display_name'
                                                    ]
                                                )
                                        );
                                @endphp

                                @if (filled($targetSearch))
                                    <label
                                        class="mt-2 flex cursor-pointer
                                               items-start gap-3
                                               rounded-lg border
                                               border-dashed
                                               border-emerald-300
                                               bg-emerald-50
                                               px-3 py-2.5
                                               dark:border-emerald-700
                                               dark:bg-gray-950"
                                    >
                                        <input
                                            type="checkbox"
                                            @checked($gospelCandidateSelected)
                                            wire:click="toggleNewGospelContactCandidate"
                                            class="mt-1 rounded
                                                   border-gray-300
                                                   text-emerald-600
                                                   focus:ring-emerald-500
                                                   dark:border-gray-600
                                                   dark:bg-gray-900"
                                        >

                                        <span class="min-w-0 text-sm">
                                            <strong
                                                class="block
                                                       font-bold
                                                       text-emerald-800
                                                       dark:text-emerald-300"
                                            >
                                                Create Gospel Contact:
                                                {{ $gospelCreationCandidate['valid']
                                                    ? $gospelCreationCandidate['display_name']
                                                    : trim($targetSearch) }}
                                            </strong>

                                            <span
                                                class="mt-1 block text-xs
                                                       leading-5
                                                       text-emerald-700
                                                       dark:text-gray-400"
                                            >
                                                Will be created when the
                                                Shepherding Record is saved.
                                                Use Last Name, First Name
                                                when a last name is known.
                                                Locality will use the Contact
                                                Locality selected on this form.
                                            </span>
                                        </span>
                                    </label>
                                @endif

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
                                    wire:key="shepherding-checkbox-householdMemberPresence-{{ $person->id }}"
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
                                   lg:grid-cols-4"
                        >
                            @foreach ($activityTypes as $activity)
                                <label
                                    wire:key="shepherding-checkbox-activityTypeIds-{{ $activity->id }}"
                                    class="flex cursor-pointer
                                           items-start gap-3
                                           rounded-xl border
                                           border-gray-200 p-3
                                           dark:border-gray-700"
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $activity->id }}"
                                        wire:model.live="activityTypeIds"
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
                    {{-- Morning Revival Reading --}}
                    {{-- ============================= --}}
                    @if ($this->isMorningRevivalSelected())
                        @php
                            $recentMorningRevivalWeeks =
                                $this
                                    ->recentMorningRevivalWeeks();

                            $selectedMorningRevivalWeek =
                                $this
                                    ->selectedMorningRevivalWeek();

                            $selectedIsRecent =
                                $selectedMorningRevivalWeek
                                && $recentMorningRevivalWeeks
                                    ->contains(
                                        'id',
                                        $selectedMorningRevivalWeek
                                            ->id
                                    );
                        @endphp

                        <section
                            class="rounded-xl border
                                   border-amber-200
                                   bg-amber-50/40 p-4
                                   dark:!border-amber-900
                                   dark:!bg-gray-900"
                        >
                            <div
                                class="flex flex-col gap-3
                                       lg:flex-row
                                       lg:items-start
                                       lg:justify-between"
                            >
                                <div>
                                    <h4
                                        style="color:#f59e0b !important;"
                                        class="text-base font-bold"
                                    >
                                        Morning Revival
                                    </h4>

                                    <p
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:!text-gray-400"
                                    >
                                        Defaults to today's
                                        scheduled reading.
                                        Week and Day remain
                                        editable.
                                    </p>
                                </div>

                                <div
                                    class="flex flex-wrap
                                           gap-2"
                                >
                                    <button
                                        type="button"
                                        wire:click="useTodaysMorningRevival"
                                        class="rounded-lg
                                               border
                                               border-amber-300
                                               bg-white
                                               px-3 py-2
                                               text-xs
                                               font-bold
                                               text-amber-800
                                               hover:bg-amber-50
                                               dark:border-amber-800
                                               dark:bg-gray-800
                                               dark:text-amber-200
                                               dark:hover:bg-gray-700"
                                    >
                                        Use Today's Reading
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="openMorningRevivalArchive"
                                        class="rounded-lg
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
                                        Find Older Reading…
                                    </button>
                                </div>
                            </div>

                            <div
                                class="mt-4 grid gap-4
                                       lg:grid-cols-[1fr_10rem]"
                            >
                                <div>
                                    <label
                                        class="block text-xs
                                               font-bold uppercase
                                               tracking-wide
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Week / Message
                                    </label>

                                    <select
                                        wire:model.live.number="morningRevivalWeekId"
                                        class="mt-1 block w-full
                                               rounded-xl border
                                               border-gray-300
                                               bg-white px-4 py-3
                                               text-sm text-gray-900
                                               dark:border-gray-700
                                               dark:bg-gray-950
                                               dark:text-gray-100"
                                    >
                                        <option value="">
                                            Select Morning Revival week...
                                        </option>

                                        @if (
                                            $selectedMorningRevivalWeek
                                            && ! $selectedIsRecent
                                        )
                                            <option
                                                value="{{
                                                    $selectedMorningRevivalWeek
                                                        ->id
                                                }}"
                                            >
                                                Saved · Week
                                                {{
                                                    $selectedMorningRevivalWeek
                                                        ->week_number
                                                }}
                                                —
                                                {{
                                                    $selectedMorningRevivalWeek
                                                        ->title
                                                }}
                                            </option>
                                        @endif

                                        @foreach (
                                            $recentMorningRevivalWeeks
                                            as $mrWeek
                                        )
                                            <option
                                                value="{{ $mrWeek->id }}"
                                            >
                                                Week
                                                {{ $mrWeek->week_number }}
                                                —
                                                {{ $mrWeek->title }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <p
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Showing the eight most
                                        recent active weeks.
                                        Use Find Older Reading
                                        for the full archive.
                                    </p>

                                    @error(
                                        'morningRevivalWeekId'
                                    )
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
                                        class="block text-xs
                                               font-bold uppercase
                                               tracking-wide
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Day
                                    </label>

                                    <select
                                        wire:model.live.number="morningRevivalDay"
                                        class="mt-1 block w-full
                                               rounded-xl border
                                               border-gray-300
                                               bg-white px-4 py-3
                                               text-sm text-gray-900
                                               dark:border-gray-700
                                               dark:bg-gray-950
                                               dark:text-gray-100"
                                    >
                                        <option value="">
                                            Day...
                                        </option>

                                        @for (
                                            $day = 1;
                                            $day <= 6;
                                            $day++
                                        )
                                            <option value="{{ $day }}">
                                                Day {{ $day }}
                                            </option>
                                        @endfor
                                    </select>

                                    @error(
                                        'morningRevivalDay'
                                    )
                                        <p
                                            class="mt-1 text-xs
                                                   text-red-600"
                                        >
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            @if (
                                $selectedMorningRevivalWeek
                                && $morningRevivalDay
                            )
                                @php
                                    $selectedMrPublication =
                                        $selectedMorningRevivalWeek
                                            ->publication;

                                    $selectedMrDate =
                                        $selectedMorningRevivalWeek
                                            ->dateForDay(
                                                (int)
                                                $morningRevivalDay
                                            );
                                @endphp

                                <div
                                    class="mt-4 rounded-xl
                                           border
                                           border-amber-200
                                           bg-white p-4
                                           dark:border-amber-900
                                           dark:bg-gray-950"
                                >
                                    <div
                                        class="text-sm font-bold
                                               text-gray-950
                                               dark:text-white"
                                    >
                                        Week
                                        {{
                                            $selectedMorningRevivalWeek
                                                ->week_number
                                        }}:
                                        {{
                                            $selectedMorningRevivalWeek
                                                ->title
                                        }}
                                        · Day
                                        {{ $morningRevivalDay }}
                                    </div>

                                    @if ($selectedMrDate)
                                        <div
                                            class="mt-1 text-xs
                                                   font-semibold
                                                   text-amber-700
                                                   dark:text-amber-300"
                                        >
                                            {{
                                                $selectedMrDate
                                                    ->format(
                                                        'l, M j, Y'
                                                    )
                                            }}
                                        </div>
                                    @endif

                                    <div
                                        class="mt-2 text-sm
                                               text-gray-700
                                               dark:text-gray-200"
                                    >
                                        {{
                                            $selectedMrPublication
                                                ?->general_subject
                                        }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        {{
                                            $selectedMrPublication
                                                ?->source_title
                                        }}
                                    </div>
                                </div>
                            @else
                                <div
                                    class="mt-4 rounded-xl
                                           border border-dashed
                                           border-amber-300
                                           px-4 py-4
                                           text-sm
                                           text-amber-800
                                           dark:border-amber-800
                                           dark:text-amber-200"
                                >
                                    Choose the Morning Revival
                                    Week and Day 1–6.
                                </div>
                            @endif


                            {{-- Historical archive search --}}
                            @if ($showMorningRevivalArchive)
                                @php
                                    $morningRevivalArchiveResults =
                                        $this
                                            ->morningRevivalArchiveResults();
                                @endphp

                                <div
                                    class="mt-4 rounded-xl
                                           border
                                           border-gray-200
                                           bg-gray-50 p-4
                                           dark:border-gray-700
                                           dark:bg-gray-950"
                                >
                                    <div
                                        class="flex flex-col
                                               gap-3
                                               sm:flex-row
                                               sm:items-center
                                               sm:justify-between"
                                    >
                                        <div>
                                            <div
                                                class="font-bold
                                                       text-gray-950
                                                       dark:text-white"
                                            >
                                                Find Older
                                                Morning Revival
                                            </div>

                                            <div
                                                class="mt-1
                                                       text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Search by year,
                                                conference,
                                                general subject,
                                                or message title.
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="closeMorningRevivalArchive"
                                            class="text-xs
                                                   font-bold
                                                   text-gray-500
                                                   hover:text-gray-900
                                                   dark:text-gray-400
                                                   dark:hover:text-white"
                                        >
                                            Close
                                        </button>
                                    </div>

                                    <input
                                        type="search"
                                        wire:model.live.debounce.400ms="morningRevivalArchiveSearch"
                                        placeholder="e.g. 2025, Thanksgiving, revival..."
                                        class="mt-3 block w-full
                                               rounded-xl border
                                               border-gray-300
                                               bg-white px-4 py-3
                                               text-sm text-gray-900
                                               dark:border-gray-700
                                               dark:bg-gray-900
                                               dark:text-gray-100"
                                    >

                                    @if (
                                        trim(
                                            $morningRevivalArchiveSearch
                                        ) === ''
                                    )
                                        <div
                                            class="mt-3 text-sm
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            Enter a search to
                                            browse historical
                                            Morning Revival
                                            weeks.
                                        </div>
                                    @elseif (
                                        $morningRevivalArchiveResults
                                            ->isEmpty()
                                    )
                                        <div
                                            class="mt-3 text-sm
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            No matching Morning
                                            Revival weeks found.
                                        </div>
                                    @else
                                        <div
                                            class="mt-3
                                                   max-h-80
                                                   space-y-2
                                                   overflow-y-auto"
                                        >
                                            @foreach (
                                                $morningRevivalArchiveResults
                                                as $archiveWeek
                                            )
                                                <button
                                                    type="button"
                                                    wire:click="selectMorningRevivalArchiveWeek({{ $archiveWeek->id }})"
                                                    class="block w-full
                                                           rounded-lg
                                                           border
                                                           border-gray-200
                                                           bg-white
                                                           p-3
                                                           text-left
                                                           hover:border-amber-300
                                                           hover:bg-amber-50
                                                           dark:border-gray-700
                                                           dark:bg-gray-900
                                                           dark:hover:border-amber-800
                                                           dark:hover:bg-gray-800"
                                                >
                                                    <div
                                                        class="text-sm
                                                               font-bold
                                                               text-gray-950
                                                               dark:text-white"
                                                    >
                                                        Week
                                                        {{
                                                            $archiveWeek
                                                                ->week_number
                                                        }}:
                                                        {{
                                                            $archiveWeek
                                                                ->title
                                                        }}
                                                    </div>

                                                    <div
                                                        class="mt-1
                                                               text-xs
                                                               text-gray-600
                                                               dark:text-gray-300"
                                                    >
                                                        {{
                                                            $archiveWeek
                                                                ->publication
                                                                ?->general_subject
                                                        }}
                                                    </div>

                                                    <div
                                                        class="mt-1
                                                               text-xs
                                                               text-gray-500
                                                               dark:text-gray-400"
                                                    >
                                                        {{
                                                            $archiveWeek
                                                                ->start_date
                                                                ->format(
                                                                    'M j, Y'
                                                                )
                                                        }}
                                                        ·
                                                        {{
                                                            $archiveWeek
                                                                ->publication
                                                                ?->source_title
                                                        }}
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endif


                    {{-- ============================= --}}
                    {{-- Bible Reading --}}
                    {{-- ============================= --}}
                    @if ($this->isBibleReadingSelected())
                        <section
                            class="rounded-xl border
                                   border-emerald-200
                                   bg-emerald-50/40 p-4
                                   dark:border-emerald-900
                                   dark:bg-emerald-950/20"
                        >
                            <div
                                class="flex flex-col gap-3
                                       sm:flex-row
                                       sm:items-start
                                       sm:justify-between"
                            >
                                <div>
                                    <h4
                                        class="text-sm font-bold
                                               text-gray-900
                                               dark:text-white"
                                    >
                                        Bible Reading
                                    </h4>

                                    <p
                                        class="mt-1 text-xs
                                               text-gray-600
                                               dark:text-gray-300"
                                    >
                                        Enter the Bible portion read
                                        during this Shepherding
                                        Contact.
                                        Examples:
                                        John 3:16-21,
                                        Romans 8:1-6,
                                        or John 3:36-4:3.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    wire:click="addBibleReadingRow"
                                    class="inline-flex
                                           items-center
                                           justify-center
                                           rounded-lg
                                           border
                                           border-emerald-300
                                           bg-white
                                           px-3 py-2
                                           text-xs
                                           font-semibold
                                           text-emerald-700
                                           shadow-sm
                                           hover:bg-emerald-50
                                           dark:border-emerald-700
                                           dark:bg-gray-900
                                           dark:text-emerald-300
                                           dark:hover:bg-gray-800"
                                >
                                    + Passage
                                </button>
                            </div>

                            <div class="mt-4 space-y-3">
                                @forelse (
                                    $bibleReadingRows
                                    as $bibleReadingIndex =>
                                        $bibleReadingRow
                                )
                                    <div
                                        wire:key="bible-reading-row-{{ $bibleReadingIndex }}"
                                        class="rounded-lg border
                                               border-emerald-200
                                               bg-white p-3
                                               dark:border-emerald-900
                                               dark:bg-gray-950"
                                    >
                                        <div
                                            class="flex items-start
                                                   gap-2"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <label
                                                    class="mb-1 block
                                                           text-xs
                                                           font-semibold
                                                           text-gray-700
                                                           dark:text-gray-300"
                                                >
                                                    Passage
                                                    {{ $bibleReadingIndex + 1 }}
                                                </label>

                                                <input
                                                    type="text"
                                                    wire:model.blur="bibleReadingRows.{{ $bibleReadingIndex }}.reference"
                                                    placeholder="Example: John 3:16-21"
                                                    autocomplete="off"
                                                    class="block w-full
                                                           rounded-lg
                                                           border
                                                           border-gray-300
                                                           bg-white
                                                           px-3 py-2
                                                           text-sm
                                                           text-gray-900
                                                           shadow-sm
                                                           focus:border-emerald-500
                                                           focus:ring-emerald-500
                                                           dark:border-gray-700
                                                           dark:bg-gray-900
                                                           dark:text-gray-100"
                                                >

                                                @error(
                                                    "bibleReadingRows.{$bibleReadingIndex}.reference"
                                                )
                                                    <p
                                                        class="mt-1
                                                               text-xs
                                                               text-danger-600
                                                               dark:text-danger-400"
                                                    >
                                                        {{ $message }}
                                                    </p>
                                                @enderror
                                            </div>

                                            <button
                                                type="button"
                                                wire:click="removeBibleReadingRow({{ $bibleReadingIndex }})"
                                                class="mt-6
                                                       inline-flex
                                                       items-center
                                                       justify-center
                                                       rounded-lg
                                                       border
                                                       border-gray-300
                                                       px-3 py-2
                                                       text-xs
                                                       font-semibold
                                                       text-gray-600
                                                       hover:bg-gray-50
                                                       dark:border-gray-700
                                                       dark:text-gray-300
                                                       dark:hover:bg-gray-800"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="rounded-lg border
                                               border-dashed
                                               border-emerald-300
                                               px-4 py-4
                                               text-center
                                               text-xs
                                               text-gray-600
                                               dark:border-emerald-800
                                               dark:text-gray-300"
                                    >
                                        No Bible passages added yet.
                                        Use + Passage to record one
                                        or more Bible portions.
                                    </div>
                                @endforelse
                            </div>

                            @error('bibleReadingRows')
                                <p
                                    class="mt-2 text-xs
                                           text-danger-600
                                           dark:text-danger-400"
                                >
                                    {{ $message }}
                                </p>
                            @enderror
                        </section>
                    @endif


                    {{-- ============================= --}}
                    {{-- Hymns Sung --}}
                    {{-- ============================= --}}
                    @if ($this->isHymnSingingSelected())
                        <section
                            class="rounded-xl border
                                   border-sky-200
                                   bg-sky-50/40 p-4
                                   dark:border-sky-900
                                   dark:bg-sky-950/20"
                        >
                            @php
                                $selectedHymns =
                                    $this->selectedHymns();
                            @endphp

                            <div
                                class="flex flex-col gap-3
                                       sm:flex-row
                                       sm:items-start
                                       sm:justify-between"
                            >
                                <div>
                                    <h4
                                        class="text-sm font-bold
                                               text-gray-900
                                               dark:text-white"
                                    >
                                        Hymns Sung
                                    </h4>

                                    <p
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Search by hymn title,
                                        lyrics, or hymn number.
                                        Add as many hymns as were
                                        sung during this contact.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    wire:click="addHymnRow"
                                    class="shrink-0 rounded-xl
                                           border border-sky-300
                                           bg-white px-4 py-2
                                           text-sm font-bold
                                           text-sky-700
                                           hover:bg-sky-100
                                           dark:border-sky-800
                                           dark:bg-gray-900
                                           dark:text-sky-300"
                                >
                                    + Hymn
                                </button>
                            </div>

                            @if ($hymnRows === [])
                                <div
                                    class="mt-4 rounded-xl
                                           border border-dashed
                                           border-sky-200
                                           px-4 py-5
                                           text-center text-sm
                                           text-gray-500
                                           dark:border-sky-900
                                           dark:text-gray-400"
                                >
                                    No hymns added yet.
                                    Use + Hymn to record one or
                                    more hymns.
                                </div>
                            @else
                                <div class="mt-4 space-y-4">
                                    @foreach (
                                        $hymnRows
                                        as $hymnIndex => $hymnRow
                                    )
                                        @php
                                            $selectedHymnId =
                                                filled(
                                                    $hymnRow[
                                                        'hymn_id'
                                                    ] ?? null
                                                )
                                                    ? (int) $hymnRow[
                                                        'hymn_id'
                                                    ]
                                                    : null;

                                            $selectedHymn =
                                                $selectedHymnId
                                                    ? $selectedHymns
                                                        ->get(
                                                            $selectedHymnId
                                                        )
                                                    : null;
                                        @endphp

                                        <div
                                            wire:key="hymn-row-{{ $hymnIndex }}"
                                            style="display:flex;
                                                   align-items:center;
                                                   gap:0.75rem;"
                                            class="rounded-xl border
                                                   border-gray-200
                                                   bg-white p-4
                                                   dark:border-gray-700
                                                   dark:bg-gray-900"
                                        >
                                            <div
                                                style="display: contents;"
                                            >
                                                <span
                                                    style="order:1;
                                                           width:2rem;
                                                           height:2rem;
                                                           flex:0 0 2rem;"
                                                    class="flex
                                                           items-center
                                                           justify-center
                                                           rounded-full
                                                           bg-sky-100
                                                           text-xs font-bold
                                                           text-sky-700
                                                           dark:bg-sky-900
                                                           dark:text-sky-200"
                                                >
                                                    {{ $hymnIndex + 1 }}
                                                </span>

                                                <button
                                                    type="button"
                                                    wire:click="removeHymnRow({{ $hymnIndex }})"
                                                    style="order:3;
                                                           flex:0 0 auto;"
                                                    class="rounded-lg
                                                           px-3 py-2
                                                           text-xs
                                                           font-semibold
                                                           text-gray-400
                                                           hover:bg-red-50
                                                           hover:text-red-600
                                                           dark:hover:bg-red-950/30"
                                                >
                                                    Remove
                                                </button>
                                            </div>

                                            @if ($selectedHymn)
                                                <div
                                                    style="order:2;
                                                           min-width:0;
                                                           flex:1 1 0%;"
                                                    class="rounded-xl
                                                           border
                                                           border-sky-200
                                                           bg-sky-50 p-4
                                                           dark:border-sky-900
                                                           dark:bg-sky-950"
                                                >
                                                    <p
                                                        class="font-bold
                                                               text-gray-950
                                                               dark:text-white"
                                                    >
                                                        {{ $selectedHymn->title }}
                                                    </p>

                                                    <div
                                                        class="mt-2 flex
                                                               flex-wrap gap-2"
                                                    >
                                                        @foreach (
                                                            $selectedHymn
                                                                ->bookEntries
                                                            as $entry
                                                        )
                                                            <span
                                                                class="rounded-full
                                                                       bg-white
                                                                       px-2.5 py-1
                                                                       text-xs
                                                                       font-semibold
                                                                       text-sky-700
                                                                       dark:bg-gray-900
                                                                       dark:text-sky-300"
                                                            >
                                                                {{
                                                                    $entry
                                                                        ->hymnBook
                                                                        ?->name
                                                                    ?? 'Hymn'
                                                                }}
                                                                #{{ $entry->number }}
                                                            </span>
                                                        @endforeach

                                                        @if ($selectedHymn->language)
                                                            <span
                                                                class="rounded-full
                                                                       bg-gray-100
                                                                       px-2.5 py-1
                                                                       text-xs
                                                                       text-gray-600
                                                                       dark:bg-gray-800
                                                                       dark:text-gray-300"
                                                            >
                                                                {{
                                                                    ucfirst(
                                                                        $selectedHymn
                                                                            ->language
                                                                    )
                                                                }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    @php
                                                        $externalSources =
                                                            $selectedHymn
                                                                ->sources;
                                                    @endphp

                                                    @if (
                                                        $externalSources
                                                            ->isNotEmpty()
                                                    )
                                                        <div
                                                            class="mt-3
                                                                   space-y-2
                                                                   border-t
                                                                   border-sky-200
                                                                   pt-3
                                                                   dark:border-sky-900"
                                                        >
                                                            @foreach (
                                                                $externalSources
                                                                as $externalSource
                                                            )
                                                                @php
                                                                    $externalMetadata =
                                                                        is_array(
                                                                            $externalSource
                                                                                ->metadata
                                                                        )
                                                                            ? $externalSource
                                                                                ->metadata
                                                                            : [];

                                                                    $externalCollection =
                                                                        $externalMetadata[
                                                                            'collection_name'
                                                                        ]
                                                                        ?? null;

                                                                    $externalTrack =
                                                                        $externalMetadata[
                                                                            'track_number'
                                                                        ]
                                                                        ?? null;

                                                                    $externalLabel =
                                                                        \App\Support\HymnSourceResolver
                                                                            ::labelForProvider(
                                                                                $externalSource
                                                                                    ->provider
                                                                            );
                                                                @endphp

                                                                <div
                                                                    class="flex
                                                                           flex-col
                                                                           gap-2
                                                                           rounded-lg
                                                                           border
                                                                           border-sky-200
                                                                           bg-white
                                                                           px-3 py-2
                                                                           dark:border-sky-800
                                                                           dark:bg-gray-900"
                                                                >
                                                                    <div
                                                                        class="flex
                                                                               flex-wrap
                                                                               items-center
                                                                               gap-2"
                                                                    >
                                                                        <span
                                                                            style="
                                                                                background-color:#e0f2fe !important;
                                                                                color:#082f49 !important;
                                                                            "
                                                                            class="rounded-full
                                                                                   px-2 py-0.5
                                                                                   text-[10px]
                                                                                   font-bold
                                                                                   ring-1
                                                                                   ring-sky-200"
                                                                        >
                                                                            {{
                                                                                $externalLabel
                                                                            }}
                                                                        </span>

                                                                        @if (
                                                                            filled(
                                                                                $externalCollection
                                                                            )
                                                                        )
                                                                            <span
                                                                                class="text-xs
                                                                                       font-semibold
                                                                                       text-gray-700
                                                                                       dark:text-gray-200"
                                                                            >
                                                                                {{
                                                                                    $externalCollection
                                                                                }}

                                                                                @if (
                                                                                    filled(
                                                                                        $externalTrack
                                                                                    )
                                                                                )
                                                                                    · Track
                                                                                    {{
                                                                                        $externalTrack
                                                                                    }}
                                                                                @endif
                                                                            </span>
                                                                        @elseif (
                                                                            filled(
                                                                                $externalTrack
                                                                            )
                                                                        )
                                                                            <span
                                                                                class="text-xs
                                                                                       font-semibold
                                                                                       text-gray-700
                                                                                       dark:text-gray-200"
                                                                            >
                                                                                Track
                                                                                {{
                                                                                    $externalTrack
                                                                                }}
                                                                            </span>
                                                                        @endif
                                                                    </div>

                                                                    @if (
                                                                        filled(
                                                                            $externalSource
                                                                                ->source_url
                                                                        )
                                                                    )
                                                                        <a
                                                                            href="{{ $externalSource->source_url }}"
                                                                            target="_blank"
                                                                            rel="noopener noreferrer"
                                                                            class="w-fit
                                                                                   text-xs
                                                                                   font-semibold
                                                                                   text-sky-700
                                                                                   hover:underline
                                                                                   dark:text-sky-400"
                                                                        >
                                                                            Open
                                                                            {{
                                                                                $externalLabel
                                                                            }}
                                                                            Source
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif (
                                                filled(
                                                    $hymnRow[
                                                        'request_id'
                                                    ] ?? null
                                                )
                                            )
                                                <div
                                                    style="order:2;
                                                           min-width:0;
                                                           flex:1 1 0%;
                                                           background-color:#fffbeb !important;
                                                           border-color:#f59e0b !important;
                                                           color:#111827 !important;"
                                                    class="rounded-xl
                                                           border
                                                           px-4 py-3"
                                                >
                                                    <div
                                                        style="color:#111827 !important;"
                                                        class="text-sm
                                                               font-bold"
                                                    >
                                                        {{
                                                            $hymnRow[
                                                                'request_title'
                                                            ]
                                                            ?? 'Requested Hymn'
                                                        }}
                                                    </div>

                                                    <div
                                                        style="color:#b45309 !important;"
                                                        class="mt-1
                                                               text-xs
                                                               font-semibold"
                                                    >
                                                        Pending Admin Approval
                                                        · Request
                                                        #{{ $hymnRow['request_id'] }}
                                                    </div>

                                                    <div
                                                        style="color:#6b7280 !important;"
                                                        class="mt-1
                                                               text-xs"
                                                    >
                                                        When approved or linked
                                                        in Hymns Setup, the
                                                        canonical Hymn will be
                                                        attached to this
                                                        Shepherding record.
                                                    </div>
                                                </div>
                                            @else
                                                @php
                                                    $hymnSearchResults =
                                                        $this
                                                            ->hymnSearchResults(
                                                                $hymnIndex
                                                            );
                                                @endphp

                                                <div
                                                    style="order:2;
                                                           min-width:0;
                                                           flex:1 1 0%;"
                                                >
                                                    <input
                                                        type="search"
                                                        wire:model.live.debounce.350ms="hymnRows.{{ $hymnIndex }}.search"
                                                        placeholder="Search title, lyrics, or hymn number..."
                                                        class="block w-full
                                                               rounded-xl border
                                                               border-gray-300
                                                               bg-white
                                                               px-4 py-3
                                                               text-sm
                                                               text-gray-900
                                                               dark:border-gray-700
                                                               dark:bg-gray-950
                                                               dark:text-gray-100"
                                                    >

                                                    @if (
                                                        filled(
                                                            $hymnRow[
                                                                'search'
                                                            ] ?? ''
                                                        )
                                                    )
                                                        <div
                                                            class="mt-2
                                                                   max-h-72
                                                                   overflow-y-auto
                                                                   rounded-xl
                                                                   border
                                                                   border-gray-200
                                                                   bg-white p-2
                                                                   dark:border-gray-700
                                                                   dark:bg-gray-950"
                                                        >
                                                            @forelse (
                                                                $hymnSearchResults
                                                                as $hymn
                                                            )
                                                                <button
                                                                    type="button"
                                                                    wire:key="hymn-result-{{ $hymnIndex }}-{{ $hymn->id }}"
                                                                    wire:click="selectHymn({{ $hymnIndex }}, {{ $hymn->id }})"
                                                                    class="block
                                                                           w-full
                                                                           rounded-lg
                                                                           px-3 py-2.5
                                                                           text-left
                                                                           hover:bg-sky-50
                                                                           dark:hover:bg-sky-950"
                                                                >
                                                                    <span
                                                                        class="block
                                                                               text-sm
                                                                               font-bold
                                                                               text-gray-900
                                                                               dark:text-white"
                                                                    >
                                                                        {{ $hymn->title }}
                                                                    </span>

                                                                    <span
                                                                        class="mt-1
                                                                               flex
                                                                               flex-wrap
                                                                               gap-x-2
                                                                               gap-y-1
                                                                               text-xs
                                                                               text-gray-500
                                                                               dark:text-gray-400"
                                                                    >
                                                                        @foreach (
                                                                            $hymn
                                                                                ->bookEntries
                                                                            as $entry
                                                                        )
                                                                            <span>
                                                                                {{
                                                                                    $entry
                                                                                        ->hymnBook
                                                                                        ?->name
                                                                                    ?? 'Hymn'
                                                                                }}
                                                                                #{{ $entry->number }}
                                                                            </span>
                                                                        @endforeach

                                                                        @if ($hymn->language)
                                                                            <span>
                                                                                {{
                                                                                    ucfirst(
                                                                                        $hymn
                                                                                            ->language
                                                                                    )
                                                                                }}
                                                                            </span>
                                                                        @endif
                                                                    </span>
                                                                </button>
                                                            @empty
                                                                <div
                                                                    class="rounded-lg
                                                                           border
                                                                           border-sky-200
                                                                           bg-sky-50/70
                                                                           px-4 py-4
                                                                           dark:border-sky-900
                                                                           dark:bg-sky-950/30"
                                                                >
                                                                    <p
                                                                        class="text-sm
                                                                               leading-6
                                                                               text-gray-700
                                                                               dark:text-gray-200"
                                                                    >
                                                                        <strong>
                                                                            No matching hymns found.
                                                                        </strong>
                                                                        Please add the hymn title
                                                                        to <strong>Notes</strong>
                                                                        for this contact.
                                                                        You may also submit a
                                                                        Hymn Addition Request
                                                                        for Admin approval
                                                                        <span
                                                                            class="ml-1
                                                                                   inline-flex
                                                                                   rounded-full
                                                                                   bg-sky-100
                                                                                   px-2 py-0.5
                                                                                   text-[10px]
                                                                                   font-bold
                                                                                   uppercase
                                                                                   text-sky-700
                                                                                   ring-1
                                                                                   ring-sky-200
                                                                                   dark:bg-sky-500/15
                                                                                   dark:text-sky-300
                                                                                   dark:ring-sky-500/30" style="color: #082f49 !important;"
                                                                        >
                                                                            Optional
                                                                        </span>
                                                                    </p>

                                                                    @if (
                                                                        ! array_key_exists(
                                                                            $hymnIndex,
                                                                            $hymnRequestForms
                                                                        )
                                                                    )
                                                                        <button
                                                                            type="button"
                                                                            wire:click="openHymnRequest({{ $hymnIndex }})"
                                                                            class="mt-3
                                                                                   rounded-lg
                                                                                   border
                                                                                   border-sky-300
                                                                                   bg-white
                                                                                   px-3 py-2
                                                                                   text-xs
                                                                                   font-bold
                                                                                   text-sky-700
                                                                                   hover:bg-sky-100
                                                                                   dark:border-sky-700
                                                                                   dark:bg-sky-950/40
                                                                                   dark:text-sky-300
                                                                                   dark:hover:bg-sky-900/50"
                                                                        
                                                                            style="color: #082f49 !important;">
                                                                            + Request Hymn Addition
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            @endforelse
                                                        </div>
                                                    @endif

                                                    @include(
                                                        'filament.pages.partials.shepherding-hymn-request-form',
                                                        [
                                                            'hymnIndex' => $hymnIndex,
                                                        ]
                                                    )
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endif


                    {{-- ============================= --}}
                    {{-- Ministry Used --}}
                    {{-- ============================= --}}
                    <section
                        x-data="{
                            ministrySearch: '',
                        }"
                    >
                        @php
                            $selectedMinistryIds =
                                collect(
                                    $ministryLessonIds
                                )
                                    ->map(
                                        fn ($id) =>
                                            (int) $id
                                    );

                            $selectedMinistryLessons =
                                $ministryBooks
                                    ->flatMap(
                                        fn ($book) =>
                                            $book->lessons
                                    )
                                    ->filter(
                                        fn ($lesson) =>
                                            $selectedMinistryIds
                                                ->contains(
                                                    (int)
                                                    $lesson->id
                                                )
                                    )
                                    ->sortBy(
                                        fn ($lesson) =>
                                            sprintf(
                                                '%05d-%05d',
                                                $lesson
                                                    ->book
                                                    ?->sort_order
                                                    ?? 0,
                                                $lesson
                                                    ->sort_order
                                                    ?? 0
                                            )
                                    )
                                    ->values();
                        @endphp

                        <div
                            class="flex flex-col gap-2
                                   sm:flex-row
                                   sm:items-start
                                   sm:justify-between"
                        >
                            <div>
                                <h4
                                    class="text-sm font-bold
                                           text-gray-900
                                           dark:text-white"
                                >
                                    Ministry Used
                                </h4>

                                <p
                                    class="mt-1 text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Optional. Select the specific
                                    ministry topics covered during
                                    this contact.
                                </p>
                            </div>

                            @if (
                                $selectedMinistryLessons
                                    ->isNotEmpty()
                            )
                                <button
                                    type="button"
                                    wire:click="clearMinistryLessons"
                                    class="shrink-0 text-xs
                                           font-semibold
                                           text-gray-500
                                           hover:text-red-600
                                           dark:text-gray-400
                                           dark:hover:text-red-400"
                                >
                                    Clear selection
                                </button>
                            @endif
                        </div>


                        {{-- Selected lessons --}}
                        @if (
                            $selectedMinistryLessons
                                ->isNotEmpty()
                        )
                            <div
                                class="mt-4 rounded-xl border
                                       border-primary-200
                                       bg-primary-50 p-4
                                       dark:border-primary-900
                                       dark:bg-primary-950"
                            >
                                <div
                                    class="flex items-center
                                           justify-between gap-3"
                                >
                                    <p
                                        class="text-xs font-bold
                                               uppercase
                                               tracking-wide
                                               text-primary-700
                                               dark:text-primary-300"
                                    >
                                        Selected Ministry
                                    </p>

                                    <span
                                        class="rounded-full
                                               bg-primary-100
                                               px-2.5 py-1
                                               text-xs font-bold
                                               text-primary-700
                                               dark:bg-primary-900
                                               dark:text-primary-200"
                                    >
                                        {{
                                            $selectedMinistryLessons
                                                ->count()
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="mt-3 flex
                                           flex-wrap gap-2"
                                >
                                    @foreach (
                                        $selectedMinistryLessons
                                        as $lesson
                                    )
                                        <button
                                            type="button"
                                            wire:click="removeMinistryLesson(
                                                {{ $lesson->id }}
                                            )"
                                            title="Remove {{ $lesson->code }}"
                                            class="inline-flex
                                                   items-center gap-1.5
                                                   rounded-lg
                                                   border
                                                   border-primary-200
                                                   bg-white px-3 py-2
                                                   text-left text-xs
                                                   font-medium
                                                   text-gray-700
                                                   transition
                                                   hover:border-red-300
                                                   hover:text-red-700
                                                   dark:border-primary-800
                                                   dark:bg-gray-900
                                                   dark:text-gray-200
                                                   dark:hover:border-red-800
                                                   dark:hover:text-red-300"
                                        >
                                            <strong
                                                class="font-mono
                                                       text-primary-700
                                                       dark:text-primary-300"
                                            >
                                                {{ $lesson->code }}
                                            </strong>

                                            <span>
                                                {{ $lesson->title }}
                                            </span>

                                            <span
                                                class="ml-1
                                                       text-gray-400"
                                            >
                                                ×
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif


                        {{-- Search --}}
                        <div class="mt-4">
                            <label
                                class="block text-xs
                                       font-semibold
                                       text-gray-600
                                       dark:text-gray-300"
                            >
                                Search Ministry Topics
                            </label>

                            <div class="relative mt-2">
                                <input
                                    type="search"
                                    x-model="ministrySearch"
                                    placeholder="Search HG01, salvation, church life, prayer..."
                                    class="block w-full
                                           rounded-xl border
                                           border-gray-300
                                           bg-white px-4 py-3
                                           pr-4 text-sm
                                           text-gray-900
                                           shadow-sm
                                           focus:border-primary-500
                                           focus:ring-primary-500
                                           dark:border-gray-700
                                           dark:bg-gray-800
                                           dark:text-white"
                                >

                            </div>
                        </div>


                        {{-- Ministry Books --}}
                        <div
                            class="mt-4 overflow-hidden
                                   rounded-xl border
                                   border-gray-200
                                   bg-white
                                   divide-y divide-gray-200
                                   dark:border-gray-700
                                   dark:bg-gray-900
                                   dark:divide-gray-700"
                        >
                            @forelse (
                                $ministryBooks
                                as $book
                            )
                                @php
                                    $bookSearchText =
                                        strtolower(
                                            trim(
                                                $book->code
                                                . ' '
                                                . $book->title
                                                . ' '
                                                . $book->lessons
                                                    ->map(
                                                        fn ($lesson) =>
                                                            $lesson->code
                                                            . ' '
                                                            . $lesson->title
                                                    )
                                                    ->implode(' ')
                                            )
                                        );
                                @endphp

                                <details
                                    x-show="
                                        ministrySearch.trim() === ''
                                        || {{ \Illuminate\Support\Js::from(
                                            $bookSearchText
                                        ) }}.includes(
                                            ministrySearch
                                                .trim()
                                                .toLowerCase()
                                        )
                                    "
                                    x-effect="
                                        const query =
                                            ministrySearch
                                                .trim()
                                                .toLowerCase();

                                        $el.open =
                                            query !== ''
                                            && {{ \Illuminate\Support\Js::from(
                                                $bookSearchText
                                            ) }}.includes(query);
                                    "
                                    class="group
                                           bg-white
                                           dark:bg-gray-900"
                                >
                                    <summary
                                        class="cursor-pointer
                                               px-3 py-2
                                               text-gray-900
                                               transition
                                               hover:bg-gray-50
                                               dark:text-gray-100
                                               dark:hover:bg-gray-800"
                                    >
                                        <span
                                            class="inline-flex
                                                   w-[calc(100%-1.5rem)]
                                                   align-middle
                                                   items-center
                                                   justify-between
                                                   gap-3"
                                        >
                                            <div
                                                class="flex min-w-0
                                                       items-center
                                                       gap-2"
                                            >
                                                <span
                                                    class="w-7 shrink-0
                                                           font-mono
                                                           text-xs font-bold
                                                           text-primary-700
                                                           dark:text-primary-300"
                                                >
                                                    {{ $book->code }}
                                                </span>

                                                <span
                                                    class="min-w-0 truncate
                                                           text-sm font-semibold
                                                           text-gray-800
                                                           dark:text-gray-100"
                                                    title="{{ $book->title }}"
                                                >
                                                    {{ $book->title }}
                                                </span>
                                            </div>

                                            <div
                                                class="flex
                                                       items-center
                                                       gap-2"
                                            >
                                                @php
                                                    $selectedInBook =
                                                        $book
                                                            ->lessons
                                                            ->whereIn(
                                                                'id',
                                                                $selectedMinistryIds
                                                            )
                                                            ->count();
                                                @endphp

                                                @if (
                                                    $selectedInBook > 0
                                                )
                                                    <span
                                                        class="rounded-full
                                                               bg-primary-100
                                                               px-2.5 py-1
                                                               text-xs
                                                               font-bold
                                                               text-primary-700
                                                               dark:bg-primary-900
                                                               dark:text-primary-200"
                                                    >
                                                        {{
                                                            $selectedInBook
                                                        }}
                                                        selected
                                                    </span>
                                                @endif

                                                <span
                                                    class="min-w-6
                                                           text-right
                                                           text-xs
                                                           font-medium
                                                           text-gray-400"
                                                    title="Topics"
                                                >
                                                    {{
                                                        $book
                                                            ->lessons
                                                            ->count()
                                                    }}
                                                </span>
                                            </div>
                                        </span>
                                    </summary>

                                    <div
                                        class="border-t
                                               border-gray-200
                                               bg-gray-50 p-2
                                               dark:border-gray-700
                                               dark:bg-gray-950"
                                    >
                                        <div
                                            class="grid
                                                   sm:grid-cols-2"
                                        >
                                            @foreach (
                                                $book->lessons
                                                as $lesson
                                            )
                                                @php
                                                    $lessonSearchText =
                                                        strtolower(
                                                            $lesson->code
                                                            . ' '
                                                            . $lesson->title
                                                        );
                                                @endphp

                                                <label
                                                    wire:key="shepherding-checkbox-ministryLessonIds-{{ $lesson->id }}"
                                                    x-show="
                                                        ministrySearch.trim() === ''
                                                        || {{ \Illuminate\Support\Js::from(
                                                            $lessonSearchText
                                                        ) }}.includes(
                                                            ministrySearch
                                                                .trim()
                                                                .toLowerCase()
                                                        )
                                                        || {{ \Illuminate\Support\Js::from(
                                                            strtolower(
                                                                $book->code
                                                                . ' '
                                                                . $book->title
                                                            )
                                                        ) }}.includes(
                                                            ministrySearch
                                                                .trim()
                                                                .toLowerCase()
                                                        )
                                                    "
                                                    wire:key="ministry-lesson-{{ $lesson->id }}"
                                                    class="flex
                                                           cursor-pointer
                                                           items-center
                                                           gap-2
                                                           px-2.5 py-1.5
                                                           text-gray-700
                                                           transition
                                                           hover:bg-gray-100
                                                           has-[:checked]:bg-primary-50
                                                           dark:bg-gray-950
                                                           dark:text-gray-200
                                                           dark:hover:bg-gray-800
                                                           dark:has-[:checked]:bg-primary-950"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        value="{{ $lesson->id }}"
                                                        wire:model.live="ministryLessonIds"
                                                        class="rounded
                                                               border-gray-300
                                                               text-primary-600
                                                               focus:ring-primary-500
                                                               dark:border-gray-600
                                                               dark:bg-gray-800"
                                                    >

                                                    <div
                                                        class="flex min-w-0
                                                               flex-1
                                                               items-center
                                                               gap-2"
                                                    >
                                                        <strong
                                                            class="w-11
                                                                   shrink-0
                                                                   font-mono
                                                                   text-xs
                                                                   text-primary-700
                                                                   dark:text-primary-300"
                                                        >
                                                            {{ $lesson->code }}
                                                        </strong>

                                                        <span
                                                            title="{{ $lesson->title }}"
                                                            class="min-w-0
                                                                   truncate
                                                                   text-sm
                                                                   text-gray-700
                                                                   dark:text-gray-200"
                                                        >
                                                            {{ $lesson->title }}
                                                        </span>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>
                            @empty
                                <div
                                    class="rounded-xl
                                           border border-dashed
                                           border-gray-300
                                           p-5 text-sm
                                           text-gray-500
                                           dark:border-gray-700
                                           dark:text-gray-400"
                                >
                                    No active Ministry Lessons are
                                    configured yet. Add them under
                                    Administration → Ministry Books.
                                </div>
                            @endforelse
                        </div>

                        <p
                            class="mt-3 text-xs
                                   text-gray-400"
                        >
                            Ministry topics are separate from
                            activity codes. Select only the
                            material actually covered during
                            this Shepherding Contact.
                        </p>
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
                                wire:key="shepherding-checkbox-participantIds-{{ $person->id }}"
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
                       sm:flex-row sm:items-start
                       sm:justify-between"
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
                        Most recent 10 Shepherding Contact records.
                    </p>
                </div>

                <a
                    href="{{ \App\Filament\Pages\ShepherdingHistory::getUrl() }}"
                    class="shrink-0 text-sm font-semibold
                           text-primary-600
                           hover:text-primary-500
                           dark:text-primary-400"
                >
                    Open Shepherding History →
                </a>
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
                                            Campus Contact ·
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
                                            Gospel Contact ·
                                            {{ $gospelContact->display_name }}
                                        </span>
                                    @endforeach

                                    @if (
                                        $contact->contactedPeople->isEmpty()
                                        && $contact->contactedHouseholds->isEmpty()
                                        && $contact->contactedCampusContacts->isEmpty()
                                        && $contact->contactedGospelContacts->isEmpty()
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
                                    @php
                                        $ministryGroups =
                                            $contact
                                                ->ministryLessons
                                                ->sortBy(
                                                    fn ($lesson) =>
                                                        sprintf(
                                                            '%05d-%05d',
                                                            $lesson
                                                                ->book
                                                                ?->sort_order
                                                                ?? 99999,
                                                            $lesson
                                                                ->sort_order
                                                                ?? 99999
                                                        )
                                                )
                                                ->groupBy(
                                                    fn ($lesson) =>
                                                        (string) (
                                                            $lesson
                                                                ->ministry_book_id
                                                            ?? 0
                                                        )
                                                );
                                    @endphp

                                    <div
                                        class="mt-3 rounded-lg
                                               border
                                               border-violet-200
                                               bg-violet-50/40
                                               px-3 py-2
                                               dark:border-violet-900
                                               dark:bg-violet-950/20"
                                    >
                                        <p
                                            class="text-xs font-bold
                                                   uppercase tracking-wide
                                                   text-violet-700
                                                   dark:text-violet-300"
                                        >
                                            Ministry Used
                                        </p>

                                        <div class="mt-1.5 space-y-2">
                                            @foreach (
                                                $ministryGroups
                                                as $lessons
                                            )
                                                @php
                                                    $book =
                                                        $lessons
                                                            ->first()
                                                            ?->book;
                                                @endphp

                                                <div>
                                                    <p
                                                        class="text-xs
                                                               font-semibold
                                                               text-violet-700
                                                               dark:text-violet-300"
                                                    >
                                                        {{ $book?->code
                                                            ?? 'Ministry' }}

                                                        @if ($book?->title)
                                                            ·
                                                            {{ $book->title }}
                                                        @endif
                                                    </p>

                                                    <div
                                                        class="mt-1
                                                               flex flex-wrap
                                                               gap-x-3 gap-y-1"
                                                    >
                                                        @foreach (
                                                            $lessons
                                                            as $lesson
                                                        )
                                                            <span
                                                                class="text-xs
                                                                       text-gray-600
                                                                       dark:text-gray-300"
                                                            >
                                                                <strong
                                                                    class="font-mono
                                                                           text-violet-700
                                                                           dark:text-violet-300"
                                                                >
                                                                    {{ $lesson->code }}
                                                                </strong>

                                                                @if ($lesson->title)
                                                                    ·
                                                                    {{ $lesson->title }}
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Morning Revival used --}}
                                @if (
                                    $contact->morningRevivalWeek
                                    && $contact->morning_revival_day
                                )
                                    @php
                                        $historyMrWeek =
                                            $contact
                                                ->morningRevivalWeek;

                                        $historyMrPublication =
                                            $historyMrWeek
                                                ->publication;

                                        $historyMrDate =
                                            $historyMrWeek
                                                ->dateForDay(
                                                    (int)
                                                    $contact
                                                        ->morning_revival_day
                                                );
                                    @endphp

                                    <div
                                        class="mt-3 rounded-lg
                                               border
                                               border-amber-200
                                               bg-amber-50/50
                                               px-3 py-2
                                               dark:border-amber-900
                                               dark:bg-amber-950/20"
                                    >
                                        <p
                                            class="text-xs font-bold
                                                   uppercase
                                                   tracking-wide
                                                   text-amber-700
                                                   dark:text-amber-300"
                                        >
                                            Morning Revival
                                        </p>

                                        <p
                                            class="mt-1 text-sm
                                                   font-semibold
                                                   text-gray-900
                                                   dark:text-gray-100"
                                        >
                                            Week
                                            {{
                                                $historyMrWeek
                                                    ->week_number
                                            }}
                                            —
                                            {{
                                                $historyMrWeek
                                                    ->title
                                            }}
                                            · Day
                                            {{
                                                $contact
                                                    ->morning_revival_day
                                            }}
                                        </p>

                                        @if ($historyMrDate)
                                            <p
                                                class="mt-1 text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                {{
                                                    $historyMrDate
                                                        ->format(
                                                            'D, M j, Y'
                                                        )
                                                }}
                                            </p>
                                        @endif

                                        @if (
                                            $historyMrPublication
                                                ?->general_subject
                                        )
                                            <p
                                                class="mt-1 text-xs
                                                       text-gray-700
                                                       dark:text-gray-300"
                                            >
                                                <strong>
                                                    Subject:
                                                </strong>

                                                {{
                                                    $historyMrPublication
                                                        ->general_subject
                                                }}
                                            </p>
                                        @endif

                                        @if (
                                            $historyMrPublication
                                                ?->source_title
                                        )
                                            <p
                                                class="mt-1 text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                {{
                                                    $historyMrPublication
                                                        ->source_title
                                                }}
                                            </p>
                                        @endif
                                    </div>
                                @endif


                                {{-- Hymns actually sung --}}
                                @if (
                                    $contact->hymns->isNotEmpty()
                                    || $contact
                                        ->hymnAdditionRequests
                                        ->isNotEmpty()
                                )
                                    @php
                                        $historyHymnRows =
                                            $contact
                                                ->hymns
                                                ->map(
                                                    fn ($hymn) => [
                                                        'sort_order' =>
                                                            (int)
                                                            $hymn
                                                                ->pivot
                                                                ->sort_order,

                                                        'type' =>
                                                            'hymn',

                                                        'record' =>
                                                            $hymn,
                                                    ]
                                                )
                                                ->concat(
                                                    $contact
                                                        ->hymnAdditionRequests
                                                        ->map(
                                                            fn ($request) => [
                                                                'sort_order' =>
                                                                    (int)
                                                                    $request
                                                                        ->pivot
                                                                        ->sort_order,

                                                                'type' =>
                                                                    'request',

                                                                'record' =>
                                                                    $request,
                                                            ]
                                                        )
                                                )
                                                ->sortBy(
                                                    'sort_order'
                                                )
                                                ->values();
                                    @endphp

                                    <div
                                        class="mt-3 rounded-lg
                                               border
                                               border-sky-200
                                               bg-sky-50/40
                                               px-3 py-2
                                               dark:border-sky-900
                                               dark:bg-sky-950/20"
                                    >
                                        <p
                                            class="text-xs font-bold
                                                   uppercase
                                                   tracking-wide
                                                   text-sky-700
                                                   dark:text-sky-300"
                                        >
                                            Hymns Sung
                                        </p>

                                        <div
                                            class="mt-1.5
                                                   space-y-2"
                                        >
                                            @foreach (
                                                $historyHymnRows
                                                as $historyHymnRow
                                            )
                                                @if (
                                                    $historyHymnRow[
                                                        'type'
                                                    ] === 'hymn'
                                                )
                                                    @php
                                                        $historyHymn =
                                                            $historyHymnRow[
                                                                'record'
                                                            ];

                                                        $historyExternalSource =
                                                            $historyHymn
                                                                ->sources
                                                                ->first(
                                                                    function (
                                                                        $source
                                                                    ): bool {
                                                                        $metadata =
                                                                            is_array(
                                                                                $source
                                                                                    ->metadata
                                                                            )
                                                                                ? $source
                                                                                    ->metadata
                                                                                : [];

                                                                        return filled(
                                                                            $metadata[
                                                                                'collection_name'
                                                                            ]
                                                                            ?? null
                                                                        );
                                                                    }
                                                                );

                                                        $historyExternalMetadata =
                                                            $historyExternalSource
                                                                && is_array(
                                                                    $historyExternalSource
                                                                        ->metadata
                                                                )
                                                                    ? $historyExternalSource
                                                                        ->metadata
                                                                    : [];
                                                    @endphp

                                                    <div
                                                        class="text-xs
                                                               text-gray-700
                                                               dark:text-gray-300"
                                                    >
                                                        <strong
                                                            class="text-gray-900
                                                                   dark:text-gray-100"
                                                        >
                                                            {{
                                                                $historyHymn
                                                                    ->title
                                                            }}
                                                        </strong>

                                                        @if (
                                                            $historyHymn
                                                                ->bookEntries
                                                                ->isNotEmpty()
                                                        )
                                                            <span
                                                                class="text-gray-500
                                                                       dark:text-gray-400"
                                                            >
                                                                —
                                                                {{
                                                                    $historyHymn
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
                                                                        ->join(
                                                                            ', '
                                                                        )
                                                                }}
                                                            </span>
                                                        @elseif (
                                                            filled(
                                                                $historyExternalMetadata[
                                                                    'collection_name'
                                                                ]
                                                                ?? null
                                                            )
                                                        )
                                                            <span
                                                                class="text-gray-500
                                                                       dark:text-gray-400"
                                                            >
                                                                —
                                                                {{
                                                                    $historyExternalMetadata[
                                                                        'collection_name'
                                                                    ]
                                                                }}

                                                                @if (
                                                                    filled(
                                                                        $historyExternalMetadata[
                                                                            'track_number'
                                                                        ]
                                                                        ?? null
                                                                    )
                                                                )
                                                                    · Track
                                                                    {{
                                                                        $historyExternalMetadata[
                                                                            'track_number'
                                                                        ]
                                                                    }}
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    @php
                                                        $historyRequest =
                                                            $historyHymnRow[
                                                                'record'
                                                            ];
                                                    @endphp

                                                    <div
                                                        class="text-xs
                                                               text-amber-700
                                                               dark:text-amber-300"
                                                    >
                                                        <strong>
                                                            {{
                                                                $historyRequest
                                                                    ->title
                                                            }}
                                                        </strong>
                                                        · Pending Hymn
                                                        approval
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
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
