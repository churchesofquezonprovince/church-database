<x-filament-panels::page>
    @php
        $schools = $this->schools();
        $provinces = $this->provinceOptions();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Administration
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                School Setup
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Manage Schools used by People, Campus Contacts, Student Centers,
                and Campus Activities. Schools may be located in any province or region
                and are independent from a Person's church Locality.
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                Add School
            </h3>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add a School or campus to the database-backed School list.
            </p>

            <form
                wire:submit="addSchool"
                class="mt-5 grid gap-4 md:grid-cols-2"
            >
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        School Name *
                    </label>

                    <input
                        type="text"
                        wire:model="newName"
                        placeholder="University of the Philippines Diliman"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('newName')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Short Name
                    </label>

                    <input
                        type="text"
                        wire:model="newShortName"
                        placeholder="UP Diliman"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('newShortName')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Province / Region
                    </label>

                    <select
                        wire:model="newProvinceId"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">
                            Not specified
                        </option>

                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}">
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('newProvinceId')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        City / Municipality
                    </label>

                    <input
                        type="text"
                        wire:model="newCityMunicipality"
                        placeholder="Quezon City"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('newCityMunicipality')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="md:col-span-2 flex justify-end">
                    <button
                        type="submit"
                        class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-500"
                    >
                        Add School
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                Mass Add Schools
            </h3>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add multiple Schools for one Province / Region.
                Use one School per line.
            </p>

            <div class="mt-3 rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                <code>
                    SHORT NAME — FULL SCHOOL NAME — CITY / MUNICIPALITY, PROVINCE
                </code>

                <p class="mt-2 text-xs">
                    Heading lines without the separators are ignored.
                    The province after the city may be omitted when it
                    matches the selected Province / Region.
                </p>
            </div>

            <form
                wire:submit="addSchools"
                class="mt-5 space-y-4"
            >
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Province / Region *
                    </label>

                    <select
                        wire:model="massProvinceId"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">
                            Select Province / Region
                        </option>

                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}">
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('massProvinceId')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Schools *
                    </label>

                    <textarea
                        wire:model="massSchools"
                        rows="14"
                        placeholder="ABC — Example University — Example City, Example Province"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-mono text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    ></textarea>

                    @error('massSchools')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-500"
                    >
                        Mass Add Schools
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                        Configured Schools
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Archived Schools remain available for historical records but
                        should not be offered for new selections later.
                    </p>
                </div>

                <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    {{ $schools->count() }} configured
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($schools as $school)
                    <div
                        wire:key="school-{{ $school->id }}"
                        class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"
                    >
                        @if ($editingSchoolId === $school->id)
                            <form
                                wire:submit="saveSchool"
                                class="grid gap-4 md:grid-cols-2"
                            >
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        School Name *
                                    </label>

                                    <input
                                        type="text"
                                        wire:model="editName"
                                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                    >

                                    @error('editName')
                                        <p class="mt-1 text-xs text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        Short Name
                                    </label>

                                    <input
                                        type="text"
                                        wire:model="editShortName"
                                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        Province / Region
                                    </label>

                                    <select
                                        wire:model="editProvinceId"
                                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                    >
                                        <option value="">
                                            Not specified
                                        </option>

                                        @foreach ($provinces as $province)
                                            <option value="{{ $province->id }}">
                                                {{ $province->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        City / Municipality
                                    </label>

                                    <input
                                        type="text"
                                        wire:model="editCityMunicipality"
                                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                    >
                                </div>

                                <div class="md:col-span-2 flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="cancelEditing"
                                        class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="submit"
                                        class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-500"
                                    >
                                        Save School
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-gray-950 dark:text-white">
                                            {{ $school->name }}
                                        </h4>

                                        @if ($school->short_name)
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $school->short_name }}
                                            </span>
                                        @endif

                                        @if ($school->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                                                Active
                                            </span>
                                        @else
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                                Archived
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $school->city_municipality ?: 'City / Municipality not specified' }}

                                        @if ($school->province)
                                            · {{ $school->province->name }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        wire:click="startEditing({{ $school->id }})"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="toggleSchool({{ $school->id }})"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                    >
                                        {{ $school->is_active ? 'Archive' : 'Restore' }}
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="deleteSchool({{ $school->id }})"
                                        wire:confirm="Delete {{ $school->name }}? Only unused Schools can be deleted."
                                        class="rounded-lg border border-red-300 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                        <p class="font-semibold text-gray-700 dark:text-gray-200">
                            No Schools configured yet.
                        </p>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Add the first School using the form above.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
