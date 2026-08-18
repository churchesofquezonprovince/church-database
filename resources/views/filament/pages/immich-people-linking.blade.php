<x-filament-panels::page>

    @php
        $sessions = $this->sessions();
        $selectedSession = $this->selectedSession();
        $people = $this->detectedPeople();
        $unmatchedPeople = $this->unmatchedPeople();
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
    Use Attendance Session to inspect people recognized in photographs
    from one attendance date, or Whole Attendance Sheet to inspect all
    recognized people across the linked Immich album.
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
            All recognized people from the linked Immich album are shown.
            This only manages permanent Immich-to-Church Person mappings and
            does not change attendance records.
        </div>
    @else
        <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-800 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-200">
            <strong>Attendance Session mode.</strong>
            Only people recognized in photographs from the selected session's
            date are shown.
        </div>
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
        No Attendance Session has a linked Immich Album yet.
        Link an album from Attendance → Attendance Sheets first.
    </div>

@endif

        @if ($selectedSession)

            {{-- Session information --}}
            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm dark:border-violet-900 dark:bg-violet-950">

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
                            Immich Album
                        </p>

                        <p class="mt-1 font-bold text-gray-900 dark:text-white">
                            {{ $selectedSession->sheet?->immichAlbum?->immich_album_name ?? 'Not linked' }}
                        </p>
                    </div>

                    <div>
<p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
    {{ $this->linkingScope === 'sheet'
        ? 'People Found in Entire Album'
        : 'People Found on Session Date' }}
</p>

<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
    @if ($this->linkingScope === 'sheet')
        All unique Immich people recognized anywhere in the linked album.
        Linking here applies permanently to future attendance synchronization
        for this sheet.
    @else
        Only people recognized in photographs from
        {{ $selectedSession?->session_date?->format('F d, Y') }}.
    @endif
</p>

                        <p class="mt-1 text-2xl font-bold text-violet-700 dark:text-violet-200">
                            {{ $people->count() }}
                        </p>
                    </div>

                </div>

                <div class="mt-4 rounded-xl border border-violet-200 bg-white p-4 text-sm text-violet-800 dark:border-violet-800 dark:bg-gray-950 dark:text-violet-200">
                    Only photographs from
                    <strong>{{ $selectedSession->session_date->format('F d, Y') }}</strong>
                    in this linked album are being examined.
                </div>

<button
    type="button"
    wire:click="syncImmich"
    wire:loading.attr="disabled"
    wire:target="syncImmich"
    class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-500 disabled:opacity-50"
>
    <span wire:loading.remove wire:target="syncImmich">
        Sync Attendance
    </span>

    <span wire:loading wire:target="syncImmich">
        Synchronizing...
    </span>
</button>

            </div>

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
                These Immich people were recognized in this session but do not yet
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

                            @if (! empty($immichPerson['thumbnailPath']))

                                <img
                                    src="{{ rtrim(config('services.immich.url'), '/') . '/api/people/' . $immichId . '/thumbnail' }}"
                                    alt="{{ $displayName ?: 'Unnamed Immich Person' }}"
                                    class="h-16 w-16 shrink-0 rounded-full object-cover"
                                >

                            @else

                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-amber-100 text-lg font-bold text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                                    {{ $displayName ? strtoupper(mb_substr($displayName, 0, 1)) : '?' }}
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

                            <button
                                type="button"
                                wire:click="linkPerson('{{ $immichId }}')"
                                wire:loading.attr="disabled"
                                class="mt-2 w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-500 disabled:opacity-50"
                            >
                                Link to Church Person
                            </button>

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
                        Only people found in photographs for this Attendance Session's date are shown.
                    </p>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">

                    @forelse ($people as $immichPerson)

                        @php
                            $immichId = $immichPerson['id'];
                            $mapping = $this->mappingFor($immichId);
                            $displayName = trim($immichPerson['name'] ?? '');
                        @endphp

                        <div class="p-5">

                            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                                {{-- Immich person --}}
                                <div class="flex min-w-0 items-center gap-4">

                                    @if (! empty($immichPerson['thumbnailPath']))

                                        <img
                                            src="{{ rtrim(config('services.immich.url'), '/') . '/api/people/' . $immichId . '/thumbnail' }}"
                                            alt="{{ $displayName ?: 'Unnamed Immich Person' }}"
                                            class="h-16 w-16 shrink-0 rounded-full object-cover"
                                        >

                                    @else

                                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-violet-100 text-lg font-bold text-violet-700 dark:bg-violet-900 dark:text-violet-200">
                                            {{ $displayName ? strtoupper(mb_substr($displayName, 0, 1)) : '?' }}
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

                                    @if ($mapping)

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

                                    @else

                                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">

                                            <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300">
                                                Needs Matching
                                            </p>

<select
    wire:model.live="selectedPeople.{{ $immichId }}"
    class="mt-2 block w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-amber-900 dark:bg-gray-950 dark:text-white"
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

<button
    type="button"
    wire:click="linkPerson('{{ $immichId }}')"
    wire:loading.attr="disabled"
    class="mt-3 w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-500 disabled:opacity-50"
>
    Link to Church Person
</button>

                                        </div>

                                    @endif

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="p-10 text-center">

                            <p class="font-bold text-gray-900 dark:text-white">
                                No recognized people found for this session.
                            </p>

                            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                No people were detected in photographs from
                                {{ $selectedSession->session_date->format('M d, Y') }}
                                in the linked Immich album.
                            </p>

                        </div>

                    @endforelse

                </div>
            </div>

        @endif

    </div>
</x-filament-panels::page>