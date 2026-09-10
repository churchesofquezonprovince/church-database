<x-filament-panels::page>

    @php
        $sessions = $this->sessions();
        $selectedSession = $this->selectedSession();
        $people = $this->detectedPeople();
        $unmatchedPeople = $this->unmatchedPeople();

        $matchedPeople = $people
            ->filter(
                fn (array $person): bool =>
                    $this->mappingFor($person['id']) !== null
            )
            ->values();

        $churchPeople = $this->churchPeople();
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <div class="rounded-2xl border border-violet-200 bg-violet-50 p-6 shadow-sm dark:border-violet-900 dark:bg-violet-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                Attendance Integration
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Immich People Linking
            </h2>

<p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
    Use Attendance Session to inspect people recognized
    from exact Immich photos or the Sheet album for one
    attendance date. Whole Attendance Sheet mode can inspect
    recognized people across the Sheet's Immich sources.
</p>
        </div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

    <p class="text-sm font-bold text-gray-900 dark:text-white">
        Linking Scope
    </p>

    <div class="mt-3 flex flex-wrap gap-2">

        <button
            type="button"
            wire:click="setLinkingScope('session')"
            class="rounded-xl px-4 py-2 text-sm font-bold transition
                {{ $this->linkingScope === 'session'
                    ? 'bg-violet-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
        >
            Attendance Session
        </button>

        <button
            type="button"
            wire:click="setLinkingScope('sheet')"
            class="rounded-xl px-4 py-2 text-sm font-bold transition
                {{ $this->linkingScope === 'sheet'
                    ? 'bg-violet-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
        >
            Whole Attendance Sheet
        </button>

    </div>

    @if ($this->linkingScope === 'sheet')
        <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
            <strong>Whole Attendance Sheet mode.</strong>
            Recognized people from the Sheet album and exact
            Session photos are shown together. This only manages
            permanent Immich-to-Church Person mappings and does
            not change attendance records.
        </div>
    @else
        <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-800 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-200">
            <strong>Attendance Session mode.</strong>
            Exact Session photos are used first. If no exact
            photos are linked, the Sheet album is filtered to
            the selected Session date.
        </div>
    @endif

</div>


{{-- Information --}}
<div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm dark:border-violet-900 dark:bg-violet-950">

    @if ($this->linkingScope === 'sheet')

        @php
            $sheet = $selectedSession?->sheet;
            $sheetSessions = $sessions
                ->filter(
                    fn ($session) =>
                        $session->attendance_sheet_id
                        === $sheet?->id
                )
                ->sortBy('session_date')
                ->values();

            $sheetExactPhotoCount =
                $sheetSessions
                    ->sum(
                        fn ($session) =>
                            $session
                                ->immichAssets
                                ->count()
                    );
        @endphp

        <div class="grid gap-4 md:grid-cols-3">

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                    Attendance Sheet
                </p>

                <p class="mt-1 font-bold text-gray-900 dark:text-white">
                    {{ $sheet?->title ?? 'Attendance' }}
                </p>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $sheetSessions->count() }} attendance
                    {{ \Illuminate\Support\Str::plural('session', $sheetSessions->count()) }}
                </p>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                    Immich Sources
                </p>

                <p class="mt-1 font-bold text-gray-900 dark:text-white">
                    @if ($sheet?->immichAlbum)
                        {{
                            $sheet
                                ->immichAlbum
                                ->immich_album_name
                        }}
                    @else
                        No Sheet album
                    @endif
                </p>

                @if ($sheetExactPhotoCount > 0)
                    <p class="mt-1 text-sm text-violet-700 dark:text-violet-300">
                        {{ $sheetExactPhotoCount }}
                        exact
                        {{ \Illuminate\Support\Str::plural(
                            'photo',
                            $sheetExactPhotoCount
                        ) }}
                    </p>
                @endif
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    People Found in Entire Album
                </p>

                <p class="mt-1 text-2xl font-bold text-violet-700 dark:text-violet-200">
                    {{ $people->count() }}
                </p>
            </div>

        </div>

        <div class="mt-4 rounded-xl border border-violet-200 bg-white p-4 dark:border-violet-800 dark:bg-gray-950">

            <p class="text-sm font-bold text-gray-900 dark:text-white">
                Attendance Sessions Covered
            </p>

            <div class="mt-3 flex flex-wrap gap-2">

                @forelse ($sheetSessions as $session)

                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-800 dark:bg-violet-900 dark:text-violet-200">
                        {{ $session->session_date->format('M d, Y') }}
                    </span>

                @empty

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        No attendance sessions found.
                    </span>

                @endforelse

            </div>

        </div>

    @else

        <div class="grid gap-4 md:grid-cols-3">

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                    Attendance Session
                </p>

                <p class="mt-1 font-bold text-gray-900 dark:text-white">
                    {{ $selectedSession->sheet?->title ?? 'Attendance' }}
                </p>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $selectedSession->session_date->format('M d, Y') }}
                </p>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                    Immich Source
                </p>

                <p class="mt-1 font-bold text-gray-900 dark:text-white">
                    @if ($selectedSession->immichAssets->isNotEmpty())
                        Exact
                        {{ $selectedSession->immichAssets->count() }}
                        {{
                            \Illuminate\Support\Str::plural(
                                'Photo',
                                $selectedSession
                                    ->immichAssets
                                    ->count()
                            )
                        }}
                    @elseif ($selectedSession->sheet?->immichAlbum)
                        {{
                            $selectedSession
                                ->sheet
                                ->immichAlbum
                                ->immich_album_name
                        }}
                    @else
                        Not linked
                    @endif
                </p>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    People Found on Session Date
                </p>

                <p class="mt-1 text-2xl font-bold text-violet-700 dark:text-violet-200">
                    {{ $people->count() }}
                </p>
            </div>

        </div>

        <div class="mt-4 rounded-xl border border-violet-200 bg-white p-4 text-sm text-violet-800 dark:border-violet-800 dark:bg-gray-950 dark:text-violet-200">
            @if ($selectedSession->immichAssets->isNotEmpty())
                Only the exact Immich
                {{
                    \Illuminate\Support\Str::plural(
                        'photo',
                        $selectedSession
                            ->immichAssets
                            ->count()
                    )
                }}
                linked to this Session
                {{ $selectedSession->immichAssets->count() === 1 ? 'is' : 'are' }}
                being examined.
            @else
                Only photographs from
                <strong>
                    {{
                        $selectedSession
                            ->session_date
                            ->format('F d, Y')
                    }}
                </strong>
                in the linked Sheet album are being examined.
            @endif
        </div>

        <button
            type="button"
            wire:click="syncImmich"
            wire:loading.attr="disabled"
            wire:target="syncImmich"
            class="rounded-xl border-2 border-violet-700 bg-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:border-violet-800 hover:bg-violet-500 disabled:opacity-50"
        >
            <span wire:loading.remove wire:target="syncImmich">
                Sync Attendance
            </span>

            <span wire:loading wire:target="syncImmich">
                Synchronizing...
            </span>
        </button>

    @endif

</div>

        {{-- Session selector --}}
@if ($sessions->isNotEmpty())

    @if ($this->linkingScope === 'session')
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

            <label
                for="session"
                class="block text-sm font-bold text-gray-900 dark:text-white"
            >
                Attendance Session
            </label>

            <select
                id="session"
                wire:model.live="selectedSessionId"
                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >
                @foreach ($sessions as $session)
                    <option value="{{ $session->id }}">
                        {{ $session->sheet?->title }}
                        —
                        {{ $session->session_date->format('M d, Y') }}
                    </option>
                @endforeach
            </select>

        </div>
    @endif

  
@else

    <div class="mt-3 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
        No Attendance Session has an Immich source yet.
        Add an exact Immich photo or link a Sheet album from
        Attendance → Attendance Sheets first.
    </div>

@endif

        @if ($selectedSession)

        {{-- Stats --}}

<div class="mt-4 grid gap-3 sm:grid-cols-3">

    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            People Detected
        </p>

        <p class="mt-1 text-2xl font-bold text-violet-700 dark:text-violet-300">
            {{ $people->count() }}
        </p>
    </div>

    <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950">
    <p class="text-xs font-bold uppercase tracking-wide text-green-600 dark:text-green-300">
        Linked
    </p>

    <p class="mt-1 text-2xl font-bold text-green-700 dark:text-green-200">
        {{ $people->count() - $unmatchedPeople->count() }}
    </p>
</div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
        <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300">
            Needs Matching
        </p>

        <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-200">
            {{ $unmatchedPeople->count() }}
        </p>
    </div>

</div>

            {{-- Search --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <label
                    for="people_search"
                    class="block text-sm font-bold text-gray-900 dark:text-white"
                >
                    Search recognized people
                </label>

                <input
                    id="people_search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search recognized name..."
                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                >

            </div>


@if ($unmatchedPeople->isNotEmpty())

    <div class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm dark:border-amber-900 dark:bg-amber-950">

        <div class="border-b border-amber-200 px-5 py-4 dark:border-amber-900">

            <h3 class="text-lg font-bold text-amber-900 dark:text-amber-100">
                People That Need Matching
            </h3>

            <p class="mt-1 text-sm text-amber-700 dark:text-amber-200">
                These recognized Immich people do not yet
                have a Church Person mapping.
            </p>

        </div>

        <div class="divide-y divide-amber-200 dark:divide-amber-900">

            @foreach ($unmatchedPeople as $immichPerson)

                @php
                    $immichId = $immichPerson['id'];
                    $displayName = trim($immichPerson['name'] ?? '');
                @endphp

                <div class="p-5">

                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                        <div class="flex min-w-0 items-center gap-4">

@if (! empty($immichPerson['localThumbnailUrl']))
    <img
        src="{{ $immichPerson['localThumbnailUrl'] }}"
        alt="{{ $displayName ?: 'Unnamed Immich Person' }}"
        class="h-16 w-16 shrink-0 rounded-full object-cover"
    >
@else
    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gray-100 text-lg font-bold text-gray-500 dark:bg-gray-800 dark:text-gray-300">
        {{ $displayName
            ? strtoupper(mb_substr($displayName, 0, 1))
            : '?' }}
    </div>
@endif

                            <div class="min-w-0">

                                <p class="break-words font-bold text-gray-900 dark:text-white">
                                    {{ $displayName ?: 'Unnamed Immich Person' }}
                                </p>

                                <p class="mt-1 break-all text-xs text-gray-500 dark:text-gray-400">
                                    {{ $immichId }}
                                </p>

                            </div>

                        </div>

                        <div class="w-full lg:max-w-xl">

                            <select
                                wire:model.live="selectedPeople.{{ $immichId }}"
                                class="block w-full rounded-xl border border-amber-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-amber-800 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="">
                                    Select Church Person...
                                </option>

                                @foreach ($churchPeople as $churchPerson)
                                    <option value="{{ $churchPerson->id }}">
                                        {{ $churchPerson->display_name }}
                                        @if ($churchPerson->locality)
                                            — {{ $churchPerson->locality }}
                                        @endif
                                    </option>
                                @endforeach

                            </select>

                            @php
                                $selectedChurchPersonId =
                                    (int) (
                                        $this
                                            ->selectedPeople[
                                                $immichId
                                            ]
                                        ?? 0
                                    );

                                $existingChurchMapping =
                                    $selectedChurchPersonId > 0
                                        ? \App\Models\ImmichPersonMapping
                                            ::query()
                                            ->where(
                                                'person_id',
                                                $selectedChurchPersonId
                                            )
                                            ->first()
                                        : null;

                                $mappingNeedsReplacement =
                                    $existingChurchMapping
                                    && $existingChurchMapping
                                        ->immich_person_id
                                        !== $immichId;
                            @endphp

                            @if ($mappingNeedsReplacement)
                                <div
                                    class="mt-2 rounded-xl
                                           border border-amber-300
                                           bg-amber-50 p-3
                                           text-xs text-amber-800
                                           dark:border-amber-800
                                           dark:bg-amber-950
                                           dark:text-amber-200"
                                >
                                    This Church Person already
                                    has another Immich identity.
                                    This may be expected after an
                                    Immich face merge.
                                </div>

                                <button
                                    type="button"
                                    wire:click="replacePersonMapping('{{ $immichId }}')"
                                    wire:confirm="Replace this Church Person's existing Immich mapping with the selected Immich identity? Historical detections from the previous identity will be preserved."
                                    wire:loading.attr="disabled"
                                    class="mt-2 w-full
                                           rounded-xl
                                           bg-amber-600
                                           px-4 py-2.5
                                           text-sm font-bold
                                           text-white
                                           hover:bg-amber-500
                                           disabled:opacity-50"
                                >
                                    Replace Existing Immich Mapping
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="linkPerson('{{ $immichId }}')"
                                    wire:loading.attr="disabled"
                                    class="mt-2 w-full
                                           rounded-xl
                                           bg-violet-600
                                           px-4 py-2.5
                                           text-sm font-bold
                                           text-white
                                           hover:bg-violet-500
                                           disabled:opacity-50"
                                >
                                    Link to Church Person
                                </button>
                            @endif

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </div>

@endif

            {{-- People --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        People Recognized in This Session
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Only people already linked to a Church Person are shown here.
                    </p>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">

                    @forelse ($matchedPeople as $immichPerson)

                        @php
                            $immichId = $immichPerson['id'];
                            $mapping = $this->mappingFor($immichId);
                            $displayName = trim($immichPerson['name'] ?? '');
                        @endphp

                        <div class="p-5">

                            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                                {{-- Immich person --}}
                                <div class="flex min-w-0 items-center gap-4">

                                    @if (! empty($immichPerson['localThumbnailUrl']))
                                        <img
                                            src="{{ $immichPerson['localThumbnailUrl'] }}"
                                            alt="{{ $displayName ?: 'Unnamed Immich Person' }}"
                                            class="h-16 w-16 shrink-0 rounded-full object-cover"
                                        >
                                    @else
                                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gray-100 text-lg font-bold text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $displayName
                                                ? strtoupper(mb_substr($displayName, 0, 1))
                                                : '?' }}
                                        </div>
                                    @endif

                                    <div class="min-w-0">

                                        <p class="break-words text-base font-bold text-gray-900 dark:text-white">
                                            {{ $displayName ?: 'Unnamed Immich Person' }}
                                        </p>

                                        <p class="mt-1 break-all text-xs text-gray-500 dark:text-gray-400">
                                            {{ $immichId }}
                                        </p>

                                    </div>

                                </div>

                                {{-- Church mapping --}}
                                <div class="w-full lg:max-w-xl">

                                    <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950">

                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-wide text-green-600 dark:text-green-300">
                                                    Linked Church Person
                                                </p>

                                                <p class="mt-1 break-words font-bold text-green-900 dark:text-green-100">
                                                    {{ $mapping->person?->display_name ?? 'Unknown' }}
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap gap-2">

                                                <span class="w-fit rounded-full bg-green-600 px-3 py-1 text-xs font-bold text-white">
                                                    Linked
                                                </span>

                                                <span class="w-fit rounded-full bg-violet-600 px-3 py-1 text-xs font-bold text-white">
                                                    Attendance Enabled
                                                </span>

                                            </div>

                                        </div>

                                        <button
                                            type="button"
                                            wire:click="unlinkPerson('{{ $immichId }}')"
                                            wire:confirm="Unlink this Immich person from the Church Person?"
                                            class="mt-3 w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50 dark:border-red-900 dark:bg-gray-900 dark:text-red-200"
                                        >
                                            Unlink
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="p-10 text-center">

                            <p class="font-bold text-gray-900 dark:text-white">
                                No linked people found for this session.
                            </p>

                            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                No recognized people with an existing Church Person
                                mapping were found for
                                {{ $selectedSession->session_date->format('M d, Y') }}.
                            </p>

                        </div>

                    @endforelse

                </div>

            </div>

        @endif

    </div>
</x-filament-panels::page>