@if (
    $response->respondent_type
    ===
    \App\Models\AttendanceMeetingResponse::RESPONDENT_GUEST
)
@php
    $guestProfile =
        $response->guest_profile ?? [];

    /*
     * Guest -> Campus form state.
     */
    $isOldPromotion =
        (int) old('promotion_response_id')
        ===
        (int) $response->id;

    $promotionValue =
        function (
            string $key,
            mixed $fallback = null
        ) use (
            $isOldPromotion
        ): mixed {
            return $isOldPromotion
                ? old($key, $fallback)
                : $fallback;
        };

    /*
     * Guest -> Existing Person form state.
     */
    $isOldPersonLink =
        (int) old('link_response_id')
        ===
        (int) $response->id;

    /*
     * Guest -> New Person form state.
     */
    $isOldPersonCreate =
        (int) old('create_person_response_id')
        ===
        (int) $response->id;

    $personCreateValue =
        function (
            string $field,
            mixed $fallback = null
        ) use (
            $isOldPersonCreate
        ): mixed {
            return $isOldPersonCreate
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

        <div class="mt-3">
            <p
                class="text-sm font-bold text-gray-900 dark:text-white"
            >
                Add Guest to Campus Database
            </p>

            <p
                class="mt-1 text-xs text-gray-500 dark:text-gray-400"
            >
                Guest name:
                <span class="font-semibold">
                    {{ $response->guest_name }}
                </span>
            </p>

            <p
                class="mt-1 text-xs text-gray-500 dark:text-gray-400"
            >
                Review or complete the fields below before
                creating the Campus Contact.
            </p>


            @if (
                $isOldPromotion
                && $errors->any()
            )
                <div
                    class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                >
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <form
                method="POST"
                action="{{ route(
                    'quezonprovinceactivities.attendance-meeting-responses.promote-to-campus',
                    ['response' => $response]
                ) }}"
                class="mt-4 space-y-3"
            >
                @csrf

                <input
                    type="hidden"
                    name="promotion_response_id"
                    value="{{ $response->id }}"
                >


                <div class="grid gap-3 sm:grid-cols-2">

                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
                        >
                            First Name
                        </label>

                        <input
                            type="text"
                            name="firstname"
                            maxlength="100"
                            value="{{ $promotionValue(
                                'firstname',
                                $guestProfile['firstname'] ?? null
                            ) }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
                        >
                    </div>


                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Last Name
                        </label>

                        <input
                            type="text"
                            name="lastname"
                            maxlength="100"
                            value="{{ $promotionValue(
                                'lastname',
                                $guestProfile['lastname'] ?? null
                            ) }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
                        >
                    </div>

                </div>


                <div class="grid gap-3 sm:grid-cols-2">

                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Sex *
                        </label>

                        @php
                            $selectedSex =
                                $promotionValue(
                                    'sex',
                                    $guestProfile['sex'] ?? null
                                );
                        @endphp

<select
    name="person_sex"
    required
    class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
>
                            <option value="">
                                Select
                            </option>

                            <option
                                value="Male"
                                @selected(
                                    $selectedSex === 'Male'
                                )
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                @selected(
                                    $selectedSex === 'Female'
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
    name="person_locality"
    required
    maxlength="150"
    value="{{ $personCreateValue(
        'person_locality',
        $guestProfile['locality']
            ?? null
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
                        name="school_campus"
                        maxlength="255"
                        value="{{ $promotionValue(
                            'school_campus',
                            $guestProfile['school_campus'] ?? null
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
                            name="course_strand"
                            maxlength="255"
                            value="{{ $promotionValue(
                                'course_strand',
                                $guestProfile['course_strand'] ?? null
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
                            name="grade_level"
                            maxlength="100"
                            value="{{ $promotionValue(
                                'grade_level',
                                $guestProfile['grade_level'] ?? null
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
                        name="contact_number"
                        maxlength="20"
                        value="{{ $promotionValue(
                            'contact_number',
                            $guestProfile['contact_number'] ?? null
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
                        name="email"
                        maxlength="255"
                        value="{{ $promotionValue(
                            'email',
                            $guestProfile['email'] ?? null
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
                        name="facebook_account"
                        maxlength="255"
                        value="{{ $promotionValue(
                            'facebook_account',
                            $guestProfile['facebook_account'] ?? null
                        ) }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                </div>


                <label
                    class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
                >
                    <input
                        type="checkbox"
                        name="create_anyway"
                        value="1"
                        @checked(
                            $isOldPromotion
                            && old('create_anyway')
                        )
                        class="mt-0.5 rounded border-gray-300"
                    >

                    <span>
                        Create anyway if a similar Campus
                        Contact already exists.
                    </span>
                </label>


                <button
                    type="submit"
                    class="inline-flex rounded-lg bg-sky-600 px-3 py-2 text-xs font-bold text-white hover:bg-sky-500"
                >
                    Add to Campus Database
                </button>

            </form>



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
        Use this when the Guest already exists in the
        People Database.
    </p>


    @if (
        $isOldPersonLink
        && $errors->has('meeting_response_person_link')
    )
        <div
            class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
        >
            {{ $errors->first('meeting_response_person_link') }}
        </div>
    @endif


    <form
        method="POST"
        action="{{ route(
            'quezonprovinceactivities.attendance-meeting-responses.link-person',
            ['response' => $response]
        ) }}"
        class="mt-3"
        data-person-link-form
    >
        @csrf

        <input
            type="hidden"
            name="link_response_id"
            value="{{ $response->id }}"
        >

        <input
            type="hidden"
            name="person_id"
            value="{{ $isOldPersonLink
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
            Search by first name, last name, or nickname.
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
        Add Directly to People Database
    </p>

    <p
        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
    >
        Use this only when this Guest is not already in
        the People Database.
    </p>

    @if (
        $isOldPersonCreate
        && $errors->has(
            'meeting_response_person_create'
        )
    )
        <div
            class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
        >
            {{ $errors->first(
                'meeting_response_person_create'
            ) }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route(
            'quezonprovinceactivities.attendance-meeting-responses.create-person',
            ['response' => $response]
        ) }}"
        class="mt-4 space-y-3"
    >
        @csrf

        <input
            type="hidden"
            name="create_person_response_id"
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
                    name="person_firstname"
                    required
                    maxlength="100"
                    value="{{ $personCreateValue(
                        'person_firstname',
                        $guestProfile['firstname']
                            ?? null
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
                    name="person_lastname"
                    required
                    maxlength="100"
                    value="{{ $personCreateValue(
                        'person_lastname',
                        $guestProfile['lastname']
                            ?? null
                    ) }}"
                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
                >
            </div>

        </div>

@php
    $personSex =
        $personCreateValue(
            'person_sex',
            $guestProfile['sex'] ?? null
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
            name="person_sex"
            required
            class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
        >
            <option value="">
                Select
            </option>

            <option
                value="Male"
                @selected($personSex === 'Male')
            >
                Male
            </option>

            <option
                value="Female"
                @selected($personSex === 'Female')
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
            name="person_locality"
            required
            maxlength="150"
            value="{{ $personCreateValue(
                'person_locality',
                $guestProfile['locality']
                    ?? null
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
                name="person_school_campus"
                maxlength="255"
                value="{{ $personCreateValue(
                    'person_school_campus',
                    $guestProfile['school_campus']
                        ?? null
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
                    name="person_course_strand"
                    maxlength="255"
                    value="{{ $personCreateValue(
                        'person_course_strand',
                        $guestProfile['course_strand']
                            ?? null
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
                    name="person_grade_level"
                    maxlength="100"
                    value="{{ $personCreateValue(
                        'person_grade_level',
                        $guestProfile['grade_level']
                            ?? null
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
                name="person_contact_number"
                maxlength="20"
                value="{{ $personCreateValue(
                    'person_contact_number',
                    $guestProfile['contact_number']
                        ?? null
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
                name="person_email"
                maxlength="255"
                value="{{ $personCreateValue(
                    'person_email',
                    $guestProfile['email']
                        ?? null
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
                name="person_facebook_account"
                maxlength="255"
                value="{{ $personCreateValue(
                    'person_facebook_account',
                    $guestProfile['facebook_account']
                        ?? null
                ) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
            >
        </div>


        <label
            class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
        >
            <input
                type="checkbox"
                name="create_person_anyway"
                value="1"
                @checked(
                    $isOldPersonCreate
                    && old('create_person_anyway')
                )
                class="mt-0.5 rounded border-gray-300"
            >

            <span>
                Create anyway if a Person with the same
                First and Last Name already exists.
            </span>
        </label>


        <button
            type="submit"
            class="inline-flex rounded-lg bg-primary-600 px-3 py-2 text-xs font-bold text-white hover:bg-primary-500"
        >
            Add to People Database
        </button>

    </form>
</div>


        </div>
    </details>
@endif
