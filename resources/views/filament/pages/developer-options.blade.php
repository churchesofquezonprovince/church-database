<x-filament-panels::page>
    <div class="space-y-6">

        <div
            class="rounded-2xl border border-gray-200
                   bg-white p-6 shadow-sm
                   dark:border-gray-700 dark:bg-gray-900"
        >
            <div>
                <h2
                    class="text-lg font-bold
                           text-gray-950 dark:text-white"
                >
                    Setup & Reference Data
                </h2>

                <p
                    class="mt-1 text-sm
                           text-gray-500 dark:text-gray-400"
                >
                    Open administrative setup pages used to
                    maintain geographic, school, and ministry
                    reference data.
                </p>
            </div>

            <div
                class="mt-5 grid gap-3
                       md:grid-cols-3"
            >
                <a
                    href="{{ \App\Filament\Pages\ProvinceSetup::getUrl() }}"
                    class="group flex items-center gap-4
                           rounded-xl border border-gray-200
                           p-4 transition
                           hover:border-primary-300
                           hover:bg-primary-50
                           dark:border-gray-700
                           dark:hover:border-primary-700
                           dark:hover:bg-primary-950"
                >
                    <div
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center
                               rounded-lg bg-gray-100
                               text-gray-600
                               group-hover:bg-primary-100
                               group-hover:text-primary-700
                               dark:bg-gray-800
                               dark:text-gray-300
                               dark:group-hover:bg-primary-900
                               dark:group-hover:text-primary-200"
                    >
                        <x-filament::icon
                            icon="heroicon-o-map"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            Province Setup
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Countries, provinces and Localities
                        </div>
                    </div>
                </a>

                <a
                    href="{{ \App\Filament\Pages\SchoolSetup::getUrl() }}"
                    class="group flex items-center gap-4
                           rounded-xl border border-gray-200
                           p-4 transition
                           hover:border-primary-300
                           hover:bg-primary-50
                           dark:border-gray-700
                           dark:hover:border-primary-700
                           dark:hover:bg-primary-950"
                >
                    <div
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center
                               rounded-lg bg-gray-100
                               text-gray-600
                               group-hover:bg-primary-100
                               group-hover:text-primary-700
                               dark:bg-gray-800
                               dark:text-gray-300
                               dark:group-hover:bg-primary-900
                               dark:group-hover:text-primary-200"
                    >
                        <x-filament::icon
                            icon="heroicon-o-academic-cap"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            School Setup
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Schools and campus locations
                        </div>
                    </div>
                </a>

                <a
                    href="{{ \App\Filament\Pages\MinistryBooks::getUrl() }}"
                    class="group flex items-center gap-4
                           rounded-xl border border-gray-200
                           p-4 transition
                           hover:border-primary-300
                           hover:bg-primary-50
                           dark:border-gray-700
                           dark:hover:border-primary-700
                           dark:hover:bg-primary-950"
                >
                    <div
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center
                               rounded-lg bg-gray-100
                               text-gray-600
                               group-hover:bg-primary-100
                               group-hover:text-primary-700
                               dark:bg-gray-800
                               dark:text-gray-300
                               dark:group-hover:bg-primary-900
                               dark:group-hover:text-primary-200"
                    >
                        <x-filament::icon
                            icon="heroicon-o-book-open"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            Ministry Books Setup
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Ministry books and lessons
                        </div>
                    </div>
                </a>

                <a
                    href="{{ \App\Filament\Pages\MorningRevivalSetup::getUrl() }}"
                    class="group flex items-center gap-4
                           rounded-xl border border-gray-200
                           p-4 transition
                           hover:border-primary-300
                           hover:bg-primary-50
                           dark:border-gray-700
                           dark:hover:border-primary-700
                           dark:hover:bg-primary-950"
                >
                    <div
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center
                               rounded-lg bg-gray-100
                               text-gray-600
                               group-hover:bg-primary-100
                               group-hover:text-primary-700
                               dark:bg-gray-800
                               dark:text-gray-300
                               dark:group-hover:bg-primary-900
                               dark:group-hover:text-primary-200"
                    >
                        <x-filament::icon
                            icon="heroicon-o-sun"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            Morning Revival Setup
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Publications, weekly messages
                            and Day 1–6 schedules
                        </div>
                    </div>
                </a>

                <a
                    href="{{ \App\Filament\Pages\HymnsSetup::getUrl() }}"
                    class="group flex items-center gap-4
                           rounded-xl border border-gray-200
                           p-4 transition
                           hover:border-primary-300
                           hover:bg-primary-50
                           dark:border-gray-700
                           dark:hover:border-primary-700
                           dark:hover:bg-primary-950"
                >
                    <div
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center
                               rounded-lg bg-gray-100
                               text-gray-600
                               group-hover:bg-primary-100
                               group-hover:text-primary-700
                               dark:bg-gray-800
                               dark:text-gray-300
                               dark:group-hover:bg-primary-900
                               dark:group-hover:text-primary-200"
                    >
                        <x-filament::icon
                            icon="heroicon-o-musical-note"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="font-bold text-gray-950
                                   dark:text-white"
                        >
                            Hymns Setup
                        </div>

                        <div
                            class="mt-1 text-xs text-gray-500
                                   dark:text-gray-400"
                        >
                            Canonical Hymns, variants, sources and reviews
                        </div>
                    </div>
                </a>

                <a
                    href="{{ \App\Filament\Pages\SongbaseSetup::getUrl() }}"
                    class="group flex items-center gap-4
                           rounded-xl border border-gray-200
                           p-4 transition
                           hover:border-primary-300
                           hover:bg-primary-50
                           dark:border-gray-700
                           dark:hover:border-primary-700
                           dark:hover:bg-primary-950"
                >
                    <div
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center
                               rounded-lg bg-gray-100
                               text-gray-600
                               group-hover:bg-primary-100
                               group-hover:text-primary-700
                               dark:bg-gray-800
                               dark:text-gray-300
                               dark:group-hover:bg-primary-900
                               dark:group-hover:text-primary-200"
                    >
                        <x-filament::icon
                            icon="heroicon-o-arrow-path"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            Songbase Setup
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Songbase sync, books,
                            languages and source status
                        </div>
                    </div>
                </a>


                <a
                    href="{{ \App\Filament\Pages\HymnalNetSetup::getUrl() }}"
                    class="group flex items-center gap-4
                           rounded-xl border border-gray-200
                           p-4 transition
                           hover:border-primary-300
                           hover:bg-primary-50
                           dark:border-gray-700
                           dark:hover:border-primary-700
                           dark:hover:bg-primary-950"
                >
                    <div
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center
                               rounded-lg bg-gray-100
                               text-gray-600
                               group-hover:bg-primary-100
                               group-hover:text-primary-700
                               dark:bg-gray-800
                               dark:text-gray-300
                               dark:group-hover:bg-primary-900
                               dark:group-hover:text-primary-200"
                    >
                        <x-filament::icon
                            icon="heroicon-o-link"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            Hymnal.net Setup
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            Link Hymnal.net pages
                            to canonical Hymns
                        </div>
                    </div>
                </a>

                <a
                    href="{{ \App\Filament\Pages\ExternalHymnsSetup::getUrl() }}"
                    class="group flex items-center gap-4
                           rounded-xl border border-gray-200
                           p-4 transition
                           hover:border-primary-300
                           hover:bg-primary-50
                           dark:border-gray-700
                           dark:hover:border-primary-700
                           dark:hover:bg-primary-950"
                >
                    <div
                        class="flex h-10 w-10 shrink-0
                               items-center justify-center
                               rounded-lg bg-gray-100
                               text-gray-600
                               group-hover:bg-primary-100
                               group-hover:text-primary-700
                               dark:bg-gray-800
                               dark:text-gray-300
                               dark:group-hover:bg-primary-900
                               dark:group-hover:text-primary-200"
                    >
                        <x-filament::icon
                            icon="heroicon-o-play-circle"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="font-bold
                                   text-gray-950
                                   dark:text-white"
                        >
                            External Hymns Setup
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            YouTube, SoundCloud and other
                            external hymn sources
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <details
            class="group rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >
            <summary
                class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5"
            >
                <div>
                    <h2
                        class="text-lg font-bold text-gray-950 dark:text-white"
                    >
                        Navigation Search Aliases
                    </h2>

                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        Add alternate search phrases for navigation
                        items or complete navigation groups.
                    </p>
                </div>

                <x-filament::icon
                    icon="heroicon-m-chevron-down"
                    class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180"
                />
            </summary>

            <div
                class="border-t border-gray-200 px-6 pb-6 pt-5 dark:border-gray-700"
            >
                <form
                wire:submit="createAlias"
                class="mt-6 grid gap-4 lg:grid-cols-[1.2fr_180px_1.5fr_auto]"
            >
                <div>
                    <label
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Search phrase
                    </label>

                    <x-filament::input.wrapper
                        class="mt-2"
                    >
                        <x-filament::input
                            wire:model="phrase"
                            type="text"
                            placeholder="e.g. ltm, immich, setup"
                        />
                    </x-filament::input.wrapper>

                    @error('phrase')
                        <p
                            class="mt-1 text-xs text-danger-600"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Target type
                    </label>

                    <select
                        wire:model.live="targetType"
                        class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="item">
                            Navigation Item
                        </option>

                        <option value="group">
                            Navigation Group
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Target
                    </label>

                    <select
                        wire:model="targetKey"
                        class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="">
                            Select target
                        </option>

                        @foreach (
                            $this->navigationTargetOptions()
                            as $targetKey => $targetLabel
                        )
                            <option
                                value="{{ $targetKey }}"
                            >
                                {{ $targetLabel }}
                            </option>
                        @endforeach
                    </select>

                    @error('targetKey')
                        <p
                            class="mt-1 text-xs text-danger-600"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div
                    class="flex flex-col justify-end gap-3"
                >
                    <label
                        class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        <input
                            wire:model="isActive"
                            type="checkbox"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950"
                        >

                        Active
                    </label>

                    <button
                        type="submit"
                        class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-primary-500"
                    >
                        Add Alias
                    </button>
                </div>
                </form>
            </div>
        </details>

        <details
            class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >
            <summary
                class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5"
            >
                <div>
                    <h3
                        class="font-bold text-gray-950 dark:text-white"
                    >
                        Configured Aliases
                    </h3>

                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        View, enable, disable, or delete existing navigation search aliases.
                    </p>
                </div>

                <x-filament::icon
                    icon="heroicon-m-chevron-down"
                    class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180"
                />
            </summary>

            <div
                class="border-t border-gray-200 dark:border-gray-700"
            >
                <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[760px] divide-y divide-gray-200 text-sm dark:divide-gray-700"
                >
                    <thead
                        class="bg-gray-50 dark:bg-gray-950"
                    >
                        <tr>
                            <th
                                class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200"
                            >
                                Search Phrase
                            </th>

                            <th
                                class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200"
                            >
                                Target
                            </th>

                            <th
                                class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200"
                            >
                                Status
                            </th>

                            <th
                                class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        @forelse ($this->aliases() as $alias)
                            <tr>
                                <td
                                    class="px-5 py-3 font-semibold text-gray-950 dark:text-white"
                                >
                                    {{ $alias->phrase }}
                                </td>

                                <td
                                    class="px-5 py-3 text-gray-600 dark:text-gray-300"
                                >
                                    @if (
                                        $alias->target_type === 'group'
                                    )
                                        <span
                                            class="font-semibold"
                                        >
                                            {{ $alias->target_label }}
                                        </span>

                                        <span
                                            class="ml-1 text-xs text-gray-500"
                                        >
                                            Entire group
                                        </span>
                                    @else
                                        @if ($alias->target_group)
                                            <span
                                                class="text-gray-500"
                                            >
                                                {{ $alias->target_group }}
                                                →
                                            </span>
                                        @endif

                                        <span
                                            class="font-semibold"
                                        >
                                            {{ $alias->target_label }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-3">
                                    @if ($alias->is_active)
                                        <span
                                            class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-bold text-success-700 dark:bg-success-950 dark:text-success-300"
                                        >
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                        >
                                            Disabled
                                        </span>
                                    @endif
                                </td>

                                <td
                                    class="px-5 py-3 text-right"
                                >
                                    <div
                                        class="flex justify-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            wire:click="toggleAlias({{ $alias->id }})"
                                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                        >
                                            {{ $alias->is_active
                                                ? 'Disable'
                                                : 'Enable' }}
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="deleteAlias({{ $alias->id }})"
                                            wire:confirm="Delete this navigation search alias?"
                                            class="rounded-lg bg-danger-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-danger-500"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-5 py-10 text-center text-gray-500 dark:text-gray-400"
                                >
                                    No navigation search aliases configured.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </details>

    </div>

    
    <details
        class="group rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
    >
        <summary
            class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5"
        >
            <div>
                <h2
                    class="text-lg font-bold text-gray-950 dark:text-white"
                >
                    Meeting Form Privacy & Autofill
                </h2>

                <p
                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                >
                    Control when public meeting forms may silently
                    auto-fill existing Database Field information.
                </p>
            </div>

            <x-filament::icon
                icon="heroicon-m-chevron-down"
                class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180"
            />
        </summary>

        <div
            class="border-t border-gray-200 px-6 pb-6 pt-5 dark:border-gray-700"
        >
            <div
                class="mt-4 max-w-xl"
            >
                <label
                    class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                >
                    Database Field matches before autofill
                </label>

                <p
                    class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400"
                >
                    Default: 2. The participant is never shown a
                    match count or identity-check message. Once this
                    many distinct eligible fields silently match the
                    selected record, remaining blank Database Fields
                    on that meeting form may be auto-filled.
                </p>

                <x-filament::input.wrapper
                    class="mt-3 max-w-32"
                >
                    <x-filament::input
                        wire:model="databaseFieldMatchesBeforeAutofill"
                        type="number"
                        min="0"
                        max="5"
                        step="1"
                    />
                </x-filament::input.wrapper>

                @error('databaseFieldMatchesBeforeAutofill')
                    <p
                        class="mt-1 text-xs text-danger-600"
                    >
                        {{ $message }}
                    </p>
                @enderror

                <div
                    class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs leading-5 text-gray-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300"
                >
                    <strong>0</strong>
                    means existing values are never revealed or
                    auto-filled.

                    <br>

                    <strong>1–5</strong>
                    is the number of distinct matching Database Fields
                    required before silent autofill becomes available.

                    <br>

                    Values already typed by the participant will never
                    be overwritten by autofill.
                </div>

                <div
                    class="mt-4"
                >
                    <x-filament::button
                        wire:click="saveMeetingFormAutofillSettings"
                        icon="heroicon-m-check"
                    >
                        Save Meeting Form Setting
                    </x-filament::button>
                </div>
            </div>
        </div>
    </details>


    <!-- Developer Options: Cache & Maintenance -->
    <details
        class="group rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
    >
        <summary
            class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5"
        >
            <div>
                <h2
                    class="text-lg font-bold text-gray-950 dark:text-white"
                >
                    Cache & Maintenance
                </h2>

                <p
                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                >
                    Clear or rebuild cached application data.
                </p>
            </div>

            <x-filament::icon
                icon="heroicon-m-chevron-down"
                class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180"
            />
        </summary>

        <div
            class="border-t border-gray-200 px-6 pb-6 pt-5 dark:border-gray-700"
        >
            <div class="mt-5 flex flex-wrap gap-3">
                        <x-filament::button
                            color="gray"
                            icon="heroicon-m-trash"
                            wire:click="clearApplicationCache"
                            wire:confirm="Clear the application cache?"
                        >
                            Clear Application Cache
                        </x-filament::button>

                        <x-filament::button
                            color="gray"
                            icon="heroicon-m-trash"
                            wire:click="clearConfigCache"
                            wire:confirm="Clear the configuration cache?"
                        >
                            Clear Config Cache
                        </x-filament::button>

                        <x-filament::button
                            color="gray"
                            icon="heroicon-m-trash"
                            wire:click="clearViewCache"
                            wire:confirm="Clear the compiled view cache?"
                        >
                            Clear View Cache
                        </x-filament::button>

                        <x-filament::button
                            color="gray"
                            icon="heroicon-m-trash"
                            wire:click="clearRouteCache"
                            wire:confirm="Clear the route cache?"
                        >
                            Clear Route Cache
                        </x-filament::button>

                        <x-filament::button
                            color="primary"
                            icon="heroicon-m-arrow-path"
                            wire:click="rebuildCaches"
                            wire:confirm="Clear and rebuild all application optimization caches?"
                        >
                            Rebuild Caches
                        </x-filament::button>
                    </div>
        </div>
    </details>

    <!-- Developer Options: System Information -->
    <details
        open
        class="group rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
    >
        <summary
            class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5"
        >
            <div>
                <h2
                    class="text-lg font-bold text-gray-950 dark:text-white"
                >
                    System Information
                </h2>

                <p
                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                >
                    Current CoQP Database application and server information.
                </p>
            </div>

            <x-filament::icon
                icon="heroicon-m-chevron-down"
                class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180"
            />
        </summary>

        <div
            class="border-t border-gray-200 px-6 pb-6 pt-5 dark:border-gray-700"
        >
            <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                App Version
                            </div>

                            <div class="mt-1 font-mono text-sm text-gray-950 dark:text-white">
                                {{ $appVersion }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Current Phase
                            </div>

                            <div class="mt-1 font-mono text-sm text-gray-950 dark:text-white">
                                {{ $currentPhase }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Git Commit
                            </div>

                            <div class="mt-1 font-mono text-sm text-gray-950 dark:text-white">
                                {{ $gitCommitHash }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Laravel Version
                            </div>

                            <div class="mt-1 font-mono text-sm text-gray-950 dark:text-white">
                                {{ $laravelVersion }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                PHP Version
                            </div>

                            <div class="mt-1 font-mono text-sm text-gray-950 dark:text-white">
                                {{ $phpVersion }}
                            </div>
                        </div>
                    </div>
        </div>
    </details>

</x-filament-panels::page>
