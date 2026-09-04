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


        @if ($participants->isNotEmpty())

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
                        for="person_id"
                        class="field-label"
                    >
                        👤 Your Name
                    </label>

                    <select
                        id="person_id"
                        name="person_id"
                        required
                    >

                        <option value="">
                            Select your name
                        </option>

                        @foreach ($participants as $participant)

                            <option
                                value="{{ $participant->person_id }}"
                                @selected(
                                    (int) old('person_id')
                                    ===
                                    (int) $participant->person_id
                                )
                            >
                                {{ $participant->person?->display_name }}
                            </option>

                        @endforeach

                    </select>

                </div>


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

        @else

            <div class="empty">
                No participants are available for this meeting.
            </div>

        @endif


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
        This public form only displays participant names.
        No contact information or other personal details
        are shown.
    </p>

</div>

</body>
</html>

