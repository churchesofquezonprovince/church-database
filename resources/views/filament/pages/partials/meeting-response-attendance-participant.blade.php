@if (
    $response->respondent_type
    ===
    \App\Models\AttendanceMeetingResponse::RESPONDENT_PERSON
    &&
    $response->person_id
)
    <div
        class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-900 dark:bg-violet-950"
    >
        <p
            class="text-sm font-bold text-violet-900 dark:text-violet-100"
        >
            Attendance Participant
        </p>

        <p
            class="mt-1 text-xs text-violet-700 dark:text-violet-300"
        >
            Add this Person to the normal attendance checklist.
            This does not mark the Person as present.
        </p>

        <div
            class="mt-3 flex flex-col gap-2 sm:flex-row"
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
                    class="inline-flex rounded-lg bg-violet-600 px-3 py-2 text-xs font-bold text-white hover:bg-violet-500"
                >
                    This Meeting Only
                </button>
            </form>


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
                    class="inline-flex rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-500"
                >
                    From This Meeting Onward
                </button>
            </form>
        </div>
    </div>
@endif
