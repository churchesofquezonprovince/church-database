@php
    $pendingProfileCorrections =
        $this->pendingMeetingProfileCorrections();

    $pendingProfileCorrectionsByResponse =
        $pendingProfileCorrections
            ->groupBy(
                'attendance_meeting_response_id'
            );
@endphp

@if (
    $selectedSession
    && $pendingProfileCorrections->isNotEmpty()
)
    <details
        open
        class="min-w-0 overflow-hidden rounded-2xl
               border border-amber-200 bg-amber-50
               shadow-sm dark:border-amber-900
               dark:bg-amber-950"
    >
        <summary
            class="cursor-pointer px-4 py-4
                   hover:bg-amber-100
                   dark:hover:bg-amber-900
                   sm:px-6"
        >
            <span
                class="ml-2 inline-flex
                       w-[calc(100%-2rem)]
                       items-center justify-between
                       gap-3 align-middle"
            >
                <span
                    class="text-lg font-bold
                           text-amber-950
                           dark:text-amber-100"
                >
                    Pending Database Changes
                </span>

                <span
                    class="rounded-full bg-amber-600
                           px-2.5 py-1 text-xs
                           font-bold text-white"
                >
                    {{ $pendingProfileCorrections->count() }}
                </span>
            </span>
        </summary>

        <div
            class="border-t border-amber-200
                   p-4 dark:border-amber-900
                   sm:p-6"
        >
            <p
                class="text-sm leading-6
                       text-amber-800
                       dark:text-amber-200"
            >
                Participants proposed these changes through
                Database Fields on the meeting form.
                Nothing below changes the canonical database
                until you approve it.
            </p>

            <div class="mt-5 space-y-5">
                @foreach (
                    $pendingProfileCorrectionsByResponse
                    as $responseCorrections
                )
                    @php
                        $response =
                            $responseCorrections
                                ->first()
                                ?->response;
                    @endphp

                    <div
                        class="overflow-hidden rounded-xl
                               border border-amber-200
                               bg-white
                               dark:border-amber-900
                               dark:bg-gray-950"
                    >
                        <div
                            class="border-b border-amber-100
                                   px-4 py-3
                                   dark:border-amber-900"
                        >
                            <div
                                class="font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                {{ $response?->respondent_name
                                    ?? 'Unknown Respondent' }}
                            </div>

                            <div
                                class="mt-1 text-xs
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                {{ $responseCorrections->count() }}
                                pending database
                                {{ $responseCorrections->count() === 1
                                    ? 'change'
                                    : 'changes' }}
                            </div>
                        </div>

                        <div
                            class="divide-y
                                   divide-amber-100
                                   dark:divide-amber-900"
                        >
                            @foreach (
                                $responseCorrections
                                as $change
                            )
                                @php
                                    $fieldLabel =
                                        \App\Support\MeetingFormDatabaseFieldRegistry::label(
                                            $change->database_field
                                        );

                                    $hasReviewIdentity =
                                        filled(
                                            $change->person_id
                                        )
                                        ||
                                        filled(
                                            $change->campus_contact_id
                                        )
                                        ||
                                        filled(
                                            $change->gospel_contact_id
                                        );
                                @endphp

                                <div class="p-4">
                                    <div
                                        class="flex flex-col gap-4
                                               lg:flex-row
                                               lg:items-start
                                               lg:justify-between"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap
                                                       items-center
                                                       gap-2"
                                            >
                                                <span
                                                    class="font-bold
                                                           text-gray-900
                                                           dark:text-white"
                                                >
                                                    {{ $fieldLabel }}
                                                </span>

                                                <span
                                                    class="rounded-full
                                                           bg-amber-100
                                                           px-2 py-1
                                                           text-[10px]
                                                           font-bold
                                                           text-amber-800
                                                           dark:bg-amber-900
                                                           dark:text-amber-100"
                                                >
                                                    {{ $change->changeTypeLabel() }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-3 grid gap-3
                                                       sm:grid-cols-2"
                                            >
                                                <div
                                                    class="rounded-lg
                                                           bg-gray-50 p-3
                                                           dark:bg-gray-900"
                                                >
                                                    <div
                                                        class="text-[10px]
                                                               font-bold
                                                               uppercase
                                                               tracking-wide
                                                               text-gray-500"
                                                    >
                                                        Current
                                                    </div>

                                                    <div
                                                        class="mt-1
                                                               break-words
                                                               text-sm
                                                               font-semibold
                                                               text-gray-800
                                                               dark:text-gray-100"
                                                    >
                                                        {{ $this
                                                            ->meetingProfileCorrectionValueLabel(
                                                                $change,
                                                                'original'
                                                            ) }}
                                                    </div>
                                                </div>

                                                <div
                                                    class="rounded-lg
                                                           bg-amber-50 p-3
                                                           dark:bg-amber-950"
                                                >
                                                    <div
                                                        class="text-[10px]
                                                               font-bold
                                                               uppercase
                                                               tracking-wide
                                                               text-amber-600"
                                                    >
                                                        Proposed
                                                    </div>

                                                    <div
                                                        class="mt-1
                                                               break-words
                                                               text-sm
                                                               font-bold
                                                               text-amber-900
                                                               dark:text-amber-100"
                                                    >
                                                        {{ $this
                                                            ->meetingProfileCorrectionValueLabel(
                                                                $change,
                                                                'proposed'
                                                            ) }}
                                                    </div>
                                                </div>
                                            </div>

                                            @if (! $hasReviewIdentity)
                                                <div
                                                    class="mt-3
                                                           text-xs
                                                           font-semibold
                                                           text-red-600
                                                           dark:text-red-300"
                                                >
                                                    Link this response to
                                                    a database identity
                                                    before approving it.
                                                </div>
                                            @endif
                                        </div>

                                        <div
                                            class="flex shrink-0
                                                   flex-wrap gap-2"
                                        >
                                            @if ($hasReviewIdentity)
                                                <button
                                                    type="button"
                                                    wire:click="approveMeetingProfileCorrection({{ $change->id }})"
                                                    wire:confirm="Approve this database change? The canonical database value will be updated."
                                                    wire:loading.attr="disabled"
                                                    class="rounded-lg
                                                           bg-emerald-600
                                                           px-3 py-2
                                                           text-xs font-bold
                                                           text-white
                                                           hover:bg-emerald-500
                                                           disabled:opacity-50"
                                                >
                                                    Approve
                                                </button>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="rejectMeetingProfileCorrection({{ $change->id }})"
                                                wire:confirm="Reject this proposed database change? The canonical value will remain unchanged."
                                                wire:loading.attr="disabled"
                                                class="rounded-lg
                                                       bg-red-600
                                                       px-3 py-2
                                                       text-xs font-bold
                                                       text-white
                                                       hover:bg-red-500
                                                       disabled:opacity-50"
                                            >
                                                Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </details>
@endif
