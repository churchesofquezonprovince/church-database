<x-filament-panels::page>
    @php
        $albumRows =
            $this->albumRows();

        $storagePercent =
            $this->storageUsagePercentage();

        $filterOptions = [
            'all' => 'All',
            'linked' => 'Linked',
            'unlinked' => 'Unlinked',
            'shared' => 'Shared',
        ];
    @endphp

    <div class="space-y-6">

        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm dark:border-sky-900 dark:bg-sky-950 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold uppercase tracking-wide text-sky-600 dark:text-sky-300">
                        Posts · Immich
                    </p>

                    <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                        Immich Albums
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                        Live album information from the connected Immich server.
                        Attendance Sheet links remain managed by the church database.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @if ($connectionHealthy)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Connected
                            </span>

                            <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-600 shadow-sm dark:bg-gray-900 dark:text-gray-300">
                                Immich {{ $this->versionLabel() }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700 dark:bg-red-900 dark:text-red-200">
                                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                Disconnected
                            </span>
                        @endif

                        @if ($lastLoadedAt)
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Refreshed {{ $this->formatDateTime($lastLoadedAt) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="openCreateAlbumModal"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <x-heroicon-o-plus
                            class="h-4 w-4"
                        />

                        Create Album
                    </button>

                    <a
                        href="{{ route('immich-albums.public') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-gray-800 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600"
                    >
                        <x-heroicon-o-arrow-top-right-on-square
                            class="h-4 w-4"
                        />

                        Public Dashboard
                    </a>

                    <button
                        type="button"
                        wire:click="refreshImmich"
                        wire:loading.attr="disabled"
                        wire:target="refreshImmich"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-sky-700 disabled:cursor-wait disabled:opacity-60"
                    >
                        <x-heroicon-o-arrow-path
                            class="h-4 w-4"
                            wire:loading.class="animate-spin"
                            wire:target="refreshImmich"
                        />

                        <span wire:loading.remove wire:target="refreshImmich">
                            Refresh
                        </span>

                        <span wire:loading wire:target="refreshImmich">
                            Refreshing…
                        </span>
                    </button>
                </div>
            </div>
        </div>

        @if ($connectionError)
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">
                    Immich connection unavailable
                </p>

                <p class="mt-1 text-sm">
                    {{ $connectionError }}
                </p>
            </div>
        @endif

        @if ($loadWarnings !== [])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">
                    Some Immich information could not be loaded.
                </p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($loadWarnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Immich Storage
                </p>

                <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $this->formatBytes($serverStorage['diskUseRaw'] ?? null) }}
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    of
                    {{ $this->formatBytes($serverStorage['diskSizeRaw'] ?? null) }}
                    used
                </p>

                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                    <div
                        class="h-full rounded-full bg-sky-600"
                        style="width: {{ $storagePercent }}%;"
                    ></div>
                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ number_format($storagePercent, 1) }}% used
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Albums
                </p>

                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format(count($albums)) }}
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Live from Immich
                </p>

                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Photos:
                    {{ $this->formatNumber($serverStatistics['photos'] ?? null) }}
                    · Videos:
                    {{ $this->formatNumber($serverStatistics['videos'] ?? null) }}
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Attendance Links
                </p>

                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($this->linkedAlbumCount()) }}
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Albums linked to Attendance Sheets
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Shared Links
                </p>

                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($this->sharedLinkCount()) }}
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Albums reporting an Immich shared link
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 flex-1 lg:max-w-xl">
                    <div
                        class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-white px-3 shadow-sm focus-within:border-sky-500 focus-within:ring-1 focus-within:ring-sky-500 dark:border-gray-700 dark:bg-gray-950"
                        style="min-height: 2.75rem;"
                    >
                        <x-heroicon-o-magnifying-glass
                            class="h-5 w-5 shrink-0 text-gray-400"
                        />

                        <input
                            type="text"
                            wire:model.live.debounce.300ms="albumSearch"
                            placeholder="Search album name, description, sheet, or locality…"
                            class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 shadow-none outline-none ring-0 focus:border-0 focus:outline-none focus:ring-0 dark:text-white"
                        >
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($filterOptions as $filterValue => $filterLabel)
                        <button
                            type="button"
                            wire:click="setAlbumFilter('{{ $filterValue }}')"
                            @class([
                                'rounded-full px-4 py-2 text-sm font-bold transition',
                                'bg-sky-600 text-white shadow-sm' =>
                                    $albumFilter === $filterValue,
                                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' =>
                                    $albumFilter !== $filterValue,
                            ])
                        >
                            {{ $filterLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Showing {{ count($albumRows) }}
                of {{ count($albums) }} album(s).
            </div>
        </div>

        @if (count($albumRows) > 0)
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($albumRows as $album)
                    <article
                        x-data="{
                            albumUrl: @js($album['clickUrl'] ?? null),
                            flipped: false,
                            touchPointer: false
                        }"
                        x-on:pointerenter="
                            if ($event.pointerType === 'mouse') {
                                flipped = true
                            }
                        "
                        x-on:pointerleave="
                            if ($event.pointerType === 'mouse') {
                                flipped = false
                            }
                        "
                        x-on:click="
                            if (albumUrl) {
                                window.open(
                                    albumUrl,
                                    '_blank',
                                    'noopener,noreferrer'
                                )
                            }
                        "
                        role="link"
                        tabindex="0"
                        class="cursor-pointer overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:border-sky-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-sky-700"
                        style="display: flex; flex-direction: column; height: 100%;"
                    >
                        <div class="immich-album-main flex min-w-0 gap-4 p-5">
                            <div
                                data-immich-album-qr
                                data-qr-url="{{ $album['sharedUrl'] ?? '' }}"
                                x-on:pointerdown.stop="
                                    touchPointer =
                                        $event.pointerType === 'touch'
                                        || $event.pointerType === 'pen'
                                "
                                x-on:pointerup.stop.prevent="
                                    if (touchPointer) {
                                        flipped = ! flipped

                                        /*
                                         * Keep this true long enough to
                                         * swallow the synthetic click that
                                         * follows a mobile tap.
                                         */
                                        window.setTimeout(
                                            () => {
                                                touchPointer = false
                                            },
                                            400
                                        )
                                    }
                                "
                                x-on:pointercancel.stop="
                                    touchPointer = false
                                "
                                x-on:click.stop.prevent="
                                    if (! touchPointer && albumUrl) {
                                        window.open(
                                            albumUrl,
                                            '_blank',
                                            'noopener,noreferrer'
                                        )
                                    }
                                "
                                class="relative shrink-0"
                                style="
                                    width: 200px;
                                    height: 200px;
                                    min-width: 200px;
                                    perspective: 1000px;
                                "
                            >
                                <div
                                    class="relative h-full w-full"
                                    x-bind:style="
                                        'transform: rotateY('
                                        + (flipped ? '180deg' : '0deg')
                                        + '); transition: transform 0.5s ease; transform-style: preserve-3d;'
                                    "
                                >
                                    {{-- FRONT: ALBUM PHOTO --}}
                                    <div
                                        class="absolute inset-0 overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-800"
                                        style="
                                            backface-visibility: hidden;
                                            -webkit-backface-visibility: hidden;
                                        "
                                    >
                                        @if (filled($album['localThumbnailUrl'] ?? null))
                                            <img
                                                src="{{ $album['localThumbnailUrl'] }}"
                                                alt="{{ $album['albumName'] }}"
                                                loading="lazy"
                                                class="h-full w-full object-cover"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center">
                                                <x-heroicon-o-photo class="h-12 w-12 text-gray-400" />
                                            </div>
                                        @endif
                                    </div>

                                    {{-- BACK: QR CODE --}}
                                    <div
                                        class="absolute inset-0 flex items-center justify-center overflow-hidden rounded-2xl bg-white p-2"
                                        style="
                                            transform: rotateY(180deg);
                                            backface-visibility: hidden;
                                            -webkit-backface-visibility: hidden;
                                        "
                                    >
                                        @if (filled($album['sharedUrl'] ?? null))
                                            <div
                                                data-qr-target
                                                class="flex h-full w-full items-center justify-center"
                                            ></div>
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-gray-950 p-4 text-center">
                                                <span class="text-sm font-bold text-white">
                                                    Create a Shared Link to show QR
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-2">
                                    <div class="min-w-0">
                                        <h3 class="break-words text-lg font-bold text-gray-900 dark:text-white">
                                            {{ $album['albumName'] }}
                                        </h3>

                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ number_format($album['assetCount']) }}
                                            asset(s)
                                            · Updated
                                            {{ $this->formatDate($album['lastModifiedAssetTimestamp'] ?? $album['updatedAt']) }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @if ($album['linked'])
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">
                                                Linked
                                            </span>
                                        @endif

                                        @if (filled($album['sharedUrl'] ?? null))
                                            <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700 dark:bg-sky-900 dark:text-sky-200">
                                                Shared Link
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                wire:click.stop="openCreateLinkModal('{{ $album['id'] }}')"
                                                x-on:click.stop
                                                class="inline-flex items-center justify-center whitespace-nowrap rounded-full bg-sky-600 px-3 py-1 text-xs font-bold text-white hover:bg-sky-700"
                                            >
                                                Create Link
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if (filled($album['description']))
                                    <p
                                        class="immich-description-desktop mt-3 break-words text-sm leading-relaxed text-gray-600 dark:text-gray-300"
                                        style="white-space: pre-line;"
                                    >
                                        {{ $album['description'] }}
                                    </p>
                                @endif
                            </div>

                            @if (filled($album['description']))
                                <p
                                    class="immich-description-mobile w-full break-words text-sm leading-relaxed text-gray-600 dark:text-gray-300"
                                    style="white-space: pre-line;"
                                >
                                    {{ $album['description'] }}
                                </p>
                            @endif
                        </div>

                        <div
                            class="grid border-t border-gray-200 bg-gray-50 text-sm dark:border-gray-700 dark:bg-gray-950 {{ $album['linked'] ? 'sm:grid-cols-2' : '' }}"
                            style="margin-top: auto;"
                        >
                            <div class="p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">
                                    Album Dates
                                </p>

                                <p class="mt-1 font-semibold text-gray-700 dark:text-gray-200">
                                    {{ $this->formatDate($album['startDate']) }}
                                    @if ($album['endDate'])
                                        to
                                        {{ $this->formatDate($album['endDate']) }}
                                    @endif
                                </p>

                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Created {{ $this->formatDate($album['createdAt']) }}
                                </p>
                            </div>

                            @if ($album['linked'])
                                <div class="border-t border-gray-200 p-4 dark:border-gray-700 sm:border-l sm:border-t-0">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">
                                        Attendance Sheet
                                    </p>

                                    <p class="mt-1 font-semibold text-gray-700 dark:text-gray-200">
                                        {{ data_get($album, 'link.sheet_title') ?: 'Linked sheet' }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ data_get($album, 'link.sheet_locality') ?: 'No locality' }}

                                        ·

                                        {{ data_get($album, 'link.enabled') ? 'Sync enabled' : 'Sync disabled' }}
                                    </p>
                                </div>
                            @endif
                        </div>


                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <x-heroicon-o-photo class="mx-auto h-10 w-10 text-gray-400" />

                <h3 class="mt-3 font-bold text-gray-900 dark:text-white">
                    No albums match
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Change the search text or album filter.
                </p>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="font-bold text-gray-900 dark:text-white">
                Immich Albums integration status
            </h3>

            <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                This page is currently read-only. Album data and server statistics
                come directly from Immich. The church database stores only its
                Attendance Sheet-to-album relationships.
            </p>

            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">
                    Live albums
                </span>

                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">
                    Live storage
                </span>

                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">
                    Attendance links
                </span>

                <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                    Album thumbnails next
                </span>

                <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                    QR / shared-link actions next
                </span>

                <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                    Create Album next
                </span>
            </div>
        </div>
    </div>

    <style>
        /*
         * Immich album responsive layout.
         *
         * Mobile:
         *   thumbnail + title on first row
         *   description spans full width underneath
         *
         * Desktop:
         *   thumbnail stays beside title + description
         */
        .immich-album-main {
            flex-wrap: wrap;
        }

        .immich-description-desktop {
            display: none;
        }

        .immich-description-mobile {
            display: block;
            flex-basis: 100%;
        }

        @media (min-width: 640px) {
            .immich-album-main {
                flex-wrap: nowrap;
            }

            .immich-description-desktop {
                display: block;
            }

            .immich-description-mobile {
                display: none;
            }
        }
    </style>


    {{-- =====================================================
         CREATE ALBUM
         ===================================================== --}}
    @if ($showCreateAlbumModal)
        <div
            wire:click.self="closeCreateAlbumModal"
            style="
                position: fixed;
                inset: 0;
                z-index: 100;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background: rgba(0, 0, 0, .72);
            "
        >
            <div
                class="rounded-2xl bg-gray-900 text-white shadow-2xl"
                style="
                    width: min(100%, 560px);
                    overflow: hidden;
                    border: 1px solid rgba(255, 255, 255, 0.10);
                "
            >
                <div
                    class="flex items-center justify-between px-6 py-5"
                    style="
                        border-bottom: 1px solid rgba(255, 255, 255, 0.10);
                    "
                >
                    <div>
                        <h2 class="text-lg font-bold">
                            Create Album
                        </h2>

                        <p class="mt-1 text-sm text-gray-400">
                            Create a new album in Immich.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeCreateAlbumModal"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-800 hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-5 p-5">
                    <div>
                        <label class="mb-2 block text-sm font-semibold">
                            Album name
                        </label>

                        <input
                            type="text"
                            wire:model.defer="newAlbumName"
                            autocomplete="off"
                            placeholder="Album name"
                            class="w-full text-sm text-white"
                            style="
                                display: block;
                                width: 100%;
                                height: 46px;
                                padding: 0 14px;
                                border-radius: 12px;
                                border: 1px solid #4b5563;
                                background: #1f2937;
                                color: #ffffff;
                                outline: none;
                            "
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">
                            Description
                        </label>

                        <textarea
                            wire:model.defer="newAlbumDescription"
                            rows="5"
                            placeholder="Optional description"
                            class="w-full text-sm text-white"
                            style="
                                display: block;
                                width: 100%;
                                min-height: 150px;
                                padding: 12px 14px;
                                border-radius: 12px;
                                border: 1px solid #4b5563;
                                background: #1f2937;
                                color: #ffffff;
                                outline: none;
                                resize: vertical;
                                line-height: 1.5;
                            "
                        ></textarea>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 px-6 py-4"
                    style="
                        border-top: 1px solid rgba(255, 255, 255, 0.10);
                    "
                >
                    <button
                        type="button"
                        wire:click="closeCreateAlbumModal"
                        class="rounded-xl bg-gray-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-gray-600"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        wire:click="createAlbum"
                        wire:loading.attr="disabled"
                        wire:target="createAlbum"
                        class="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-sky-700 disabled:opacity-60"
                    >
                        <span
                            wire:loading.remove
                            wire:target="createAlbum"
                        >
                            Create Album
                        </span>

                        <span
                            wire:loading
                            wire:target="createAlbum"
                        >
                            Creating…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif


    {{-- =====================================================
         CREATE LINK
         ===================================================== --}}
    @if ($showCreateLinkModal)
        <div
            wire:click.self="closeCreateLinkModal"
            style="
                position: fixed;
                inset: 0;
                z-index: 100;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background: rgba(0, 0, 0, .72);
                overflow-y: auto;
            "
        >
            <div
                class="rounded-2xl bg-gray-900 text-white shadow-2xl"
                style="
                    width: min(100%, 580px);
                    max-height: calc(100vh - 2rem);
                    overflow-y: auto;
                    border: 1px solid rgba(255, 255, 255, 0.10);
                "
            >
                <div
                    class="flex items-center justify-between px-6 py-5"
                    style="
                        border-bottom: 1px solid rgba(255, 255, 255, 0.10);
                    "
                >
                    <div>
                        <h2 class="text-lg font-bold">
                            Create link to share
                        </h2>

                        <p class="mt-1 text-sm text-gray-400">
                            {{ $createLinkAlbumName }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeCreateLinkModal"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-800 hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-5 p-5">
                    <p class="text-sm text-gray-300">
                        Let anyone with the link see photos and people
                        in this album.
                    </p>

                    <div
                        x-data="{
                            slugPreview: @js($shareSlug),
                            shareBaseUrl: @js($this->immichPublicUrl() . '/s/')
                        }"
                    >
                        <label class="mb-1 block text-sm font-semibold">
                            Custom URL
                        </label>

                        <p class="mb-2 text-xs text-gray-400">
                            Optional custom name for the shared URL.
                        </p>

                        <input
                            type="text"
                            wire:model.defer="shareSlug"
                            x-model="slugPreview"
                            autocomplete="off"
                            placeholder="Optional custom URL"
                            class="w-full text-sm text-white"
                            style="
                                display: block;
                                width: 100%;
                                height: 46px;
                                padding: 0 14px;
                                border-radius: 12px;
                                border: 1px solid #4b5563;
                                background: #1f2937;
                                color: #ffffff;
                                outline: none;
                            "
                        >

                        <p
                            class="mt-2 break-all text-xs text-gray-400"
                            style="
                                min-height: 18px;
                                font-family:
                                    ui-monospace,
                                    SFMono-Regular,
                                    Menlo,
                                    Monaco,
                                    Consolas,
                                    monospace;
                            "
                            x-text="shareBaseUrl + slugPreview"
                        ></p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold">
                            Password
                        </label>

                        <p class="mb-2 text-xs text-gray-400">
                            Require a password to access this shared link.
                        </p>

                        <input
                            type="password"
                            wire:model.defer="sharePassword"
                            autocomplete="new-password"
                            placeholder="Optional password"
                            class="w-full text-sm text-white"
                            style="
                                display: block;
                                width: 100%;
                                height: 46px;
                                padding: 0 14px;
                                border-radius: 12px;
                                border: 1px solid #4b5563;
                                background: #1f2937;
                                color: #ffffff;
                                outline: none;
                            "
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">
                            Description
                        </label>

                        <textarea
                            wire:model.defer="shareDescription"
                            rows="4"
                            placeholder="Optional shared-link description"
                            class="w-full text-sm text-white"
                            style="
                                display: block;
                                width: 100%;
                                min-height: 120px;
                                padding: 12px 14px;
                                border-radius: 12px;
                                border: 1px solid #4b5563;
                                background: #1f2937;
                                color: #ffffff;
                                outline: none;
                                resize: vertical;
                                line-height: 1.5;
                            "
                        ></textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">
                            Expire after
                        </label>

                        <div class="flex flex-wrap gap-2">
                            @foreach ([
                                'never' => 'Never',
                                '1_day' => 'in 1 day',
                                '7_days' => 'in 7 days',
                                '30_days' => 'in 30 days',
                                '3_months' => 'in 3 months',
                                '1_year' => 'in 1 year',
                            ] as $expiryValue => $expiryLabel)
                                <button
                                    type="button"
                                    wire:click="$set('shareExpiry', '{{ $expiryValue }}')"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-bold"
                                    style="
                                        @if ($shareExpiry === $expiryValue)
                                            background: #2563eb;
                                            border-color: #60a5fa;
                                            color: white;
                                        @else
                                            background: #111827;
                                            border-color: #4b5563;
                                            color: #d1d5db;
                                        @endif
                                    "
                                >
                                    {{ $expiryLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div
                        class="space-y-4 pt-5"
                        style="
                            border-top: 1px solid rgba(255, 255, 255, 0.10);
                        "
                    >
                        <label class="flex cursor-pointer items-center justify-between gap-4">
                            <span>
                                <span class="block text-sm font-semibold">
                                    Show metadata
                                </span>

                                <span class="block text-xs text-gray-400">
                                    Allow shared viewers to see photo metadata.
                                </span>
                            </span>

                            <input
                                type="checkbox"
                                wire:model.defer="shareShowMetadata"
                                class="h-5 w-5 rounded"
                                style="
                                    width: 20px;
                                    height: 20px;
                                    flex: 0 0 20px;
                                    accent-color: #0ea5e9;
                                "
                            >
                        </label>

                        <label class="flex cursor-pointer items-center justify-between gap-4">
                            <span>
                                <span class="block text-sm font-semibold">
                                    Allow public user to download
                                </span>
                            </span>

                            <input
                                type="checkbox"
                                wire:model.defer="shareAllowDownload"
                                class="h-5 w-5 rounded"
                                style="
                                    width: 20px;
                                    height: 20px;
                                    flex: 0 0 20px;
                                    accent-color: #0ea5e9;
                                "
                            >
                        </label>

                        <label class="flex cursor-pointer items-center justify-between gap-4">
                            <span>
                                <span class="block text-sm font-semibold">
                                    Allow public user to upload
                                </span>
                            </span>

                            <input
                                type="checkbox"
                                wire:model.defer="shareAllowUpload"
                                class="h-5 w-5 rounded"
                                style="
                                    width: 20px;
                                    height: 20px;
                                    flex: 0 0 20px;
                                    accent-color: #0ea5e9;
                                "
                            >
                        </label>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 px-6 py-4"
                    style="
                        border-top: 1px solid rgba(255, 255, 255, 0.10);
                    "
                >
                    <button
                        type="button"
                        wire:click="closeCreateLinkModal"
                        class="rounded-xl bg-gray-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-gray-600"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        wire:click="createSharedLink"
                        wire:loading.attr="disabled"
                        wire:target="createSharedLink"
                        class="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-sky-700 disabled:opacity-60"
                    >
                        <span
                            wire:loading.remove
                            wire:target="createSharedLink"
                        >
                            Create link
                        </span>

                        <span
                            wire:loading
                            wire:target="createSharedLink"
                        >
                            Creating…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif


    <script src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}"></script>

    <script>
        (() => {
            const initializeAlbumQrCodes = () => {
                document
                    .querySelectorAll('[data-immich-album-qr]')
                    .forEach((wrapper) => {
                        if (wrapper.dataset.qrReady === '1') {
                            return;
                        }

                        const url =
                            wrapper.dataset.qrUrl;

                        const target =
                            wrapper.querySelector(
                                '[data-qr-target]'
                            );

                        if (
                            ! url
                            || ! target
                            || typeof QRCode === 'undefined'
                        ) {
                            return;
                        }

                        new QRCode(target, {
                            text: url,
                            width: 180,
                            height: 180,
                            correctLevel:
                                QRCode.CorrectLevel.M,
                        });

                        wrapper.dataset.qrReady = '1';
                    });
            };

            document.addEventListener(
                'DOMContentLoaded',
                initializeAlbumQrCodes
            );

            document.addEventListener(
                'livewire:navigated',
                initializeAlbumQrCodes
            );

            document.addEventListener(
                'livewire:updated',
                initializeAlbumQrCodes
            );

            initializeAlbumQrCodes();
        })();
    </script>

</x-filament-panels::page>
