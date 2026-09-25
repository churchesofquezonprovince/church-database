<x-filament-panels::page>
    @php
        $contacts = $this->contacts();
        $summary = $this->summary();
        $localityGroups = $this->localityOptions();

        $possibleDuplicates = collect(
            session(
                'gospel_contact_possible_duplicates',
                []
            )
        );

        $duplicateInput = session(
            'gospel_contact_possible_duplicate_input',
            []
        );

        $possiblePeopleMatches = collect(
            session(
                'gospel_contact_possible_matches',
                []
            )
        );

        $possibleMatchContactId = session(
            'gospel_contact_possible_match_contact_id'
        );
    @endphp

    <div class="space-y-6">

        <div
            class="rounded-2xl border border-primary-200
                   bg-primary-50 p-6 shadow-sm
                   dark:border-primary-900
                   dark:bg-primary-950"
        >
            <p
                class="text-sm font-semibold uppercase tracking-wide
                       text-primary-600 dark:text-primary-300"
            >
                Gospel Work
            </p>

            <h2
                class="mt-2 text-3xl font-bold
                       text-gray-900 dark:text-white"
            >
                Gospel Contacts
            </h2>

            <p
                class="mt-2 max-w-4xl text-sm
                       text-gray-600 dark:text-gray-300"
            >
                @if ($this->viewMode === 'archived')
                    These Gospel Contacts have already been
                    absorbed into the People Database. Their
                    original Gospel Contact records are retained
                    so Shepherding history remains intact.
                @else
                    Maintain people currently being contacted
                    through gospel work. Once a Gospel Contact
                    is absorbed into the People Database, the
                    contact moves to Archived Gospel Contacts.
                @endif
            </p>
        </div>


        {{-- Flash messages --}}
        @if (session('gospel_contact_created'))
            <div data-coqp-flash="success" data-coqp-flash-id="d69dba80aca76ece" data-coqp-keep="false" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                Gospel Contact created.
            </div>
        @endif

        @if (session('gospel_contact_updated'))
            <div data-coqp-flash="success" data-coqp-flash-id="01eb89c348e726eb" data-coqp-keep="false" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                Gospel Contact updated.
            </div>
        @endif

        @if (session('gospel_contact_deleted'))
            <div data-coqp-flash="success" data-coqp-flash-id="13f6baf83229ee16" data-coqp-keep="false" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                Gospel Contact deleted.
            </div>
        @endif

        @if (session('gospel_contact_delete_blocked'))
            @php
                $shepherdingCount = (int) session(
                    'gospel_contact_delete_shepherding_count',
                    0
                );
            @endphp

            <div
                data-coqp-flash="danger"
                data-coqp-flash-id="gospel-contact-delete-blocked"
                data-coqp-keep="true"
                class="rounded-xl bg-red-50 p-4 text-red-800"
            >
                Cannot delete this Gospel Contact because it is
                referenced by {{ $shepherdingCount }}
                Shepherding {{ $shepherdingCount === 1 ? 'Record' : 'Records' }}.
                Delete or edit the related Shepherding
                {{ $shepherdingCount === 1 ? 'Record' : 'Records' }}
                first.
            </div>
        @endif

        @if (session('gospel_contact_added_to_people'))
            <div data-coqp-flash="success" data-coqp-flash-id="a63340a505773ce6" data-coqp-keep="false" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                Gospel Contact absorbed into the People Database
                and moved to Archived Gospel Contacts.
            </div>
        @endif

        @if (session('gospel_contact_linked_existing_person'))
            <div data-coqp-flash="success" data-coqp-flash-id="b18eb3509f8d5ecc" data-coqp-keep="false" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                Gospel Contact linked to the existing Person.
            </div>
        @endif

        @if (session('gospel_contact_existing_people_added'))
            <div data-coqp-flash="success" data-coqp-flash-id="7a97eb563d630848" data-coqp-keep="false" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                {{ session('gospel_contact_existing_people_added_count', 0) }}
                existing People added as Gospel Contacts.
            </div>
        @endif

        @if ($errors->any())
            <div data-coqp-flash="danger" data-coqp-flash-id="19b595ed1d5eedb1" data-coqp-keep="true" class="rounded-xl bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Duplicate Gospel Contact warning --}}
        @if ($possibleDuplicates->isNotEmpty())
            <div
                class="rounded-2xl border border-amber-300
                       bg-amber-50 p-5
                       dark:border-amber-800
                       dark:bg-amber-950"
            >
                <h3 class="font-bold text-amber-900 dark:text-amber-100">
                    Possible Gospel Contact Duplicate
                </h3>

                <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                    A similar Gospel Contact already exists.
                </p>

                <div class="mt-4 space-y-2">
                    @foreach ($possibleDuplicates as $match)
                        <div
                            class="rounded-xl border border-amber-200
                                   bg-white p-3 text-sm
                                   dark:border-amber-800
                                   dark:bg-gray-900"
                        >
                            <strong>{{ $match['name'] }}</strong>

                            @if ($match['locality'] ?? null)
                                · {{ $match['locality'] }}
                            @endif

                            @if ($match['contact_place'] ?? null)
                                · {{ $match['contact_place'] }}
                            @endif

                            · {{ $match['people_status'] }}
                        </div>
                    @endforeach
                </div>

                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.gospel-work.contacts.store') }}"
                    class="mt-4"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="create_anyway"
                        value="1"
                    >

                    @foreach ([
                        'firstname',
                        'lastname',
                        'sex',
                        'locality_id',
                        'contact_number',
                        'email',
                        'facebook_account',
                        'address',
                        'contact_place',
                        'notes',
                    ] as $field)
                        <input
                            type="hidden"
                            name="{{ $field }}"
                            value="{{ $duplicateInput[$field] ?? '' }}"
                        >
                    @endforeach

                    <button
                        type="submit"
                        class="rounded-xl bg-amber-600 px-4 py-2
                               text-sm font-bold text-white"
                    >
                        Create Gospel Contact Anyway
                    </button>
                </form>
            </div>
        @endif


        {{-- Possible existing Person --}}
        @if (
            $possiblePeopleMatches->isNotEmpty()
            && $possibleMatchContactId
        )
            <div
                class="rounded-2xl border border-blue-300
                       bg-blue-50 p-5
                       dark:border-blue-800
                       dark:bg-blue-950"
            >
                <h3 class="font-bold text-blue-900 dark:text-blue-100">
                    Possible Existing Person
                </h3>

                <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                    Before creating a new Person, check whether
                    this Gospel Contact is already in the
                    People Database.
                </p>

                <div class="mt-4 space-y-3">
                    @foreach ($possiblePeopleMatches as $match)
                        <div
                            class="flex flex-wrap items-center
                                   justify-between gap-3
                                   rounded-xl bg-white p-3
                                   dark:bg-gray-900"
                        >
                            <div class="text-sm">
                                <strong>{{ $match['name'] }}</strong>

                                @if ($match['locality'] ?? null)
                                    · {{ $match['locality'] }}
                                @endif

                                @if ($match['status'] ?? null)
                                    · {{ $match['status'] }}
                                @endif

                                @if ($match['contact_origin'] ?? null)
                                    · Origin:
                                    {{ $match['contact_origin'] }}
                                @endif
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'quezonprovinceactivities.gospel-work.contacts.link-existing-person',
                                    $possibleMatchContactId
                                ) }}"
                            >
                                @csrf

                                <input
                                    type="hidden"
                                    name="person_id"
                                    value="{{ $match['id'] }}"
                                >

                                <button
                                    type="submit"
                                    class="rounded-xl bg-blue-600
                                           px-3 py-2 text-xs
                                           font-bold text-white"
                                >
                                    Link This Person
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <form
                    method="POST"
                    action="{{ route(
                        'quezonprovinceactivities.gospel-work.contacts.create-new-person-anyway',
                        $possibleMatchContactId
                    ) }}"
                    class="mt-4"
                >
                    @csrf

                    <button
                        type="submit"
                        class="rounded-xl border border-blue-400
                               px-4 py-2 text-sm font-bold
                               text-blue-800 dark:text-blue-200"
                    >
                        Create New Person Anyway
                    </button>
                </form>
            </div>
        @endif


        {{-- Gospel Contact Actions folder --}}
        <details
            class="min-w-0 overflow-hidden rounded-2xl border border-gray-300 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            @if (
                $errors->any()
                || $possibleDuplicates->isNotEmpty()
                || $possiblePeopleMatches->isNotEmpty()
            )
                open
            @endif
        >
            <summary
                class="cursor-pointer px-5 py-4
                       text-lg font-bold text-gray-900
                       hover:bg-gray-100
                       dark:text-gray-100
                       dark:hover:bg-gray-800"
            >
                Gospel Contact Actions
            </summary>

            <div
                class="space-y-5 border-t
                       border-gray-300 p-5
                       dark:border-gray-700"
            >
        {{-- Add Gospel Contact --}}
        <details
            class="rounded-2xl border border-gray-200
                   bg-white p-6 shadow-sm
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <summary
                class="cursor-pointer text-lg font-bold
                       text-gray-950 dark:text-white"
            >
                Add Gospel Contact
            </summary>

            <form
                method="POST"
                action="{{ route('quezonprovinceactivities.gospel-work.contacts.store') }}"
                class="mt-5 space-y-4"
            >
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold">
                            First Name *
                        </label>

                        <input
                            name="firstname"
                            value="{{ old('firstname') }}"
                            required
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300
                                   dark:border-gray-700
                                   dark:bg-gray-950 px-4 py-3"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Last Name
                        </label>

                        <input
                            name="lastname"
                            value="{{ old('lastname') }}"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300
                                   dark:border-gray-700
                                   dark:bg-gray-950 px-4 py-3"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Sex
                        </label>

                        <select
                            name="sex"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300
                                   dark:border-gray-700
                                   dark:bg-gray-950 px-4 py-3"
                        >
                            <option value="">Not recorded</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Locality
                        </label>

                        <select
                            name="locality_id"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300
                                   dark:border-gray-700
                                   dark:bg-gray-950 px-4 py-3"
                        >
                            <option value="">Not recorded</option>

                            @foreach ($localityGroups as $group => $options)
                                <optgroup label="{{ $group }}">
                                    @foreach ($options as $id => $label)
                                        <option
                                            value="{{ $id }}"
                                            @selected((string) old('locality_id') === (string) $id)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Contact Number
                        </label>

                        <input
                            name="contact_number"
                            value="{{ old('contact_number') }}"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300
                                   dark:border-gray-700
                                   dark:bg-gray-950 px-4 py-3"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300
                                   dark:border-gray-700
                                   dark:bg-gray-950 px-4 py-3"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Facebook
                        </label>

                        <input
                            name="facebook_account"
                            value="{{ old('facebook_account') }}"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300
                                   dark:border-gray-700
                                   dark:bg-gray-950 px-4 py-3"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-semibold">
                            Contact Place
                        </label>

                        <input
                            name="contact_place"
                            value="{{ old('contact_place') }}"
                            placeholder="Home, workplace, barangay, marketplace..."
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300
                                   dark:border-gray-700
                                   dark:bg-gray-950 px-4 py-3"
                        >
                    </div>
                </div>

                <div>
                    <label class="text-sm font-semibold">
                        Address
                    </label>

                    <input
                        name="address"
                        value="{{ old('address') }}"
                        class="mt-2 block w-full rounded-xl
                               border border-gray-300
                               dark:border-gray-700
                               dark:bg-gray-950 px-4 py-3"
                    >
                </div>

                <div>
                    <label class="text-sm font-semibold">
                        Notes
                    </label>

                    <textarea
                        name="notes"
                        rows="3"
                        class="mt-2 block w-full rounded-xl
                               border border-gray-300
                               dark:border-gray-700
                               dark:bg-gray-950 px-4 py-3"
                    >{{ old('notes') }}</textarea>
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-primary-600
                           px-5 py-2.5 text-sm
                           font-bold text-white"
                >
                    Add Gospel Contact
                </button>
            </form>
        </details>


        {{-- Active / Archived Gospel Contacts --}}
        <a
            href="{{ \App\Filament\Pages\GospelContacts::getUrl([
                'view' => $this->viewMode === 'archived'
                    ? 'active'
                    : 'archived',
            ]) }}"
            class="flex items-center justify-between gap-4
                   rounded-2xl border border-gray-200
                   bg-white p-6 shadow-sm transition
                   hover:border-primary-300 hover:bg-primary-50
                   dark:border-gray-700 dark:bg-gray-900
                   dark:hover:border-primary-800
                   dark:hover:bg-gray-800"
        >
            <div>
                <p class="text-lg font-bold text-gray-950 dark:text-white">
                    {{ $this->viewMode === 'archived'
                        ? 'Back to Active Gospel Contacts'
                        : 'Archived Gospel Contacts' }}
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($this->viewMode === 'archived')
                        Return to people still being followed up
                        as Gospel Contacts.
                    @else
                        View Gospel Contacts already absorbed into
                        the People Database.
                    @endif
                </p>
            </div>

            @if ($this->viewMode !== 'archived')
                <span
                    class="inline-flex shrink-0 rounded-full
                           bg-gray-100 px-3 py-1
                           text-sm font-bold text-gray-700
                           dark:bg-gray-800 dark:text-gray-200"
                >
                    {{ $summary['archived'] }}
                </span>
            @endif
        </a>
            </div>
        </details>


        {{-- Summary --}}
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['label' => 'Active Gospel Contacts', 'value' => $summary['active']],
                ['label' => 'Archived Gospel Contacts', 'value' => $summary['archived']],
                ['label' => 'Historical Total', 'value' => $summary['historical_total']],
            ] as $card)
                <div
                    class="rounded-2xl border border-gray-200
                           bg-white p-5 shadow-sm
                           dark:border-gray-700 dark:bg-gray-900"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $card['label'] }}
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold
                               text-gray-950 dark:text-white"
                    >
                        {{ $card['value'] }}
                    </p>
                </div>
            @endforeach
        </div>


        {{-- Search --}}
        <div
            class="rounded-2xl border border-gray-200
                   bg-white p-4
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ $this->viewMode === 'archived'
                    ? 'Search Archived Gospel Contacts...'
                    : 'Search Gospel Contacts...' }}"
                class="block w-full rounded-xl
                       border border-gray-300 px-4 py-3
                       dark:border-gray-700 dark:bg-gray-950"
            >
        </div>


        {{-- Contact list --}}
        <div class="space-y-3">
            @forelse ($contacts as $contact)
                <div
                    class="rounded-2xl border border-gray-200
                           bg-white p-5 shadow-sm
                           dark:border-gray-700 dark:bg-gray-900"
                >
                    <div
                        class="flex flex-col gap-4
                               lg:flex-row
                               lg:items-start
                               lg:justify-between"
                    >
                        <div class="min-w-0">
                            <div
                                class="flex flex-wrap
                                       items-center gap-2"
                            >
                                <h3
                                    class="text-lg font-bold
                                           text-gray-950
                                           dark:text-white"
                                >
                                    {{ $contact->display_name }}
                                </h3>

                                @if ($contact->person_id)
                                    <span
                                        class="rounded-full
                                               bg-emerald-100
                                               px-2.5 py-1
                                               text-xs font-bold
                                               text-emerald-800"
                                    >
                                        People Database
                                    </span>
                                @else
                                    <span
                                        class="rounded-full
                                               bg-amber-100
                                               px-2.5 py-1
                                               text-xs font-bold
                                               text-amber-800"
                                    >
                                        Gospel Contact
                                    </span>
                                @endif
                            </div>

                            <div
                                class="mt-2 flex flex-wrap
                                       gap-x-4 gap-y-1
                                       text-sm text-gray-500"
                            >
                                @if ($contact->effective_locality)
                                    <span>
                                        {{ $contact->effective_locality }}
                                    </span>
                                @endif

                                @if ($contact->contact_place)
                                    <span>
                                        Place:
                                        {{ $contact->contact_place }}
                                    </span>
                                @endif

                                @if ($contact->effective_contact_number)
                                    <span>
                                        {{ $contact->effective_contact_number }}
                                    </span>
                                @endif
                            </div>

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

                        <div class="flex flex-wrap gap-2">
                            @if (! $contact->person_id)
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'quezonprovinceactivities.gospel-work.contacts.add-to-people',
                                        $contact
                                    ) }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="rounded-xl
                                               bg-emerald-600
                                               px-3 py-2
                                               text-xs font-bold
                                               text-white"
                                    >
                                        Absorb into People Database
                                    </button>
                                </form>
                            @else
                                <a
                                    href="{{ $this->personUrl($contact->person) }}"
                                    class="rounded-xl border
                                           border-gray-300
                                           px-3 py-2
                                           text-xs font-bold"
                                >
                                    View Person
                                </a>
                            @endif
                        </div>
                    </div>


                    {{-- Edit --}}
                    <details class="mt-4">
                        <summary
                            class="cursor-pointer text-sm
                                   font-semibold text-primary-700"
                        >
                            Edit Gospel Contact
                        </summary>

                        <form
                            method="POST"
                            action="{{ route(
                                'quezonprovinceactivities.gospel-work.contacts.update',
                                $contact
                            ) }}"
                            class="mt-4 space-y-4"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="grid gap-3 md:grid-cols-2">
                                <input
                                    name="firstname"
                                    value="{{ $contact->effective_firstname }}"
                                    placeholder="First Name"
                                    class="rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950"
                                >

                                <input
                                    name="lastname"
                                    value="{{ $contact->effective_lastname }}"
                                    placeholder="Last Name"
                                    class="rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950"
                                >

                                <select
                                    name="sex"
                                    class="rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950 px-4 py-3"
                                >
                                    <option value="">Sex not recorded</option>
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

                                <select
                                    name="locality_id"
                                    class="rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950 px-4 py-3"
                                >
                                    <option value="">
                                        Locality not recorded
                                    </option>

                                    @foreach ($localityGroups as $group => $options)
                                        <optgroup label="{{ $group }}">
                                            @foreach ($options as $id => $label)
                                                <option
                                                    value="{{ $id }}"
                                                    @selected((int) $contact->locality_id === (int) $id)
                                                >
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>

                                <input
                                    name="contact_number"
                                    value="{{ $contact->effective_contact_number }}"
                                    placeholder="Contact Number"
                                    class="rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950"
                                >

                                <input
                                    type="email"
                                    name="email"
                                    value="{{ $contact->effective_email }}"
                                    placeholder="Email"
                                    class="rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950"
                                >

                                <input
                                    name="facebook_account"
                                    value="{{ $contact->effective_facebook_account }}"
                                    placeholder="Facebook"
                                    class="rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950"
                                >

                                <input
                                    name="contact_place"
                                    value="{{ $contact->contact_place }}"
                                    placeholder="Contact Place"
                                    class="rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950"
                                >
                            </div>

                            <input
                                name="address"
                                value="{{ $contact->effective_address }}"
                                placeholder="Address"
                                class="block w-full rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950"
                            >

                            <textarea
                                name="notes"
                                rows="3"
                                placeholder="Notes"
                                class="block w-full rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950 px-4 py-3"
                            >{{ $contact->notes }}</textarea>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="submit"
                                    class="rounded-xl bg-primary-600
                                           px-4 py-2 text-sm
                                           font-bold text-white"
                                >
                                    Save Changes
                                </button>
                            </div>
                        </form>

                        <form
                            method="POST"
                            action="{{ route(
                                'quezonprovinceactivities.gospel-work.contacts.destroy',
                                $contact
                            ) }}"
                            class="mt-3"
                            onsubmit="return confirm('Delete this Gospel Contact? Linked People records will not be deleted.');"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="rounded-xl border
                                       border-red-300
                                       px-3 py-2
                                       text-xs font-bold
                                       text-red-700"
                            >
                                Delete Gospel Contact
                            </button>
                        </form>
                    </details>
                </div>
            @empty
                <div
                    class="rounded-2xl border border-dashed
                           border-gray-300 p-10 text-center
                           text-gray-500"
                >
                    No Gospel Contacts found.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
