@if (
    $response->respondent_type
    ===
    \App\Models\AttendanceMeetingResponse::RESPONDENT_PERSON
    &&
    $response->person_id
)
    @php
        /*
         * One-time Attendance has no future meeting scope.
         *
         * If this partial is ever reused outside Attendance Sheets,
         * preserve the previous behavior by allowing Onward when
         * no selected Sheet context exists.
         */
        $showOnwardParticipantScope =
            ! isset($selectedSheet)
            ||
            $selectedSheet->schedule_type
            !==
            \App\Models\AttendanceSheet::SCHEDULE_ONE_TIME;
    @endphp

    <div
        class="mt-1 w-full rounded-xl border border-violet-200
               bg-violet-50 p-3
               dark:border-violet-900 dark:bg-violet-950"
    >
        <div
            class="flex w-full flex-col gap-3
                   lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="min-w-0 flex-1">
                <p
                    class="text-sm font-bold
                           text-violet-900 dark:text-violet-100"
                >
                    Attendance Participant
                </p>

                <p
                    class="mt-1 text-xs
                           text-violet-700 dark:text-violet-300"
                >
                    Add this Person to the normal attendance checklist.
                    This does not mark the Person as present.
                </p>
            </div>

            <div
                class="flex shrink-0 flex-wrap gap-2
                       lg:justify-end"
            >
                <form
                    method="POST"
                    action="{{ route(
                        'quezonprovinceactivities.attendance-meeting-responses.attendance-participant',
                        ['response' => $response]
                    ) }}"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="participant_response_id"
                        value="{{ $response->id }}"
                    >

                    <input
                        type="hidden"
                        name="participant_scope"
                        value="this_meeting"
                    >

                    <button
                        type="submit"
                        class="rounded-lg bg-violet-600
                               px-3 py-2 text-xs font-bold
                               text-white hover:bg-violet-500"
                    >
                        This Meeting Only
                    </button>
                </form>

                @if ($showOnwardParticipantScope)
                    <form
                        method="POST"
                        action="{{ route(
                            'quezonprovinceactivities.attendance-meeting-responses.attendance-participant',
                            ['response' => $response]
                        ) }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="participant_response_id"
                            value="{{ $response->id }}"
                        >

                        <input
                            type="hidden"
                            name="participant_scope"
                            value="onward"
                        >

                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600
                                   px-3 py-2 text-xs font-bold
                                   text-white hover:bg-indigo-500"
                        >
                            From This Meeting Onward
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endif
