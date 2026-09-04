<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow,noarchive"
    >

    <title>
        {{ $sheet->title }} · Meeting Form
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            color-scheme: light;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
        }

        .page {
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
            padding: 24px 16px 48px;
        }

        .header {
            text-align: center;
            padding: 20px 10px 24px;
        }

        .eyebrow {
            margin: 0;
            color: #047857;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        h1 {
            margin: 8px 0 0;
            font-size: clamp(28px, 8vw, 42px);
            line-height: 1.05;
            overflow-wrap: anywhere;
        }

        .meeting-meta {
            display: grid;
            gap: 10px;
            margin-top: 20px;
        }

        .meta-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            font-size: 15px;
            font-weight: 650;
        }

        .card {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 22px;
            padding: 22px;
            box-shadow:
                0 12px 28px rgba(17, 24, 39, .08);
        }

        .success {
            margin-bottom: 18px;
            border: 1px solid #86efac;
            background: #f0fdf4;
            border-radius: 16px;
            padding: 16px;
            color: #166534;
        }

        .success strong {
            display: block;
            font-size: 16px;
        }

        .success p {
            margin: 5px 0 0;
            font-size: 14px;
        }

        .errors {
            margin-bottom: 18px;
            border: 1px solid #fca5a5;
            background: #fef2f2;
            border-radius: 16px;
            padding: 16px;
            color: #991b1b;
        }

        .errors strong {
            display: block;
            margin-bottom: 6px;
        }

        .errors ul {
            margin: 0;
            padding-left: 20px;
        }

        .question {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
        }

        .description {
            margin: 7px 0 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        .field {
            margin-top: 24px;
        }

        label.field-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 750;
        }

        select {
            width: 100%;
            min-height: 52px;
            border: 1px solid #9ca3af;
            border-radius: 13px;
            background: #ffffff;
            padding: 0 14px;
            font-size: 16px;
            color: #111827;
        }

        select:focus {
            outline: 3px solid rgba(16, 185, 129, .20);
            border-color: #059669;
        }

        .choices {
            display: grid;
            gap: 12px;
            margin-top: 10px;
        }

        .choice {
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 64px;
            border: 1px solid #d1d5db;
            border-radius: 15px;
            padding: 14px 16px;
            cursor: pointer;
            background: #ffffff;
        }

        .choice:hover {
            border-color: #6b7280;
            background: #f9fafb;
        }

        .choice input {
            width: 21px;
            height: 21px;
            flex: 0 0 auto;
        }

        .choice-content {
            min-width: 0;
        }

        .choice-title {
            display: block;
            font-size: 16px;
            font-weight: 800;
        }

        .choice-help {
            display: block;
            margin-top: 2px;
            color: #6b7280;
            font-size: 12px;
        }

        .yes {
            border-color: #a7f3d0;
        }

        .yes .choice-title {
            color: #047857;
        }

        .no {
            border-color: #fecaca;
        }

        .no .choice-title {
            color: #b91c1c;
        }

        .submit {
            width: 100%;
            min-height: 54px;
            margin-top: 26px;
            border: 0;
            border-radius: 14px;
            background: #059669;
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
        }

        .submit:hover {
            background: #047857;
        }

        .submit:focus {
            outline: 3px solid rgba(16, 185, 129, .30);
            outline-offset: 2px;
        }

        .empty {
            margin-top: 20px;
            border: 1px dashed #d1d5db;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        .remarks {
            margin-top: 18px;
            border-top: 1px solid #e5e7eb;
            padding-top: 18px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.5;
        }

        .privacy {
            margin: 18px 8px 0;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
            line-height: 1.5;
        }
    </style>
</head>

<body>

<div class="page">

    <header class="header">

        <p class="eyebrow">
            Meeting Response
        </p>

        <h1>
            {{ $sheet->title }}
        </h1>

        <div class="meeting-meta">

            <div class="meta-row">
                <span aria-hidden="true">📅</span>

                <span>
                    {{ $session->session_date->format('F j, Y') }}
                </span>
            </div>

            @if ($session->sessionTimeLabel())
                <div class="meta-row">
                    <span aria-hidden="true">🕐</span>

                    <span>
                        {{ $session->sessionTimeLabel() }}
                    </span>
                </div>
            @endif

            @if (filled($sheet->locality))
                <div class="meta-row">
                    <span aria-hidden="true">📍</span>

                    <span>
                        {{ $sheet->locality }}
                    </span>
                </div>
            @endif

        </div>

    </header>


    @if (session('meeting_response_saved'))

        <div
            class="success"
            role="status"
        >
            <strong>
                Response saved.
            </strong>

            <p>
                {{ session('meeting_response_name') }},
                your response is

                <strong style="display:inline">
                    {{ session('meeting_response_value') === 'yes'
                        ? 'YES — attending'
                        : 'NO — unable to attend' }}
                </strong>.
            </p>
        </div>

    @endif


    @if ($errors->any())

        <div
            class="errors"
            role="alert"
        >
            <strong>
                Please check your response:
            </strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>

    @endif


    <main class="card">

        <h2 class="question">
            Are you attending?
        </h2>

        <p class="description">
            Select your name and let us know if you plan
            to attend this meeting.
        </p>

            <form
                method="POST"
                action="{{ route(
                    'meeting.store',
                    ['slug' => $session->public_slug],
                    false
                ) }}"
            >

                @csrf


<div class="field">
    <label
        for="name_search"
        class="field-label"
    >
        👤 Your Name
    </label>

    <input
        id="name_search"
        type="search"
        inputmode="search"
        autocomplete="off"
        placeholder="Search your name..."
        style="
            width: 100%;
            min-height: 52px;
            border: 1px solid #9ca3af;
            border-radius: 13px;
            background: #ffffff;
            padding: 0 14px;
            font-size: 16px;
            color: #111827;
        "
    >

    <input
        id="respondent_type"
        type="hidden"
        name="respondent_type"
        value="{{ old('respondent_type') }}"
    >

    <input
        id="respondent_id"
        type="hidden"
        name="respondent_id"
        value="{{ old('respondent_id') }}"
    >

<input
    id="respondent_name_hint_field"
    type="hidden"
    name="respondent_name_hint"
    value="{{ old('respondent_name_hint') }}"
>

    <p
        id="search_status"
        style="
            margin: 8px 2px 0;
            color: #6b7280;
            font-size: 12px;
        "
        aria-live="polite"
    >
        Type at least 2 characters to search.
    </p>


    <div
        id="name_results"
        hidden
        style="
            margin-top: 10px;
            overflow: hidden;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            background: white;
        "
    ></div>


    <div
        id="selected_name"
        hidden
        style="
            margin-top: 12px;
            border: 1px solid #a7f3d0;
            border-radius: 14px;
            background: #ecfdf5;
            padding: 14px;
        "
    >
        <div
            style="
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
            "
        >
            <div>
                <strong id="selected_name_text"></strong>

                <div
                    id="selected_name_source"
                    style="
                        margin-top: 3px;
                        color: #047857;
                        font-size: 12px;
                        font-weight: 700;
                    "
                ></div>
            </div>

            <button
                id="change_name"
                type="button"
                style="
                    border: 0;
                    background: transparent;
                    color: #047857;
                    font-weight: 700;
                    cursor: pointer;
                "
            >
                Change
            </button>
        </div>
    </div>


    <div
        style="
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
        "
    >
        <button
            id="guest_toggle"
            type="button"
            style="
                width: 100%;
                min-height: 46px;
                border: 1px solid #d1d5db;
                border-radius: 12px;
                background: #f9fafb;
                color: #374151;
                font-size: 14px;
                font-weight: 750;
                cursor: pointer;
            "
        >
            My name isn't listed
        </button>
    </div>


<div
    id="guest_panel"
    hidden
    style="
        margin-top: 12px;
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        background: #eff6ff;
        padding: 14px;
    "
>
    <label
        for="guest_name"
        style="
            display: block;
            margin-bottom: 8px;
            color: #1e3a8a;
            font-size: 14px;
            font-weight: 750;
        "
    >
        Your Full Name
    </label>

    <input
        id="guest_name"
        name="guest_name"
        type="text"
        autocomplete="name"
        value="{{ old('guest_name') }}"
        placeholder="Your full name"
        maxlength="255"
        style="
            width: 100%;
            min-height: 52px;
            border: 1px solid #93c5fd;
            border-radius: 12px;
            background: #ffffff;
            padding: 0 14px;
            font-size: 16px;
        "
    >

    <p
        style="
            margin: 8px 0 0;
            color: #1d4ed8;
            font-size: 12px;
            line-height: 1.45;
        "
    >
        Your name will only be attached to this meeting
        response. It will not automatically create a
        People or Campus Database record.
    </p>


    <label
        style="
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 18px;
            border-top: 1px solid #bfdbfe;
            padding-top: 16px;
            cursor: pointer;
        "
    >
        <input
            id="guest_profile_enabled"
            type="checkbox"
            name="guest_profile_enabled"
            value="1"
            @checked(old('guest_profile_enabled'))
            style="
                width: 19px;
                height: 19px;
                margin-top: 2px;
                flex: 0 0 auto;
            "
        >

        <span>
            <strong
                style="
                    display: block;
                    color: #1e3a8a;
                    font-size: 14px;
                "
            >
                Add optional information
            </strong>

            <span
                style="
                    display: block;
                    margin-top: 3px;
                    color: #1d4ed8;
                    font-size: 12px;
                    line-height: 1.4;
                "
            >
                You may add locality, school, or contact
                information. All fields below are optional.
            </span>
        </span>
    </label>


<div
    id="guest_optional_fields"
    hidden
    style="
        margin-top: 16px;
    "
>
    <div
        style="
            display: grid;
            gap: 14px;
        "
    >

        <div
            style="
                display: grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            "
        >
            <div>
                <label
                    for="guest_firstname"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    First Name
                </label>

                <input
                    id="guest_firstname"
                    name="guest_profile[firstname]"
                    type="text"
                    maxlength="100"
                    value="{{ old('guest_profile.firstname') }}"
                    autocomplete="given-name"
                    style="
                        width:100%;
                        min-height:48px;
                        border:1px solid #93c5fd;
                        border-radius:10px;
                        padding:0 12px;
                        font-size:16px;
                        background:white;
                    "
                >
            </div>

            <div>
                <label
                    for="guest_lastname"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    Last Name
                </label>

                <input
                    id="guest_lastname"
                    name="guest_profile[lastname]"
                    type="text"
                    maxlength="100"
                    value="{{ old('guest_profile.lastname') }}"
                    autocomplete="family-name"
                    style="
                        width:100%;
                        min-height:48px;
                        border:1px solid #93c5fd;
                        border-radius:10px;
                        padding:0 12px;
                        font-size:16px;
                        background:white;
                    "
                >
            </div>
        </div>


        <div
            style="
                display: grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            "
        >
            <div>
                <label
                    for="guest_sex"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    Sex
                </label>

                <select
                    id="guest_sex"
                    name="guest_profile[sex]"
                >
                    <option value="">
                        Select
                    </option>

                    <option
                        value="Male"
                        @selected(
                            old('guest_profile.sex')
                            === 'Male'
                        )
                    >
                        Male
                    </option>

                    <option
                        value="Female"
                        @selected(
                            old('guest_profile.sex')
                            === 'Female'
                        )
                    >
                        Female
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="guest_locality"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    Locality
                </label>

                <input
                    id="guest_locality"
                    name="guest_profile[locality]"
                    type="text"
                    maxlength="150"
                    value="{{ old('guest_profile.locality') }}"
                    style="
                        width:100%;
                        min-height:48px;
                        border:1px solid #93c5fd;
                        border-radius:10px;
                        padding:0 12px;
                        font-size:16px;
                        background:white;
                    "
                >
            </div>
        </div>


        <div>
            <label
                for="guest_school"
                style="
                    display:block;
                    margin-bottom:6px;
                    font-size:13px;
                    font-weight:700;
                "
            >
                School / Campus
            </label>

            <input
                id="guest_school"
                name="guest_profile[school_campus]"
                type="text"
                maxlength="255"
                value="{{ old('guest_profile.school_campus') }}"
                style="
                    width:100%;
                    min-height:48px;
                    border:1px solid #93c5fd;
                    border-radius:10px;
                    padding:0 12px;
                    font-size:16px;
                    background:white;
                "
            >
        </div>


        <div
            style="
                display: grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            "
        >
            <div>
                <label
                    for="guest_course"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    Course / Strand
                </label>

                <input
                    id="guest_course"
                    name="guest_profile[course_strand]"
                    type="text"
                    maxlength="255"
                    value="{{ old('guest_profile.course_strand') }}"
                    style="
                        width:100%;
                        min-height:48px;
                        border:1px solid #93c5fd;
                        border-radius:10px;
                        padding:0 12px;
                        font-size:16px;
                        background:white;
                    "
                >
            </div>

            <div>
                <label
                    for="guest_grade"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    Grade Level
                </label>

                <input
                    id="guest_grade"
                    name="guest_profile[grade_level]"
                    type="text"
                    maxlength="100"
                    value="{{ old('guest_profile.grade_level') }}"
                    style="
                        width:100%;
                        min-height:48px;
                        border:1px solid #93c5fd;
                        border-radius:10px;
                        padding:0 12px;
                        font-size:16px;
                        background:white;
                    "
                >
            </div>
        </div>


        <div>
            <label
                for="guest_contact"
                style="
                    display:block;
                    margin-bottom:6px;
                    font-size:13px;
                    font-weight:700;
                "
            >
                Contact Number
            </label>

            <input
                id="guest_contact"
                name="guest_profile[contact_number]"
                type="tel"
                maxlength="20"
                value="{{ old('guest_profile.contact_number') }}"
                autocomplete="tel"
                style="
                    width:100%;
                    min-height:48px;
                    border:1px solid #93c5fd;
                    border-radius:10px;
                    padding:0 12px;
                    font-size:16px;
                    background:white;
                "
            >
        </div>


        <div>
            <label
                for="guest_email"
                style="
                    display:block;
                    margin-bottom:6px;
                    font-size:13px;
                    font-weight:700;
                "
            >
                Email
            </label>

            <input
                id="guest_email"
                name="guest_profile[email]"
                type="email"
                maxlength="255"
                value="{{ old('guest_profile.email') }}"
                autocomplete="email"
                style="
                    width:100%;
                    min-height:48px;
                    border:1px solid #93c5fd;
                    border-radius:10px;
                    padding:0 12px;
                    font-size:16px;
                    background:white;
                "
            >
        </div>


        <div>
            <label
                for="guest_facebook"
                style="
                    display:block;
                    margin-bottom:6px;
                    font-size:13px;
                    font-weight:700;
                "
            >
                Facebook
            </label>

            <input
                id="guest_facebook"
                name="guest_profile[facebook_account]"
                type="text"
                maxlength="255"
                value="{{ old('guest_profile.facebook_account') }}"
                style="
                    width:100%;
                    min-height:48px;
                    border:1px solid #93c5fd;
                    border-radius:10px;
                    padding:0 12px;
                    font-size:16px;
                    background:white;
                "
            >
        </div>

    </div>
</div>
</div>
</div>

<input
    id="respondent_name_hint_field"
    type="hidden"
    name="respondent_name_hint"
    value="{{ old('respondent_name_hint') }}"
>

                <fieldset
                    class="field"
                    style="
                        border:0;
                        padding:0;
                        margin-left:0;
                        margin-right:0;
                    "
                >

                    <legend class="field-label">
                        Your Response
                    </legend>

                    <div class="choices">

                        <label class="choice yes">

                            <input
                                type="radio"
                                name="response"
                                value="yes"
                                required
                                @checked(
                                    old('response') === 'yes'
                                )
                            >

                            <span class="choice-content">

                                <span class="choice-title">
                                    YES, I'll attend
                                </span>

                                <span class="choice-help">
                                    I plan to be at this meeting.
                                </span>

                            </span>

                        </label>


                        <label class="choice no">

                            <input
                                type="radio"
                                name="response"
                                value="no"
                                required
                                @checked(
                                    old('response') === 'no'
                                )
                            >

                            <span class="choice-content">

                                <span class="choice-title">
                                    NO, I can't attend
                                </span>

                                <span class="choice-help">
                                    I will not be able to attend.
                                </span>

                            </span>

                        </label>

                    </div>

                </fieldset>


                <button
                    type="submit"
                    class="submit"
                >
                    Submit Response
                </button>

            </form>

        @if (filled($sheet->remarks))

            <div class="remarks">

                <strong>
                    Meeting information
                </strong>

                <div style="margin-top:6px">
                    {{ $sheet->remarks }}
                </div>

            </div>

        @endif

    </main>


<p class="privacy">
    Name search displays names only.
    Optional information entered by a guest is stored
    privately for authorized database users and is not
    shown in public search results.
</p>

</div>

<script>
(() => {
    const searchUrl = @json(
        route(
            'meeting.search',
            ['slug' => $session->public_slug],
            false
        )
    );

    const searchInput =
        document.getElementById('name_search');

    const searchStatus =
        document.getElementById('search_status');

    const resultsBox =
        document.getElementById('name_results');

    const typeInput =
        document.getElementById('respondent_type');

    const idInput =
        document.getElementById('respondent_id');

    const hintInput =
        document.getElementById(
            'respondent_name_hint_field'
        );

    const selectedBox =
        document.getElementById('selected_name');

    const selectedName =
        document.getElementById('selected_name_text');

    const selectedSource =
        document.getElementById(
            'selected_name_source'
        );

    const changeButton =
        document.getElementById('change_name');

    const guestToggle =
        document.getElementById('guest_toggle');

    const guestPanel =
        document.getElementById('guest_panel');

    const guestInput =
        document.getElementById('guest_name');

        const guestProfileEnabled =
    document.getElementById(
        'guest_profile_enabled'
    );

const guestOptionalFields =
    document.getElementById(
        'guest_optional_fields'
    );

    const form =
        searchInput.closest('form');

    let searchTimer = null;
    let searchController = null;


    function clearResults() {
        resultsBox.innerHTML = '';
        resultsBox.hidden = true;
    }


    function sourceLabel(type) {
        if (type === 'person') {
            return 'People Database';
        }

        if (type === 'campus') {
            return 'Campus Database';
        }

        return '';
    }


    function selectResult(result) {
        typeInput.value = result.type;
        idInput.value = result.id;
        hintInput.value = result.name;

        selectedName.textContent = result.name;
        selectedSource.textContent = result.source;

        selectedBox.hidden = false;

        guestPanel.hidden = true;
        guestInput.required = false;
        guestInput.value = '';

        clearResults();

        searchInput.value = result.name;

        searchStatus.textContent =
            'Name selected.';
    }

    function syncGuestOptionalFields() {
    const enabled =
        guestProfileEnabled.checked;

    guestOptionalFields.hidden =
        ! enabled;

    guestOptionalFields
        .querySelectorAll('input, select')
        .forEach((field) => {
            field.disabled = ! enabled;
        });
        guestProfileEnabled.addEventListener(
    'change',
    syncGuestOptionalFields
);
}



    function useGuestMode() {
        typeInput.value = 'guest';
        idInput.value = '';
        hintInput.value = '';

        selectedBox.hidden = true;

        clearResults();

        searchInput.value = '';

        guestPanel.hidden = false;
        guestInput.required = true;

        searchStatus.textContent =
            'Enter your full name below.';

        guestInput.focus();
    }


    function resetSelection() {
        typeInput.value = '';
        idInput.value = '';
        hintInput.value = '';

        selectedBox.hidden = true;

        guestPanel.hidden = true;
        guestInput.required = false;
        guestInput.value = '';

        searchInput.value = '';

        clearResults();

        searchStatus.textContent =
            'Type at least 2 characters to search.';

        searchInput.focus();
    }


    function renderResults(results) {
        clearResults();

        if (results.length === 0) {
            searchStatus.textContent =
                'No matching names found. You can use "My name isn\'t listed" below.';

            return;
        }

        results.forEach((result, index) => {
            const button =
                document.createElement('button');

            button.type = 'button';

            button.style.cssText = [
                'display:block',
                'width:100%',
                'min-height:62px',
                'padding:12px 14px',
                'border:0',
                index === 0
                    ? 'border-top:0'
                    : 'border-top:1px solid #e5e7eb',
                'background:#ffffff',
                'text-align:left',
                'cursor:pointer'
            ].join(';');

            const name =
                document.createElement('div');

            name.textContent = result.name;

            name.style.cssText = [
                'font-size:15px',
                'font-weight:800',
                'color:#111827'
            ].join(';');

            const source =
                document.createElement('div');

            source.textContent = result.source;

            source.style.cssText = [
                'margin-top:3px',
                'font-size:11px',
                'font-weight:700',
                result.type === 'person'
                    ? 'color:#047857'
                    : 'color:#1d4ed8'
            ].join(';');

            button.appendChild(name);
            button.appendChild(source);

            button.addEventListener(
                'click',
                () => selectResult(result)
            );

            resultsBox.appendChild(button);
        });

        resultsBox.hidden = false;

        searchStatus.textContent =
            results.length
            + ' matching name(s).';
    }


    async function searchNames(query) {
        if (searchController) {
            searchController.abort();
        }

        searchController =
            new AbortController();

        searchStatus.textContent =
            'Searching...';

        try {
            const response = await fetch(
                searchUrl
                    + '?q='
                    + encodeURIComponent(query),
                {
                    headers: {
                        'Accept': 'application/json'
                    },
                    signal: searchController.signal
                }
            );

            if (! response.ok) {
                throw new Error(
                    'Search request failed'
                );
            }

            const data = await response.json();

            renderResults(
                Array.isArray(data.results)
                    ? data.results
                    : []
            );
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            clearResults();

            searchStatus.textContent =
                'Unable to search right now. You can enter your name manually.';
        }
    }


    searchInput.addEventListener(
        'input',
        () => {
            const query =
                searchInput.value.trim();

            clearTimeout(searchTimer);

            /*
             * Typing again means the old selection
             * should no longer silently remain selected.
             */
            if (
                typeInput.value === 'person'
                || typeInput.value === 'campus'
            ) {
                typeInput.value = '';
                idInput.value = '';
                hintInput.value = '';
                selectedBox.hidden = true;
            }

            if (query.length < 2) {
                clearResults();

                searchStatus.textContent =
                    'Type at least 2 characters to search.';

                return;
            }

            searchTimer = setTimeout(
                () => searchNames(query),
                250
            );
        }
    );


    guestToggle.addEventListener(
        'click',
        useGuestMode
    );


    changeButton.addEventListener(
        'click',
        resetSelection
    );


    guestInput.addEventListener(
        'input',
        () => {
            if (typeInput.value === 'guest') {
                hintInput.value =
                    guestInput.value.trim();
            }
        }
    );


    form.addEventListener(
        'submit',
        (event) => {
            if (! typeInput.value) {
                event.preventDefault();

                searchStatus.textContent =
                    'Please select your name or choose "My name isn\'t listed".';

                searchInput.focus();

                return;
            }

            if (
                typeInput.value === 'guest'
                && guestInput.value.trim().length < 2
            ) {
                event.preventDefault();

                searchStatus.textContent =
                    'Please enter your full name.';

                guestInput.focus();
            }
        }
    );


    /*
     * Restore guest mode after server-side validation.
     */
    if (typeInput.value === 'guest') {
        guestPanel.hidden = false;
        guestInput.required = true;

        searchStatus.textContent =
            'Enter your full name below.';
    } else if (
        (
            typeInput.value === 'person'
            || typeInput.value === 'campus'
        )
        && hintInput.value
    ) {
        selectedName.textContent =
            hintInput.value;

        selectedSource.textContent =
            sourceLabel(typeInput.value);

        selectedBox.hidden = false;

        searchInput.value =
            hintInput.value;

        searchStatus.textContent =
            'Name selected.';
    }
syncGuestOptionalFields();
})();
</script>

</body>
</html>

