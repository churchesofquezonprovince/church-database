<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-6 shadow-sm dark:border-sky-900 dark:bg-sky-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-sky-600 dark:text-sky-300">
                Posts Roadmap · Phase 21
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Immich Albums
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Planned module for connecting this website to Immich albums, shared album links, QR codes, gallery thumbnails, and Immich storage usage.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Immich Storage Widget
                </p>

                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">
                    45.2 GiB
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    of 2.7 TiB used
                </p>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                    <div class="h-full w-[2%] rounded-full bg-sky-600"></div>
                </div>

                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-300">
                    Coming Soon · sample display only
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Albums
                </p>

                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">
                    —
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Synced from Immich
                </p>

                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-300">
                    Coming Soon
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Shared Links
                </p>

                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">
                    —
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Clickable links and QR codes
                </p>

                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-300">
                    Coming Soon
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-dashed border-sky-300 bg-white p-6 shadow-sm dark:border-sky-800 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Album Actions
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Planned actions for managing Immich albums from this website.
                    </p>
                </div>

                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                    Coming Soon
                </span>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-950">
                    <h4 class="font-bold text-gray-900 dark:text-white">
                        Create Album
                    </h4>

                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Create a new Immich album from this website. Planned fields: album title, description, locality, meeting type, and date.
                    </p>

                    <button
                        type="button"
                        disabled
                        class="mt-4 rounded-xl bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                    >
                        Create Album · Coming Soon
                    </button>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-950">
                    <h4 class="font-bold text-gray-900 dark:text-white">
                        Sync Albums
                    </h4>

                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Planned sync from Immich API to display albums, thumbnails, shared links, and storage statistics.
                    </p>

                    <button
                        type="button"
                        disabled
                        class="mt-4 rounded-xl bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                    >
                        Sync from Immich · Coming Soon
                    </button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Gallery Preview
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Planned layout: each album thumbnail will show available shared links and QR codes below it.
                    </p>
                </div>

                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                    Coming Soon
                </span>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['title' => 'Lord’s Table Album', 'locality' => 'Lucena City'],
                    ['title' => 'Prayer Meeting Album', 'locality' => 'Candelaria'],
                    ['title' => 'YP Meeting Album', 'locality' => 'Tayabas'],
                ] as $album)
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-950">
                        <div class="flex h-40 items-center justify-center bg-gray-200 dark:bg-gray-800">
                            <x-heroicon-o-photo class="h-14 w-14 text-gray-400" />
                        </div>

                        <div class="p-4">
                            <h4 class="font-bold text-gray-900 dark:text-white">
                                {{ $album['title'] }}
                            </h4>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $album['locality'] }}
                            </p>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    disabled
                                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
                                >
                                    QR · Soon
                                </button>

                                <button
                                    type="button"
                                    disabled
                                    class="rounded-xl bg-sky-600 px-3 py-2 text-xs font-bold text-white opacity-60"
                                >
                                    Shared Link · Soon
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Planned Immich Integration
            </h3>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="font-bold text-gray-900 dark:text-white">API Settings</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Future environment values: IMMICH_URL, IMMICH_API_KEY, IMMICH_PUBLIC_URL.
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="font-bold text-gray-900 dark:text-white">Storage Widget</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Future widget will display Immich storage usage, total storage, asset count, photo count, and video count.
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="font-bold text-gray-900 dark:text-white">Album Creation</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Future form will create albums directly in Immich and save the album ID locally.
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="font-bold text-gray-900 dark:text-white">Shared Links and QR</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Future gallery will show clickable Immich shared links and QR codes under every album thumbnail.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
