@if (isset($selectedSheet) && $selectedSheet->sheet_type === 'custom')
    @if (in_array($response->respondent_type, ['person','guest','campus','gospel'], true))
        @php
            $isPersonResponse = $response->respondent_type === 'person' && $response->person_id;
            $responseGuestChoices = $isPersonResponse ? collect() : \App\Services\GuestAttendance::guests((int) $selectedSheet->id);
            $mappedGuest = $isPersonResponse ? null : \Illuminate\Support\Facades\DB::table('attendance_guest_responses')
                ->where('attendance_meeting_response_id', $response->id)->value('attendance_guest_id');
            $guestCovered = $mappedGuest && $selectedSession && \App\Services\GuestAttendance::activeIds(
                (int) $selectedSheet->id, $selectedSession->session_date->toDateString())->contains((int) $mappedGuest);
        @endphp
        <div class="mt-3 rounded-xl border border-violet-200 p-3 dark:border-violet-900">
            <p class="text-sm font-bold">{{ $guestCovered ? 'Enrolled for this meeting' : 'Enroll from this response' }}</p>
            <p class="mt-1 text-xs">Enrollment does not mark attendance. Linking a guest or contact to People is optional.</p>
            @if ($selectedSheet->is_active && !$selectedSession?->is_no_meeting)
            <details class="mt-2" @if (!$guestCovered) open @endif>
                <summary class="cursor-pointer text-sm font-semibold">{{ $guestCovered ? 'Review or extend enrollment' : 'Choose enrollment' }}</summary>
                <form class="mt-3 space-y-3" method="POST" action="{{ route('quezonprovinceactivities.attendance-meeting-responses.attendance-participant', ['response'=>$response]) }}">
                    @csrf
                    <input type="hidden" name="participant_response_id" value="{{ $response->id }}">
                    @if (!$isPersonResponse)
                    <label class="block text-sm">Attendance identity
                        <select class="mt-1 block w-full rounded-lg dark:bg-gray-900" name="participant_guest_id">
                            <option value="">Use this response's attendee (create only if needed)</option>
                            @foreach ($responseGuestChoices as $choice)
                            <option value="{{ $choice->id }}" @selected((int)$mappedGuest === (int)$choice->id)>{{ $choice->name }} · {{ match($choice->source_type) {'campus'=>'Campus Contact','gospel'=>'Gospel Contact',default=>'Guest'} }} · {{ $choice->locality ?: 'No locality' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <p class="text-xs">For repeat registrations, choose an existing attendee only after confirming they are the same person.</p>
                    @endif
                    <label class="block text-sm">Enrollment
                        <select class="mt-1 block w-full rounded-lg dark:bg-gray-900" name="participant_scope">
                            <option value="this_meeting">This Meeting Only</option>
                            @if ($selectedSheet->schedule_type !== 'one_time')<option value="onward">From This Meeting Onward</option>@endif
                        </select>
                    </label>
                    <button type="submit" class="rounded-lg bg-violet-600 px-3 py-2 text-sm font-bold text-white">{{ $guestCovered ? 'Update Enrollment' : 'Enroll Attendee' }}</button>
                </form>
            </details>
            @endif
        </div>
    @endif
@else
@if (in_array($response->respondent_type, ['person','guest','campus','gospel'], true))
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
                    Enroll this attendee for the selected date range.
                    This does not mark the attendee present. Guests and contacts can attend without creating a Person.
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
@endif
