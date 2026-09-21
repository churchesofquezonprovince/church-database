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
        * { box-sizing: border-box; }

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
            max-width: 640px;
            margin: 0 auto;
            padding: 24px 16px 56px;
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
            margin-top: 18px;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            background: white;
            padding: 22px;
            box-shadow:
                0 10px 28px rgba(17, 24, 39, .07);
        }

        .success,
        .errors {
            margin-bottom: 18px;
            border-radius: 16px;
            padding: 16px;
        }

        .success {
            border: 1px solid #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .errors {
            border: 1px solid #fca5a5;
            background: #fef2f2;
            color: #991b1b;
        }

        .errors ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }

        .field {
            margin-top: 20px;
        }

        .field-label {
            display: block;
            margin-bottom: 7px;
            font-size: 15px;
            font-weight: 800;
        }

        .required {
            color: #dc2626;
        }

        .description {
            margin: 4px 0 10px;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.45;
        }

        input[type="text"],
        input[type="search"],
        input[type="date"],
        input[type="email"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            min-height: 50px;
            border: 1px solid #9ca3af;
            border-radius: 12px;
            background: #fff;
            padding: 0 13px;
            color: #111827;
            font-size: 16px;
        }

        textarea {
            min-height: 110px;
            padding-top: 12px;
            padding-bottom: 12px;
            resize: vertical;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: 3px solid rgba(16, 185, 129, .18);
            border-color: #059669;
        }

        .choices {
            display: grid;
            gap: 9px;
            margin-top: 10px;
        }

        .choice {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 12px 14px;
            cursor: pointer;
        }

        .choice input {
            width: 19px;
            height: 19px;
            margin-top: 1px;
            flex: 0 0 auto;
        }

        .search-results {
            margin-top: 9px;
            overflow: hidden;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: white;
        }

        .selected {
            margin-top: 10px;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            background: #ecfdf5;
            padding: 12px;
        }

        .selected-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        button {
            font: inherit;
        }

        .secondary {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            padding: 10px 13px;
            color: #374151;
            font-weight: 750;
            cursor: pointer;
        }

        .submit {
            width: 100%;
            min-height: 54px;
            margin-top: 26px;
            border: 0;
            border-radius: 14px;
            background: #059669;
            color: white;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
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
            Meeting Form
        </p>

        <h1>
            {{ $sheet->title }}
        </h1>

        <div class="meeting-meta">
            <div class="meta-row">
                📅
                {{ $session->session_date->format('F j, Y') }}
            </div>

            @if ($session->sessionTimeLabel())
                <div class="meta-row">
                    🕐
                    {{ $session->sessionTimeLabel() }}

                    @if ($session->sessionEndTimeLabel())
                        –
                        {{ $session->sessionEndTimeLabel() }}
                    @endif
                </div>
            @endif

            @if (filled($sheet->locality))
                <div class="meta-row">
                    📍
                    {{ $sheet->locality }}
                </div>
            @endif
        </div>
    </header>


    @if (session('meeting_google_form_saved'))
        <div class="success">
            <strong>
                Response saved.
            </strong>

            @if (session('meeting_response_name'))
                <div style="margin-top:4px">
                    Thank you,
                    {{ rtrim(
                        (string) session('meeting_response_name'),
                        '.'
                    ) }}.
                </div>
            @endif
        </div>
    @endif


    @if ($errors->any())
        <div class="errors">
            <strong>
                Please check your response.
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


    <form
        method="POST"
        action="{{ route(
            request()->getHost() === 'm.overcomers.win'
                ? 'meeting.short.store'
                : 'meeting.store',
            ['slug' => $publicSlug],
            false
        ) }}"
    >
        @csrf

        <section class="card">
            <div class="field" style="margin-top:0">
                <label
                    for="name_search"
                    class="field-label"
                >
                    👤 Your Name
                </label>

                <input
                    id="name_search"
                    type="search"
                    autocomplete="off"
                    placeholder="Search your name..."
                >

                <input
                    id="respondent_type"
                    type="hidden"
                    name="respondent_type"
                    value="{{ old('respondent_type') }}"
                >

                <input
                    id="respondent_token"
                    type="hidden"
                    name="respondent_token"
                    value="{{ old('respondent_token') }}"
                >

                <input
                    id="respondent_name_hint"
                    type="hidden"
                    value="{{ old('respondent_name_hint') }}"
                >

                <div
                    id="search_status"
                    class="description"
                    aria-live="polite"
                >
                    Type at least 3 characters to search.
                </div>

                <div
                    id="name_results"
                    class="search-results"
                    hidden
                ></div>

                <div
                    id="selected_name"
                    class="selected"
                    hidden
                >
                    <div class="selected-row">
                        <div>
                            <strong
                                id="selected_name_text"
                            ></strong>

                            <div
                                style="
                                    margin-top:3px;
                                    color:#047857;
                                    font-size:12px;
                                    font-weight:700;
                                "
                            >
                                Existing Record
                            </div>
                        </div>

                        <button
                            id="change_name"
                            type="button"
                            style="
                                border:0;
                                background:transparent;
                                color:#047857;
                                font-weight:800;
                                cursor:pointer;
                            "
                        >
                            Change
                        </button>
                    </div>
                </div>

                <button
                    id="guest_toggle"
                    type="button"
                    class="secondary"
                    style="
                        width:100%;
                        margin-top:14px;
                    "
                >
                    My name isn't listed
                </button>

                <div
                    id="guest_panel"
                    hidden
                    style="margin-top:14px"
                >
                    <label
                        for="guest_name"
                        class="field-label"
                    >
                        Your Full Name
                    </label>

                    <input
                        id="guest_name"
                        name="guest_name"
                        type="text"
                        maxlength="255"
                        value="{{ old('guest_name') }}"
                    >
                </div>
            </div>
        </section>


        @forelse ($questions as $question)

            @if ($question->isNotice())
                <section
                    class="card"
                    style="
                        border-left:6px solid #0f9f8f;
                    "
                >
                    <div
                        style="
                            font-size:17px;
                            font-weight:800;
                            color:#1f2937;
                        "
                    >
                        {{ $question->question_text }}
                    </div>

                    @if (filled($question->description))
                        <div
                            style="
                                margin-top:12px;
                                white-space:pre-line;
                                line-height:1.7;
                                color:#4b5563;
                            "
                        >{{ $question->description }}</div>
                    @endif
                </section>

                @continue
            @endif

            @php
                $fieldName =
                    'answers['
                    . $question->id
                    . ']';

                $oldValue =
                    old(
                        'answers.'
                        . $question->id
                    );

                $definition =
                    $question
                        ->databaseFieldDefinition();

                $databaseInput =
                    $definition['input']
                    ?? 'text';

                $databaseOptions =
                    \App\Support\MeetingFormDatabaseFieldRegistry::options(
                        $question->database_field
                    );

                $allowsReferenceProposal =
                    \App\Support\MeetingFormDatabaseFieldRegistry
                        ::allowsReferenceProposal(
                            $question->database_field
                        );

                $referenceProposalValue =
                    \App\Support\MeetingFormDatabaseFieldRegistry
                        ::REFERENCE_PROPOSAL_VALUE;
            @endphp

            <section class="card">
                <div
                    class="field"
                    style="margin-top:0"
                >
                    <label
                        class="field-label"
                    >
                        {{ $question->question_text }}

                        @if ($question->is_required)
                            <span class="required">
                                *
                            </span>
                        @endif
                    </label>

                    @if (filled($question->description))
                        <p class="description">
                            {{ $question->description }}
                        </p>
                    @endif


                    @if (
                        $question->question_type
                        ===
                        \App\Models\AttendanceMeetingFormQuestion::TYPE_SHORT_ANSWER
                    )
                        <input
                            type="text"
                            name="{{ $fieldName }}"
                            value="{{ $oldValue }}"
                            @required($question->is_required)
                        >

                    @elseif (
                        $question->question_type
                        ===
                        \App\Models\AttendanceMeetingFormQuestion::TYPE_PARAGRAPH
                    )
                        <textarea
                            name="{{ $fieldName }}"
                            @required($question->is_required)
                        >{{ $oldValue }}</textarea>

                    @elseif (
                        $question->question_type
                        ===
                        \App\Models\AttendanceMeetingFormQuestion::TYPE_MULTIPLE_CHOICE
                    )
                        <div class="choices">
                            @foreach (
                                $question->options ?? []
                                as $option
                            )
                                <label class="choice">
                                    <input
                                        type="radio"
                                        name="{{ $fieldName }}"
                                        value="{{ $option }}"
                                        @checked($oldValue === $option)
                                        @required($question->is_required)
                                    >

                                    <span>
                                        {{ $option }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                    @elseif (
                        $question->question_type
                        ===
                        \App\Models\AttendanceMeetingFormQuestion::TYPE_CHECKBOXES
                    )
                        <div class="choices">
                            @foreach (
                                $question->options ?? []
                                as $option
                            )
                                <label class="choice">
                                    <input
                                        type="checkbox"
                                        name="answers[{{ $question->id }}][]"
                                        value="{{ $option }}"
                                        @checked(
                                            in_array(
                                                $option,
                                                (array) $oldValue,
                                                true
                                            )
                                        )
                                    >

                                    <span>
                                        {{ $option }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                    @elseif (
                        $question->question_type
                        ===
                        \App\Models\AttendanceMeetingFormQuestion::TYPE_DROPDOWN
                    )
                        <select
                            name="{{ $fieldName }}"
                            @required($question->is_required)
                        >
                            <option value="">
                                Select
                            </option>

                            @foreach (
                                $question->options ?? []
                                as $option
                            )
                                <option
                                    value="{{ $option }}"
                                    @selected($oldValue === $option)
                                >
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>

                    @elseif ($question->isDatabaseField())

                        @if ($databaseOptions !== [])
                            <select
                                name="{{ $fieldName }}"
                                data-database-question="{{ $question->id }}"
                                data-database-field="{{ $question->database_field }}"
                                @required($question->is_required)
                            >
                                <option value="">
                                    Select {{ $question->databaseFieldLabel() }}
                                </option>

                                @foreach (
                                    $databaseOptions
                                    as $value => $label
                                )
                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            (string) $oldValue
                                            === (string) $value
                                        )
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                        @elseif ($databaseInput === 'date')
                            <input
                                type="date"
                                name="{{ $fieldName }}"
                                value="{{ $oldValue }}"
                                data-database-question="{{ $question->id }}"
                            >

                        @elseif ($databaseInput === 'locality')
                            <select
                                name="{{ $fieldName }}"
                                data-database-question="{{ $question->id }}"
                                data-reference-proposal-field="locality"
                                data-reference-proposal-question="{{ $question->id }}"
                            >
                                <option value="">
                                    Select Locality
                                </option>

                                @foreach (
                                    $localityGroups
                                    as $group => $localities
                                )
                                    <optgroup
                                        label="{{ $group }}"
                                    >
                                        @foreach (
                                            $localities
                                            as $id => $name
                                        )
                                            <option
                                                value="{{ $id }}"
                                                @selected(
                                                    (string) $oldValue
                                                    === (string) $id
                                                )
                                            >
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach

                                <option
                                    value="{{ $referenceProposalValue }}"
                                    @selected(
                                        (string) $oldValue
                                        === (string) $referenceProposalValue
                                    )
                                >
                                    My locality isn't listed
                                </option>
                            </select>

                            <div
                                data-reference-proposal-panel="{{ $question->id }}"
                                hidden
                                style="
                                    margin-top:12px;
                                    padding:14px;
                                    border:1px solid #d1d5db;
                                    border-radius:10px;
                                    background:#f9fafb;
                                "
                            >
                                <label class="field-label">
                                    Enter your actual locality
                                </label>

                                <input
                                    type="text"
                                    maxlength="150"
                                    name="reference_proposals[{{ $question->id }}][label]"
                                    value="{{ old('reference_proposals.' . $question->id . '.label') }}"
                                    data-reference-proposal-required
                                    placeholder="Actual locality name"
                                >

                                @error(
                                    'reference_proposals.'
                                    . $question->id
                                    . '.label'
                                )
                                    <div
                                        style="
                                            margin-top:6px;
                                            color:#b91c1c;
                                            font-size:12px;
                                        "
                                    >
                                        {{ $message }}
                                    </div>
                                @enderror

                                <label
                                    class="field-label"
                                    style="margin-top:12px"
                                >
                                    Province
                                </label>

                                <input
                                    type="text"
                                    maxlength="150"
                                    name="reference_proposals[{{ $question->id }}][province_name]"
                                    value="{{ old('reference_proposals.' . $question->id . '.province_name') }}"
                                    data-reference-proposal-required
                                    placeholder="e.g. Quezon"
                                    autocomplete="address-level1"
                                >

                                @error(
                                    'reference_proposals.'
                                    . $question->id
                                    . '.province_name'
                                )
                                    <div
                                        style="
                                            margin-top:6px;
                                            color:#b91c1c;
                                            font-size:12px;
                                        "
                                    >
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        @elseif ($databaseInput === 'school')
                            <select
                                name="{{ $fieldName }}"
                                data-database-question="{{ $question->id }}"
                                data-reference-proposal-field="school"
                                data-reference-proposal-question="{{ $question->id }}"
                            >
                                <option value="">
                                    Select School / Campus
                                </option>

                                @foreach (
                                    $schools
                                    as $id => $name
                                )
                                    <option
                                        value="{{ $id }}"
                                        @selected(
                                            (string) $oldValue
                                            === (string) $id
                                        )
                                    >
                                        {{ $name }}
                                    </option>
                                @endforeach

                                <option
                                    value="{{ $referenceProposalValue }}"
                                    @selected(
                                        (string) $oldValue
                                        === (string) $referenceProposalValue
                                    )
                                >
                                    My school isn't listed
                                </option>
                            </select>

                            <div
                                data-reference-proposal-panel="{{ $question->id }}"
                                hidden
                                style="
                                    margin-top:12px;
                                    padding:14px;
                                    border:1px solid #d1d5db;
                                    border-radius:10px;
                                    background:#f9fafb;
                                "
                            >
                                <label class="field-label">
                                    Enter your actual School / Campus
                                </label>

                                <input
                                    type="text"
                                    maxlength="255"
                                    name="reference_proposals[{{ $question->id }}][label]"
                                    value="{{ old('reference_proposals.' . $question->id . '.label') }}"
                                    data-reference-proposal-required
                                    placeholder="Actual school name"
                                >

                                @error(
                                    'reference_proposals.'
                                    . $question->id
                                    . '.label'
                                )
                                    <div
                                        style="
                                            margin-top:6px;
                                            color:#b91c1c;
                                            font-size:12px;
                                        "
                                    >
                                        {{ $message }}
                                    </div>
                                @enderror

                                <label
                                    class="field-label"
                                    style="margin-top:12px"
                                >
                                    Province
                                </label>

                                <input
                                    type="text"
                                    maxlength="150"
                                    name="reference_proposals[{{ $question->id }}][province_name]"
                                    value="{{ old('reference_proposals.' . $question->id . '.province_name') }}"
                                    data-reference-proposal-required
                                    placeholder="e.g. Quezon"
                                    autocomplete="address-level1"
                                >

                                @error(
                                    'reference_proposals.'
                                    . $question->id
                                    . '.province_name'
                                )
                                    <div
                                        style="
                                            margin-top:6px;
                                            color:#b91c1c;
                                            font-size:12px;
                                        "
                                    >
                                        {{ $message }}
                                    </div>
                                @enderror

                                <label
                                    class="field-label"
                                    style="margin-top:12px"
                                >
                                    City / Municipality
                                    <span style="font-weight:400">
                                        (optional)
                                    </span>
                                </label>

                                <input
                                    type="text"
                                    maxlength="150"
                                    name="reference_proposals[{{ $question->id }}][city_municipality]"
                                    value="{{ old('reference_proposals.' . $question->id . '.city_municipality') }}"
                                    placeholder="e.g. Lucban"
                                >

                                @error(
                                    'reference_proposals.'
                                    . $question->id
                                    . '.city_municipality'
                                )
                                    <div
                                        style="
                                            margin-top:6px;
                                            color:#b91c1c;
                                            font-size:12px;
                                        "
                                    >
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        @elseif ($databaseInput === 'sex')
                            <select
                                name="{{ $fieldName }}"
                                data-database-question="{{ $question->id }}"
                            >
                                <option value="">
                                    Select
                                </option>

                                <option
                                    value="Male"
                                    @selected($oldValue === 'Male')
                                >
                                    Male
                                </option>

                                <option
                                    value="Female"
                                    @selected($oldValue === 'Female')
                                >
                                    Female
                                </option>
                            </select>

                        @elseif ($databaseInput === 'textarea')
                            <textarea
                                name="{{ $fieldName }}"
                                data-database-question="{{ $question->id }}"
                            >{{ $oldValue }}</textarea>

                        @elseif ($databaseInput === 'email')
                            <input
                                type="email"
                                name="{{ $fieldName }}"
                                value="{{ $oldValue }}"
                                data-database-question="{{ $question->id }}"
                            >

                        @elseif ($databaseInput === 'tel')
                            <input
                                type="tel"
                                name="{{ $fieldName }}"
                                value="{{ $oldValue }}"
                                data-database-question="{{ $question->id }}"
                            >

                        @else
                            <input
                                type="text"
                                name="{{ $fieldName }}"
                                value="{{ $oldValue }}"
                                data-database-question="{{ $question->id }}"
                            >
                        @endif

                    @endif

                    @error(
                        'answers.'
                        . $question->id
                    )
                        <div
                            style="
                                margin-top:6px;
                                color:#b91c1c;
                                font-size:12px;
                            "
                        >
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </section>

        @empty
            <section class="card">
                No questions have been configured yet.
            </section>
        @endforelse


        <button
            type="submit"
            class="submit"
        >
            Submit Response
        </button>
    </form>

    <p class="privacy">
        Information entered on this form is used for this
        meeting response and authorized database administration.
    </p>
</div>


<script>
(() => {
    const searchUrl = @json(
        route(
            request()->getHost() === 'm.overcomers.win'
                ? 'meeting.short.search'
                : 'meeting.search',
            ['slug' => $publicSlug],
            false
        )
    );

    const autofillUrl = @json(
        route(
            request()->getHost() === 'm.overcomers.win'
                ? 'meeting.short.autofill'
                : 'meeting.autofill',
            ['slug' => $publicSlug],
            false
        )
    );

    const form =
        document.querySelector('form');

    const csrfToken =
        form.querySelector(
            'input[name="_token"]'
        ).value;

    const searchInput =
        document.getElementById('name_search');

    const searchStatus =
        document.getElementById('search_status');

    const resultsBox =
        document.getElementById('name_results');

    const typeInput =
        document.getElementById('respondent_type');

    const tokenInput =
        document.getElementById('respondent_token');

    const hintInput =
        document.getElementById('respondent_name_hint');

    const selectedBox =
        document.getElementById('selected_name');

    const selectedName =
        document.getElementById('selected_name_text');

    const changeButton =
        document.getElementById('change_name');

    const guestToggle =
        document.getElementById('guest_toggle');

    const guestPanel =
        document.getElementById('guest_panel');

    const guestInput =
        document.getElementById('guest_name');

    const databaseFields =
        Array.from(
            document.querySelectorAll(
                '[data-database-question]'
            )
        );


    /*
     * -------------------------------------------------
     * FILAMENT-STYLE SEARCHABLE PUBLIC DROPDOWNS
     * -------------------------------------------------
     *
     * Dropdowns with more than five choices become
     * searchable comboboxes similar to the searchable
     * Select used inside the People Database.
     *
     * The original <select> remains the actual form
     * control. This custom interface only mirrors and
     * changes its selected value.
     */
    function enhanceSearchableSelects() {
        if (
            ! document.getElementById(
                'meeting-searchable-select-styles'
            )
        ) {
            const style =
                document.createElement('style');

            style.id =
                'meeting-searchable-select-styles';

            style.textContent = `
                .meeting-search-select {
                    position: relative;
                    width: 100%;
                }

                .meeting-search-select-native {
                    position: absolute !important;
                    width: 1px !important;
                    height: 1px !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    opacity: 0 !important;
                    pointer-events: none !important;
                    overflow: hidden !important;
                }

                .meeting-search-trigger {
                    display: flex;
                    width: 100%;
                    min-height: 46px;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    box-sizing: border-box;
                    border: 1px solid #d1d5db;
                    border-radius: 10px;
                    background: #ffffff;
                    padding: 11px 14px;
                    color: #111827;
                    font: inherit;
                    text-align: left;
                    cursor: pointer;
                    transition:
                        border-color .15s ease,
                        box-shadow .15s ease;
                }

                .meeting-search-trigger:hover {
                    border-color: #9ca3af;
                }

                .meeting-search-trigger:focus,
                .meeting-search-select.is-open
                    .meeting-search-trigger {
                    outline: none;
                    border-color: #0f9f8f;
                    box-shadow:
                        0 0 0 2px
                        rgba(15, 159, 143, .16);
                }

                .meeting-search-trigger-text {
                    min-width: 0;
                    flex: 1;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .meeting-search-trigger.is-placeholder
                    .meeting-search-trigger-text {
                    color: #6b7280;
                }

                .meeting-search-chevron {
                    flex: 0 0 auto;
                    width: 16px;
                    height: 16px;
                    color: #6b7280;
                    transition: transform .15s ease;
                }

                .meeting-search-select.is-open
                    .meeting-search-chevron {
                    transform: rotate(180deg);
                }

                .meeting-search-panel {
                    position: absolute;
                    z-index: 1000;
                    top: calc(100% + 5px);
                    left: 0;
                    width: 100%;
                    min-width: 100%;
                    box-sizing: border-box;
                    overflow: hidden;
                    border: 1px solid #d1d5db;
                    border-radius: 10px;
                    background: #ffffff;
                    box-shadow:
                        0 10px 24px
                        rgba(15, 23, 42, .16);
                }

                .meeting-search-select.drop-up
                    .meeting-search-panel {
                    top: auto;
                    bottom: calc(100% + 6px);
                }

                .meeting-search-box {
                    position: sticky;
                    top: 0;
                    z-index: 2;
                    padding: 10px;
                    border-bottom: 1px solid #e5e7eb;
                    background: #ffffff;
                }

                .meeting-search-input {
                    display: block;
                    width: 100%;
                    box-sizing: border-box;
                    border: 0 !important;
                    border-radius: 0 !important;
                    background: transparent !important;
                    padding: 3px 2px !important;
                    color: #111827 !important;
                    font: inherit;
                    box-shadow: none !important;
                    outline: none !important;
                }

                .meeting-search-input::placeholder {
                    color: #9ca3af;
                }

                .meeting-search-options {
                    max-height: 210px;
                    overflow-y: auto;
                    padding: 6px;
                    overscroll-behavior: contain;
                }

                .meeting-search-group {
                    padding: 8px 10px 5px;
                    color: #6b7280;
                    font-size: 11px;
                    font-weight: 800;
                    letter-spacing: .04em;
                    text-transform: uppercase;
                }

                .meeting-search-option {
                    display: block;
                    width: 100%;
                    box-sizing: border-box;
                    border: 0;
                    border-radius: 7px;
                    background: transparent;
                    padding: 8px 10px;
                    color: #111827;
                    font: inherit;
                    font-size: 14px;
                    line-height: 1.4;
                    text-align: left;
                    cursor: pointer;
                }

                .meeting-search-option:hover,
                .meeting-search-option:focus {
                    outline: none;
                    background: #f3f4f6;
                }

                .meeting-search-option.is-selected {
                    background: #ecfdf5;
                    color: #047857;
                    font-weight: 700;
                }

                .meeting-search-empty {
                    padding: 16px 10px;
                    color: #6b7280;
                    font-size: 13px;
                    text-align: center;
                }


            `;

            document.head.appendChild(style);
        }

        const selects =
            Array.from(
                form.querySelectorAll('select')
            );

        const closeAll =
            (except = null) => {
                document
                    .querySelectorAll(
                        '.meeting-search-select.is-open'
                    )
                    .forEach(
                        (wrapper) => {
                            if (wrapper === except) {
                                return;
                            }

                            wrapper.classList.remove(
                                'is-open',
                                'drop-up'
                            );

                            const panel =
                                wrapper.querySelector(
                                    '.meeting-search-panel'
                                );

                            if (panel) {
                                panel.hidden = true;
                            }
                        }
                    );
            };

        selects.forEach(
            (select) => {
                if (
                    select.dataset.searchEnhanced
                    === '1'
                ) {
                    return;
                }

                const actualOptions =
                    Array.from(
                        select.options
                    ).filter(
                        (option) =>
                            String(
                                option.value ?? ''
                            ).trim() !== ''
                    );

                if (actualOptions.length <= 5) {
                    return;
                }

                select.dataset.searchEnhanced = '1';

                const wrapper =
                    document.createElement('div');

                wrapper.className =
                    'meeting-search-select';

                select.parentNode.insertBefore(
                    wrapper,
                    select
                );

                wrapper.appendChild(select);

                select.classList.add(
                    'meeting-search-select-native'
                );

                const trigger =
                    document.createElement('button');

                trigger.type = 'button';
                trigger.className =
                    'meeting-search-trigger';

                trigger.setAttribute(
                    'aria-haspopup',
                    'listbox'
                );

                trigger.setAttribute(
                    'aria-expanded',
                    'false'
                );

                const triggerText =
                    document.createElement('span');

                triggerText.className =
                    'meeting-search-trigger-text';

                const chevron =
                    document.createElement('span');

                chevron.className =
                    'meeting-search-chevron';

                chevron.innerHTML = `
                    <svg
                        viewBox="0 0 20 20"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                    >
                        <path
                            d="M6 8l4 4 4-4"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                `;

                trigger.appendChild(
                    triggerText
                );

                trigger.appendChild(
                    chevron
                );

                wrapper.appendChild(
                    trigger
                );

                const panel =
                    document.createElement('div');

                panel.className =
                    'meeting-search-panel';

                panel.hidden = true;

                const searchBox =
                    document.createElement('div');

                searchBox.className =
                    'meeting-search-box';

                const search =
                    document.createElement('input');

                search.type = 'search';
                search.autocomplete = 'off';
                search.spellcheck = false;
                search.placeholder =
                    'Start typing to search...';

                search.className =
                    'meeting-search-input';

                searchBox.appendChild(
                    search
                );

                panel.appendChild(
                    searchBox
                );

                const optionsBox =
                    document.createElement('div');

                optionsBox.className =
                    'meeting-search-options';

                optionsBox.setAttribute(
                    'role',
                    'listbox'
                );

                panel.appendChild(
                    optionsBox
                );

                wrapper.appendChild(
                    panel
                );

                const placeholderOption =
                    Array.from(
                        select.options
                    ).find(
                        (option) =>
                            String(
                                option.value ?? ''
                            ).trim() === ''
                    );

                const placeholder =
                    placeholderOption
                        ? placeholderOption.textContent.trim()
                        : 'Select';

                const optionRows = [];

                Array.from(
                    select.children
                ).forEach(
                    (child) => {
                        if (
                            child.tagName
                            === 'OPTGROUP'
                        ) {
                            Array.from(
                                child.children
                            ).forEach(
                                (option) => {
                                    if (
                                        String(
                                            option.value ?? ''
                                        ).trim() === ''
                                    ) {
                                        return;
                                    }

                                    optionRows.push({
                                        option,
                                        group:
                                            child.label
                                            || null,
                                    });
                                }
                            );

                            return;
                        }

                        if (
                            child.tagName
                            === 'OPTION'
                            &&
                            String(
                                child.value ?? ''
                            ).trim() !== ''
                        ) {
                            optionRows.push({
                                option: child,
                                group: null,
                            });
                        }
                    }
                );

                const refreshTrigger =
                    () => {
                        const selected =
                            select.options[
                                select.selectedIndex
                            ];

                        const hasValue =
                            selected
                            &&
                            String(
                                selected.value ?? ''
                            ).trim() !== '';

                        triggerText.textContent =
                            hasValue
                                ? selected.textContent.trim()
                                : placeholder;

                        trigger.classList.toggle(
                            'is-placeholder',
                            ! hasValue
                        );
                    };

                const renderOptions =
                    () => {
                        const query =
                            search.value
                                .trim()
                                .toLocaleLowerCase();

                        optionsBox.innerHTML = '';

                        let previousGroup = null;
                        let matchCount = 0;

                        optionRows.forEach(
                            ({
                                option,
                                group,
                            }) => {
                                const label =
                                    String(
                                        option.textContent
                                        ?? ''
                                    ).trim();

                                if (
                                    query !== ''
                                    &&
                                    ! label
                                        .toLocaleLowerCase()
                                        .includes(query)
                                ) {
                                    return;
                                }

                                matchCount++;

                                if (
                                    group
                                    &&
                                    group
                                    !== previousGroup
                                ) {
                                    const groupLabel =
                                        document.createElement(
                                            'div'
                                        );

                                    groupLabel.className =
                                        'meeting-search-group';

                                    groupLabel.textContent =
                                        group;

                                    optionsBox.appendChild(
                                        groupLabel
                                    );

                                    previousGroup =
                                        group;
                                }

                                if (! group) {
                                    previousGroup = null;
                                }

                                const button =
                                    document.createElement(
                                        'button'
                                    );

                                button.type = 'button';

                                button.className =
                                    'meeting-search-option';

                                button.setAttribute(
                                    'role',
                                    'option'
                                );

                                button.textContent =
                                    label;

                                if (
                                    String(select.value)
                                    ===
                                    String(option.value)
                                ) {
                                    button.classList.add(
                                        'is-selected'
                                    );

                                    button.setAttribute(
                                        'aria-selected',
                                        'true'
                                    );
                                }

                                button.addEventListener(
                                    'click',
                                    () => {
                                        select.value =
                                            option.value;

                                        select.dispatchEvent(
                                            new Event(
                                                'input',
                                                {
                                                    bubbles:
                                                        true,
                                                }
                                            )
                                        );

                                        select.dispatchEvent(
                                            new Event(
                                                'change',
                                                {
                                                    bubbles:
                                                        true,
                                                }
                                            )
                                        );

                                        refreshTrigger();

                                        closeAll();

                                        trigger.focus();
                                    }
                                );

                                optionsBox.appendChild(
                                    button
                                );
                            }
                        );

                        if (matchCount === 0) {
                            const databaseField =
                                select.dataset
                                    .databaseField
                                ?? null;

                            /*
                             * Grade Level is a controlled
                             * canonical list. If the search
                             * matches nothing, offer the
                             * existing Not Applicable value
                             * instead of allowing a new one.
                             */
                            if (
                                databaseField
                                === 'grade_level'
                            ) {
                                const notApplicableOption =
                                    Array.from(
                                        select.options
                                    ).find(
                                        (option) =>
                                            String(
                                                option.value
                                                ?? ''
                                            )
                                            === 'Not Applicable'
                                    );

                                if (notApplicableOption) {
                                    const fallbackButton =
                                        document.createElement(
                                            'button'
                                        );

                                    fallbackButton.type =
                                        'button';

                                    fallbackButton.className =
                                        'meeting-search-option';

                                    fallbackButton.textContent =
                                        'Not Applicable';

                                    fallbackButton.addEventListener(
                                        'click',
                                        () => {
                                            select.value =
                                                notApplicableOption.value;

                                            select.dispatchEvent(
                                                new Event(
                                                    'input',
                                                    {
                                                        bubbles:
                                                            true,
                                                    }
                                                )
                                            );

                                            select.dispatchEvent(
                                                new Event(
                                                    'change',
                                                    {
                                                        bubbles:
                                                            true,
                                                    }
                                                )
                                            );

                                            refreshTrigger();

                                            closeAll();

                                            trigger.focus();
                                        }
                                    );

                                    optionsBox.appendChild(
                                        fallbackButton
                                    );

                                    return;
                                }
                            }

                            const referenceField =
                                select.dataset
                                    .referenceProposalField
                                ?? null;

                            const proposalOption =
                                Array.from(
                                    select.options
                                ).find(
                                    (option) =>
                                        String(
                                            option.value
                                            ?? ''
                                        )
                                        === '__not_listed__'
                                );

                            const proposalLabel =
                                referenceField === 'school'
                                    ? "My school isn't listed"
                                    : (
                                        referenceField
                                        === 'locality'
                                            ? "My locality isn't listed"
                                            : null
                                    );

                            /*
                             * School / Locality are controlled
                             * reference fields that support a
                             * pending "not listed" proposal.
                             *
                             * Make the empty-search result an
                             * actionable choice instead of a
                             * dead-end message.
                             */
                            if (
                                proposalLabel
                                && proposalOption
                            ) {
                                const proposalButton =
                                    document.createElement(
                                        'button'
                                    );

                                proposalButton.type =
                                    'button';

                                proposalButton.className =
                                    'meeting-search-option';

                                proposalButton.textContent =
                                    proposalLabel;

                                proposalButton.addEventListener(
                                    'click',
                                    () => {
                                        select.value =
                                            proposalOption.value;

                                        select.dispatchEvent(
                                            new Event(
                                                'input',
                                                {
                                                    bubbles:
                                                        true,
                                                }
                                            )
                                        );

                                        select.dispatchEvent(
                                            new Event(
                                                'change',
                                                {
                                                    bubbles:
                                                        true,
                                                }
                                            )
                                        );

                                        refreshTrigger();

                                        closeAll();

                                        trigger.focus();
                                    }
                                );

                                optionsBox.appendChild(
                                    proposalButton
                                );

                                return;
                            }

                            const empty =
                                document.createElement(
                                    'div'
                                );

                            empty.className =
                                'meeting-search-empty';

                            empty.textContent =
                                'No matching options.';

                            optionsBox.appendChild(
                                empty
                            );
                        }
                    };

                const openDropdown =
                    () => {
                        closeAll(wrapper);

                        wrapper.classList.add(
                            'is-open'
                        );

                        panel.hidden = false;

                        trigger.setAttribute(
                            'aria-expanded',
                            'true'
                        );

                        /*
                         * Open upward automatically when
                         * there is not enough room below,
                         * similar to Filament's Select.
                         */
                        const rect =
                            trigger.getBoundingClientRect();

                        const spaceBelow =
                            window.innerHeight
                            - rect.bottom;

                        const spaceAbove =
                            rect.top;

                        wrapper.classList.toggle(
                            'drop-up',
                            spaceBelow < 300
                            &&
                            spaceAbove > spaceBelow
                        );

                        search.value = '';

                        renderOptions();

                        requestAnimationFrame(
                            () => search.focus()
                        );
                    };

                const closeDropdown =
                    () => {
                        wrapper.classList.remove(
                            'is-open',
                            'drop-up'
                        );

                        panel.hidden = true;

                        trigger.setAttribute(
                            'aria-expanded',
                            'false'
                        );
                    };

                trigger.addEventListener(
                    'click',
                    () => {
                        if (
                            wrapper.classList.contains(
                                'is-open'
                            )
                        ) {
                            closeDropdown();

                            return;
                        }

                        openDropdown();
                    }
                );

                search.addEventListener(
                    'input',
                    renderOptions
                );

                search.addEventListener(
                    'keydown',
                    (event) => {
                        if (event.key === 'Escape') {
                            event.preventDefault();

                            closeDropdown();

                            trigger.focus();
                        }
                    }
                );

                select.addEventListener(
                    'change',
                    () => {
                        refreshTrigger();

                        if (
                            wrapper.classList.contains(
                                'is-open'
                            )
                        ) {
                            renderOptions();
                        }
                    }
                );

                /*
                 * Required native selects remain the real
                 * validation controls. Redirect focus to
                 * the visible combobox when invalid.
                 */
                select.addEventListener(
                    'invalid',
                    (event) => {
                        event.preventDefault();

                        openDropdown();
                    }
                );

                /*
                 * Public autofill changes select.value
                 * programmatically, so expose a refresh
                 * hook for that code path.
                 */
                select.__searchableSelectRefresh =
                    () => {
                        refreshTrigger();

                        if (
                            wrapper.classList.contains(
                                'is-open'
                            )
                        ) {
                            renderOptions();
                        }
                    };

                refreshTrigger();
            }
        );

        document.addEventListener(
            'click',
            (event) => {
                if (
                    event.target.closest(
                        '.meeting-search-select'
                    )
                ) {
                    return;
                }

                closeAll();
            }
        );
    }


    enhanceSearchableSelects();


    /*
     * -------------------------------------------------
     * NOT-LISTED REFERENCE PROPOSALS
     * -------------------------------------------------
     */
    const referenceProposalValue =
        @json(
            \App\Support\MeetingFormDatabaseFieldRegistry
                ::REFERENCE_PROPOSAL_VALUE
        );

    const referenceProposalSelects =
        Array.from(
            form.querySelectorAll(
                '[data-reference-proposal-field]'
            )
        );

    function syncReferenceProposalPanel(
        select
    ) {
        const questionId =
            select.dataset
                .referenceProposalQuestion;

        const panel =
            form.querySelector(
                '[data-reference-proposal-panel="'
                + questionId
                + '"]'
            );

        if (! panel) {
            return;
        }

        const enabled =
            select.value
            === referenceProposalValue;

        panel.hidden =
            ! enabled;

        panel
            .querySelectorAll(
                '[data-reference-proposal-required]'
            )
            .forEach(
                (field) => {
                    field.required =
                        enabled;
                }
            );

        if (
            enabled
            && select.dataset.autofilled
            === '1'
        ) {
            delete select.dataset.autofilled;
        }
    }

    referenceProposalSelects.forEach(
        (select) => {
            select.addEventListener(
                'change',
                () =>
                    syncReferenceProposalPanel(
                        select
                    )
            );

            syncReferenceProposalPanel(
                select
            );
        }
    );


    let searchTimer = null;
    let searchController = null;
    let autofillController = null;


    function clearResults() {
        resultsBox.innerHTML = '';
        resultsBox.hidden = true;
    }


    function clearAutofilledValues() {
        databaseFields.forEach((field) => {
            if (
                field.dataset.autofilled
                !== '1'
            ) {
                return;
            }

            field.value = '';

            if (
                typeof field.__searchableSelectRefresh
                === 'function'
            ) {
                field.__searchableSelectRefresh();
            }

            delete field.dataset.autofilled;
        });
    }


    function selectResult(result) {
        clearAutofilledValues();

        typeInput.value = 'existing';
        tokenInput.value = result.token;
        hintInput.value = result.name;

        selectedName.textContent =
            result.name;

        selectedBox.hidden =
            false;

        guestPanel.hidden =
            true;

        guestInput.required =
            false;

        guestInput.value =
            '';

        searchInput.value =
            result.name;

        searchStatus.textContent =
            'Name selected.';

        clearResults();

        attemptAutofill();
    }


    function useGuestMode() {
        clearAutofilledValues();

        typeInput.value =
            'guest';

        tokenInput.value =
            '';

        hintInput.value =
            '';

        selectedBox.hidden =
            true;

        clearResults();

        searchInput.value =
            '';

        guestPanel.hidden =
            false;

        guestInput.required =
            true;

        searchStatus.textContent =
            'Enter your full name below.';

        guestInput.focus();
    }


    function resetSelection() {
        clearAutofilledValues();

        typeInput.value =
            '';

        tokenInput.value =
            '';

        hintInput.value =
            '';

        selectedBox.hidden =
            true;

        guestPanel.hidden =
            true;

        guestInput.required =
            false;

        guestInput.value =
            '';

        searchInput.value =
            '';

        clearResults();

        searchStatus.textContent =
            'Type at least 3 characters to search.';

        searchInput.focus();
    }


    function renderResults(results) {
        clearResults();

        if (results.length === 0) {
            searchStatus.textContent =
                'No matching names found.';

            return;
        }

        results.forEach((result) => {
            const button =
                document.createElement('button');

            button.type = 'button';

            button.style.cssText = [
                'display:block',
                'width:100%',
                'padding:12px 14px',
                'border:0',
                'border-top:1px solid #e5e7eb',
                'background:white',
                'text-align:left',
                'cursor:pointer'
            ].join(';');

            const name =
                document.createElement('div');

            name.textContent =
                result.name;

            name.style.cssText =
                'font-weight:800;color:#111827';

            const source =
                document.createElement('div');

            source.textContent =
                'Existing Record';

            source.style.cssText =
                'margin-top:3px;font-size:11px;color:#6b7280';

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
            const response =
                await fetch(
                    searchUrl
                        + '?q='
                        + encodeURIComponent(query),
                    {
                        headers: {
                            'Accept':
                                'application/json'
                        },

                        signal:
                            searchController.signal
                    }
                );

            if (! response.ok) {
                throw new Error(
                    'Search request failed'
                );
            }

            const data =
                await response.json();

            renderResults(
                Array.isArray(data.results)
                    ? data.results
                    : []
            );
        } catch (error) {
            if (
                error.name
                === 'AbortError'
            ) {
                return;
            }

            clearResults();

            searchStatus.textContent =
                'Unable to search right now.';
        }
    }


    function databaseAnswers() {
        const answers = {};

        databaseFields.forEach(
            (field) => {
                answers[
                    field.dataset.databaseQuestion
                ] = field.value;
            }
        );

        return answers;
    }


    function fieldIsBlank(field) {
        return String(
            field.value ?? ''
        ).trim() === '';
    }


    async function attemptAutofill() {
        if (
            typeInput.value !== 'existing'
            || ! tokenInput.value
        ) {
            return;
        }

        if (autofillController) {
            autofillController.abort();
        }

        autofillController =
            new AbortController();

        try {
            const response =
                await fetch(
                    autofillUrl,
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrfToken,
                        },

                        body:
                            JSON.stringify({
                                respondent_token:
                                    tokenInput.value,

                                answers:
                                    databaseAnswers(),
                            }),

                        signal:
                            autofillController.signal,
                    }
                );

            if (! response.ok) {
                return;
            }

            const data =
                await response.json();

            if (
                ! data.autofill
                || ! data.values
            ) {
                return;
            }

            databaseFields.forEach(
                (field) => {
                    if (! fieldIsBlank(field)) {
                        return;
                    }

                    const questionId =
                        field.dataset.databaseQuestion;

                    if (
                        ! Object.prototype.hasOwnProperty.call(
                            data.values,
                            questionId
                        )
                    ) {
                        return;
                    }

                    field.value =
                        String(
                            data.values[
                                questionId
                            ]
                        );

                    if (
                        typeof field.__searchableSelectRefresh
                        === 'function'
                    ) {
                        field.__searchableSelectRefresh();
                    }

                    field.dataset.autofilled =
                        '1';
                }
            );
        } catch (error) {
            if (
                error.name
                === 'AbortError'
            ) {
                return;
            }

            /*
             * Silent by design.
             *
             * Autofill failure must never block ordinary
             * meeting form completion.
             */
        }
    }


    searchInput.addEventListener(
        'input',
        () => {
            const query =
                searchInput.value.trim();

            clearTimeout(searchTimer);

            if (
                typeInput.value
                === 'existing'
            ) {
                typeInput.value = '';
                tokenInput.value = '';
                hintInput.value = '';

                selectedBox.hidden =
                    true;

                clearAutofilledValues();
            }

            if (query.length < 3) {
                clearResults();

                searchStatus.textContent =
                    'Type at least 3 characters to search.';

                return;
            }

            searchTimer =
                setTimeout(
                    () =>
                        searchNames(query),
                    250
                );
        }
    );


    databaseFields.forEach(
        (field) => {
            /*
             * A manually typed/changed value belongs to the
             * participant and can never be overwritten later.
             */
            const markManual = () => {
                delete field.dataset.autofilled;
            };

            field.addEventListener(
                'input',
                markManual
            );

            field.addEventListener(
                'change',
                () => {
                    markManual();
                    attemptAutofill();
                }
            );

            field.addEventListener(
                'blur',
                attemptAutofill
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


    if (
        typeInput.value === 'guest'
    ) {
        guestPanel.hidden = false;
        guestInput.required = true;
    } else if (
        typeInput.value === 'existing'
        && tokenInput.value
        && hintInput.value
    ) {
        selectedName.textContent =
            hintInput.value;

        selectedBox.hidden =
            false;

        searchInput.value =
            hintInput.value;
    }


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
                && guestInput.value
                    .trim()
                    .length < 2
            ) {
                event.preventDefault();

                guestInput.focus();
            }
        }
    );
})();
</script>

@include('filament.components.back-to-top')
</body>
</html>
