<x-filament-panels::page>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @php
        $terms = $this->terms();
        $archivedTerms = $this->archivedTerms();
        $selectedTerm = $this->selectedTerm();
        $copySourceTerms = $this->copySourceTerms();
        $groupedMembers = $this->groupedMembers();
        $availablePeople = $this->availablePeople();
        $summary = $this->summary();
    @endphp

    <div class="min-w-0 space-y-6">

        {{-- Header --}}
        <div class="min-w-0 overflow-hidden rounded-2xl border border-primary-200 bg-primary-50 p-5 shadow-sm dark:border-primary-900 dark:bg-primary-950 sm:p-6">
            <p class="text-sm font-bold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Campus Work
            </p>

            <h2 class="mt-2 break-words text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                Student Nucleus
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Students are manually assigned to the nucleus. School, locality, course,
                grade or year level, and contact information are automatically taken from
                the People Database.
            </p>
        </div>


        {{-- Success messages --}}

        @if (session('campus_work_term_created'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Academic term created successfully.</p>
            </div>
        @endif

        @if (session('campus_work_term_activated'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Active academic term updated.</p>
            </div>
        @endif

        @if (session('campus_work_term_members_copied'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">
                    {{ session('campus_work_term_members_added', 0) }} student(s) copied into this term.
                </p>

                <p class="mt-1 text-sm">
                    Spiritual Condition records were not copied.
                </p>
            </div>
        @endif

        @if (session('campus_work_term_archived'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">Academic term archived.</p>
            </div>
        @endif

        @if (session('campus_work_term_restored'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Academic term restored.</p>
            </div>
        @endif

        @if (session('student_nucleus_members_saved'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">
                    {{ session('student_nucleus_members_added', 0) }} student(s) added to the Student Nucleus.
                </p>
            </div>
        @endif

        @if (session('student_nucleus_spiritual_condition_saved'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Spiritual condition updated.</p>
            </div>
        @endif

        @if (session('student_nucleus_member_removed'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">Student removed from the Student Nucleus.</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Please fix the following:</p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Manual student allocation --}}
        <details class="min-w-0 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
            <summary class="cursor-pointer px-4 py-4 text-lg font-bold text-emerald-900 hover:bg-emerald-100 dark:text-emerald-100 dark:hover:bg-emerald-900 sm:px-6">
                Add Students to the Nucleus
            </summary>

            <div class="border-t border-emerald-200 p-4 dark:border-emerald-900 sm:p-6">
                <p class="text-sm text-emerald-700 dark:text-emerald-200">
                    Search and select only the students who are actually part of the Student Nucleus.
                </p>

                <form
                method="POST"
                action="{{ route('quezonprovinceactivities.campus-work.student-nucleus.store') }}"
                class="mt-5 min-w-0 space-y-4"
            >
                @csrf

                @if ($selectedTerm)
                    <input
                        type="hidden"
                        name="campus_work_term_id"
                        value="{{ $selectedTerm->id }}"
                    >
                @endif

                <div class="min-w-0">
                    <label
                        for="student_nucleus_search"
                        class="block text-sm font-semibold text-emerald-900 dark:text-emerald-100"
                    >
                        Search people
                    </label>

                    <input
                        id="student_nucleus_search"
                        type="search"
                        placeholder="Search name, school, locality, course, or year level..."
                        oninput="
                            const q = this.value.toLowerCase();

                            this.closest('form')
                                .querySelectorAll('[data-student-option]')
                                .forEach((card) => {
                                    card.hidden = ! card.dataset.searchText.includes(q);
                                });
                        "
                        class="mt-2 block w-full min-w-0 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-base text-gray-900 shadow-sm dark:border-emerald-900 dark:bg-gray-950 dark:text-gray-100"
                    >

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            type="button"
                            onclick="
                                this.closest('form')
                                    .querySelectorAll('[data-student-option]:not([hidden]) input[type=checkbox]')
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
                                    .querySelectorAll('input[name=&quot;person_ids[]&quot;]')
                                    .forEach((box) => box.checked = false)
                            "
                            class="rounded-lg border border-emerald-300 bg-white px-3 py-2 text-xs font-bold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100"
                        >
                            Clear selected
                        </button>
                    </div>

                    <div
                        class="mt-3 min-w-0 space-y-2 rounded-xl border border-emerald-200 bg-white p-2 dark:border-emerald-900 dark:bg-gray-950"
                        style="max-height: 24rem; overflow-y: auto; overflow-x: hidden;"
                    >
                        @forelse ($availablePeople as $person)
                            @php
                                $education = $person->educationProfile;

                                $searchText = \Illuminate\Support\Str::lower(
                                    collect([
                                        $person->display_name,
                                        $person->locality,
                                        $person->contact_number,
                                        $person->email,
                                        $education?->school?->name,
                                        $education?->course_strand,
                                        $education?->grade_level,
                                    ])->filter()->implode(' ')
                                );
                            @endphp

                            <label
                                data-student-option
                                data-search-text="{{ $searchText }}"
                                class="flex w-full min-w-0 cursor-pointer items-start gap-3 overflow-hidden rounded-xl border border-gray-200 bg-gray-50 p-3 hover:bg-emerald-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-emerald-950"
                            >
                                <input
                                    type="checkbox"
                                    name="person_ids[]"
                                    value="{{ $person->id }}"
                                    class="mt-1 h-5 w-5 shrink-0 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                >

                                <span class="min-w-0 flex-1 overflow-hidden">
                                    <span class="block break-words font-bold text-gray-900 dark:text-white">
                                        {{ $person->display_name }}
                                    </span>

                                    <span class="mt-1 block break-words text-xs text-gray-500 dark:text-gray-400">
                                        {{ $education?->school?->name ?: 'School not recorded' }}
                                        · {{ $person->locality ?: 'No locality' }}
                                        · {{ $education?->course_strand ?: 'No course recorded' }}
                                        · {{ $education?->grade_level ?: 'No year level recorded' }}
                                    </span>
                                </span>
                            </label>
                        @empty
                            <p class="rounded-xl border border-dashed border-emerald-300 p-5 text-center text-sm text-emerald-700 dark:border-emerald-800 dark:text-emerald-200">
                                Everyone in the People Database is already allocated to the Student Nucleus.
                            </p>
                        @endforelse
                    </div>
                </div>

                <button
                    type="submit"
                    class="flex w-full justify-center rounded-xl bg-emerald-600 px-5 py-3 text-base font-bold text-white hover:bg-emerald-500 sm:w-auto"
                >
                    Add Selected Students
                </button>
            </form>
            </div>
        </details>

        {{-- Academic Term Management --}}
        <details class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <summary class="cursor-pointer px-5 py-4 text-base font-bold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-gray-800">
                Manage Academic Terms
            </summary>

            <div class="space-y-6 border-t border-gray-200 p-5 dark:border-gray-700">

                {{-- Create new term --}}
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
                    <h3 class="font-bold text-emerald-900 dark:text-emerald-100">
                        Create New Academic Term
                    </h3>

                    <form
                        method="POST"
                        action="{{ route('quezonprovinceactivities.campus-work.terms.store') }}"
                        class="mt-4 grid gap-4 md:grid-cols-2"
                    >
                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                                Academic Year
                            </label>

                            <input
                                type="text"
                                name="academic_year"
                                placeholder="2026-2027"
                                pattern="\d{4}-\d{4}"
                                required
                                class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                                Semester
                            </label>

                            <select
                                name="semester"
                                required
                                class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer Term">Summer Term</option>
                            </select>
                        </div>

                        <label class="flex items-center gap-3 md:col-span-2">
                            <input
                                type="checkbox"
                                name="set_active"
                                value="1"
                                class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                            >

                            <span class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                Set this as the active academic term immediately
                            </span>
                        </label>

                        <div class="md:col-span-2">
                            <button
                                type="submit"
                                class="w-full rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500 sm:w-auto"
                            >
                                Create Academic Term
                            </button>
                        </div>
                    </form>
                </div>

                @if ($selectedTerm)
                    {{-- Current selected term actions --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                            Selected Academic Term
                        </p>

                        <h3 class="mt-1 break-words text-lg font-bold text-gray-900 dark:text-white">
                            AY {{ $selectedTerm->academic_year }}
                            · {{ $selectedTerm->semester }}
                        </h3>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if (! $selectedTerm->is_active && ! $selectedTerm->is_archived)
                                <form
                                    method="POST"
                                    action="{{ route('quezonprovinceactivities.campus-work.terms.activate', $selectedTerm) }}"
                                    onsubmit="return confirm('Set this as the active academic term?');"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-500"
                                    >
                                        Set as Active Term
                                    </button>
                                </form>
                            @endif

                            @if (! $selectedTerm->is_active && ! $selectedTerm->is_archived)
                                <form
                                    method="POST"
                                    action="{{ route('quezonprovinceactivities.campus-work.terms.archive', $selectedTerm) }}"
                                    onsubmit="return confirm('Archive this academic term? Historical Student Nucleus data will be preserved.');"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-bold text-white hover:bg-amber-500"
                                    >
                                        Archive Term
                                    </button>
                                </form>
                            @endif

                            @if ($selectedTerm->is_archived)
                                <form
                                    method="POST"
                                    action="{{ route('quezonprovinceactivities.campus-work.terms.restore', $selectedTerm) }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-500"
                                    >
                                        Restore Term
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Copy student allocations --}}
                    @if (! $selectedTerm->is_archived && $copySourceTerms->isNotEmpty())
                        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950">
                            <h3 class="font-bold text-sky-900 dark:text-sky-100">
                                Copy Students from Another Term
                            </h3>

                            <p class="mt-1 text-sm text-sky-700 dark:text-sky-200">
                                Copies only Student Nucleus membership. Spiritual Condition records will remain blank.
                            </p>

                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.campus-work.terms.copy-members', $selectedTerm) }}"
                                class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                                onsubmit="return confirm('Copy students into the selected academic term? Existing students will not be duplicated.');"
                            >
                                @csrf

                                <div class="min-w-0 flex-1">
                                    <label class="block text-sm font-bold text-sky-900 dark:text-sky-100">
                                        Copy from
                                    </label>

                                    <select
                                        name="source_term_id"
                                        required
                                        class="mt-2 block w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-sky-900 dark:bg-gray-950 dark:text-white"
                                    >
                                        <option value="">Choose academic term...</option>

                                        @foreach ($copySourceTerms as $sourceTerm)
                                            <option value="{{ $sourceTerm->id }}">
                                                AY {{ $sourceTerm->academic_year }}
                                                · {{ $sourceTerm->semester }}
                                                · {{ $sourceTerm->student_nucleus_memberships_count }} student(s)
                                                @if ($sourceTerm->is_archived)
                                                    · Archived
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <button
                                    type="submit"
                                    class="rounded-xl bg-sky-600 px-5 py-3 text-sm font-bold text-white hover:bg-sky-500"
                                >
                                    Copy Students
                                </button>
                            </form>
                        </div>
                    @endif
                @endif

                {{-- Archived terms --}}
                @if ($archivedTerms->isNotEmpty())
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            Archived Academic Terms
                        </h3>

                        <div class="mt-4 space-y-2">
                            @foreach ($archivedTerms as $term)
                                <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="break-words font-bold text-gray-900 dark:text-white">
                                            AY {{ $term->academic_year }}
                                            · {{ $term->semester }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $term->student_nucleus_memberships_count }} Student Nucleus member(s)
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ $this->termUrl($term) }}"
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                        >
                                            View
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('quezonprovinceactivities.campus-work.terms.restore', $term) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-lg bg-sky-600 px-3 py-2 text-xs font-bold text-white hover:bg-sky-500"
                                            >
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </details>

        {{-- Student Nucleus Report Summary --}}
        @if ($selectedTerm)
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                            Student Nucleus Report Summary
                        </p>

                        <h3 class="mt-1 break-words text-lg font-bold text-gray-900 dark:text-white">
                            AY {{ $selectedTerm->academic_year }}
                            · {{ $selectedTerm->semester }}
                        </h3>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a
                            href="{{ route(
                                'quezonprovinceactivities.campus-work.student-nucleus.print',
                                $selectedTerm
                            ) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center justify-center rounded-lg bg-gray-800 px-4 py-2 text-sm font-bold text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                        >
                            Print
                        </a>

                        <a
                            href="{{ route(
                                'quezonprovinceactivities.campus-work.student-nucleus.export',
                                $selectedTerm
                            ) }}"
                            class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-500"
                        >
                            Export CSV
                        </a>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-900 dark:bg-primary-950">
                        <p class="text-xs font-bold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                            Students
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $summary['students'] }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950">
                        <p class="text-xs font-bold uppercase tracking-wide text-sky-600 dark:text-sky-300">
                            Schools
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $summary['schools'] }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950">
                        <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                            Localities
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $summary['localities'] }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">
                            With Spiritual Condition
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $summary['with_spiritual_condition'] }}
                        </p>
                    </div>
                </div>

                <details class="mt-4 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-950">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-gray-900 dark:text-white">
                        Data Completeness
                    </summary>

                    <div class="grid gap-3 border-t border-gray-200 p-4 dark:border-gray-700 sm:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Missing Course
                            </p>

                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                                {{ $summary['missing_course'] }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Missing Year Level
                            </p>

                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                                {{ $summary['missing_year_level'] }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Missing Facebook Link
                            </p>

                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                                {{ $summary['missing_contact'] }}
                            </p>
                        </div>
                    </div>
                </details>
            </div>
        @endif

        {{-- Academic Year and Semester --}}
        <div class="min-w-0 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-900 dark:bg-amber-950 sm:p-6">
            <div class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                        Academic Term
                    </p>

                    @if ($selectedTerm)
                        <h3 class="mt-1 break-words text-xl font-bold text-gray-900 dark:text-white">
                            AY {{ $selectedTerm->academic_year }}
                            · {{ $selectedTerm->semester }}
                        </h3>

                        @if ($selectedTerm->is_active)
                            <span class="mt-2 inline-flex rounded-full bg-green-600 px-3 py-1 text-xs font-bold text-white">
                                Active Term
                            </span>
                        @else
                            <span class="mt-2 inline-flex rounded-full bg-gray-600 px-3 py-1 text-xs font-bold text-white">
                                Historical Term
                            </span>
                        @endif
                    @else
                        <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                            No academic term available
                        </h3>
                    @endif
                </div>

                @if ($terms->isNotEmpty())
                    <div class="flex min-w-0 flex-col items-start gap-2 lg:items-end">
                        <div class="flex min-w-0 flex-wrap gap-2 lg:justify-end">
                            @foreach ($terms as $term)
                                <a
                                    href="{{ $this->termUrl($term) }}"
                                    @class([
                                        'rounded-full px-4 py-2 text-sm font-bold transition',
                                        'bg-amber-600 text-white' => $selectedTerm?->id === $term->id,
                                        'border border-amber-300 bg-white text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-gray-900 dark:text-amber-200 dark:hover:bg-amber-950' => $selectedTerm?->id !== $term->id,
                                    ])
                                >
                                    AY {{ $term->academic_year }}
                                    · {{ $term->semester }}

                                    @if ($term->is_active)
                                        · Active
                                    @endif
                                </a>
                            @endforeach
                        </div>

                    </div>
                @endif
            </div>
        </div>





        {{-- Student Nucleus grouped by school --}}
        @forelse ($groupedMembers as $school => $members)
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="border-b border-sky-200 bg-sky-100 px-4 py-3 dark:border-sky-900 dark:bg-sky-950">
                    <h3 class="break-words text-center text-base font-bold text-gray-900 dark:text-white">
                        {{ $school }}
                    </h3>
                </div>

                {{-- Desktop table --}}
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="w-14 px-3 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">
                                    No.
                                </th>

                                <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                    Name of Student
                                </th>

                                <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                    Locality
                                </th>

                                <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                    Course
                                </th>

                                <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                    Grade/Year Level
                                </th>

                                <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                    Facebook Link
                                </th>

                                <th class="min-w-[260px] px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                    Spiritual Condition
                                </th>

                                <th class="w-24 px-3 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach ($members as $membership)
                                @php
                                    $person = $membership->person;
                                    $education = $person?->educationProfile;
                                @endphp

                                <tr class="align-top">
                                    <td class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="max-w-[240px] break-words px-3 py-3 font-semibold">
                                        @if ($person)
                                            <a
                                                href="{{ $this->personUrl($person) }}"
                                                class="text-primary-600 hover:underline dark:text-primary-400"
                                            >
                                                {{ $person->display_name }}
                                            </a>
                                        @else
                                            Unknown person
                                        @endif
                                    </td>

                                    <td class="break-words px-3 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $person?->locality ?: 'Not recorded' }}
                                    </td>

                                    <td class="break-words px-3 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $education?->course_strand ?: 'Not recorded' }}
                                    </td>

                                    <td class="break-words px-3 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $education?->grade_level ?: 'Not recorded' }}
                                    </td>

                                    <td class="break-words px-3 py-3 text-gray-600 dark:text-gray-300">
                                        @php
                                            $facebookValue = trim((string) ($person?->facebook_account ?? ''));

                                            $facebookUrl = null;

                                            if ($facebookValue !== '') {
                                                if (\Illuminate\Support\Str::startsWith($facebookValue, ['http://', 'https://'])) {
                                                    $facebookUrl = $facebookValue;
                                                } elseif (\Illuminate\Support\Str::startsWith($facebookValue, ['facebook.com/', 'www.facebook.com/'])) {
                                                    $facebookUrl = 'https://' . $facebookValue;
                                                } elseif (\Illuminate\Support\Str::startsWith($facebookValue, '@')) {
                                                    $facebookUrl = 'https://facebook.com/' . ltrim($facebookValue, '@');
                                                } else {
                                                    $facebookUrl = 'https://facebook.com/' . $facebookValue;
                                                }
                                            }
                                        @endphp

                                        @if ($facebookUrl)
                                            <a
                                                href="{{ $facebookUrl }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="break-all text-primary-600 hover:underline dark:text-primary-400"
                                            >
                                                {{ $facebookValue }}
                                            </a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">
                                                Not recorded
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-3">
                                        <div
                                            x-data="{
                                                viewOpen: false,
                                                editOpen: false
                                            }"
                                            class="flex flex-wrap gap-2"
                                        >
                                            {{-- View button --}}
                                            <button
                                                type="button"
                                                @click="viewOpen = true"
                                                class="rounded-lg border border-sky-300 bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-200"
                                            >
                                                View
                                            </button>

                                            {{-- Edit button --}}
                                            <button
                                                type="button"
                                                @click="editOpen = true"
                                                class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-primary-500"
                                            >
                                                Edit
                                            </button>

                                            {{-- View Spiritual Condition Modal --}}
                                            <template x-teleport="body">
                                            <div
                                                x-cloak
                                                x-show="viewOpen"
                                                x-transition.opacity
                                                @keydown.escape.window="viewOpen = false"
                                                class="fixed inset-0 flex items-center justify-center bg-black/70 p-4" style="z-index: 9999;"
                                                role="dialog"
                                                aria-modal="true"
                                            >
                                                <div
                                                    x-show="viewOpen"
                                                    x-transition
                                                    @click.outside="viewOpen = false"
                                                    class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
                                                >
                                                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                                                        <div class="min-w-0">
                                                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                                Spiritual Condition
                                                            </p>

                                                            <h3 class="mt-1 break-words text-lg font-bold text-gray-900 dark:text-white">
                                                                {{ $person?->display_name ?? 'Unknown person' }}
                                                            </h3>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            @click="viewOpen = false"
                                                            class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-bold text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                                                        >
                                                            ✕
                                                        </button>
                                                    </div>

                                                    <div class="p-5">
                                                        @if (filled($membership->spiritual_condition))
                                                            <div class="whitespace-pre-wrap break-words rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm leading-relaxed text-gray-800 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                                                                {{ $membership->spiritual_condition }}
                                                            </div>
                                                        @else
                                                            <div class="rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                                                No spiritual condition recorded yet.
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="flex justify-end border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                                                        <button
                                                            type="button"
                                                            @click="viewOpen = false"
                                                            class="rounded-lg bg-gray-700 px-4 py-2 text-sm font-bold text-white hover:bg-gray-600"
                                                        >
                                                            Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            </template>

                                            {{-- Edit Spiritual Condition Modal --}}
                                            <template x-teleport="body">
                                            <div
                                                x-cloak
                                                x-show="editOpen"
                                                x-transition.opacity
                                                @keydown.escape.window="editOpen = false"
                                                class="fixed inset-0 flex items-center justify-center bg-black/70 p-4" style="z-index: 9999;"
                                                role="dialog"
                                                aria-modal="true"
                                            >
                                                <div
                                                    x-show="editOpen"
                                                    x-transition
                                                    @click.outside="editOpen = false"
                                                    class="w-full max-w-xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
                                                >
                                                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                                                        <div class="min-w-0">
                                                            <p class="text-xs font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                                                                Edit Spiritual Condition
                                                            </p>

                                                            <h3 class="mt-1 break-words text-lg font-bold text-gray-900 dark:text-white">
                                                                {{ $person?->display_name ?? 'Unknown person' }}
                                                            </h3>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            @click="editOpen = false"
                                                            class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-bold text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                                                        >
                                                            ✕
                                                        </button>
                                                    </div>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('quezonprovinceactivities.campus-work.student-nucleus.update', $membership) }}"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <div class="p-5">
                                                            <label
                                                                for="spiritual_condition_{{ $membership->id }}"
                                                                class="block text-sm font-bold text-gray-700 dark:text-gray-200"
                                                            >
                                                                Spiritual Condition
                                                            </label>

                                                            <textarea
                                                                id="spiritual_condition_{{ $membership->id }}"
                                                                name="spiritual_condition"
                                                                rows="7"
                                                                placeholder="Enter spiritual condition..."
                                                                class="mt-2 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                            >{{ $membership->spiritual_condition }}</textarea>
                                                        </div>

                                                        <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                                                            <button
                                                                type="button"
                                                                @click="editOpen = false"
                                                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                                                            >
                                                                Cancel
                                                            </button>

                                                            <button
                                                                type="submit"
                                                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-500"
                                                            >
                                                                Save Spiritual Condition
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            </template>
                                        </div>
                                    </td>

                                    <td class="px-3 py-3 text-right">
                                        @if (auth()->user()?->canDeleteRecords())
                                            <form
                                                method="POST"
                                                action="{{ route('quezonprovinceactivities.campus-work.student-nucleus.destroy', $membership) }}"
                                                onsubmit="return confirm('Remove this student from the Student Nucleus?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-500"
                                                >
                                                    Remove
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    No Student Nucleus members yet.
                </h3>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Use the Add Students section above to manually allocate students.
                </p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
