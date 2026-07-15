<x-filament-panels::page>
    @php
        $groupedContacts = $this->groupedContacts();
        $summary = $this->summary();
        $schoolOptions = $this->schoolOptions();
        $localityOptions = $this->localityOptions();

        $selectedSchool = request('school', '');
        $selectedPeopleStatus = request('peopleStatus', '');
        $search = request('q', '');
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

        {{-- Add Campus Contact --}}
        <details class="min-w-0 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
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

                                        @if ($contact->facebook_account)
                                            <p class="mt-1 break-words text-xs font-normal text-blue-600 dark:text-blue-400">
                                                {{ $contact->facebook_account }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $contact->locality }}
                                    </td>

                                    <td class="max-w-[200px] break-words px-4 py-3">
                                        {{ $contact->course_strand ?: 'Not recorded' }}

                                        @if ($contact->grade_level)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $contact->grade_level }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="max-w-[220px] break-words px-4 py-3">
                                        {{ $contact->contact_number ?: 'No phone' }}

                                        @if ($contact->email)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $contact->email }}
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
                                            <form
                                                method="POST"
                                                action="{{ route('quezonprovinceactivities.campus-work.contacts.add-to-people', $contact) }}"
                                                onsubmit="return confirm('Add this Campus Contact to the People Database as a Gospel Friend?');"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-500"
                                                >
                                                    Add to People Database
                                                </button>
                                            </form>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-right">
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

    </div>
</x-filament-panels::page>
