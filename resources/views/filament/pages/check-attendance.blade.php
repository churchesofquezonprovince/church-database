<x-filament-panels::page>
    @php
        $sheets = $this->sheets();
        $selectedSheet = $this->selectedSheet();
        $sessions = $this->sessions();
        $selectedSession = $this->selectedSession();
        $participantRows = $this->participantRows();
        $presentPersonIds = $this->presentPersonIds();
        $recordCounts = $this->recordCounts();
        $attendanceRecords = $this->attendanceRecords();
        $immichConfirmationCounts = $this->immichConfirmationCounts();
        $lordsTableLocalities = $this->permanentMeetingLocalities(\App\Models\AttendanceSheet::TYPE_LORDS_TABLE);
        $prayerMeetingLocalities = $this->permanentMeetingLocalities(\App\Models\AttendanceSheet::TYPE_PRAYER_MEETING);
        $meetingResponses = $this->meetingResponses();
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
        $immichPresentCount =
    $attendanceRecords
        ->filter(
            fn ($record) =>
                $record->attendance_source
                    === \App\Models\AttendanceRecord::SOURCE_IMMICH
                &&
                $record->is_present
        )
        ->count();

$presentWithoutPreListingCount =
    $attendanceRecords
        ->filter(
            fn ($record) =>
                $record->is_present
                &&
                ! $preListedResponsesByPersonId
                    ->has((int) $record->person_id)
        )
        ->count();
        @endphp

    <div class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-4">
            <div class="space-y-4 xl:col-span-1">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="font-bold text-gray-900 dark:text-white">
                        Sheets
                    </h3>

                    <div class="mt-4 space-y-2">
                        @foreach ($sheets as $sheet)
                            <a
                                href="{{ $this->sheetUrl($sheet) }}"
@class([
    'block rounded-xl border p-3 transition',

    'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950'
        => $selectedSheet?->id === $sheet->id,

    'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:hover:bg-gray-800'
        => $selectedSheet?->id !== $sheet->id,
])
                            >
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ $sheet->title }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $sheet->locality ?: 'No locality' }}
                                    · {{ $sheet->sessions_count }} date(s)
                                    · {{ $sheet->participants_count }} participant(s)
                                </p>
                            </a>
                        @endforeach

                        <details class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950">
                            <summary class="cursor-pointer font-bold text-amber-900 dark:text-amber-100">
                                Lord's Table Meeting
                            </summary>

                            <div class="mt-3 space-y-2">
                                @forelse ($lordsTableLocalities as $row)
                                    <a
                                        href="{{ $this->permanentMeetingUrl(\App\Models\AttendanceSheet::TYPE_LORDS_TABLE, $row['locality'], $row['sheet']) }}"
                                        class="block rounded-lg border border-amber-200 bg-white p-3 hover:bg-amber-100 dark:border-amber-900 dark:bg-gray-950 dark:hover:bg-amber-950"
                                    >
                                        <p class="font-bold text-gray-900 dark:text-white">
                                            {{ $row['label'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $row['sessions_count'] }} date(s)
                                            · {{ $row['participants_count'] }} participant(s)
                                        </p>
                                    </a>
                                @empty
                                    <p class="rounded-lg border border-dashed border-amber-300 p-3 text-xs text-amber-800 dark:border-amber-900 dark:text-amber-100">
                                        No localities found.
                                    </p>
                                @endforelse
                            </div>
                        </details>

                        <details class="rounded-xl border border-sky-200 bg-sky-50 p-3 dark:border-sky-900 dark:bg-sky-950">
                            <summary class="cursor-pointer font-bold text-sky-900 dark:text-sky-100">
                                Prayer Meeting
                            </summary>

                            <div class="mt-3 space-y-2">
                                @forelse ($prayerMeetingLocalities as $row)
                                    <a
                                        href="{{ $this->permanentMeetingUrl(\App\Models\AttendanceSheet::TYPE_PRAYER_MEETING, $row['locality'], $row['sheet']) }}"
                                        class="block rounded-lg border border-sky-200 bg-white p-3 hover:bg-sky-100 dark:border-sky-900 dark:bg-gray-950 dark:hover:bg-sky-950"
                                    >
                                        <p class="font-bold text-gray-900 dark:text-white">
                                            {{ $row['label'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $row['sessions_count'] }} date(s)
                                            · {{ $row['participants_count'] }} participant(s)
                                        </p>
                                    </a>
                                @empty
                                    <p class="rounded-lg border border-dashed border-sky-300 p-3 text-xs text-sky-800 dark:border-sky-900 dark:text-sky-100">
                                        No localities found.
                                    </p>
                                @endforelse
                            </div>
                        </details>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="font-bold text-gray-900 dark:text-white">
                        Meeting Dates
                    </h3>

                    <div class="mt-4 max-h-96 space-y-2 overflow-auto pr-1">
                        @foreach ($sessions as $session)
                            <a
                                href="{{ $this->sessionUrl($selectedSheet, $session) }}"
                                @class([
                                    'block rounded-xl border p-3 text-sm transition',
                                    'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100' => $selectedSession?->id === $session->id,
                                    'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800' => $selectedSession?->id !== $session->id,
                                ])
                            >
                                <span class="font-bold">
                                    {{ $session->dateTimeLabel() }}
                                </span>

                                <span class="block text-xs opacity-75">
                                    {{ $session->session_date->format('l') }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6 xl:col-span-3">
                @if (! $selectedSheet || ! $selectedSession)
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                        Select an attendance sheet and meeting date.
                    </div>
                @else
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $selectedSheet->title }}
                                </h3>

                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $selectedSheet->locality ?: 'No locality' }}
                                    · {{ $selectedSession->dateTimeLabel('l, F d, Y') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ \App\Filament\Pages\AttendanceReports::getUrl() . '?sheetId=' . $selectedSheet->id }}"
                                    class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white hover:bg-primary-500"
                                >
                                    View Report
                                </a>

                                <span class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white">
                                    Present: {{ $recordCounts['present'] }}
                                </span>

                                <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">
                                    Absent: {{ $recordCounts['absent'] }}
                                </span>

                                <span class="rounded-full bg-gray-600 px-3 py-1 text-xs font-bold text-white">
                                    Participants: {{ $participantRows->count() }}
                                </span>
                            </div>
                        </div>
                    </div>


@if ($selectedSheet->meetingFormEnabled())
    <div
        class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm dark:border-indigo-900 dark:bg-indigo-950"
    >
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
        >
            <div>
                <h3
                    class="text-lg font-bold text-indigo-950 dark:text-indigo-100"
                >
                    Meeting Responses
                </h3>

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
@endif



@if ($immichConfirmationCounts['detected'] > 0)
    <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold text-violet-900 dark:text-violet-100">
                    Immich Attendance Review
                </p>

                <p class="mt-1 text-xs text-violet-700 dark:text-violet-200">
                    Immich has automatically marked
                    {{ $immichConfirmationCounts['detected'] }}
                    attendance record(s) as present.
                    Review and confirm them below.
                </p>
            </div>

            <div class="flex flex-wrap gap-2 text-xs font-bold">
                <span class="rounded-full bg-violet-600 px-3 py-1 text-white">
                    Detected: {{ $immichConfirmationCounts['detected'] }}
                </span>

                <span class="rounded-full bg-amber-500 px-3 py-1 text-white">
                    Pending: {{ $immichConfirmationCounts['pending'] }}
                </span>

                <span class="rounded-full bg-emerald-600 px-3 py-1 text-white">
                    Confirmed: {{ $immichConfirmationCounts['confirmed'] }}
                </span>
            </div>
        </div>
    </div>
@endif



                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Attendance Checklist
                                </h3>
                            </div>

                            <div class="flex gap-2">


@if ($attendanceRecords->where('attendance_source', \App\Models\AttendanceRecord::SOURCE_IMMICH)->where('is_present', true)->where('immich_confirmed', false)->isNotEmpty())
    <button
        type="button"
        wire:click="confirmAllImmichAttendance"
        wire:confirm="Confirm all pending Immich attendance records for this session? This records that an administrator reviewed them."
        wire:loading.attr="disabled"
        class="rounded-lg border border-violet-300 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-200"
    >
        Confirm All Pending Immich
    </button>
@endif


                                <button
                                    type="button"
                                    onclick="document.querySelectorAll('.attendance-checkbox').forEach((box) => box.checked = true)"
                                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
                                >
                                    Check All
                                </button>

                                <button
                                    type="button"
                                    onclick="document.querySelectorAll('.attendance-checkbox').forEach((box) => box.checked = false)"
                                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                                >
                                    Clear All
                                </button>
                            </div>
                        </div>

<div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Pre-listed YES
        </p>

        <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">
            {{ $yesMeetingResponses->count() }}
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Pre-listed NO
        </p>

        <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">
            {{ $noMeetingResponses->count() }}
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Present
        </p>

        <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">
            {{ $recordCounts['present'] }}
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Absent
        </p>

        <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">
            {{ $recordCounts['absent'] }}
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Immich Present
        </p>

        <p class="mt-1 text-xl font-bold text-violet-600 dark:text-violet-400">
            {{ $immichPresentCount }}
        </p>

        @if ($immichConfirmationCounts['pending'] > 0)
            <p class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-300">
                {{ $immichConfirmationCounts['pending'] }} pending review
            </p>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Present Without Pre-listing
        </p>

        <p class="mt-1 text-xl font-bold text-amber-600 dark:text-amber-400">
            {{ $presentWithoutPreListingCount }}
        </p>
    </div>
</div>

                        @if ($participantRows->isEmpty())
                            <div class="mt-5 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                No participants active for this date.

                                <div class="mt-4">
                                    <a
                                        href="{{ \App\Filament\Pages\AttendanceSheets::getUrl() . '?sheetId=' . $selectedSheet->id }}"
                                        class="inline-flex rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                                    >
                                        Add Participants
                                    </a>
                                </div>
                            </div>
                        @else
                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.attendance-sheets.records.store', ['session' => $selectedSession]) }}"
                                class="mt-5"
                            >
                                @csrf

<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-950">
<tr>
    <th class="w-20 px-3 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">
        Present
    </th>

    <th class="min-w-44 px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
        Name
    </th>

    <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
        Pre-listed
    </th>

    <th class="min-w-40 px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
        Participant
    </th>

<th class="min-w-40 px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
    Attendance
</th>

<th class="min-w-40 px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
    Review
</th>
</tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
@foreach ($participantRows as $participant)
    @php
        $person =
            $participant->person;

        $personId =
            (int) $participant->person_id;

        $preListedResponse =
            $preListedResponsesByPersonId
                ->get($personId);

        $record =
            $attendanceRecords
                ->get($personId);

        $sessionDate =
            $selectedSession
                ->session_date
                ->format('Y-m-d');

        /*
         * PARTICIPANT STATUS
         *
         * participantRows() may create a temporary,
         * unsaved AttendanceParticipant when a Person
         * has an attendance record but is not formally
         * enrolled as a participant.
         */
        if (! $participant->exists) {
            $participantStatus =
                'Not added';

            $participantStatusDetail =
                'Attendance record only';
        } elseif (
            $participant->starts_on?->format('Y-m-d')
            === $sessionDate
            &&
            $participant->ends_on?->format('Y-m-d')
            === $sessionDate
        ) {
            $participantStatus =
                'This meeting only';

            $participantStatusDetail =
                null;
        } elseif (
            $participant->starts_on?->format('Y-m-d')
            === $sessionDate
            &&
            blank($participant->ends_on)
        ) {
            $participantStatus =
                'From this meeting onward';

            $participantStatusDetail =
                null;
        } elseif (
            blank($participant->starts_on)
            &&
            blank($participant->ends_on)
        ) {
            $participantStatus =
                'All meetings';

            $participantStatusDetail =
                null;
        } elseif (
            blank($participant->ends_on)
        ) {
            $participantStatus =
                'Ongoing';

            $participantStatusDetail =
                $participant->starts_on
                    ? 'Since '
                        . $participant
                            ->starts_on
                            ->format('M d, Y')
                    : null;
        } else {
            $participantStatus =
                'Date range';

            $participantStatusDetail =
                collect([
                    $participant->starts_on
                        ?->format('M d, Y'),

                    $participant->ends_on
                        ?->format('M d, Y'),
                ])
                    ->filter()
                    ->implode(' → ');
        }

        /*
         * ACTUAL ATTENDANCE STATUS
         */
        if (! $record) {
            $attendanceStatus =
                'Not recorded';
        } elseif ($record->is_present) {
            $attendanceStatus =
                'Present';
        } else {
            $attendanceStatus =
                'Absent';
        }

        /*
         * Useful differences between intention
         * and actual attendance.
         */
        $attendanceStatusNote =
            null;

        if (
            $record?->is_present
            &&
            $preListedResponse?->response
            ===
            \App\Models\AttendanceMeetingResponse::RESPONSE_NO
        ) {
            $attendanceStatusNote =
                'Present despite pre-listed NO';
        } elseif (
            $record?->is_present
            &&
            ! $preListedResponse
        ) {
            $attendanceStatusNote =
                'Present without pre-listing';
        } elseif (
            $record
            &&
            ! $record->is_present
            &&
            $preListedResponse?->response
            ===
            \App\Models\AttendanceMeetingResponse::RESPONSE_YES
        ) {
            $attendanceStatusNote =
                'Pre-listed YES but marked absent';
        }
    @endphp

                                                <tr>
                                                    <td class="px-3 py-3 text-center">
                                                        <input
                                                            type="checkbox"
                                                            name="present_person_ids[]"
                                                            value="{{ $personId }}"
                                                            @checked(in_array($personId, $presentPersonIds, true))
                                                            class="attendance-checkbox h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                        >
                                                    </td>

<td class="min-w-44 px-3 py-3">
    <div class="space-y-1">
        <p class="font-semibold text-gray-900 dark:text-white">
            {{ $person?->display_name ?? 'Unknown person' }}
        </p>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $person?->locality ?: 'No locality' }}
        </p>

        <p class="text-xs text-gray-400 dark:text-gray-500">
            {{ $person?->churchProfile?->category ?: 'No category' }}
        </p>
    </div>
</td>

<td class="px-3 py-3">
    @if (
        $preListedResponse?->response
        ===
        \App\Models\AttendanceMeetingResponse::RESPONSE_YES
    )
        <div class="space-y-1">
            <span
                class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100"
            >
                YES
            </span>

            <p
                class="text-xs text-gray-400 dark:text-gray-500"
            >
                Pre-listed
            </p>
            @if (
    filled($preListedResponse->original_source)
    &&
    $preListedResponse->original_source
    !==
    \App\Models\AttendanceMeetingResponse::RESPONDENT_PERSON
)
    <p
        class="text-xs text-gray-400 dark:text-gray-500"
    >
        Originally
        {{ $preListedResponse->originalSourceLabel() }}
    </p>
@endif
        </div>

    @elseif (
        $preListedResponse?->response
        ===
        \App\Models\AttendanceMeetingResponse::RESPONSE_NO
    )
        <div class="space-y-1">
            <span
                class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-800 dark:bg-red-900 dark:text-red-100"
            >
                NO
            </span>

            <p
                class="text-xs text-gray-400 dark:text-gray-500"
            >
                Pre-listed
            </p>
            @if (
    filled($preListedResponse->original_source)
    &&
    $preListedResponse->original_source
    !==
    \App\Models\AttendanceMeetingResponse::RESPONDENT_PERSON
)
    <p
        class="text-xs text-gray-400 dark:text-gray-500"
    >
        Originally
        {{ $preListedResponse->originalSourceLabel() }}
    </p>
@endif
        </div>

    @else
        <span
            class="text-xs text-gray-400 dark:text-gray-500"
        >
            Not pre-listed
        </span>
    @endif
</td>

<td class="px-3 py-3">
    @if ($participant->exists)
        <div class="space-y-1">
            <span
                class="inline-flex rounded-full bg-sky-100 px-2 py-1 text-xs font-bold text-sky-800 dark:bg-sky-900 dark:text-sky-100"
            >
                {{ $participantStatus }}
            </span>

            @if ($participantStatusDetail)
                <p
                    class="text-xs text-gray-400 dark:text-gray-500"
                >
                    {{ $participantStatusDetail }}
                </p>
            @endif
        </div>
    @else
        <div class="space-y-1">
            <span
                class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
            >
                Not added
            </span>

            <p
                class="text-xs text-gray-400 dark:text-gray-500"
            >
                Attendance record only
            </p>
        </div>
    @endif
</td>

<td class="px-3 py-3">
    <div class="space-y-2">
        {{-- Actual attendance --}}
        @if ($attendanceStatus === 'Present')
            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                Present
            </span>

        @elseif ($attendanceStatus === 'Absent')
            <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-800 dark:bg-red-900 dark:text-red-100">
                Absent
            </span>

        @else
            <span class="text-xs text-gray-400 dark:text-gray-500">
                Not recorded
            </span>
        @endif

        {{-- Difference between pre-listing and actual attendance --}}
        @if ($attendanceStatusNote)
            <p class="max-w-48 text-xs font-medium text-amber-600 dark:text-amber-300">
                {{ $attendanceStatusNote }}
            </p>
        @endif

        {{-- Attendance source --}}
        @if ($record?->attendance_source === \App\Models\AttendanceRecord::SOURCE_IMMICH)
            <div>
                <span class="inline-flex rounded-full bg-violet-100 px-2 py-1 text-xs font-bold text-violet-800 dark:bg-violet-900 dark:text-violet-100">
                    Immich
                </span>
            </div>


        @elseif ($record?->attendance_source === \App\Models\AttendanceRecord::SOURCE_MANUAL)
            <div>
                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    Manual
                </span>
            </div>
        @endif
    </div>
</td>


<td class="px-3 py-3">
    @if (
        $record?->attendance_source
        === \App\Models\AttendanceRecord::SOURCE_IMMICH
        &&
        $record->is_present
    )
        @if ($record->immich_confirmed)
            <div class="space-y-1">
                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                    Confirmed
                </span>

                @if ($record->immichConfirmedBy)
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        by {{ $record->immichConfirmedBy->name }}
                    </p>
                @endif

                @if ($record->immich_confirmed_at)
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $record->immich_confirmed_at->format('M d, Y · g:i A') }}
                    </p>
                @endif
            </div>
        @else
            <div class="space-y-2">
                <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                    Pending Review
                </span>

                <button
                    type="button"
                    wire:click="confirmImmichAttendance({{ $personId }})"
                    wire:loading.attr="disabled"
                    wire:target="confirmImmichAttendance({{ $personId }})"
                    class="block rounded-lg border border-violet-300 bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-200"
                >
                    Confirm
                </button>
            </div>
        @endif
    @else
        <span class="text-xs text-gray-400 dark:text-gray-500">
            —
        </span>
    @endif
</td>

                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <button
                                    type="submit"
                                    class="mt-5 inline-flex rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                                >
                                    Save Attendance
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>





<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchUrl = @json(
        route(
            'quezonprovinceactivities.attendance-meeting-responses.person-search'
        )
    );

    document
        .querySelectorAll('[data-person-link-form]')
        .forEach((form) => {
            const searchInput =
                form.querySelector(
                    '[data-person-search]'
                );

            const status =
                form.querySelector(
                    '[data-person-search-status]'
                );

            const results =
                form.querySelector(
                    '[data-person-results]'
                );

            const personId =
                form.querySelector(
                    '[data-person-id]'
                );

            const selected =
                form.querySelector(
                    '[data-selected-person]'
                );

            const selectedName =
                form.querySelector(
                    '[data-selected-person-name]'
                );

            const clearButton =
                form.querySelector(
                    '[data-clear-person]'
                );

            let timer = null;
            let controller = null;


            function clearResults() {
                results.innerHTML = '';
                results.classList.add('hidden');
            }


            function clearSelection() {
                personId.value = '';
                selectedName.textContent = '';
                selected.classList.add('hidden');

                searchInput.value = '';
                searchInput.disabled = false;

                status.textContent =
                    'Search by first name, last name, or nickname.';

                searchInput.focus();
            }


            function choosePerson(person) {
                personId.value = person.id;

                selectedName.textContent =
                    person.name
                    + (
                        person.locality
                            ? ' · ' + person.locality
                            : ''
                    );

                selected.classList.remove('hidden');

                searchInput.value = person.name;
                searchInput.disabled = true;

                clearResults();

                status.textContent =
                    'Person selected.';
            }


            searchInput.addEventListener(
                'input',
                () => {
                    clearTimeout(timer);

                    const query =
                        searchInput.value.trim();

                    personId.value = '';
                    selected.classList.add('hidden');

                    if (query.length < 2) {
                        clearResults();

                        status.textContent =
                            'Type at least 2 characters.';

                        return;
                    }

                    timer = setTimeout(
                        async () => {
                            if (controller) {
                                controller.abort();
                            }

                            controller =
                                new AbortController();

                            status.textContent =
                                'Searching...';

                            try {
                                const response =
                                    await fetch(
                                        searchUrl
                                        + '?q='
                                        + encodeURIComponent(
                                            query
                                        ),
                                        {
                                            headers: {
                                                'Accept':
                                                    'application/json'
                                            },

                                            signal:
                                                controller.signal
                                        }
                                    );

                                if (! response.ok) {
                                    throw new Error(
                                        'Search failed'
                                    );
                                }

                                const data =
                                    await response.json();

                                clearResults();

                                const rows =
                                    Array.isArray(
                                        data.results
                                    )
                                        ? data.results
                                        : [];

                                if (rows.length === 0) {
                                    status.textContent =
                                        'No matching People found.';

                                    return;
                                }

                                rows.forEach(
                                    (person) => {
                                        const button =
                                            document.createElement(
                                                'button'
                                            );

                                        button.type =
                                            'button';

                                        button.className =
                                            'block w-full border-t border-gray-100 px-3 py-3 text-left first:border-t-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800';

                                        const name =
                                            document.createElement(
                                                'div'
                                            );

                                        name.className =
                                            'text-sm font-bold text-gray-900 dark:text-white';

                                        name.textContent =
                                            person.name;

                                        button.appendChild(
                                            name
                                        );

                                        if (
                                            person.locality
                                        ) {
                                            const locality =
                                                document.createElement(
                                                    'div'
                                                );

                                            locality.className =
                                                'mt-1 text-xs text-gray-500 dark:text-gray-400';

                                            locality.textContent =
                                                person.locality;

                                            button.appendChild(
                                                locality
                                            );
                                        }

                                        button.addEventListener(
                                            'click',
                                            () =>
                                                choosePerson(
                                                    person
                                                )
                                        );

                                        results.appendChild(
                                            button
                                        );
                                    }
                                );

                                results.classList.remove(
                                    'hidden'
                                );

                                status.textContent =
                                    rows.length
                                    + ' match(es).';
                            } catch (error) {
                                if (
                                    error.name
                                    === 'AbortError'
                                ) {
                                    return;
                                }

                                clearResults();

                                status.textContent =
                                    'Unable to search right now.';
                            }
                        },
                        250
                    );
                }
            );


            clearButton.addEventListener(
                'click',
                clearSelection
            );


            form.addEventListener(
                'submit',
                (event) => {
                    if (! personId.value) {
                        event.preventDefault();

                        status.textContent =
                            'Select a Person before linking.';

                        searchInput.focus();
                    }
                }
            );
        });
});
</script>



</x-filament-panels::page>
