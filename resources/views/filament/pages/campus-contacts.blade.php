<x-filament-panels::page>
    @php
        $groupedContacts = $this->groupedContacts();
        $summary = $this->summary();
        $schoolOptions = $this->schoolOptions();
        $localityOptions = $this->localityOptions();

        $selectedSchool = request('school', '');
        $selectedPeopleStatus = request('peopleStatus', '');
        $search = request('q', '');

        $existingPeopleSearch = trim($this->existingPeopleSearch);
        $availableExistingPeople = $this->availableExistingPeople();
        $linkablePeople = $this->linkablePeople();

        $possibleMatchContactId = session(
            'campus_contact_possible_match_contact_id'
        );

        $possibleMatches = collect(
            session(
                'campus_contact_possible_matches',
                []
            )
        );

        $possibleCampusContactDuplicates = collect(
            session(
                'campus_contact_possible_duplicates',
                []
            )
        );

        $campusContactDuplicateInput = session(
            'campus_contact_possible_duplicate_input',
            []
        );
    @endphp

    <div class="min-w-0 space-y-6">

        {{-- Header --}}
        <div class="min-w-0 overflow-hidden rounded-2xl border border-primary-200 bg-primary-50 p-5 shadow-sm dark:border-primary-900 dark:bg-primary-950 sm:p-6">
            <p class="text-sm font-bold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Campus Work
            </p>

            <h2 class="mt-2 break-words text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                Campus Contacts
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Manage campus contacts grouped by school. A contact may later be added to the People Database as a Gospel Friend.
            </p>
        </div>

        {{-- Success Messages --}}
        @if (session('campus_contact_created'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Campus Contact created.</p>
            </div>
        @endif

        @if (session('campus_contact_updated'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Campus Contact updated.</p>
            </div>
        @endif

        @if (session('campus_contact_deleted'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">Campus Contact deleted.</p>
            </div>
        @endif

        @if (session('campus_contact_added_to_people'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">
                    Campus Contact added to the People Database as a Gospel Friend.
                </p>
            </div>
        @endif

        @if (session('campus_contact_linked_existing_person'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">
                    Campus Contact successfully linked to an existing Person.
                </p>

                <p class="mt-1 text-sm">
                    The existing Person's church status was preserved.
                </p>
            </div>
        @endif

        @if (session('campus_contact_existing_people_added'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">
                    Existing People added to Campus Contacts.
                </p>

                <p class="mt-1 text-sm">
                    {{ session('campus_contact_existing_people_added_count', 0) }}
                    person(s) added and linked successfully.
                </p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Please fix the following:</p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Campus Contact Actions folder --}}
        <details
            class="min-w-0 rounded-2xl border border-gray-300 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            @if ($errors->any() || $existingPeopleSearch !== '' || $possibleCampusContactDuplicates->isNotEmpty()) open @endif
        >
            <summary class="cursor-pointer px-5 py-4 text-lg font-bold text-gray-900 hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-800">
                Campus Contact Actions
            </summary>

            <div class="space-y-5 border-t border-gray-300 p-5 dark:border-gray-700">
        {{-- Add Campus Contact --}}
        <details
            class="min-w-0 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm dark:border-emerald-900 dark:bg-emerald-950"
            @if ($possibleCampusContactDuplicates->isNotEmpty()) open @endif
        >
            <summary class="cursor-pointer px-5 py-4 text-lg font-bold text-emerald-900 hover:bg-emerald-100 dark:text-emerald-100 dark:hover:bg-emerald-900">
                Add Campus Contact
            </summary>

            <form
                method="POST"
                action="{{ route('quezonprovinceactivities.campus-work.contacts.store') }}"
                class="grid gap-4 border-t border-emerald-200 p-5 dark:border-emerald-900 md:grid-cols-2"
            >
                @csrf

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        First Name
                    </label>

                    <input
                        type="text"
                        name="firstname"
                        value="{{ old('firstname') }}"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Last Name
                    </label>

                    <input
                        type="text"
                        name="lastname"
                        value="{{ old('lastname') }}"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Sex
                    </label>

                    <select
                        name="sex"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="">Choose...</option>
                        <option value="Male" @selected(old('sex') === 'Male')>
                            Male
                        </option>
                        <option value="Female" @selected(old('sex') === 'Female')>
                            Female
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Locality
                    </label>

                    <input
                        type="text"
                        name="locality"
                        value="{{ old('locality') }}"
                        list="campus-contact-localities"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        School / Campus
                    </label>

                    <input
                        type="text"
                        name="school_campus"
                        value="{{ old('school_campus') }}"
                        list="campus-contact-schools"
                        placeholder="Enter or select school..."
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Course / Strand
                    </label>

                    <input
                        type="text"
                        name="course_strand"
                        value="{{ old('course_strand') }}"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Grade / Year Level
                    </label>

                    <input
                        type="text"
                        name="grade_level"
                        value="{{ old('grade_level') }}"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Contact Number
                    </label>

                    <input
                        type="text"
                        name="contact_number"
                        value="{{ old('contact_number') }}"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Facebook Account
                    </label>

                    <input
                        type="text"
                        name="facebook_account"
                        value="{{ old('facebook_account') }}"
                        placeholder="Profile URL, username, or Facebook name"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Notes
                    </label>

                    <textarea
                        name="notes"
                        rows="4"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >{{ old('notes') }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500 sm:w-auto"
                    >
                        Add Campus Contact
                    </button>
                </div>
            </form>
        </details>

        {{-- Add Existing Person from People Database --}}
        <details
            class="min-w-0 rounded-2xl border border-gray-300 bg-gray-50 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            @if ($existingPeopleSearch !== '') open @endif
        >
            <summary class="cursor-pointer px-5 py-4 text-lg font-bold text-gray-900 hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-800">
                Add Existing Person from People Database
            </summary>

            <div class="border-t border-gray-300 p-5 dark:border-gray-700">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Search the People Database and add one or multiple existing
                    people directly as linked Campus Contacts. Their existing
                    church status will not be changed.
                </p>

                                {{-- Search Existing People --}}
                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <input
                        type="search"
                        wire:model.live.debounce.500ms="existingPeopleSearch"
                        placeholder="Search name, locality, school, course, status, Facebook..."
                        class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-500 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-400"
                    >

                    <button
                        type="button"
                        wire:click="$set('existingPeopleSearch', '')"
                        class="rounded-xl border border-gray-300 bg-white px-5 py-3 text-center text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Clear
                    </button>
                </div>


                {{-- Available Existing People --}}
                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.campus-work.contacts.existing-people.store') }}"
                    class="mt-5"
                >
                    @csrf

                    <div
                        class="space-y-2 rounded-xl border border-gray-300 bg-white p-3 pr-2 dark:border-gray-700 dark:bg-gray-950"
                        style="height: 360px; max-height: 360px; overflow-y: scroll; overflow-x: hidden; overscroll-behavior: contain;"
                    >
                        @forelse ($availableExistingPeople as $person)
                            @php
                                $personSchool = $person
                                    ->educationProfile
                                    ?->school_workplace;

                                $personCourse = $person
                                    ->educationProfile
                                    ?->course_strand;

                                $personYear = $person
                                    ->educationProfile
                                    ?->grade_level;

                                $personStatus = $person
                                    ->churchProfile
                                    ?->status;

                                $personDetails = collect([
                                    $person->locality ?: 'Locality not recorded',
                                    $personSchool ?: 'School not recorded',
                                    $personCourse,
                                    $personYear,
                                ])
                                    ->filter()
                                    ->implode(' · ');
                            @endphp

                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-300 bg-gray-50 p-3 transition hover:border-indigo-400 hover:bg-indigo-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-500 dark:hover:bg-gray-800">
                                <input
                                    type="checkbox"
                                    name="person_ids[]"
                                    value="{{ $person->id }}"
                                    class="mt-1 rounded border-gray-400 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-950"
                                >

                                <div class="min-w-0 flex-1">
                                    <p class="break-words font-bold text-gray-900 dark:text-gray-100">
                                        {{ $person->display_name }}
                                    </p>

                                    <p class="mt-1 break-words text-xs leading-relaxed text-gray-700 dark:text-gray-300">
                                        {{ $personDetails }}
                                    </p>

                                    <div class="mt-2">
                                        <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-1 text-xs font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-100">
                                            {{ $personStatus ?: 'Unknown status' }}
                                        </span>
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="p-6 text-center text-sm text-gray-600 dark:text-gray-400">
                                No available People records found.

                                @if ($existingPeopleSearch !== '')
                                    Try a different search.
                                @else
                                    People already linked to Campus Contacts are excluded automatically.
                                @endif
                            </div>
                        @endforelse
                    </div>

                    @if ($availableExistingPeople->isNotEmpty())
                        <p class="mt-3 text-xs font-semibold text-gray-600 dark:text-gray-400">
                            Showing around 5 people at a time. Scroll inside the box to view more results.
                        </p>

                        <div class="mt-4">
                            <button
                                type="submit"
                                class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400 sm:w-auto"
                            >
                                Add Selected to Campus Contacts
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </details>

        {{-- Campus Contact Import Results --}}
        @if (
            session('campus_contact_import_status')
            === 'validated'
        )
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-100">
                <p class="font-bold">
                    CSV validation successful.
                </p>

                <p class="mt-1 text-sm">
                    {{ session('campus_contact_import_summary.rows_found', 0) }}
                    contact row(s) are ready to import.
                </p>
            </div>
        @endif

        @if (
            session('campus_contact_import_status')
            === 'imported'
        )
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">
                    Campus Contacts imported successfully.
                </p>

                <p class="mt-1 text-sm">
                    {{ session('campus_contact_import_summary.rows_imported', 0) }}
                    contact(s) imported.
                </p>
            </div>
        @endif

        @if (
            session('campus_contact_import_status')
            === 'failed'
        )
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">
                    Campus Contact CSV validation failed.
                </p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach (
                        session(
                            'campus_contact_import_errors',
                            []
                        ) as $error
                    )
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Import Campus Contacts --}}
        <details class="min-w-0 overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 shadow-sm dark:border-blue-900 dark:bg-blue-950">
            <summary class="cursor-pointer px-5 py-4 text-lg font-bold text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900">
                Import Campus Contacts
            </summary>

            <form
                method="POST"
                action="{{ route('quezonprovinceactivities.campus-work.contacts.import') }}"
                enctype="multipart/form-data"
                class="border-t border-blue-200 p-5 dark:border-blue-900"
            >
                @csrf

                <p class="text-sm text-blue-800 dark:text-blue-200">
                    Upload a CSV file containing Campus Contacts.
                    Incomplete contacts are allowed. The four People
                    fields are required only when adding a contact
                    to the People Database.
                </p>

                <div class="mt-4">
                    <input
                        type="file"
                        name="csv_file"
                        accept=".csv,text/csv"
                        required
                        class="block w-full rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-blue-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        type="submit"
                        name="action"
                        value="validate"
                        class="rounded-xl border border-blue-300 bg-white px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-gray-950 dark:text-blue-200"
                    >
                        Validate CSV
                    </button>

                    <button
                        type="submit"
                        name="action"
                        value="import"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-500"
                    >
                        Import Contacts
                    </button>

                    <a
                        href="{{ route('quezonprovinceactivities.campus-work.contacts.import-template') }}"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Download CSV Template
                    </a>
                </div>
            </form>
        </details>

            </div>
        </details>

        <datalist id="campus-contact-schools">
            @foreach ($schoolOptions as $school)
                <option value="{{ $school }}"></option>
            @endforeach
        </datalist>

        <datalist id="campus-contact-localities">
            @foreach ($localityOptions as $locality)
                <option value="{{ $locality }}"></option>
            @endforeach
        </datalist>

        {{-- Summary --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-900 dark:bg-primary-950">
                <p class="text-xs font-bold uppercase text-primary-600 dark:text-primary-300">
                    Total Contacts
                </p>

                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $summary['total'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                <p class="text-xs font-bold uppercase text-amber-600 dark:text-amber-300">
                    Not in People
                </p>

                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $summary['not_in_people'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950">
                <p class="text-xs font-bold uppercase text-green-600 dark:text-green-300">
                    Added to People
                </p>

                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $summary['added_to_people'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950">
                <p class="text-xs font-bold uppercase text-sky-600 dark:text-sky-300">
                    Schools
                </p>

                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $summary['schools'] }}
                </p>
            </div>
        </div>

        {{-- Filters --}}
        <form
            method="GET"
            class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:grid-cols-4"
        >
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Search contacts..."
                class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >

            <select
                name="school"
                class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >
                <option value="">All Schools</option>

                <option
                    value="__no_school"
                    @selected($selectedSchool === '__no_school')
                >
                    School not recorded
                </option>

                @foreach ($schoolOptions as $school)
                    <option
                        value="{{ $school }}"
                        @selected($selectedSchool === $school)
                    >
                        {{ $school }}
                    </option>
                @endforeach
            </select>

            <select
                name="peopleStatus"
                class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >
                <option value="">All People Statuses</option>

                <option
                    value="unlinked"
                    @selected($selectedPeopleStatus === 'unlinked')
                >
                    Not yet in People Database
                </option>

                <option
                    value="linked"
                    @selected($selectedPeopleStatus === 'linked')
                >
                    Added to People Database
                </option>
            </select>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="flex-1 rounded-xl bg-primary-600 px-4 py-3 text-sm font-bold text-white hover:bg-primary-500"
                >
                    Apply
                </button>

                <a
                    href="{{ \App\Filament\Pages\CampusContacts::getUrl() }}"
                    class="rounded-xl border border-gray-300 px-4 py-3 text-sm font-bold text-gray-700 dark:border-gray-700 dark:text-gray-200"
                >
                    Clear
                </a>
            </div>
        </form>

        {{-- Contacts grouped by School --}}
        @forelse ($groupedContacts as $school => $contacts)
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="border-b border-sky-200 bg-sky-100 px-4 py-3 dark:border-sky-900 dark:bg-sky-950">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="break-words font-bold text-gray-900 dark:text-white">
                            {{ $school }}
                        </h3>

                        <span class="shrink-0 rounded-full bg-sky-600 px-3 py-1 text-xs font-bold text-white">
                            {{ $contacts->count() }}
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">
                                    Name
                                </th>

                                <th class="px-4 py-3 text-left font-semibold">
                                    Locality
                                </th>

                                <th class="px-4 py-3 text-left font-semibold">
                                    Course / Year
                                </th>

                                <th class="px-4 py-3 text-left font-semibold">
                                    Contact
                                </th>

                                <th class="px-4 py-3 text-left font-semibold">
                                    People Database
                                </th>

                                <th class="px-4 py-3 text-right font-semibold">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($contacts as $contact)
                                <tr>
                                    <td class="max-w-[230px] break-words px-4 py-3 font-bold text-gray-900 dark:text-white">
                                        {{ $contact->display_name }}

                                        @if ($contact->effective_facebook_account)
                                            <p class="mt-1 break-words text-xs font-normal text-blue-600 dark:text-blue-400">
                                                {{ $contact->effective_facebook_account }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $contact->effective_locality ?: 'Not recorded' }}
                                    </td>

                                    <td class="max-w-[200px] break-words px-4 py-3">
                                        {{ $contact->effective_course_strand ?: 'Not recorded' }}

                                        @if ($contact->effective_grade_level)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $contact->effective_grade_level }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="max-w-[220px] break-words px-4 py-3">
                                        {{ $contact->effective_contact_number ?: 'No phone' }}

                                        @if ($contact->effective_email)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $contact->effective_email }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($contact->person)
                                            <a
                                                href="{{ $this->personUrl($contact->person) }}"
                                                class="inline-flex rounded-full bg-green-600 px-3 py-1 text-xs font-bold text-white hover:bg-green-500"
                                            >
                                                View Person
                                            </a>
                                        @else
                                            <div class="flex flex-col items-start gap-2">
                                                <form
                                                    method="POST"
                                                    action="{{ route('quezonprovinceactivities.campus-work.contacts.add-to-people', $contact) }}"
                                                >
                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-500"
                                                    >
                                                        Add to People Database
                                                    </button>
                                                </form>

                                                <button
                                                    type="button"
                                                    onclick="
                                                        const dialog = document.getElementById('link-existing-person-dialog');
                                                        const form = document.getElementById('link-existing-person-form');
                                                        const name = document.getElementById('link-existing-person-contact-name');

                                                        form.action = @js(route(
                                                            'quezonprovinceactivities.campus-work.contacts.link-existing-person',
                                                            $contact
                                                        ));

                                                        name.textContent = @js($contact->display_name);

                                                        dialog.showModal();
                                                    "
                                                    class="rounded-lg border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950 dark:text-violet-200"
                                                >
                                                    Link Existing Person
                                                </button>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                onclick="document.getElementById('edit-campus-contact-{{ $contact->id }}').showModal()"
                                                class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-primary-500"
                                            >
                                                Edit
                                            </button>

                                            @if (auth()->user()?->canDeleteRecords())
                                                <form
                                                    method="POST"
                                                    action="{{ route('quezonprovinceactivities.campus-work.contacts.destroy', $contact) }}"
                                                    onsubmit="return confirm('Delete this Campus Contact? Linked People records will not be deleted.');"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-500"
                                                    >
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                <h3 class="font-bold text-gray-900 dark:text-white">
                    No Campus Contacts found.
                </h3>
            </div>
        @endforelse

        {{-- Possible Campus Contact duplicate warning --}}
        @if ($possibleCampusContactDuplicates->isNotEmpty())
            <dialog
                id="possible-campus-contact-duplicate-dialog"
                class="m-auto w-[calc(100%-2rem)] max-w-3xl rounded-2xl border border-amber-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/70 dark:border-amber-900 dark:bg-gray-900 dark:text-white"
                style="z-index: 10000; position: fixed; inset: 0; margin: auto;"
            >
                <div class="min-w-0">
                    <div class="flex items-start justify-between gap-4 border-b border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-900 dark:bg-amber-950">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                Possible Campus Contact Duplicate
                            </p>

                            <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                                A similar Campus Contact already exists.
                            </h3>

                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Review the existing contact before creating another one.
                            </p>
                        </div>

                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="shrink-0 rounded-lg px-3 py-1.5 font-bold text-gray-500 hover:bg-amber-100 dark:text-gray-300 dark:hover:bg-amber-900"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="max-h-[65vh] space-y-3 overflow-y-auto p-5">
                        @foreach ($possibleCampusContactDuplicates as $match)
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="break-words text-lg font-bold text-gray-900 dark:text-white">
                                            {{ $match['name'] }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-200">
                                                {{ $match['reason'] }}
                                            </span>

                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                {{ $match['sex'] ?: 'Sex not recorded' }}
                                            </span>

                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                {{ $match['locality'] ?: 'Locality not recorded' }}
                                            </span>

                                            <span class="rounded-full bg-sky-100 px-2.5 py-1 font-semibold text-sky-700 dark:bg-sky-950 dark:text-sky-200">
                                                {{ $match['school'] ?: 'School not recorded' }}
                                            </span>

                                            <span class="rounded-full bg-violet-100 px-2.5 py-1 font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-200">
                                                {{ $match['people_status'] }}
                                            </span>
                                        </div>

                                        @if ($match['course'] || $match['year_level'])
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $match['course'] ?: 'Course not recorded' }}
                                                @if ($match['year_level'])
                                                    · {{ $match['year_level'] }}
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-between">
                        <a
                            href="{{ \App\Filament\Pages\CampusContacts::getUrl([
                                'q' => $campusContactDuplicateInput['firstname'] ?? '',
                            ]) }}"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Review Existing Contacts
                        </a>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <button
                                type="button"
                                onclick="this.closest('dialog').close()"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </button>

                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.campus-work.contacts.store') }}"
                                onsubmit="return confirm('Create this Campus Contact anyway even though a similar contact exists?');"
                            >
                                @csrf

                                <input type="hidden" name="create_anyway" value="1">

                                @foreach ([
                                    'firstname',
                                    'lastname',
                                    'sex',
                                    'locality',
                                    'school_campus',
                                    'course_strand',
                                    'grade_level',
                                    'contact_number',
                                    'email',
                                    'facebook_account',
                                    'notes',
                                ] as $field)
                                    <input
                                        type="hidden"
                                        name="{{ $field }}"
                                        value="{{ old($field, $campusContactDuplicateInput[$field] ?? '') }}"
                                    >
                                @endforeach

                                <button
                                    type="submit"
                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-500"
                                >
                                    Create Campus Contact Anyway
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </dialog>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const dialog = document.getElementById(
                        'possible-campus-contact-duplicate-dialog'
                    );

                    if (dialog && !dialog.open) {
                        dialog.showModal();
                    }
                });
            </script>
        @endif

        {{-- Possible Existing Person duplicate warning --}}
        @if ($possibleMatchContactId && $possibleMatches->isNotEmpty())
            <dialog
                id="possible-existing-person-dialog"
                class="m-auto w-[calc(100%-2rem)] max-w-3xl rounded-2xl border border-amber-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/70 dark:border-amber-900 dark:bg-gray-900 dark:text-white"
                style="z-index: 10000; position: fixed; inset: 0; margin: auto;"
            >
                <div class="min-w-0">

                    <div class="flex items-start justify-between gap-4 border-b border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-900 dark:bg-amber-950">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                Possible Existing Person Found
                            </p>

                            <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                                A possible duplicate already exists.
                            </h3>

                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Review the possible matches before creating a new Person.
                            </p>
                        </div>

                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="shrink-0 rounded-lg px-3 py-1.5 font-bold text-gray-500 hover:bg-amber-100 dark:text-gray-300 dark:hover:bg-amber-900"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="max-h-[65vh] space-y-3 overflow-y-auto p-5">
                        @foreach ($possibleMatches as $match)
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="break-words text-lg font-bold text-gray-900 dark:text-white">
                                            {{ $match['name'] }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                {{ $match['sex'] ?: 'Sex not recorded' }}
                                            </span>

                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                {{ $match['locality'] ?: 'Locality not recorded' }}
                                            </span>

                                            <span class="rounded-full bg-sky-100 px-2.5 py-1 font-semibold text-sky-700 dark:bg-sky-950 dark:text-sky-200">
                                                {{ $match['school'] ?: 'School not recorded' }}
                                            </span>

                                            <span class="rounded-full bg-green-100 px-2.5 py-1 font-semibold text-green-700 dark:bg-green-950 dark:text-green-200">
                                                {{ $match['status'] ?: 'Unknown status' }}
                                            </span>
                                        </div>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'quezonprovinceactivities.campus-work.contacts.link-existing-person',
                                            ['contact' => $possibleMatchContactId]
                                        ) }}"
                                        class="shrink-0"
                                    >
                                        @csrf

                                        <input
                                            type="hidden"
                                            name="person_id"
                                            value="{{ $match['id'] }}"
                                        >

                                        <button
                                            type="submit"
                                            class="w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-500 sm:w-auto"
                                        >
                                            Link to This Person
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-between">
                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>

                        <form
                            method="POST"
                            action="{{ route(
                                'quezonprovinceactivities.campus-work.contacts.create-new-person-anyway',
                                ['contact' => $possibleMatchContactId]
                            ) }}"
                            onsubmit="return confirm('Create a new Person anyway even though possible matches exist?');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-500"
                            >
                                Create New Person Anyway
                            </button>
                        </form>
                    </div>
                </div>
            </dialog>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const dialog = document.getElementById(
                        'possible-existing-person-dialog'
                    );

                    if (dialog && !dialog.open) {
                        dialog.showModal();
                    }
                });
            </script>
        @endif

        {{-- Link Existing Person dialog --}}
        <dialog
            id="link-existing-person-dialog"
            class="m-auto w-[calc(100%-2rem)] max-w-2xl rounded-2xl border border-gray-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/70 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            style="z-index: 9999; position: fixed; inset: 0; margin: auto;"
        >
            <form
                id="link-existing-person-form"
                method="POST"
                action=""
                class="min-w-0"
            >
                @csrf

                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-400">
                            Link Existing Person
                        </p>

                        <h3
                            id="link-existing-person-contact-name"
                            class="mt-1 break-words text-lg font-bold"
                        >
                            Campus Contact
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Select an existing Person to link to this Campus Contact.
                            The existing church status will be preserved.
                        </p>
                    </div>

                    <button
                        type="button"
                        onclick="this.closest('dialog').close()"
                        class="shrink-0 rounded-lg px-3 py-1.5 font-bold text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        ✕
                    </button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto p-5">
                    <label class="block text-sm font-bold text-gray-900 dark:text-white">
                        Existing Person
                    </label>

                    <select
                        name="person_id"
                        required
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="">
                            Choose an existing Person...
                        </option>

                        @foreach ($linkablePeople as $person)
                            <option value="{{ $person->id }}">
                                {{ $person->display_name }}
                                · {{ $person->locality ?: 'No locality' }}
                                · {{ $person->educationProfile?->school_workplace ?: 'No school' }}
                                · {{ $person->churchProfile?->status ?: 'Unknown' }}
                            </option>
                        @endforeach
                    </select>

                    @if ($linkablePeople->isEmpty())
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                            No available existing People records remain.
                            People already linked to another Campus Contact are excluded.
                        </div>
                    @endif

                    <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
                        When linking, existing Person information will not be overwritten.
                        Blank Person fields may be filled from the Campus Contact.
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onclick="this.closest('dialog').close()"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        @disabled($linkablePeople->isEmpty())
                        class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-bold text-white hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Link Existing Person
                    </button>
                </div>
            </form>
        </dialog>

        {{-- Edit Campus Contact dialogs --}}
        @foreach ($groupedContacts->flatten(1) as $contact)
            <dialog
                id="edit-campus-contact-{{ $contact->id }}"
                class="m-auto w-[calc(100%-2rem)] max-w-3xl rounded-2xl border border-gray-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/70 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                style="z-index: 9999; position: fixed; inset: 0; margin: auto;"
            >
                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.campus-work.contacts.update', $contact) }}"
                    class="min-w-0"
                >
                    @csrf
                    @method('PATCH')

                    {{-- Modal Header --}}
                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                                Edit Campus Contact
                            </p>

                            <h3 class="mt-1 break-words text-lg font-bold">
                                {{ $contact->display_name }}
                            </h3>

                            @if ($contact->person_id)
                                <p class="mt-1 text-xs font-semibold text-green-600 dark:text-green-400">
                                    Already linked to People Database
                                </p>
                            @endif
                        </div>

                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="shrink-0 rounded-lg px-3 py-1.5 font-bold text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="grid max-h-[70vh] gap-4 overflow-y-auto p-5 md:grid-cols-2">

                        {{-- First Name --}}
                        <div>
                            <label class="block text-sm font-bold">
                                First Name
                            </label>

                            <input
                                type="text"
                                name="firstname"
                                value="{{ $contact->effective_firstname }}"
                                maxlength="100"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Last Name --}}
                        <div>
                            <label class="block text-sm font-bold">
                                Last Name
                            </label>

                            <input
                                type="text"
                                name="lastname"
                                value="{{ $contact->effective_lastname }}"
                                maxlength="100"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Sex --}}
                        <div>
                            <label class="block text-sm font-bold">
                                Sex
                            </label>

                            <select
                                name="sex"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="">
                                    Not recorded
                                </option>

                                <option
                                    value="Male"
                                    @selected($contact->effective_sex === 'Male')
                                >
                                    Male
                                </option>

                                <option
                                    value="Female"
                                    @selected($contact->effective_sex === 'Female')
                                >
                                    Female
                                </option>
                            </select>
                        </div>

                        {{-- Locality --}}
                        <div>
                            <label class="block text-sm font-bold">
                                Locality
                            </label>

                            <input
                                type="text"
                                name="locality"
                                value="{{ $contact->effective_locality ?: 'Not recorded' }}"
                                list="campus-contact-localities"
                                maxlength="150"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- School / Campus --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold">
                                School / Campus
                            </label>

                            <input
                                type="text"
                                name="school_campus"
                                value="{{ $contact->effective_school_campus }}"
                                list="campus-contact-schools"
                                maxlength="255"
                                placeholder="Enter or select school..."
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Course / Strand --}}
                        <div>
                            <label class="block text-sm font-bold">
                                Course / Strand
                            </label>

                            <input
                                type="text"
                                name="course_strand"
                                value="{{ $contact->effective_course_strand }}"
                                maxlength="255"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Grade / Year Level --}}
                        <div>
                            <label class="block text-sm font-bold">
                                Grade / Year Level
                            </label>

                            <input
                                type="text"
                                name="grade_level"
                                value="{{ $contact->effective_grade_level }}"
                                maxlength="100"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Contact Number --}}
                        <div>
                            <label class="block text-sm font-bold">
                                Contact Number
                            </label>

                            <input
                                type="text"
                                name="contact_number"
                                value="{{ $contact->effective_contact_number }}"
                                maxlength="20"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-bold">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="{{ $contact->effective_email }}"
                                maxlength="255"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Facebook Account --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold">
                                Facebook Account
                            </label>

                            <input
                                type="text"
                                name="facebook_account"
                                value="{{ $contact->effective_facebook_account }}"
                                maxlength="255"
                                placeholder="Profile URL, username, or Facebook name"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        {{-- Notes --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold">
                                Notes
                            </label>

                            <textarea
                                name="notes"
                                rows="5"
                                maxlength="5000"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ $contact->notes }}</textarea>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-500"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </dialog>
        @endforeach

    </div>
</x-filament-panels::page>
