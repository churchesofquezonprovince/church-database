<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Public Shepherding Record</title>

    <style>
        :root {
            color-scheme: dark;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #09090b;
            color: #f4f4f5;
        }

        .wrap {
            width: min(100% - 24px, 980px);
            margin: 0 auto;
            padding: 28px 0 80px;
        }

        .card {
            border: 1px solid #3f3f46;
            border-radius: 18px;
            background: #18181b;
            padding: 20px;
            margin-bottom: 18px;
        }

        h1, h2, h3, p {
            margin-top: 0;
        }

        .muted {
            color: #a1a1aa;
        }

        .notice {
            border-color: #047857;
            background: #052e2b;
        }

        .warning {
            border-color: #a16207;
            background: #2b1d04;
        }

        .grid {
            display: grid;
            gap: 14px;
        }

        .grid-2 {
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
        }

        label {
            display: block;
            font-weight: 700;
            font-size: .9rem;
        }

        input,
        select,
        textarea {
            width: 100%;
            margin-top: 7px;
            border: 1px solid #52525b;
            border-radius: 11px;
            background: #09090b;
            color: #fafafa;
            padding: 11px 12px;
            font: inherit;
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        .check {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            font-weight: 500;
            padding: 7px 0;
        }

        .check input {
            width: auto;
            margin: 3px 0 0;
        }

        details {
            border: 1px solid #3f3f46;
            border-radius: 12px;
            padding: 10px 12px;
            margin-top: 10px;
        }

        summary {
            cursor: pointer;
            font-weight: 800;
        }

        .code {
            font-family: monospace;
            color: #c4b5fd;
            font-weight: 800;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        button {
            border: 0;
            border-radius: 12px;
            background: #7c3aed;
            color: white;
            padding: 12px 18px;
            font-weight: 800;
            cursor: pointer;
        }

        .error {
            color: #fca5a5;
            font-size: .85rem;
            margin-top: 5px;
        }

        .honeypot {
            position: absolute;
            left: -10000px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }

        @media (max-width: 700px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .wrap {
                width: min(100% - 16px, 980px);
            }

            .card {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
<div class="wrap">
    <div class="card">
        <p class="muted">
            Church in Quezon Province
        </p>

        <h1>
            Shepherding Record Public Dashboard
        </h1>

        <p class="muted">
            Submit a shepherding contact for review.
            Your submission will not become an official
            Shepherding Record until an administrator
            reviews and approves it.
        </p>
    </div>

    @if (session('public_shepherding_saved'))
        <div class="card notice">
            <h2>
                Submission received
            </h2>

            <p>
                Your Shepherding Record was sent to the
                administrators for review.
            </p>

            @if (
                session(
                    'public_shepherding_reference'
                )
            )
                <p class="muted">
                    Reference:
                    <strong>
                        {{
                            session(
                                'public_shepherding_reference'
                            )
                        }}
                    </strong>
                </p>
            @endif
        </div>
    @endif

    @if ($errors->any())
        <div class="card warning">
            <strong>
                Please check the highlighted fields.
            </strong>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('shepherding.public.store') }}"
    >
        @csrf

        <div class="honeypot">
            <label>
                Website
                <input
                    type="text"
                    name="website"
                    tabindex="-1"
                    autocomplete="off"
                >
            </label>
        </div>

        <div class="card">
            <h2>
                Submitted By
            </h2>

            <div class="grid grid-2">
                <label>
                    Your Name *
                    <input
                        type="text"
                        name="submitted_by_name"
                        value="{{ old('submitted_by_name') }}"
                        required
                    >

                    @error('submitted_by_name')
                        <div class="error">
                            {{ $message }}
                        </div>
                    @enderror
                </label>

                <label>
                    Contact Number / Messenger
                    <input
                        type="text"
                        name="submitted_by_contact"
                        value="{{ old('submitted_by_contact') }}"
                    >
                </label>
            </div>
        </div>

        <div class="card">
            <h2>
                Contact Details
            </h2>

            <div class="grid grid-2">
                <label>
                    Contact Date *
                    <input
                        type="date"
                        name="contact_date"
                        value="{{
                            old(
                                'contact_date',
                                now()->toDateString()
                            )
                        }}"
                        max="{{ now()->toDateString() }}"
                        required
                    >
                </label>

                <label>
                    Contact Time
                    <input
                        type="time"
                        name="contact_time"
                        value="{{ old('contact_time') }}"
                    >
                </label>

                <label>
                    Locality
                    <select name="locality_id">
                        <option value="">
                            Select Locality
                        </option>

                        @foreach ($localities as $locality)
                            <option
                                value="{{ $locality->id }}"
                                @selected(
                                    (string)
                                    old('locality_id')
                                    ===
                                    (string)
                                    $locality->id
                                )
                            >
                                {{ $locality->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Outcome *
                    <select
                        name="outcome"
                        required
                    >
                        @foreach (
                            $outcomeOptions
                            as $value => $label
                        )
                            <option
                                value="{{ $value }}"
                                @selected(
                                    old(
                                        'outcome',
                                        \App\Models\ShepherdingContact
                                            ::OUTCOME_COMPLETED
                                    )
                                    ===
                                    $value
                                )
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div style="margin-top:16px">
                <label>
                    People / Households Contacted *
                    <textarea
                        name="contact_targets_text"
                        required
                        placeholder="Enter the names of the people or households contacted. One per line is recommended."
                    >{{ old('contact_targets_text') }}</textarea>

                    <div class="muted">
                        An administrator will match these names
                        to the official database before approval.
                    </div>

                    @error('contact_targets_text')
                        <div class="error">
                            {{ $message }}
                        </div>
                    @enderror
                </label>
            </div>
        </div>

        <div class="card">
            <h2>
                Shepherding Activities
            </h2>

            <div class="grid grid-2">
                @foreach (
                    $activityTypes
                    as $activity
                )
                    <label class="check">
                        <input
                            type="checkbox"
                            name="activity_type_ids[]"
                            value="{{ $activity->id }}"
                            @checked(
                                in_array(
                                    $activity->id,
                                    old(
                                        'activity_type_ids',
                                        []
                                    )
                                )
                            )
                        >

                        <span>
                            <span class="code">
                                {{ $activity->code }}
                            </span>

                            {{ $activity->name }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div
            class="card"
            id="public-ministry"
        >
            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    gap:16px;
                    flex-wrap:wrap;
                "
            >
                <div>
                    <h2>
                        Ministry Used
                    </h2>

                    <p class="muted">
                        Select the ministry topics covered.
                    </p>
                </div>

                <label
                    class="check"
                    style="font-weight:800"
                >
                    <input
                        type="checkbox"
                        id="public-tagalog"
                    >
                    Tagalog
                </label>
            </div>

            <label>
                Search Ministry Topics
                <input
                    type="search"
                    id="ministry-search"
                    placeholder="Search code, English, or Tagalog title..."
                >
            </label>

            @foreach ($ministryBooks as $book)
                <details
                    class="ministry-book"
                    data-search="{{
                        strtolower(
                            $book->code
                            . ' '
                            . $book->title
                            . ' '
                            . (
                                $book->title_tagalog
                                ?? ''
                            )
                            . ' '
                            . $book->lessons
                                ->map(
                                    fn ($lesson) =>
                                        $lesson->code
                                        . ' '
                                        . $lesson->title
                                        . ' '
                                        . (
                                            $lesson
                                                ->title_tagalog
                                            ?? ''
                                        )
                                )
                                ->implode(' ')
                        )
                    }}"
                >
                    <summary>
                        <span class="code">
                            {{ $book->code }}
                        </span>

                        <span
                            class="language-title"
                            data-en="{{ $book->title }}"
                            data-tl="{{
                                $book->title_tagalog
                                ?: $book->title
                            }}"
                        >
                            {{ $book->title }}
                        </span>
                    </summary>

                    @foreach (
                        $book->lessons
                        as $lesson
                    )
                        <label
                            class="check ministry-lesson"
                            data-search="{{
                                strtolower(
                                    $lesson->code
                                    . ' '
                                    . $lesson->title
                                    . ' '
                                    . (
                                        $lesson
                                            ->title_tagalog
                                        ?? ''
                                    )
                                )
                            }}"
                        >
                            <input
                                type="checkbox"
                                name="ministry_lesson_ids[]"
                                value="{{ $lesson->id }}"
                                @checked(
                                    in_array(
                                        $lesson->id,
                                        old(
                                            'ministry_lesson_ids',
                                            []
                                        )
                                    )
                                )
                            >

                            <span>
                                <span class="code">
                                    {{ $lesson->code }}
                                </span>

                                <span
                                    class="language-title"
                                    data-en="{{
                                        $lesson->title
                                    }}"
                                    data-tl="{{
                                        $lesson->title_tagalog
                                        ?: $lesson->title
                                    }}"
                                >
                                    {{ $lesson->title }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </details>
            @endforeach
        </div>

        <div class="card">
            <h2>
                Content Used
            </h2>

            <div class="grid">
                <label>
                    Bible Reading
                    <textarea
                        name="bible_references_text"
                        placeholder="One reference per line, e.g. John 3:16"
                    >{{ old('bible_references_text') }}</textarea>
                </label>

                <label>
                    Morning Revival
                    <input
                        type="text"
                        name="morning_revival_text"
                        value="{{ old('morning_revival_text') }}"
                        placeholder="Example: Week 4 · Day 2"
                    >
                </label>

                <label>
                    Hymns
                    <textarea
                        name="hymns_text"
                        placeholder="Enter hymn numbers or titles. One per line is recommended."
                    >{{ old('hymns_text') }}</textarea>
                </label>
            </div>
        </div>

        <div class="card">
            <h2>
                Serving Saints
            </h2>

            <label>
                Names
                <textarea
                    name="participant_names_text"
                    placeholder="Enter the names of the serving saints. One per line is recommended."
                >{{ old('participant_names_text') }}</textarea>
            </label>
        </div>

        <div class="card">
            <h2>
                Notes
            </h2>

            <label>
                Additional Notes
                <textarea
                    name="notes"
                >{{ old('notes') }}</textarea>
            </label>
        </div>

        <div class="card warning">
            <strong>
                Admin confirmation required
            </strong>

            <p class="muted">
                Submitting this form does not immediately
                create an official Shepherding Record.
                An administrator must review and approve it.
            </p>

            <div class="actions">
                <button type="submit">
                    Submit for Admin Review
                </button>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    const tagalog =
        document.getElementById(
            'public-tagalog'
        );

    const search =
        document.getElementById(
            'ministry-search'
        );

    function updateLanguage() {
        const useTagalog =
            Boolean(tagalog?.checked);

        document
            .querySelectorAll(
                '.language-title'
            )
            .forEach((element) => {
                element.textContent =
                    useTagalog
                        ? element.dataset.tl
                        : element.dataset.en;
            });
    }

    function filterMinistry() {
        const query =
            String(search?.value ?? '')
                .trim()
                .toLowerCase();

        document
            .querySelectorAll(
                '.ministry-book'
            )
            .forEach((book) => {
                const bookMatches =
                    query === ''
                    || String(
                        book.dataset.search
                        ?? ''
                    ).includes(query);

                book.style.display =
                    bookMatches
                        ? ''
                        : 'none';

                if (
                    query !== ''
                    && bookMatches
                ) {
                    book.open = true;
                }
            });
    }

    tagalog?.addEventListener(
        'change',
        updateLanguage
    );

    search?.addEventListener(
        'input',
        filterMinistry
    );

    updateLanguage();
})();
</script>

@include('filament.components.back-to-top')

</body>
</html>
