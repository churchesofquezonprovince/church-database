@php
$meetingResponses = $this->meetingResponses();

        $promotionLocalityOptions =
            \App\Support\LocalityOptions::groupedActiveConfigured();

        $promotionLocalityIdForName =
            function (?string $name) use (
                $promotionLocalityOptions
            ): ?int {
                $name = trim((string) $name);

                if ($name === '') {
                    return null;
                }

                $matches = [];

                foreach ($promotionLocalityOptions as $options) {
                    foreach ($options as $id => $label) {
                        if (
                            strcasecmp(
                                (string) $label,
                                $name
                            ) === 0
                        ) {
                            $matches[] = (int) $id;
                        }
                    }
                }

                return count($matches) === 1
                    ? $matches[0]
                    : null;
            };
        $yesMeetingResponses = $meetingResponses->where('response',\App\Models\AttendanceMeetingResponse::RESPONSE_YES);
        $noMeetingResponses = $meetingResponses->where('response',\App\Models\AttendanceMeetingResponse::RESPONSE_NO);
        $meetingResponseWorkflowStatuses =
    $this->meetingResponseWorkflowStatuses(
        $meetingResponses
    );

$selectedPreListedFilter =
    $this->selectedPreListedFilter();

$preListedFilterOptions =
    $this->preListedFilterOptions();

$filteredMeetingResponses =
    $meetingResponses
        ->filter(
            fn ($response) =>
                $this->meetingResponseMatchesPreListedFilter(
                    $response,
                    $meetingResponseWorkflowStatuses
                        ->get($response->id, []),
                    $selectedPreListedFilter,
                )
        );

$filteredYesMeetingResponses =
    $filteredMeetingResponses
        ->where(
            'response',
            \App\Models\AttendanceMeetingResponse::RESPONSE_YES
        );

$filteredNoMeetingResponses =
    $filteredMeetingResponses
        ->where(
            'response',
            \App\Models\AttendanceMeetingResponse::RESPONSE_NO
        );

$preListedFilterCounts = [
    'all' =>
        $meetingResponses->count(),

    'needs_action' =>
        $meetingResponseWorkflowStatuses
            ->where('needs_action', true)
            ->count(),

    'yes' =>
        $yesMeetingResponses->count(),

    'no' =>
        $noMeetingResponses->count(),

    'needs_identity_review' =>
        $meetingResponseWorkflowStatuses
            ->where(
                'key',
                'needs_identity_review'
            )
            ->count(),

    'ready_for_participant' =>
        $meetingResponseWorkflowStatuses
            ->where(
                'key',
                'ready_for_participant'
            )
            ->count(),

    'participant_covered' =>
        $meetingResponseWorkflowStatuses
            ->where(
                'key',
                'participant_covered'
            )
            ->count(),

    'participant_review' =>
        $meetingResponseWorkflowStatuses
            ->where(
                'key',
                'participant_review'
            )
            ->count(),
];
        $preListedResponsesByPersonId =
    $meetingResponses
        ->filter(
            fn ($response) =>
                $response->respondent_type
                ===
                \App\Models\AttendanceMeetingResponse::RESPONDENT_PERSON
                &&
                filled($response->person_id)
        )
        ->keyBy(
            fn ($response) =>
                (int) $response->person_id
        );

    $meetingResponsesNeedsActionCount =
        $meetingResponseWorkflowStatuses
            ->filter(
                fn ($status) =>
                    (bool) ($status['needs_action'] ?? false)
            )
            ->count();

    /*
     * Closed by default.
     *
     * Re-open automatically on a fresh page load only while
     * one or more pre-listed responses still need admin work.
     */
    $meetingResponsesShouldOpen =
        $meetingResponsesNeedsActionCount > 0;

@endphp

@if ($selectedSession && $selectedSheet->meetingFormEnabled())
    <details
        @if ($meetingResponsesShouldOpen) open @endif
        class="overflow-hidden rounded-2xl border border-indigo-200 bg-indigo-50 shadow-sm dark:border-indigo-900 dark:bg-indigo-950"
    >
        <summary
            class="cursor-pointer px-5 py-4 text-indigo-950 hover:bg-indigo-100 dark:text-indigo-100 dark:hover:bg-indigo-900 sm:px-6"
        >
            <span
                class="ml-2 inline-flex w-[calc(100%-2rem)] align-middle items-center justify-between gap-3"
            >
                <span class="text-lg font-bold">
                    Pre-listed Responses
                </span>

                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                    {{ $meetingResponses->count() }} response(s)

                    @if ($meetingResponsesNeedsActionCount > 0)
                        · {{ $meetingResponsesNeedsActionCount }} needs action
                    @endif
                </span>
            </span>
        </summary>

        <div class="border-t border-indigo-200 p-5 dark:border-indigo-900 sm:p-6">
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
        >
            <div>
                <p
                    class="mt-1 text-sm text-indigo-700 dark:text-indigo-300"
                >
                    Pre-listed responses are separate from actual
                    attendance. A YES response does not mark a
                    person as present.
                </p>
@if (session('meeting_response_promoted_to_campus'))
    <div
        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
    >
        <strong>
            Campus Contact created.
        </strong>

        {{ session('meeting_response_promoted_name') }}
        is now linked to this pre-listed entry through the Campus Database.
    </div>
@endif

@if (session('meeting_response_linked_to_person'))
    <div
        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
    >
        <strong>
            Guest linked to People Database.
        </strong>

        {{ session('meeting_response_linked_person_name') }}
        is now the canonical identity for this pre-listed entry.
    </div>
@endif

@if (session('meeting_response_person_created'))
    <div
        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
    >
        <strong>
            Person created.
        </strong>

        {{ session('meeting_response_created_person_name') }}
        is now the canonical identity for this pre-listed entry.
    </div>
@endif


@if (session('meeting_response_campus_promoted_to_person'))
    <div
        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
    >
        <strong>
            Campus pre-listed entry linked to People Database.
        </strong>

        {{ session('meeting_response_campus_person_name') }}
        is now the canonical identity for this pre-listed entry.
    </div>
@endif

@if (session('meeting_response_participant_added'))
    <div
        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
    >
        <strong>
            Attendance Participant added.
        </strong>

        {{ session('meeting_response_participant_name') }}

        @if (
            session('meeting_response_participant_scope')
            === 'this_meeting'
        )
            was added for this meeting only.
        @else
            was added from this meeting onward.
        @endif

        Actual attendance has not been marked.
    </div>
@endif


@if (session('meeting_response_participant_already'))
    <div
        class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200"
    >
        {{ session('meeting_response_participant_name') }}
        is already an Attendance Participant for this meeting.
        No changes were needed.
    </div>
@endif


@if ($errors->has('meeting_response_participant'))
    <div
        class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
    >
        {{ $errors->first(
            'meeting_response_participant'
        ) }}
    </div>
@endif

@if (session('meeting_response_deleted'))
    <div
        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
    >
        <strong>
            Pre-listed response deleted.
        </strong>

        {{ session('meeting_response_deleted_name') }}

        was removed from this meeting's pre-listed responses.

        People, Campus, Participant, Attendance, and Immich
        records were preserved.
    </div>
@endif






            </div>

            <div class="flex flex-wrap gap-2">
                <span
                    class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white"
                >
                    YES: {{ $yesMeetingResponses->count() }}
                </span>

                <span
                    class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white"
                >
                    NO: {{ $noMeetingResponses->count() }}
                </span>

                <span
                    class="rounded-full bg-indigo-600 px-3 py-1 text-xs font-bold text-white"
                >
                    Total: {{ $meetingResponses->count() }}
                </span>
            </div>
        </div>



<div class="mt-5 flex flex-wrap gap-2">
    @foreach (
        $preListedFilterOptions
        as $filterKey => $filterLabel
    )
        <a
            href="{{ $this->preListedFilterUrl($filterKey) }}"
            @class([
                'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold transition',

                'border-indigo-600 bg-indigo-600 text-white'
                    => $selectedPreListedFilter === $filterKey,

                'border-indigo-200 bg-white text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-gray-950 dark:text-indigo-200 dark:hover:bg-indigo-900'
                    => $selectedPreListedFilter !== $filterKey,
            ])
        >
            {{ $filterLabel }}

            <span
                @class([
                    'rounded-full px-1.5 py-0.5 text-[10px]',

                    'bg-white/20 text-white'
                        => $selectedPreListedFilter === $filterKey,

                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                        => $selectedPreListedFilter !== $filterKey,
                ])
            >
                {{ $preListedFilterCounts[$filterKey] ?? 0 }}
            </span>
        </a>
    @endforeach
</div>



        @if ($meetingResponses->isEmpty())

            <div
                class="mt-5 rounded-xl border border-dashed border-indigo-300 bg-white/60 p-6 text-center text-sm text-indigo-700 dark:border-indigo-800 dark:bg-gray-950 dark:text-indigo-200"
            >
                No public meeting responses have been submitted
                for this date yet.
            </div>

@elseif ($filteredMeetingResponses->isEmpty())
    <div
        class="mt-5 rounded-xl border border-dashed border-indigo-300 bg-white/60 p-6 text-center text-sm text-indigo-700 dark:border-indigo-800 dark:bg-gray-950 dark:text-indigo-200"
    >
        No pre-listed responses match this filter.
    </div>

@else


            <div class="mt-6 grid gap-6 xl:grid-cols-2">

                {{-- =========================================
                     YES RESPONSES
                ========================================== --}}
                <div>
                    <div
                        class="flex items-center justify-between"
                    >
                        <h4
                            class="font-bold text-emerald-800 dark:text-emerald-200"
                        >
                            YES — Attending
                        </h4>

                        <span
                            class="text-xs font-bold text-emerald-700 dark:text-emerald-300"
                        >
                            {{ $filteredYesMeetingResponses->count() }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-3">

                        @forelse ($filteredYesMeetingResponses as $response)

                            <div
                                class="rounded-xl border border-emerald-200 bg-white p-4 dark:border-emerald-900 dark:bg-gray-950"
                            >
                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div class="min-w-0">

                                        <p
                                            class="font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ $response->respondent_name }}
                                        </p>

                                        <div
                                            class="mt-2 flex flex-wrap gap-2"
                                        >

                                            @if (
                                                $response->respondent_type
                                                ===
                                                \App\Models\AttendanceMeetingResponse::RESPONDENT_PERSON
                                            )
                                                <span
                                                    class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100"
                                                >
                                                    People Database
                                                </span>

                                            @elseif (
                                                $response->respondent_type
                                                ===
                                                \App\Models\AttendanceMeetingResponse::RESPONDENT_CAMPUS
                                            )
                                                <span
                                                    class="rounded-full bg-sky-100 px-2 py-1 text-xs font-bold text-sky-800 dark:bg-sky-900 dark:text-sky-100"
                                                >
                                                    Campus Database
                                                </span>

                                            @else
                                                <span
                                                    class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-100"
                                                >
                                                    Guest
                                                </span>
                                            @endif


                                            @if (
                                                filled($response->original_source)
                                                &&
                                                $response->original_source
                                                !== $response->respondent_type
                                            )
                                                <span
                                                    class="rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                                >
                                                    Originally
                                                    {{ $response->originalSourceLabel() }}
                                                </span>
                                            @endif
@include(
    'filament.pages.partials.meeting-response-workflow-status',
    [
        'workflow' =>
            $meetingResponseWorkflowStatuses
                ->get($response->id, []),
    ]
)
                                        </div>

                                        @if (
                                            $response->original_source
                                            ===
                                            \App\Models\AttendanceMeetingResponse::RESPONDENT_GUEST
                                            &&
                                            filled($response->guest_profile)
                                        )
                                            <details
                                                class="mt-3"
                                            >
                                                <summary
                                                    class="cursor-pointer text-xs font-bold text-indigo-700 dark:text-indigo-300"
                                                >
                                                    Optional guest information
                                                </summary>

                                                <div
                                                    class="mt-2 space-y-1 rounded-lg bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-900 dark:text-gray-300"
                                                >
                                                    @foreach ($response->guest_profile as $key => $value)

                                                        @continue(blank($value))

                                                        <p>
                                                            <span class="font-semibold">
                                                                {{ match ($key) {
                                                                    'firstname' => 'First Name',
                                                                    'lastname' => 'Last Name',
                                                                    'sex' => 'Sex',
                                                                    'locality' => 'Locality',
                                                                    'school_campus' => 'School / Campus',
                                                                    'course_strand' => 'Course / Strand',
                                                                    'grade_level' => 'Grade Level',
                                                                    'contact_number' => 'Contact Number',
                                                                    'email' => 'Email',
                                                                    'facebook_account' => 'Facebook',
                                                                    default => str($key)->headline(),
                                                                } }}:
                                                            </span>

                                                            {{ $value }}
                                                        </p>

                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif

@include(
    'filament.pages.partials.meeting-response-promotion',
    [
        'response' => $response,
    ]
)

@include(
    'filament.pages.partials.meeting-response-campus-promotion',
    [
        'response' => $response,
    ]
)

@include(
    'filament.pages.partials.meeting-response-attendance-participant',
    [
        'response' => $response,
    ]
)

@include(
    'filament.pages.partials.meeting-response-delete',
    [
        'response' => $response,
    ]
)

                                    </div>

                                    @if ($response->responded_at)
                                        <span
                                            class="shrink-0 text-xs text-gray-400"
                                        >
                                            {{ $response->responded_at->format('M d · g:i A') }}
                                        </span>
                                    @endif

                                </div>
                            </div>

                        @empty

                            <div
                                class="rounded-xl border border-dashed border-emerald-300 p-4 text-center text-sm text-emerald-700 dark:border-emerald-800 dark:text-emerald-200"
                            >
                                No YES responses.
                            </div>

                        @endforelse

                    </div>
                </div>


                {{-- =========================================
                     NO RESPONSES
                ========================================== --}}
                <div>
                    <div
                        class="flex items-center justify-between"
                    >
                        <h4
                            class="font-bold text-red-800 dark:text-red-200"
                        >
                            NO — Unable to Attend
                        </h4>

                        <span
                            class="text-xs font-bold text-red-700 dark:text-red-300"
                        >
                            {{ $filteredNoMeetingResponses->count() }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-3">

                        @forelse ($filteredNoMeetingResponses as $response)

                            <div
                                class="rounded-xl border border-red-200 bg-white p-4 dark:border-red-900 dark:bg-gray-950"
                            >
                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                                >

                                    <div class="min-w-0">

                                        <p
                                            class="font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ $response->respondent_name }}
                                        </p>

                                        <div
                                            class="mt-2 flex flex-wrap gap-2"
                                        >

                                            @if (
                                                $response->respondent_type
                                                ===
                                                \App\Models\AttendanceMeetingResponse::RESPONDENT_PERSON
                                            )
                                                <span
                                                    class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100"
                                                >
                                                    People Database
                                                </span>

                                            @elseif (
                                                $response->respondent_type
                                                ===
                                                \App\Models\AttendanceMeetingResponse::RESPONDENT_CAMPUS
                                            )
                                                <span
                                                    class="rounded-full bg-sky-100 px-2 py-1 text-xs font-bold text-sky-800 dark:bg-sky-900 dark:text-sky-100"
                                                >
                                                    Campus Database
                                                </span>

                                            @else
                                                <span
                                                    class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-100"
                                                >
                                                    Guest
                                                </span>
                                            @endif


                                            @if (
                                                filled($response->original_source)
                                                &&
                                                $response->original_source
                                                !== $response->respondent_type
                                            )
                                                <span
                                                    class="rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                                >
                                                    Originally
                                                    {{ $response->originalSourceLabel() }}
                                                </span>
                                            @endif
@include(
    'filament.pages.partials.meeting-response-workflow-status',
    [
        'workflow' =>
            $meetingResponseWorkflowStatuses
                ->get($response->id, []),
    ]
)
                                        </div>


                                        @if (
                                            $response->original_source
                                            ===
                                            \App\Models\AttendanceMeetingResponse::RESPONDENT_GUEST
                                            &&
                                            filled($response->guest_profile)
                                        )
                                            <details
                                                class="mt-3"
                                            >
                                                <summary
                                                    class="cursor-pointer text-xs font-bold text-indigo-700 dark:text-indigo-300"
                                                >
                                                    Optional guest information
                                                </summary>

                                                <div
                                                    class="mt-2 space-y-1 rounded-lg bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-900 dark:text-gray-300"
                                                >
                                                    @foreach ($response->guest_profile as $key => $value)

                                                        @continue(blank($value))

                                                        <p>
                                                            <span class="font-semibold">
                                                                {{ match ($key) {
                                                                    'firstname' => 'First Name',
                                                                    'lastname' => 'Last Name',
                                                                    'sex' => 'Sex',
                                                                    'locality' => 'Locality',
                                                                    'school_campus' => 'School / Campus',
                                                                    'course_strand' => 'Course / Strand',
                                                                    'grade_level' => 'Grade Level',
                                                                    'contact_number' => 'Contact Number',
                                                                    'email' => 'Email',
                                                                    'facebook_account' => 'Facebook',
                                                                    default => str($key)->headline(),
                                                                } }}:
                                                            </span>

                                                            {{ $value }}
                                                        </p>

                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif

@include(
    'filament.pages.partials.meeting-response-promotion',
    [
        'response' => $response,
    ]
)

@include(
    'filament.pages.partials.meeting-response-campus-promotion',
    [
        'response' => $response,
    ]
)

@include(
    'filament.pages.partials.meeting-response-delete',
    [
        'response' => $response,
    ]
)

                                    </div>


                                    @if ($response->responded_at)
                                        <span
                                            class="shrink-0 text-xs text-gray-400"
                                        >
                                            {{ $response->responded_at->format('M d · g:i A') }}
                                        </span>
                                    @endif

                                </div>
                            </div>

                        @empty

                            <div
                                class="rounded-xl border border-dashed border-red-300 p-4 text-center text-sm text-red-700 dark:border-red-800 dark:text-red-200"
                            >
                                No NO responses.
                            </div>

                        @endforelse

                    </div>
                </div>

            </div>

        @endif
        </div>
    </details>
@endif
