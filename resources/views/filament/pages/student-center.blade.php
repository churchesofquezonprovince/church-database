<x-filament-panels::page>
    @php
        $centers = $this->centers();
        $schoolOptions = $this->schoolOptions();
        $localityOptions = $this->localityOptions();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Campus Work
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Student Center
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Manage Student Centers by school and locality. Students here are added from the Campus Contacts database only.
            </p>
        </div>

        @if (session('student_center_saved'))
            <div data-coqp-flash="success" data-coqp-flash-id="b320d04abfe4d249" data-coqp-keep="false" class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Student Center saved.</p>
            </div>
        @endif

        @if (session('student_center_updated'))
            <div data-coqp-flash="success" data-coqp-flash-id="b429dbe40ca3a573" data-coqp-keep="false" class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Student Center updated.</p>
            </div>
        @endif

        @if (session('student_center_deleted'))
            <div data-coqp-flash="warning" data-coqp-flash-id="0c1b4ec35383002a" data-coqp-keep="false" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">Student Center deleted.</p>
            </div>
        @endif

        @if (session('student_center_members_saved'))
            <div data-coqp-flash="success" data-coqp-flash-id="7d9775e22b69d538" data-coqp-keep="false" class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">
                    Added {{ session('student_center_members_added') }} contact(s) to the Student Center.
                </p>
            </div>
        @endif

        @if (session('student_center_member_removed'))
            <div data-coqp-flash="warning" data-coqp-flash-id="420f9ce066a44f75" data-coqp-keep="false" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">Student removed from the Student Center.</p>
            </div>
        @endif

        @if ($errors->any())
            <div data-coqp-flash="danger" data-coqp-flash-id="9d79971a0fca231e" data-coqp-keep="true" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Please fix the following:</p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <details class="min-w-0 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
            <summary class="cursor-pointer px-4 py-4 text-lg font-bold text-emerald-900 hover:bg-emerald-100 dark:text-emerald-100 dark:hover:bg-emerald-900 sm:px-6">
                Add Student Center
            </summary>

            <form
                method="POST"
                action="{{ route('quezonprovinceactivities.campus-work.student-center.store') }}"
                class="border-t border-emerald-200 p-4 dark:border-emerald-900 sm:p-6"
            >
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="school_id" class="block text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                            School
                        </label>

                        <select
                            id="school_id"
                            name="school_id"
                            class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                            <option value="">
                                School not recorded
                            </option>

                            @foreach ($schoolOptions as $school)
                                <option
                                    value="{{ $school->id }}"
                                    @selected(
                                        (string) old('school_id')
                                        === (string) $school->id
                                    )
                                >
                                    {{ $school->name }}

                                    @if ($school->city_municipality || $school->province)
                                        —
                                        {{ collect([
                                            $school->city_municipality,
                                            $school->province?->name,
                                        ])->filter()->implode(', ') }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="locality_id" class="block text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                            Locality / Place of Student Center
                        </label>

                        <select
                            id="locality_id"
                            name="locality_id"
                            required
                            class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                            <option value="">Select Locality</option>

                            @foreach ($localityOptions as $localityId => $locality)
                                <option value="{{ $localityId }}">
                                    {{ $locality }}
                                </option>
                            @endforeach
                        </select>

                        <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-200">
                            Name will be created automatically, for example: Student Center - Lucban.
                        </p>
                    </div>

                    <div>
                        <label for="place" class="block text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                            Exact Place / Address
                        </label>

                        <input
                            id="place"
                            name="place"
                            type="text"
                            placeholder="Optional address or landmark"
                            class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>
                </div>

                <div class="mt-4">
                    <label for="notes" class="block text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    class="mt-5 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500"
                >
                    Save Student Center
                </button>
            </form>
        </details>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Search Student Centers
            </h3>

            <form method="GET" class="mt-4 grid gap-4 lg:grid-cols-4">
                <div>
                    <label for="search" class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Search
                    </label>

                    <input
                        id="search"
                        name="search"
                        type="search"
                        value="{{ request('search') }}"
                        placeholder="Name, school, locality, place..."
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>

                <div>
                    <label for="school_filter" class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                        School
                    </label>

                    <select
                        id="school_filter"
                        name="school_id"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">All schools</option>

                        @foreach ($schoolOptions as $school)
                            <option
                                value="{{ $school->id }}"
                                @selected(
                                    (int) request('school_id')
                                    === (int) $school->id
                                )
                            >
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="locality_filter" class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Locality
                    </label>

                    <select
                        id="locality_filter"
                        name="locality_id"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">All localities</option>

                        @foreach ($localityOptions as $localityId => $locality)
                            <option
                                value="{{ $localityId }}"
                                @selected((string) request('locality_id') === (string) $localityId)
                            >
                                {{ $locality }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="submit"
                        class="rounded-xl bg-primary-600 px-5 py-3 text-sm font-bold text-white hover:bg-primary-500"
                    >
                        Search
                    </button>

                    <a
                        href="{{ \App\Filament\Pages\StudentCenter::getUrl() }}"
                        class="rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="space-y-5">
            @forelse ($centers as $center)
                <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-700 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <h3 class="break-words text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $center->name }}
                                </h3>

                                <p class="mt-2 break-words text-sm text-gray-600 dark:text-gray-300">
                                    {{ $center->school?->name ?: 'School not recorded' }}
                                    · {{ $center->locality ?: 'Locality not recorded' }}
                                </p>

                                @if ($center->place)
                                    <p class="mt-1 break-words text-sm text-gray-500 dark:text-gray-400">
                                        {{ $center->place }}
                                    </p>
                                @endif

                                @if ($center->notes)
                                    <p class="mt-3 whitespace-pre-line break-words text-sm text-gray-600 dark:text-gray-300">
                                        {{ $center->notes }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2">
                                <span class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white">
                                    {{ $center->members_count }} student(s)
                                </span>
                            </div>
                        </div>

                        <details class="mt-4 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-950">
                            <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-gray-900 dark:text-white">
                                Edit Student Center
                            </summary>

                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.campus-work.student-center.update', $center) }}"
                                class="border-t border-gray-200 p-4 dark:border-gray-700"
                            >
                                @csrf
                                @method('PATCH')

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            School
                                        </label>

                                        <select
                                            name="school_id"
                                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        >
                                            <option value="">
                                                School not recorded
                                            </option>

                                            @foreach ($schoolOptions as $school)
                                                <option
                                                    value="{{ $school->id }}"
                                                    @selected(
                                                        (int) $center->school_id
                                                        === (int) $school->id
                                                    )
                                                >
                                                    {{ $school->name }}

                                                    @if ($school->city_municipality || $school->province)
                                                        —
                                                        {{ collect([
                                                            $school->city_municipality,
                                                            $school->province?->name,
                                                        ])->filter()->implode(', ') }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            Locality / Place of Student Center
                                        </label>

                                        <select
                                            name="locality_id"
                                            required
                                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        >
                                            @foreach ($localityOptions as $localityId => $locality)
                                                <option
                                                    value="{{ $localityId }}"
                                                    @selected((int) $center->locality_id === (int) $localityId)
                                                >
                                                    {{ $locality }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            Exact Place / Address
                                        </label>

                                        <input
                                            name="place"
                                            type="text"
                                            value="{{ $center->place }}"
                                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        >
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        Notes
                                    </label>

                                    <textarea
                                        name="notes"
                                        rows="3"
                                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    >{{ $center->notes }}</textarea>
                                </div>

                                <div class="mt-5 flex flex-wrap gap-2">
                                    <button
                                        type="submit"
                                        class="rounded-xl bg-primary-600 px-5 py-3 text-sm font-bold text-white hover:bg-primary-500"
                                    >
                                        Save Changes
                                    </button>
                                </div>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.campus-work.student-center.destroy', $center) }}"
                                onsubmit="return confirm('Delete this Student Center and remove its student list?');"
                                class="border-t border-gray-200 p-4 dark:border-gray-700"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-bold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                                >
                                    Delete Student Center
                                </button>
                            </form>
                        </details>
                    </div>

                    <div class="grid gap-0 lg:grid-cols-2">
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700 lg:border-b-0 lg:border-r sm:p-6">
                            <h4 class="font-bold text-gray-900 dark:text-white">
                                Students in this Student Center
                            </h4>

                            <div class="mt-4 space-y-3">
                                @forelse ($center->members->sortBy(fn ($member) => $this->contactName($member->contact)) as $member)
                                    @php
                                        $contact = $member->contact;
                                    @endphp

                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <p class="break-words font-bold text-gray-900 dark:text-white">
                                                    {{ $this->contactName($contact) }}
                                                </p>

                                                <p class="mt-1 break-words text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $this->contactSchoolName($contact) ?: 'School not recorded' }}
                                                    · {{ $this->contactField($contact, 'locality') ?: 'Locality not recorded' }}
                                                </p>

                                                <p class="mt-1 break-words text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $this->contactField($contact, 'course_strand') ?: 'Course not recorded' }}
                                                    · {{ $this->contactField($contact, 'grade_level') ?: 'Year level not recorded' }}
                                                </p>
                                            </div>

                                            <form
                                                method="POST"
                                                action="{{ route('quezonprovinceactivities.campus-work.student-center.members.destroy', [$center, $member]) }}"
                                                onsubmit="return confirm('Remove this student from this Student Center?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                                                >
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        No students added yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="p-4 sm:p-6">
                            <details class="rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950">
                                <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-emerald-900 dark:text-emerald-100">
                                    Add Students from Campus Contacts
                                </summary>

                                <form
                                    method="POST"
                                    action="{{ route('quezonprovinceactivities.campus-work.student-center.members.store', $center) }}"
                                    class="border-t border-emerald-200 p-4 dark:border-emerald-900"
                                >
                                    @csrf

                                    <input
                                        type="search"
                                        placeholder="Search Campus Contact name, school, locality, course, contact, or Facebook..."
                                        oninput="
                                            const q = this.value.toLowerCase();

                                            this.closest('form')
                                                .querySelectorAll('[data-campus-contact-option]')
                                                .forEach((card) => {
                                                    card.hidden = ! card.dataset.searchText.includes(q);
                                                });
                                        "
                                        class="block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                                    >

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            onclick="
                                                this.closest('form')
                                                    .querySelectorAll('[data-campus-contact-option]:not([hidden]) input[type=checkbox]')
                                                    .forEach((box) => box.checked = true)
                                            "
                                            class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-600"
                                        >
                                            Select visible
                                        </button>

                                        <button
                                            type="button"
                                            onclick="
                                                this.closest('form')
                                                    .querySelectorAll('input[name=&quot;campus_contact_ids[]&quot;]')
                                                    .forEach((box) => box.checked = false)
                                            "
                                            class="rounded-lg border border-emerald-300 bg-white px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100"
                                        >
                                            Clear selected
                                        </button>
                                    </div>

                                    <div
                                        class="mt-3 space-y-2 rounded-xl border border-emerald-200 bg-white p-2 dark:border-emerald-900 dark:bg-gray-950"
                                        style="max-height: 22rem; overflow-y: auto; overflow-x: hidden;"
                                    >
                                        @forelse ($this->availableContactsForCenter($center) as $contact)
                                            <label
                                                data-campus-contact-option
                                                data-search-text="{{ $this->contactSearchText($contact) }}"
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm hover:bg-emerald-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-emerald-950"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="campus_contact_ids[]"
                                                    value="{{ $contact->id }}"
                                                    class="mt-1 h-5 w-5 shrink-0 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                                >

                                                <span class="min-w-0">
                                                    <span class="block break-words font-bold text-gray-900 dark:text-white">
                                                        {{ $this->contactName($contact) }}
                                                    </span>

                                                    <span class="mt-1 block break-words text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $this->contactSchoolName($contact) ?: 'School not recorded' }}
                                                        · {{ $this->contactField($contact, 'locality') ?: 'Locality not recorded' }}
                                                    </span>

                                                    <span class="mt-1 block break-words text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $this->contactField($contact, 'course_strand') ?: 'Course not recorded' }}
                                                        · {{ $this->contactField($contact, 'grade_level') ?: 'Year level not recorded' }}
                                                    </span>
                                                </span>
                                            </label>
                                        @empty
                                            <p class="rounded-lg border border-dashed border-emerald-300 p-4 text-center text-sm text-emerald-700 dark:border-emerald-800 dark:text-emerald-200">
                                                No available Campus Contacts for this Student Center.
                                            </p>
                                        @endforelse
                                    </div>

                                    <button
                                        type="submit"
                                        class="mt-4 flex w-full justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500"
                                    >
                                        Add Selected Campus Contacts
                                    </button>
                                </form>
                            </details>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    No Student Centers found yet.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
