<div
    class="mt-3 pt-2"
>
    <div
        class="flex flex-wrap items-center gap-x-3 gap-y-2"
    >
        <form
            method="POST"
            action="{{ route(
                'quezonprovinceactivities.attendance-meeting-responses.destroy',
                ['response' => $response]
            ) }}"
            onsubmit="return confirm('Delete this pre-listed response? Only the pre-listed YES/NO response will be deleted. People, Campus, Participant, Attendance, and Immich records will be preserved.');"
        >
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="inline-flex rounded-lg border border-red-300
                       bg-red-50 px-3 py-1.5 text-xs font-bold
                       text-red-700 hover:bg-red-100
                       dark:border-red-900 dark:bg-red-950
                       dark:text-red-200 dark:hover:bg-red-900"
            >
                Delete Pre-listed
            </button>
        </form>

        <span
            class="text-xs text-gray-400
                   dark:text-gray-500"
        >
            Removes only this YES/NO response.
        </span>
    </div>
</div>
