@if (auth()->user()?->isAdmin())
    @php
        $publicSubmissions =
            $this->publicShepherdingSubmissions();
    @endphp

    <section
        class="rounded-2xl border border-violet-200
               bg-violet-50 p-5 shadow-sm
               dark:border-violet-900
               dark:bg-gray-900"
    >
        <div
            class="flex flex-wrap items-center
                   justify-between gap-3"
        >
            <div>
                <h3
                    class="text-lg font-bold
                           text-gray-950 dark:text-white"
                >
                    Public Shepherding Dashboard
                </h3>

                <p
                    class="mt-1 text-sm
                           text-gray-600
                           dark:text-gray-300"
                >
                    Public submissions remain pending
                    until an Admin reviews and records them.
                </p>
            </div>

            <a
                href="{{ route('shepherding.public.show') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="rounded-xl bg-violet-600
                       px-4 py-2 text-sm font-bold
                       text-white hover:bg-violet-500"
            >
                Open Public Dashboard ↗
            </a>
        </div>

        <div class="mt-4">
            <span
                class="rounded-full
                       bg-violet-600
                       px-3 py-1
                       text-xs font-bold
                       text-white"
            >
                {{
                    $publicSubmissions->count()
                }}
                Pending
            </span>
        </div>

        @if ($reviewingPublicSubmissionId)
            <div
                class="mt-4 rounded-xl
                       border border-amber-300
                       bg-amber-50 p-4
                       dark:border-amber-800
                       dark:bg-gray-950"
            >
                <div
                    class="flex flex-wrap
                           justify-between gap-3"
                >
                    <div>
                        <p
                            class="font-bold
                                   text-amber-900
                                   dark:text-amber-100"
                        >
                            Reviewing Public Submission
                            #{{ $reviewingPublicSubmissionId }}
                        </p>

                        <p
                            class="mt-1 text-xs
                                   text-amber-800
                                   dark:text-amber-200"
                        >
                            Reference:
                            {{ $publicReviewReference }}
                        </p>

                        <p
                            class="mt-1 text-sm
                                   text-amber-900
                                   dark:text-amber-100"
                        >
                            Submitted by:
                            <strong>
                                {{ $publicReviewSubmittedBy }}
                            </strong>

                            @if ($publicReviewSubmitterContact)
                                ·
                                {{
                                    $publicReviewSubmitterContact
                                }}
                            @endif
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="cancelPublicSubmissionReview"
                        class="rounded-xl border
                               border-amber-400
                               px-3 py-2
                               text-xs font-bold
                               text-amber-900
                               dark:text-amber-100"
                    >
                        Cancel Review
                    </button>
                </div>

                <div
                    class="mt-4 grid gap-3
                           lg:grid-cols-2"
                >
                    <div>
                        <p
                            class="text-xs font-bold
                                   uppercase tracking-wide
                                   text-amber-700
                                   dark:text-amber-300"
                        >
                            Public Contact Targets
                        </p>

                        <p
                            class="mt-1 whitespace-pre-line
                                   text-sm text-gray-800
                                   dark:text-gray-100"
                        >{{ $publicReviewTargets }}</p>
                    </div>

                    @if ($publicReviewParticipants)
                        <div>
                            <p
                                class="text-xs font-bold
                                       uppercase tracking-wide
                                       text-amber-700
                                       dark:text-amber-300"
                            >
                                Public Serving Saints
                            </p>

                            <p
                                class="mt-1 whitespace-pre-line
                                       text-sm text-gray-800
                                       dark:text-gray-100"
                            >{{ $publicReviewParticipants }}</p>
                        </div>
                    @endif

                    @if ($publicReviewHymns)
                        <div>
                            <p
                                class="text-xs font-bold
                                       uppercase tracking-wide
                                       text-amber-700
                                       dark:text-amber-300"
                            >
                                Public Hymns
                            </p>

                            <p
                                class="mt-1 whitespace-pre-line
                                       text-sm text-gray-800
                                       dark:text-gray-100"
                            >{{ $publicReviewHymns }}</p>
                        </div>
                    @endif

                    @if ($publicReviewMorningRevival)
                        <div>
                            <p
                                class="text-xs font-bold
                                       uppercase tracking-wide
                                       text-amber-700
                                       dark:text-amber-300"
                            >
                                Public Morning Revival
                            </p>

                            <p
                                class="mt-1 text-sm
                                       text-gray-800
                                       dark:text-gray-100"
                            >
                                {{
                                    $publicReviewMorningRevival
                                }}
                            </p>
                        </div>
                    @endif
                </div>

                <div
                    class="mt-4 rounded-xl border
                           border-gray-200 bg-white p-4
                           dark:border-gray-700
                           dark:bg-gray-950"
                >
                    <div
                        class="flex flex-wrap
                               items-center
                               justify-between gap-3"
                    >
                        <div>
                            <p
                                class="text-sm font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                Public Contact Target Matching
                            </p>

                            <p
                                class="mt-1 text-xs
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                Exact, unambiguous matches are
                                automatically checked below.
                                Review all selections before
                                approving the Shepherding Record.
                            </p>
                        </div>

                        <div
                            class="flex flex-wrap gap-2"
                        >
                            <button
                                type="button"
                                wire:click="parseAndCheckPublicContactTargets"
                                class="rounded-lg bg-primary-600
                                       px-3 py-2
                                       text-xs font-bold
                                       text-white
                                       hover:bg-primary-500"
                            >
                                Parse & Check
                            </button>

                            <button
                                type="button"
                                wire:click="clearPublicTargetParsedChecks"
                                class="rounded-lg border
                                       border-gray-300
                                       px-3 py-2
                                       text-xs font-bold
                                       text-gray-700
                                       dark:border-gray-600
                                       dark:text-gray-200"
                            >
                                Clear Parsed Checks
                            </button>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex flex-wrap gap-2"
                    >
                        <span
                            class="rounded-full
                                   bg-emerald-100
                                   px-2.5 py-1
                                   text-xs font-bold
                                   text-emerald-800
                                   dark:bg-emerald-950
                                   dark:text-emerald-200"
                        >
                            {{
                                count(
                                    $publicReviewTargetMatches
                                )
                            }}
                            Checked
                        </span>

                        <span
                            class="rounded-full
                                   bg-amber-100
                                   px-2.5 py-1
                                   text-xs font-bold
                                   text-amber-800
                                   dark:bg-amber-950
                                   dark:text-amber-200"
                        >
                            {{
                                count(
                                    $publicReviewTargetNeedsReview
                                )
                            }}
                            Need Review
                        </span>
                    </div>

                    @if (
                        $publicReviewTargetMatches
                        !== []
                    )
                        <div class="mt-4 space-y-2">
                            <p
                                class="text-xs font-bold
                                       uppercase tracking-wide
                                       text-emerald-700
                                       dark:text-emerald-300"
                            >
                                Automatically Checked
                            </p>

                            @foreach (
                                $publicReviewTargetMatches
                                as $match
                            )
                                <div
                                    class="rounded-lg
                                           bg-emerald-50
                                           px-3 py-2
                                           text-sm
                                           text-emerald-900
                                           dark:bg-emerald-950
                                           dark:text-emerald-100"
                                >
                                    <strong>
                                        ✓
                                        {{ $match['raw'] }}
                                    </strong>

                                    <span
                                        class="text-emerald-700
                                               dark:text-emerald-300"
                                    >
                                        →
                                        {{
                                            $match[
                                                'type_label'
                                            ]
                                        }}
                                        ·
                                        {{
                                            $match[
                                                'label'
                                            ]
                                        }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (
                        $publicReviewTargetNeedsReview
                        !== []
                    )
                        <div class="mt-4 space-y-2">
                            <p
                                class="text-xs font-bold
                                       uppercase tracking-wide
                                       text-amber-700
                                       dark:text-amber-300"
                            >
                                Needs Admin Review
                            </p>

                            @foreach (
                                $publicReviewTargetNeedsReview
                                as $item
                            )
                                <div
                                    class="rounded-lg
                                           bg-amber-50
                                           px-3 py-2
                                           text-sm
                                           text-amber-900
                                           dark:bg-gray-950
                                           dark:text-amber-100"
                                >
                                    <strong>
                                        ?
                                        {{ $item['raw'] }}
                                    </strong>

                                    <span
                                        class="block
                                               text-xs
                                               text-amber-700
                                               dark:text-amber-300"
                                    >
                                        {{
                                            $item[
                                                'reason'
                                            ]
                                        }}
                                    </span>

                                    @if (
                                        $item[
                                            'candidates'
                                        ]
                                        !== []
                                    )
                                        <div
                                            class="mt-1 text-xs"
                                        >
                                            Possible matches:
                                            {{
                                                implode(
                                                    ' · ',
                                                    $item[
                                                        'candidates'
                                                    ]
                                                )
                                            }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <p
                    class="mt-4 text-sm font-semibold
                           text-amber-900
                           dark:text-amber-100"
                >
                    The Record Contact form below has been
                    pre-filled where canonical IDs are safe.
                    Resolve Contact Targets, serving saints,
                    Hymns, and Morning Revival before saving.
                    Saving the form approves this submission.
                </p>
            </div>
        @endif

        @if ($publicSubmissions->isEmpty())
            <p
                class="mt-4 text-sm
                       text-gray-500
                       dark:text-gray-400"
            >
                No pending public submissions.
            </p>
        @else
            <div class="mt-4 space-y-3">
                @foreach (
                    $publicSubmissions
                    as $submission
                )
                    <article
                        class="rounded-xl border
                               border-violet-200
                               bg-white p-4
                               dark:border-violet-900
                               dark:bg-gray-950"
                    >
                        <div
                            class="flex flex-wrap
                                   justify-between gap-4"
                        >
                            <div class="min-w-0">
                                <div
                                    class="flex flex-wrap
                                           items-center gap-2"
                                >
                                    <strong>
                                        {{
                                            $submission
                                                ->submitted_by_name
                                        }}
                                    </strong>

                                    <span
                                        class="rounded-full
                                               bg-amber-100
                                               px-2 py-0.5
                                               text-xs font-bold
                                               text-amber-800
                                               dark:bg-amber-900
                                               dark:text-amber-100"
                                    >
                                        Pending
                                    </span>
                                </div>

                                <p
                                    class="mt-1 text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    {{
                                        $submission
                                            ->contact_date
                                            ?->format(
                                                'M j, Y'
                                            )
                                    }}

                                    @if (
                                        $submission
                                            ->contact_time
                                    )
                                        ·
                                        {{
                                            $submission
                                                ->contact_time
                                        }}
                                    @endif

                                    @if ($submission->locality)
                                        ·
                                        {{
                                            $submission
                                                ->locality
                                                ->name
                                        }}
                                    @endif

                                    ·
                                    {{
                                        $submission
                                            ->outcome
                                    }}
                                </p>

                                <p
                                    class="mt-3 whitespace-pre-line
                                           text-sm text-gray-800
                                           dark:text-gray-100"
                                >{{ $submission->contact_targets_text }}</p>

                                <p
                                    class="mt-2 text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    {{
                                        count(
                                            $submission
                                                ->activity_type_ids
                                            ?? []
                                        )
                                    }}
                                    activities
                                    ·
                                    {{
                                        count(
                                            $submission
                                                ->ministry_lesson_ids
                                            ?? []
                                        )
                                    }}
                                    ministry topics
                                </p>
                            </div>

                            <div
                                class="flex shrink-0
                                       flex-wrap items-start
                                       gap-2"
                            >
                                <button
                                    type="button"
                                    wire:click="reviewPublicSubmission({{ $submission->id }})"
                                    onclick="
                                        setTimeout(
                                            () =>
                                                document
                                                    .getElementById(
                                                        'shepherding-contact-form'
                                                    )
                                                    ?.scrollIntoView({
                                                        behavior: 'smooth',
                                                        block: 'start'
                                                    }),
                                            150
                                        )
                                    "
                                    class="rounded-xl
                                           bg-violet-600
                                           px-3 py-2
                                           text-xs font-bold
                                           text-white
                                           hover:bg-violet-500"
                                >
                                    Review in Record Contact
                                </button>

                                <button
                                    type="button"
                                    wire:click="rejectPublicSubmission({{ $submission->id }})"
                                    wire:confirm="Reject this Public Shepherding Submission? No canonical Shepherding Record will be created."
                                    class="rounded-xl border
                                           border-red-300
                                           px-3 py-2
                                           text-xs font-bold
                                           text-red-700
                                           dark:border-red-900
                                           dark:text-red-300"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endif
