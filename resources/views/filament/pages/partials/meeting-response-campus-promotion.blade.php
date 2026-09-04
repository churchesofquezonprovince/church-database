@if (
    $response->respondent_type
    ===
    \App\Models\AttendanceMeetingResponse::RESPONDENT_CAMPUS
)
    @php
        $contact =
            $response->campusContact;

        $isOldCampusLink =
            (int) old('link_campus_response_id')
            ===
            (int) $response->id;

        $isOldCampusCreate =
            (int) old('create_campus_person_response_id')
            ===
            (int) $response->id;


    
    $campusCreateValue =
    function (
        string $field,
        mixed $fallback = null
    ) use (
        $isOldCampusCreate
    ): mixed {
        return $isOldCampusCreate
            ? old($field, $fallback)
            : $fallback;
    };

    @endphp

    <details
        class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-3 dark:border-sky-900 dark:bg-sky-950"
    >
        <summary
            class="cursor-pointer text-xs font-bold text-sky-800 dark:text-sky-200"
        >
            Identity Actions
        </summary>

        @if (! $contact)

            <div
                class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
            >
                The linked Campus Contact could not be found.
            </div>

        @else

            <div class="mt-4">
                <p
                    class="text-sm font-bold text-gray-900 dark:text-white"
                >
                    Campus Database Identity
                </p>

                <div
                    class="mt-2 rounded-lg border border-sky-200 bg-white p-3 text-xs text-gray-600 dark:border-sky-900 dark:bg-gray-900 dark:text-gray-300"
                >
                    <p>
                        <strong>Name:</strong>
                        {{ $contact->display_name }}
                    </p>

                    <p class="mt-1">
                        <strong>Sex:</strong>
                        {{ $contact->effective_sex ?: '—' }}
                    </p>

                    <p class="mt-1">
                        <strong>Locality:</strong>
                        {{ $contact->effective_locality ?: '—' }}
                    </p>

                    <p class="mt-1">
                        <strong>School / Campus:</strong>
                        {{ $contact->effective_school_campus ?: '—' }}
                    </p>

                    <p class="mt-1">
                        <strong>Course / Strand:</strong>
                        {{ $contact->effective_course_strand ?: '—' }}
                    </p>
                </div>
            </div>


            @if ($contact->person_id)

                <div
                    class="mt-5 border-t border-sky-200 pt-4 dark:border-sky-900"
                >
                    <p
                        class="text-sm font-bold text-gray-900 dark:text-white"
                    >
                        Already Linked to People
                    </p>

                    <p
                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                    >
                        {{ $contact->person?->display_name }}
                    </p>

                    <form
                        method="POST"
                        action="{{ route(
                            'quezonprovinceactivities.attendance-meeting-responses.use-campus-linked-person',
                            ['response' => $response]
                        ) }}"
                        class="mt-3"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-500"
                        >
                            Use Linked Person
                        </button>
                    </form>
                </div>

            @else

                <div
                    class="mt-5 border-t border-sky-200 pt-4 dark:border-sky-900"
                >
                    <p
                        class="text-sm font-bold text-gray-900 dark:text-white"
                    >
                        Link to Existing Person
                    </p>

                    <p
                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                    >
                        Use this if this Campus Contact already
                        exists in the People Database.
                    </p>

                    @if (
                        $isOldCampusLink
                        && $errors->has(
                            'meeting_response_campus_person_link'
                        )
                    )
                        <div
                            class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                        >
                            {{ $errors->first(
                                'meeting_response_campus_person_link'
                            ) }}
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route(
                            'quezonprovinceactivities.attendance-meeting-responses.link-campus-person',
                            ['response' => $response]
                        ) }}"
                        class="mt-3"
                        data-person-link-form
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="link_campus_response_id"
                            value="{{ $response->id }}"
                        >

                        <input
                            type="hidden"
                            name="person_id"
                            value="{{ $isOldCampusLink
                                ? old('person_id')
                                : '' }}"
                            data-person-id
                        >

                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Search People Database
                        </label>

                        <input
                            type="search"
                            autocomplete="off"
                            placeholder="Type at least 2 characters..."
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
                            data-person-search
                        >

                        <p
                            class="mt-1 text-xs text-gray-400"
                            data-person-search-status
                        >
                            Search by first name, last name,
                            or nickname.
                        </p>

                        <div
                            class="mt-2 hidden overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                            data-person-results
                        ></div>

                        <div
                            class="mt-3 hidden rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900 dark:bg-emerald-950"
                            data-selected-person
                        >
                            <p
                                class="text-xs font-bold text-emerald-800 dark:text-emerald-200"
                                data-selected-person-name
                            ></p>

                            <button
                                type="button"
                                class="mt-2 text-xs font-bold text-emerald-700 underline dark:text-emerald-300"
                                data-clear-person
                            >
                                Change Person
                            </button>
                        </div>

                        <button
                            type="submit"
                            class="mt-3 inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-500"
                        >
                            Link Existing Person
                        </button>
                    </form>
                </div>


                <div
                    class="mt-5 border-t border-sky-200 pt-4 dark:border-sky-900"
                >
<p
    class="text-sm font-bold text-gray-900 dark:text-white"
>
    Review & Add to People Database
</p>

<p
    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
>
    Complete or correct the Campus information below.
    First Name, Last Name, Sex, and Locality are required.
</p>

@if (
    $isOldCampusCreate
    && $errors->has(
        'meeting_response_campus_person_create'
    )
)
    <div
        class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
    >
        {{ $errors->first(
            'meeting_response_campus_person_create'
        ) }}
    </div>
@endif

<form
    method="POST"
    action="{{ route(
        'quezonprovinceactivities.attendance-meeting-responses.create-campus-person',
        ['response' => $response]
    ) }}"
    class="mt-4 space-y-3"
>
    @csrf

    <input
        type="hidden"
        name="create_campus_person_response_id"
        value="{{ $response->id }}"
    >


    <div class="grid gap-3 sm:grid-cols-2">

        <div>
            <label
                class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
            >
                First Name *
            </label>

            <input
                type="text"
                name="campus_person_firstname"
                required
                maxlength="100"
                value="{{ $campusCreateValue(
                    'campus_person_firstname',
                    $contact->firstname
                ) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
            >
        </div>


        <div>
            <label
                class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
            >
                Last Name *
            </label>

            <input
                type="text"
                name="campus_person_lastname"
                required
                maxlength="100"
                value="{{ $campusCreateValue(
                    'campus_person_lastname',
                    $contact->lastname
                ) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
            >
        </div>

    </div>


    @php
        $campusPersonSex =
            $campusCreateValue(
                'campus_person_sex',
                $contact->sex
            );
    @endphp

    <div class="grid gap-3 sm:grid-cols-2">

        <div>
            <label
                class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
            >
                Sex *
            </label>

            <select
                name="campus_person_sex"
                required
                class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <option value="">
                    Select
                </option>

                <option
                    value="Male"
                    @selected(
                        $campusPersonSex === 'Male'
                    )
                >
                    Male
                </option>

                <option
                    value="Female"
                    @selected(
                        $campusPersonSex === 'Female'
                    )
                >
                    Female
                </option>
            </select>
        </div>


        <div>
            <label
                class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
            >
                Locality *
            </label>

            <input
                type="text"
                name="campus_person_locality"
                required
                maxlength="150"
                value="{{ $campusCreateValue(
                    'campus_person_locality',
                    $contact->locality
                ) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
            >
        </div>

    </div>


    <div>
        <label
            class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
        >
            School / Campus
        </label>

        <input
            type="text"
            name="campus_person_school_campus"
            maxlength="255"
            value="{{ $campusCreateValue(
                'campus_person_school_campus',
                $contact->school_campus
            ) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
        >
    </div>


    <div class="grid gap-3 sm:grid-cols-2">

        <div>
            <label
                class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
            >
                Course / Strand
            </label>

            <input
                type="text"
                name="campus_person_course_strand"
                maxlength="255"
                value="{{ $campusCreateValue(
                    'campus_person_course_strand',
                    $contact->course_strand
                ) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
            >
        </div>


        <div>
            <label
                class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
            >
                Grade Level
            </label>

            <input
                type="text"
                name="campus_person_grade_level"
                maxlength="100"
                value="{{ $campusCreateValue(
                    'campus_person_grade_level',
                    $contact->grade_level
                ) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
            >
        </div>

    </div>


    <div>
        <label
            class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
        >
            Contact Number
        </label>

        <input
            type="text"
            name="campus_person_contact_number"
            maxlength="20"
            value="{{ $campusCreateValue(
                'campus_person_contact_number',
                $contact->contact_number
            ) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
        >
    </div>


    <div>
        <label
            class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
        >
            Email
        </label>

        <input
            type="email"
            name="campus_person_email"
            maxlength="255"
            value="{{ $campusCreateValue(
                'campus_person_email',
                $contact->email
            ) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
        >
    </div>


    <div>
        <label
            class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
        >
            Facebook
        </label>

        <input
            type="text"
            name="campus_person_facebook_account"
            maxlength="255"
            value="{{ $campusCreateValue(
                'campus_person_facebook_account',
                $contact->facebook_account
            ) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
        >
    </div>


    <label
        class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
    >
        <input
            type="checkbox"
            name="create_campus_person_anyway"
            value="1"
            @checked(
                $isOldCampusCreate
                && old(
                    'create_campus_person_anyway'
                )
            )
            class="mt-0.5 rounded border-gray-300"
        >

        <span>
            Create anyway if a similar Person
            already exists.
        </span>
    </label>


    <button
        type="submit"
        class="inline-flex rounded-lg bg-primary-600 px-3 py-2 text-xs font-bold text-white hover:bg-primary-500"
    >
        Save & Add to People Database
    </button>
</form>
                </div>

            @endif

        @endif
    </details>
@endif
