<x-filament-panels::page>
    @php
        $settings = $this->primarySetting();
        $localities = $this->localities();
        $outsideCountries = $this->outsideCountryGroups();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Administration
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Province Setup
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Configure the primary country, province, and church Localities for this installation.
                The primary province controls the normal Locality list, but does not prevent People
                from other provinces from being stored later.
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                Primary Province
            </h3>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                This is the default geographic scope for this installation.
            </p>

            <form
                wire:submit="savePrimaryProvince"
                class="mt-5 grid gap-4 md:grid-cols-2"
            >
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Country
                    </label>

                    <input
                        type="text"
                        wire:model="countryName"
                        placeholder="Philippines"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('countryName')
                        <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Country Code
                    </label>

                    <input
                        type="text"
                        wire:model="countryCode"
                        maxlength="3"
                        placeholder="PH"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm uppercase text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('countryCode')
                        <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Primary Province
                    </label>

                    <input
                        type="text"
                        wire:model="provinceName"
                        placeholder="Quezon"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('provinceName')
                        <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Province Code
                    </label>

                    <input
                        type="text"
                        wire:model="provinceCode"
                        maxlength="30"
                        placeholder="Optional"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm uppercase text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('provinceCode')
                        <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="md:col-span-2 flex justify-end">
                    <button
                        type="submit"
                        class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-500"
                    >
                        Save Primary Province
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                        Localities in
                        {{ $settings?->primaryProvince?->name ?? 'Primary Province' }}
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Add only church Localities belonging to the configured primary province.
                    </p>
                </div>

                @if ($settings?->primaryCountry && $settings?->primaryProvince)
                    <div class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                        {{ $settings->primaryProvince->name }}
                        ·
                        {{ $settings->primaryCountry->name }}
                    </div>
                @endif
            </div>

            @if ($settings?->primary_province_id)
                <form
                    wire:submit="addLocality"
                    class="mt-5 flex flex-col gap-3 sm:flex-row"
                >
                    <div class="min-w-0 flex-1">
                        <input
                            type="text"
                            wire:model="newLocality"
                            placeholder="Example: Lucena City"
                            class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        >

                        @error('newLocality')
                            <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500"
                    >
                        Add Locality
                    </button>
                </form>

                <div class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
    <h4 class="font-bold text-gray-900 dark:text-white">
        Mass Add Localities
    </h4>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Enter one Locality per line, or separate names with commas.
    </p>

    <form
        wire:submit="addLocalities"
        class="mt-4 space-y-3"
    >
        <textarea
            wire:model="massLocalities"
            rows="7"
            placeholder="Lucena City&#10;Lucban&#10;Tayabas&#10;Candelaria"
            class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
        ></textarea>

        @error('massLocalities')
            <p data-coqp-field-error class="text-xs text-red-600">
                {{ $message }}
            </p>
        @enderror

        <div class="flex justify-end">
            <button
                type="submit"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-500"
            >
                Add Localities
            </button>
        </div>
    </form>
</div>

                <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($localities as $locality)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $locality->name }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $settings->primaryProvince?->name }}
                                    ·
                                    {{ $settings->primaryCountry?->name }}
                                </p>
                            </div>

<div class="flex shrink-0 items-center gap-2">
    <button
        type="button"
        wire:click="toggleLocality({{ $locality->id }})"
        class="rounded-full px-3 py-1 text-xs font-bold
            {{ $locality->is_active
                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100'
                : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}"
    >
        {{ $locality->is_active ? 'Active' : 'Archived' }}
    </button>

    <button
        type="button"
        wire:click="deleteLocality({{ $locality->id }})"
        wire:confirm="Delete {{ $locality->name }}? This is only allowed when the Locality is not used by any database records."
        class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700 hover:bg-red-100 dark:bg-red-950 dark:text-red-200"
    >
        Delete
    </button>
</div>
                        </div>
                    @empty
                        <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No Localities have been configured yet.
                        </div>
                    @endforelse
                </div>
            @else
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    Save the Primary Country and Primary Province first before adding Localities.
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div>
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                    Outside Primary Province Geography
                </h3>

                <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                    Manage Countries, Provinces / Regions, and Localities outside
                    {{ $settings?->primaryProvince?->name ?? 'the Primary Province' }}.
                    Provinces created from School proposals remain visible even when
                    they do not yet have any Localities.
                </p>
            </div>

            @if ($settings?->primary_province_id)
                <form
                    wire:submit="addOutsideLocalities"
                    class="mt-6 space-y-4"
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Country
                            </label>

                            <input
                                type="text"
                                wire:model="outsideCountryName"
                                placeholder="Philippines"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >

                            @error('outsideCountryName')
                                <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Country Code
                            </label>

                            <input
                                type="text"
                                wire:model="outsideCountryCode"
                                maxlength="3"
                                placeholder="PH"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm uppercase text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >

                            @error('outsideCountryCode')
                                <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Province / Region
                            </label>

                            <input
                                type="text"
                                wire:model="outsideProvinceName"
                                placeholder="Example: Cavite"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >

                            @error('outsideProvinceName')
                                <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Province / Region Code
                            </label>

                            <input
                                type="text"
                                wire:model="outsideProvinceCode"
                                maxlength="30"
                                placeholder="Optional"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm uppercase text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            >

                            @error('outsideProvinceCode')
                                <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Localities
                        </label>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Enter one Locality per line, or separate names with commas.
                        </p>

                        <textarea
                            wire:model="outsideMassLocalities"
                            rows="6"
                            placeholder="Imus City&#10;Dasmariñas City"
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        ></textarea>

                        @error('outsideMassLocalities')
                            <p data-coqp-field-error class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-500"
                        >
                            Add Outside Localities
                        </button>
                    </div>
                </form>

                <div class="mt-8 space-y-6">
                    @forelse ($outsideCountries as $country)
                        <div
                            wire:key="outside-country-{{ $country->id }}"
                            class="overflow-hidden rounded-2xl
                                   border border-gray-200
                                   dark:border-gray-700"
                        >
                            <div
                                class="flex flex-col gap-3
                                       border-b border-gray-200
                                       bg-gray-50 px-5 py-4
                                       dark:border-gray-700
                                       dark:bg-gray-950
                                       sm:flex-row
                                       sm:items-center
                                       sm:justify-between"
                            >
                                <div>
                                    <h4
                                        class="text-lg font-bold
                                               text-gray-950
                                               dark:text-white"
                                    >
                                        {{ $country->name }}

                                        @if (filled($country->code))
                                            <span
                                                class="text-sm font-semibold
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                ({{ $country->code }})
                                            </span>
                                        @endif
                                    </h4>

                                    <p
                                        class="mt-1 text-xs
                                               text-gray-500
                                               dark:text-gray-400"
                                    >
                                        Country
                                    </p>
                                </div>

                                <div
                                    class="flex flex-wrap
                                           items-center gap-2"
                                >
                                    @if (
                                        (int) $country->id
                                        ===
                                        (int) $settings?->primary_country_id
                                    )
                                        <span
                                            class="rounded-full
                                                   bg-primary-100
                                                   px-3 py-1
                                                   text-xs font-bold
                                                   text-primary-800
                                                   dark:bg-primary-900
                                                   dark:text-primary-100"
                                        >
                                            Primary Country
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="toggleOutsideCountry({{ $country->id }})"
                                            @if ($country->is_active)
                                                wire:confirm="Archive {{ $country->name }}? All Provinces in this Country must already be archived."
                                            @endif
                                            class="rounded-full px-3 py-1
                                                   text-xs font-bold
                                                {{ $country->is_active
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100'
                                                    : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}"
                                        >
                                            {{ $country->is_active
                                                ? 'Active'
                                                : 'Archived' }}
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="deleteOutsideCountry({{ $country->id }})"
                                            wire:confirm="Delete Country {{ $country->name }}? This is only allowed after all Provinces in it have been deleted."
                                            class="rounded-full
                                                   bg-red-50 px-3 py-1
                                                   text-xs font-bold
                                                   text-red-700
                                                   hover:bg-red-100
                                                   dark:bg-red-950
                                                   dark:text-red-200"
                                        >
                                            Delete Country
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-4 p-5">
                                @forelse ($country->provinces as $province)
                                    <div
                                        wire:key="outside-province-{{ $province->id }}"
                                        class="rounded-xl border
                                               border-gray-200 p-4
                                               dark:border-gray-700"
                                    >
                                        <div
                                            class="flex flex-col gap-3
                                                   sm:flex-row
                                                   sm:items-start
                                                   sm:justify-between"
                                        >
                                            <div>
                                                <h5
                                                    class="font-bold
                                                           text-gray-900
                                                           dark:text-white"
                                                >
                                                    {{ $province->name }}
                                                </h5>

                                                <p
                                                    class="mt-1 text-xs
                                                           text-gray-500
                                                           dark:text-gray-400"
                                                >
                                                    Province / Region
                                                    @if (filled($province->code))
                                                        · {{ $province->code }}
                                                    @endif
                                                </p>
                                            </div>

                                            <div
                                                class="flex flex-wrap
                                                       items-center gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    wire:click="toggleOutsideProvince({{ $province->id }})"
                                                    class="rounded-full
                                                           px-3 py-1
                                                           text-xs font-bold
                                                        {{ $province->is_active
                                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100'
                                                            : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}"
                                                >
                                                    {{ $province->is_active
                                                        ? 'Active'
                                                        : 'Archived' }}
                                                </button>

                                                <button
                                                    type="button"
                                                    wire:click="deleteOutsideProvince({{ $province->id }})"
                                                    wire:confirm="Delete Province / Region {{ $province->name }}? This is only allowed when no Schools or Localities reference it."
                                                    class="rounded-full
                                                           bg-red-50 px-3 py-1
                                                           text-xs font-bold
                                                           text-red-700
                                                           hover:bg-red-100
                                                           dark:bg-red-950
                                                           dark:text-red-200"
                                                >
                                                    Delete Province
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            class="mt-4 grid gap-3
                                                   md:grid-cols-2
                                                   xl:grid-cols-3"
                                        >
                                            @forelse ($province->localities as $locality)
                                                <div
                                                    class="flex items-center
                                                           justify-between
                                                           gap-3 rounded-xl
                                                           border
                                                           border-gray-200
                                                           p-4
                                                           dark:border-gray-700"
                                                >
                                                    <div class="min-w-0">
                                                        <p
                                                            class="font-semibold
                                                                   text-gray-900
                                                                   dark:text-white"
                                                        >
                                                            {{ $locality->name }}
                                                        </p>

                                                        <p
                                                            class="mt-1 text-xs
                                                                   text-gray-500
                                                                   dark:text-gray-400"
                                                        >
                                                            {{ $province->name }}
                                                        </p>
                                                    </div>

                                                    <div
                                                        class="flex shrink-0
                                                               items-center
                                                               gap-2"
                                                    >
                                                        <button
                                                            type="button"
                                                            wire:click="toggleOutsideLocality({{ $locality->id }})"
                                                            class="rounded-full
                                                                   px-3 py-1
                                                                   text-xs
                                                                   font-bold
                                                                {{ $locality->is_active
                                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100'
                                                                    : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}"
                                                        >
                                                            {{ $locality->is_active
                                                                ? 'Active'
                                                                : 'Archived' }}
                                                        </button>

                                                        <button
                                                            type="button"
                                                            wire:click="deleteOutsideLocality({{ $locality->id }})"
                                                            wire:confirm="Delete {{ $locality->name }}? This is only allowed when it is not used by database records."
                                                            class="rounded-full
                                                                   bg-red-50
                                                                   px-3 py-1
                                                                   text-xs
                                                                   font-bold
                                                                   text-red-700
                                                                   hover:bg-red-100
                                                                   dark:bg-red-950
                                                                   dark:text-red-200"
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            @empty
                                                <div
                                                    class="rounded-xl border
                                                           border-dashed
                                                           border-gray-300
                                                           p-5 text-sm
                                                           text-gray-500
                                                           dark:border-gray-700
                                                           dark:text-gray-400
                                                           md:col-span-2
                                                           xl:col-span-3"
                                                >
                                                    No Localities configured
                                                    for this Province / Region.
                                                    Schools may still reference
                                                    this Province independently.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="rounded-xl border
                                               border-dashed
                                               border-gray-300
                                               p-5 text-sm
                                               text-gray-500
                                               dark:border-gray-700
                                               dark:text-gray-400"
                                    >
                                        No Provinces / Regions remain in
                                        this Country. You may delete the
                                        Country if it is no longer needed.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div
                            class="rounded-xl border
                                   border-dashed
                                   border-gray-300
                                   p-8 text-center
                                   text-sm text-gray-500
                                   dark:border-gray-700
                                   dark:text-gray-400"
                        >
                            No outside Countries, Provinces / Regions,
                            or Localities have been configured yet.
                        </div>
                    @endforelse
                </div>
            @else
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    Configure the Primary Province first.
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
