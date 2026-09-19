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

                <button
                    type="button"
                    wire:click="refreshImmich"
                    wire:loading.attr="disabled"
                    wire:target="refreshImmich"
                    class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-sky-700 disabled:cursor-wait disabled:opacity-60"
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
                            flipped: false
                        }"
                        x-on:mouseenter="flipped = true"
                        x-on:mouseleave="flipped = false"
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
                    >
                        <div class="flex min-w-0 gap-4 p-5">
                            <div
                                data-immich-album-qr
                                data-qr-url="{{ $album['sharedUrl'] ?? '' }}"
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
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
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

                                    <div class="flex shrink-0 flex-wrap gap-2">
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
                                                wire:click.stop="createSharedLink('{{ $album['id'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="createSharedLink('{{ $album['id'] }}')"
                                                x-on:click.stop
                                                class="inline-flex items-center justify-center whitespace-nowrap rounded-full bg-sky-600 px-3 py-1 text-xs font-bold text-white hover:bg-sky-700 disabled:opacity-60"
                                            >
                                                Create Link
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if (filled($album['description']))
                                    <p class="mt-3 break-words text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                        {{ $album['description'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="grid border-t border-gray-200 bg-gray-50 text-sm dark:border-gray-700 dark:bg-gray-950 sm:grid-cols-2">
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

                            <div class="border-t border-gray-200 p-4 dark:border-gray-700 sm:border-l sm:border-t-0">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">
                                    Attendance Sheet
                                </p>

                                @if ($album['linked'])
                                    <p class="mt-1 font-semibold text-gray-700 dark:text-gray-200">
                                        {{ data_get($album, 'link.sheet_title') ?: 'Linked sheet' }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ data_get($album, 'link.sheet_locality') ?: 'No locality' }}

                                        ·

                                        {{ data_get($album, 'link.enabled') ? 'Sync enabled' : 'Sync disabled' }}
                                    </p>
                                @else
                                    <p class="mt-1 text-gray-500 dark:text-gray-400">
                                        Not linked
                                    </p>
                                @endif
                            </div>
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
