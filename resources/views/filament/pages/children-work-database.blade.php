<x-filament-panels::page>
@php

    /*
     * The Persons Database remains the source of truth.
     *
     * A child belongs on this page only when the existing
     * Church Profile automatic category is "Children".
     *
     * Do not maintain a second children table here.
     */
    $childrenBaseQuery = \App\Models\Person::query()
        ->whereHas(
            'churchProfile',
            fn ($query) => $query->where('category', 'Children')
        );

    $totalChildren =
        (clone $childrenBaseQuery)->count();

    $withoutBirthdateCount =
        (clone $childrenBaseQuery)
            ->whereNull('birthdate')
            ->count();

    $activeChildrenWorkCount =
        (clone $childrenBaseQuery)
            ->whereHas(
                'childrenWorkProfile',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
            )
            ->count();

    $profilesNotSetCount =
        (clone $childrenBaseQuery)
            ->whereDoesntHave(
                'childrenWorkProfile'
            )
            ->count();

    $childrenWorkLocalitiesCount =
        \App\Models\ChildrenWorkProfile::query()
            ->whereHas(
                'person.churchProfile',
                fn ($query) =>
                    $query->where(
                        'category',
                        'Children'
                    )
            )
            ->whereNotNull('locality_id')
            ->distinct()
            ->count('locality_id');

    /*
     * Existing Person filters.
     */
    $search =
        trim(
            (string) request(
                'search',
                ''
            )
        );

    $sex =
        trim(
            (string) request(
                'sex',
                ''
            )
        );

    $localityId =
        request()->integer(
            'locality_id'
        );

    /*
     * Children's Work profile filters.
     */
    $childrenWorkLocalityId =
        request()->integer(
            'children_work_locality_id'
        );

    $childrenWorkStatus =
        trim(
            (string) request(
                'children_work_status',
                ''
            )
        );

    $childrenWorkGroup =
        trim(
            (string) request(
                'children_work_group',
                ''
            )
        );

    $servingOneId =
        request()->integer(
            'serving_one_id'
        );

    $childrenQuery =
        \App\Models\Person::query()
            ->with([
                'churchProfile',
                'localityRecord',
                'household',
                'educationProfile.school',
                'parentRelationships.parent',
                'parentRelationships.gospelContact',
                'childrenWorkProfile.locality',
                'childrenWorkProfile.servingOne',
            ])
            ->whereHas(
                'churchProfile',
                fn ($query) =>
                    $query->where(
                        'category',
                        'Children'
                    )
            );

    /*
     * Search both canonical Person data and
     * Children's Work assignment data.
     */
    if ($search !== '') {
        $childrenQuery->where(
            function ($query) use ($search): void {
                $like =
                    '%' . $search . '%';

                $query
                    ->where(
                        'firstname',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'middlename',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'lastname',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'suffix',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'nickname',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'contact_number',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'locality',
                        'like',
                        $like
                    )
                    ->orWhereHas(
                        'localityRecord',
                        fn ($localityQuery) =>
                            $localityQuery->where(
                                'name',
                                'like',
                                $like
                            )
                    )
                    ->orWhereHas(
                        'childrenWorkProfile',
                        function (
                            $profileQuery
                        ) use (
                            $like
                        ): void {
                            $profileQuery
                                ->where(
                                    'group_name',
                                    'like',
                                    $like
                                )
                                ->orWhereHas(
                                    'locality',
                                    fn ($localityQuery) =>
                                        $localityQuery
                                            ->where(
                                                'name',
                                                'like',
                                                $like
                                            )
                                )
                                ->orWhereHas(
                                    'servingOne',
                                    function (
                                        $personQuery
                                    ) use (
                                        $like
                                    ): void {
                                        $personQuery
                                            ->where(
                                                'firstname',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'middlename',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'lastname',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'nickname',
                                                'like',
                                                $like
                                            );
                                    }
                                );
                        }
                    );
            }
        );
    }

    if (
        in_array(
            $sex,
            ['Male', 'Female'],
            true
        )
    ) {
        $childrenQuery->where(
            'sex',
            $sex
        );
    }

    /*
     * Person Locality.
     */
    if ($localityId > 0) {
        $childrenQuery->where(
            'locality_id',
            $localityId
        );
    }

    /*
     * Children's Work Locality.
     */
    if ($childrenWorkLocalityId > 0) {
        $childrenQuery->whereHas(
            'childrenWorkProfile',
            fn ($query) =>
                $query->where(
                    'locality_id',
                    $childrenWorkLocalityId
                )
        );
    }

    /*
     * Children's Work profile status.
     */
    if ($childrenWorkStatus === 'active') {
        $childrenQuery->whereHas(
            'childrenWorkProfile',
            fn ($query) =>
                $query->where(
                    'is_active',
                    true
                )
        );
    } elseif (
        $childrenWorkStatus === 'inactive'
    ) {
        $childrenQuery->whereHas(
            'childrenWorkProfile',
            fn ($query) =>
                $query->where(
                    'is_active',
                    false
                )
        );
    } elseif (
        $childrenWorkStatus === 'not_set'
    ) {
        $childrenQuery->whereDoesntHave(
            'childrenWorkProfile'
        );
    }

    /*
     * Group / Class.
     */
    if ($childrenWorkGroup !== '') {
        $childrenQuery->whereHas(
            'childrenWorkProfile',
            fn ($query) =>
                $query->where(
                    'group_name',
                    $childrenWorkGroup
                )
        );
    }

    /*
     * Serving One.
     */
    if ($servingOneId > 0) {
        $childrenQuery->whereHas(
            'childrenWorkProfile',
            fn ($query) =>
                $query->where(
                    'serving_one_id',
                    $servingOneId
                )
        );
    }

    $children =
        $childrenQuery
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->paginate(30)
            ->withQueryString();

    /*
     * Canonical Person Localities represented
     * by children.
     */
    $localities =
        \App\Models\Locality::query()
            ->whereHas(
                'people',
                fn ($query) =>
                    $query->whereHas(
                        'churchProfile',
                        fn ($profileQuery) =>
                            $profileQuery->where(
                                'category',
                                'Children'
                            )
                    )
            )
            ->orderBy('name')
            ->get();

    /*
     * Children's Work Localities currently
     * represented by Children's Work profiles.
     */
    $childrenWorkLocalities =
        \App\Models\Locality::query()
            ->whereIn(
                'id',
                \App\Models\ChildrenWorkProfile::query()
                    ->whereHas(
                        'person.churchProfile',
                        fn ($query) =>
                            $query->where(
                                'category',
                                'Children'
                            )
                    )
                    ->whereNotNull(
                        'locality_id'
                    )
                    ->select(
                        'locality_id'
                    )
            )
            ->orderBy('name')
            ->get();

    /*
     * Existing Children's Work groups/classes.
     */
    $childrenWorkGroups =
        \App\Models\ChildrenWorkProfile::query()
            ->whereHas(
                'person.churchProfile',
                fn ($query) =>
                    $query->where(
                        'category',
                        'Children'
                    )
            )
            ->whereNotNull(
                'group_name'
            )
            ->where(
                'group_name',
                '!=',
                ''
            )
            ->orderBy(
                'group_name'
            )
            ->distinct()
            ->pluck(
                'group_name'
            );

    /*
     * Serving One filter options.
     *
     * Use the same locality eligibility rule as the
     * Children's Work Profile editor:
     *
     * - if a Children's Work Locality filter is selected,
     *   show Persons from that Locality;
     * - otherwise show Persons from all active Localities
     *   in the configured primary province.
     *
     * Do not require the Person to already be assigned
     * as a Serving One, otherwise a new/empty setup gives
     * an unusable dropdown.
     */
    $servingOneLocalityIds =
        $childrenWorkLocalityId > 0
            ? [$childrenWorkLocalityId]
            : array_map(
                'intval',
                array_keys(
                    $this->childrenWorkLocalityOptions()
                )
            );

    $servingOnes =
        \App\Models\Person::query()
            ->whereIn(
                'locality_id',
                $servingOneLocalityIds
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

    /*
     * Parent / guardian identity can come from:
     * 1. an existing Person,
     * 2. a Gospel Contact,
     * 3. a legacy recorded name.
     */
    $parentLabel = function (
        \App\Models\Person $person,
        string $relationship
    ): string {
        $record = $person->parentRelationships
            ->firstWhere('relationship', $relationship);

        if (! $record) {
            return '—';
        }

        if ($record->parent) {
            return $record->parent->display_name;
        }

        if ($record->gospelContact) {
            return $record->gospelContact->display_name;
        }

        if (filled($record->parent_name)) {
            return trim((string) $record->parent_name);
        }

        return '—';
    };
@endphp

<div class="space-y-6">

    {{-- Introduction --}}
    <div
        class="
            rounded-2xl border border-gray-200 bg-white p-6 shadow-sm
            dark:border-gray-700 dark:bg-gray-900
        "
    >
        <div
            class="
                flex flex-col gap-4
                sm:flex-row sm:items-start sm:justify-between
            "
        >
            <div>
                <p
                    class="
                        text-sm font-semibold uppercase tracking-wide
                        text-pink-600 dark:text-pink-400
                    "
                >
                    Children's Work
                </p>

                <h2
                    class="
                        mt-1 text-2xl font-bold
                        text-gray-950 dark:text-white
                    "
                >
                    Children Database
                </h2>

                <p
                    class="
                        mt-2 max-w-3xl text-sm
                        text-gray-600 dark:text-gray-300
                    "
                >
                    This page automatically shows people whose
                    existing Church Profile category is
                    <strong>Children</strong>.
                    All records still come directly from the
                    Persons Database.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{
                        \App\Filament\Resources\People\PersonResource::getUrl(
                            'create',
                            ['source' => 'children-work']
                        )
                    }}"
                    class="
                        inline-flex items-center justify-center rounded-xl
                        bg-pink-600 px-4 py-2
                        text-sm font-semibold text-white shadow-sm
                        transition hover:bg-pink-500
                    "
                >
                    <x-heroicon-o-plus
                        class="mr-2 h-5 w-5"
                    />

                    Add Child
                </a>

                <a
                    href="{{ \App\Filament\Resources\People\PersonResource::getUrl('index') }}"
                    class="
                        inline-flex items-center justify-center rounded-xl
                        border border-gray-300 bg-white px-4 py-2
                        text-sm font-semibold text-gray-700 shadow-sm
                        transition hover:bg-gray-50
                        dark:border-gray-700 dark:bg-gray-950
                        dark:text-gray-200 dark:hover:bg-gray-800
                    "
                >
                    Open Persons Database
                </a>
            </div>
        </div>
    </div>

    @if ($editingChildId)
        @php
            $editingChild =
                \App\Models\Person::query()
                    ->find($editingChildId);
        @endphp

        <div
            class="
                rounded-2xl border border-pink-200
                bg-pink-50 p-6 shadow-sm
                dark:border-pink-900 dark:bg-pink-950/40
            "
        >
            <div
                class="
                    flex flex-col gap-3
                    sm:flex-row sm:items-start
                    sm:justify-between
                "
            >
                <div>
                    <p
                        class="
                            text-sm font-semibold uppercase
                            tracking-wide text-pink-700
                            dark:text-pink-300
                        "
                    >
                        Children's Work Profile
                    </p>

                    <h3
                        class="
                            mt-1 text-xl font-bold
                            text-gray-950 dark:text-white
                        "
                    >
                        {{
                            $editingChild?->display_name
                                ?? 'Child'
                        }}
                    </h3>

                    <p
                        class="
                            mt-1 text-sm
                            text-gray-600 dark:text-gray-300
                        "
                    >
                        Manage this child's Children's Work
                        assignment without changing the
                        master Person record.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="cancelChildrenWorkProfile"
                    class="
                        inline-flex items-center justify-center
                        rounded-xl border border-gray-300
                        bg-white px-4 py-2 text-sm
                        font-semibold text-gray-700 shadow-sm
                        hover:bg-gray-50
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-gray-200
                    "
                >
                    Cancel
                </button>
            </div>

            <form
                wire:submit="saveChildrenWorkProfile"
                class="
                    mt-6 grid gap-4
                    md:grid-cols-2
                "
            >
                <div>
                    <label
                        class="
                            mb-1 block text-sm font-semibold
                            text-gray-700 dark:text-gray-200
                        "
                    >
                        Children's Work Locality
                    </label>

                    <select
                        wire:model.live="childrenWorkLocalityId"
                        class="
                            block w-full rounded-xl
                            border-gray-300 bg-white
                            text-sm text-gray-950 shadow-sm
                            focus:border-primary-500
                            focus:ring-primary-500
                            dark:border-gray-700
                            dark:bg-gray-950
                            dark:text-white
                        "
                        required
                    >
                        <option value="">
                            Select locality
                        </option>

                        @foreach (
                            $this->childrenWorkLocalityOptions()
                            as $id => $name
                        )
                            <option value="{{ $id }}">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>

                    <p
                        class="
                            mt-1 text-xs
                            text-gray-500 dark:text-gray-400
                        "
                    >
                        Active localities from the configured
                        primary province.
                    </p>

                    @error('childrenWorkLocalityId')
                        <p
                            class="
                                mt-1 text-sm text-red-600
                                dark:text-red-400
                            "
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        class="
                            mb-1 block text-sm font-semibold
                            text-gray-700 dark:text-gray-200
                        "
                    >
                        Serving One
                    </label>

                    <select
                        wire:model="childrenWorkServingOneId"
                        class="
                            block w-full rounded-xl
                            border-gray-300 bg-white
                            text-sm text-gray-950 shadow-sm
                            focus:border-primary-500
                            focus:ring-primary-500
                            dark:border-gray-700
                            dark:bg-gray-950
                            dark:text-white
                        "
                    >
                        <option value="">
                            No Serving One assigned
                        </option>

                        @foreach (
                            $this->childrenWorkServingOneOptions()
                            as $id => $name
                        )
                            <option value="{{ $id }}">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>

                    <p
                        class="
                            mt-1 text-xs
                            text-gray-500 dark:text-gray-400
                        "
                    >
                        Choose any Person who serves with
                        or cares for this child.
                    </p>

                    @error('childrenWorkServingOneId')
                        <p
                            class="
                                mt-1 text-sm text-red-600
                                dark:text-red-400
                            "
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        class="
                            mb-1 block text-sm font-semibold
                            text-gray-700 dark:text-gray-200
                        "
                    >
                        Group / Class
                    </label>

                    <input
                        type="text"
                        wire:model="childrenWorkGroupName"
                        maxlength="100"
                        placeholder="e.g. Toddlers, Kinder, Group A"
                        class="
                            block w-full rounded-xl
                            border-gray-300 bg-white
                            text-sm text-gray-950 shadow-sm
                            focus:border-primary-500
                            focus:ring-primary-500
                            dark:border-gray-700
                            dark:bg-gray-950
                            dark:text-white
                        "
                    >

                    @error('childrenWorkGroupName')
                        <p
                            class="
                                mt-1 text-sm text-red-600
                                dark:text-red-400
                            "
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div
                    class="
                        flex items-center
                        rounded-xl border border-gray-200
                        bg-white px-4 py-3
                        dark:border-gray-700
                        dark:bg-gray-950
                    "
                >
                    <label
                        class="
                            flex cursor-pointer items-center
                            gap-3 text-sm font-semibold
                            text-gray-700 dark:text-gray-200
                        "
                    >
                        <input
                            type="checkbox"
                            wire:model="childrenWorkIsActive"
                            class="
                                rounded border-gray-300
                                text-primary-600
                                focus:ring-primary-500
                                dark:border-gray-700
                            "
                        >

                        Active in Children's Work
                    </label>
                </div>

                <div class="md:col-span-2">
                    <label
                        class="
                            mb-1 block text-sm font-semibold
                            text-gray-700 dark:text-gray-200
                        "
                    >
                        Notes
                    </label>

                    <textarea
                        wire:model="childrenWorkNotes"
                        rows="4"
                        placeholder="Optional Children's Work notes..."
                        class="
                            block w-full rounded-xl
                            border-gray-300 bg-white
                            text-sm text-gray-950 shadow-sm
                            focus:border-primary-500
                            focus:ring-primary-500
                            dark:border-gray-700
                            dark:bg-gray-950
                            dark:text-white
                        "
                    ></textarea>

                    @error('childrenWorkNotes')
                        <p
                            class="
                                mt-1 text-sm text-red-600
                                dark:text-red-400
                            "
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div
                    class="
                        flex flex-wrap gap-2
                        md:col-span-2
                    "
                >
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="
                            inline-flex items-center
                            justify-center rounded-xl
                            bg-pink-600 px-4 py-2
                            text-sm font-semibold text-white
                            shadow-sm hover:bg-pink-500
                            disabled:opacity-60
                        "
                    >
                        Save Profile
                    </button>

                    <button
                        type="button"
                        wire:click="cancelChildrenWorkProfile"
                        class="
                            inline-flex items-center
                            justify-center rounded-xl
                            border border-gray-300
                            bg-white px-4 py-2
                            text-sm font-semibold
                            text-gray-700 shadow-sm
                            hover:bg-gray-50
                            dark:border-gray-700
                            dark:bg-gray-950
                            dark:text-gray-200
                        "
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Summary cards --}}
    <div
        class="
            grid gap-4
            sm:grid-cols-2 xl:grid-cols-4
        "
    >
        <div
            class="
                rounded-2xl border border-gray-200
                bg-white p-5 shadow-sm
                dark:border-gray-700 dark:bg-gray-900
            "
        >
            <p
                class="
                    text-sm font-medium
                    text-gray-500 dark:text-gray-400
                "
            >
                Total Children
            </p>

            <p
                class="
                    mt-2 text-3xl font-bold
                    text-gray-950 dark:text-white
                "
            >
                {{ number_format($totalChildren) }}
            </p>
        </div>

        <div
            class="
                rounded-2xl border border-gray-200
                bg-white p-5 shadow-sm
                dark:border-gray-700 dark:bg-gray-900
            "
        >
            <p
                class="
                    text-sm font-medium
                    text-gray-500 dark:text-gray-400
                "
            >
                Active in Children's Work
            </p>

            <p
                class="
                    mt-2 text-3xl font-bold
                    text-emerald-600 dark:text-emerald-400
                "
            >
                {{
                    number_format(
                        $activeChildrenWorkCount
                    )
                }}
            </p>
        </div>

        <div
            class="
                rounded-2xl border border-gray-200
                bg-white p-5 shadow-sm
                dark:border-gray-700 dark:bg-gray-900
            "
        >
            <p
                class="
                    text-sm font-medium
                    text-gray-500 dark:text-gray-400
                "
            >
                Profiles Not Set
            </p>

            <p
                class="
                    mt-2 text-3xl font-bold
                    text-amber-600 dark:text-amber-400
                "
            >
                {{
                    number_format(
                        $profilesNotSetCount
                    )
                }}
            </p>
        </div>

        <div
            class="
                rounded-2xl border border-gray-200
                bg-white p-5 shadow-sm
                dark:border-gray-700 dark:bg-gray-900
            "
        >
            <p
                class="
                    text-sm font-medium
                    text-gray-500 dark:text-gray-400
                "
            >
                Localities Represented
            </p>

            <p
                class="
                    mt-2 text-3xl font-bold
                    text-gray-950 dark:text-white
                "
            >
                {{
                    number_format(
                        $childrenWorkLocalitiesCount
                    )
                }}
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <form
        method="GET"
        class="
            rounded-2xl border border-gray-200
            bg-white p-5 shadow-sm
            dark:border-gray-700 dark:bg-gray-900
        "
    >
        <div>
            <h3
                class="
                    text-base font-bold
                    text-gray-950 dark:text-white
                "
            >
                Filter Children
            </h3>

            <p
                class="
                    mt-1 text-sm
                    text-gray-500 dark:text-gray-400
                "
            >
                Person Locality and Children's Work Locality
                are separate filters.
            </p>
        </div>

        <div
            class="
                mt-4 grid gap-4
                md:grid-cols-2 xl:grid-cols-4
            "
        >
            <div class="xl:col-span-2">
                <label
                    for="children-search"
                    class="
                        mb-1 block text-sm font-semibold
                        text-gray-700 dark:text-gray-200
                    "
                >
                    Search
                </label>

                <input
                    id="children-search"
                    name="search"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Name, contact, locality, group, serving one..."
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500
                        focus:ring-primary-500
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-white
                    "
                >
            </div>

            <div>
                <label
                    for="children-sex"
                    class="
                        mb-1 block text-sm font-semibold
                        text-gray-700 dark:text-gray-200
                    "
                >
                    Sex
                </label>

                <select
                    id="children-sex"
                    name="sex"
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500
                        focus:ring-primary-500
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-white
                    "
                >
                    <option value="">
                        All
                    </option>

                    <option
                        value="Male"
                        @selected($sex === 'Male')
                    >
                        Male
                    </option>

                    <option
                        value="Female"
                        @selected($sex === 'Female')
                    >
                        Female
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="children-locality"
                    class="
                        mb-1 block text-sm font-semibold
                        text-gray-700 dark:text-gray-200
                    "
                >
                    Person Locality
                </label>

                <select
                    id="children-locality"
                    name="locality_id"
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500
                        focus:ring-primary-500
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-white
                    "
                >
                    <option value="">
                        All Person Localities
                    </option>

                    @foreach ($localities as $locality)
                        <option
                            value="{{ $locality->id }}"
                            @selected(
                                $localityId
                                ===
                                (int) $locality->id
                            )
                        >
                            {{ $locality->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="children-work-locality"
                    class="
                        mb-1 block text-sm font-semibold
                        text-gray-700 dark:text-gray-200
                    "
                >
                    Children's Work Locality
                </label>

                <select
                    id="children-work-locality"
                    name="children_work_locality_id"
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500
                        focus:ring-primary-500
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-white
                    "
                >
                    <option value="">
                        All CW Localities
                    </option>

                    @foreach (
                        $childrenWorkLocalities
                        as $locality
                    )
                        <option
                            value="{{ $locality->id }}"
                            @selected(
                                $childrenWorkLocalityId
                                ===
                                (int) $locality->id
                            )
                        >
                            {{ $locality->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="children-work-status"
                    class="
                        mb-1 block text-sm font-semibold
                        text-gray-700 dark:text-gray-200
                    "
                >
                    CW Status
                </label>

                <select
                    id="children-work-status"
                    name="children_work_status"
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500
                        focus:ring-primary-500
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-white
                    "
                >
                    <option value="">
                        All Statuses
                    </option>

                    <option
                        value="active"
                        @selected(
                            $childrenWorkStatus
                            === 'active'
                        )
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        @selected(
                            $childrenWorkStatus
                            === 'inactive'
                        )
                    >
                        Inactive
                    </option>

                    <option
                        value="not_set"
                        @selected(
                            $childrenWorkStatus
                            === 'not_set'
                        )
                    >
                        Profile Not Set
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="children-work-group"
                    class="
                        mb-1 block text-sm font-semibold
                        text-gray-700 dark:text-gray-200
                    "
                >
                    Group / Class
                </label>

                <select
                    id="children-work-group"
                    name="children_work_group"
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500
                        focus:ring-primary-500
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-white
                    "
                >
                    <option value="">
                        All Groups / Classes
                    </option>

                    @foreach (
                        $childrenWorkGroups
                        as $groupName
                    )
                        <option
                            value="{{ $groupName }}"
                            @selected(
                                $childrenWorkGroup
                                === $groupName
                            )
                        >
                            {{ $groupName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="children-serving-one"
                    class="
                        mb-1 block text-sm font-semibold
                        text-gray-700 dark:text-gray-200
                    "
                >
                    Serving One
                </label>

                <select
                    id="children-serving-one"
                    name="serving_one_id"
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500
                        focus:ring-primary-500
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-white
                    "
                >
                    <option value="">
                        All Serving Ones
                    </option>

                    @foreach (
                        $servingOnes
                        as $servingOne
                    )
                        <option
                            value="{{ $servingOne->id }}"
                            @selected(
                                $servingOneId
                                ===
                                (int) $servingOne->id
                            )
                        >
                            {{
                                $servingOne->display_name
                            }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div
            class="
                mt-4 flex flex-wrap gap-2
            "
        >
            <button
                type="submit"
                class="
                    inline-flex items-center justify-center
                    rounded-xl bg-primary-600
                    px-4 py-2 text-sm font-semibold
                    text-white shadow-sm
                    transition hover:bg-primary-500
                "
            >
                Apply Filters
            </button>

            @if (
                $search !== ''
                || $sex !== ''
                || $localityId > 0
                || $childrenWorkLocalityId > 0
                || $childrenWorkStatus !== ''
                || $childrenWorkGroup !== ''
                || $servingOneId > 0
            )
                <a
                    href="{{ \App\Filament\Pages\ChildrenWorkDatabase::getUrl() }}"
                    class="
                        inline-flex items-center justify-center
                        rounded-xl border border-gray-300
                        bg-white px-4 py-2
                        text-sm font-semibold
                        text-gray-700 shadow-sm
                        transition hover:bg-gray-50
                        dark:border-gray-700
                        dark:bg-gray-950
                        dark:text-gray-200
                        dark:hover:bg-gray-800
                    "
                >
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    {{-- Database table --}}
    <div
        class="
            overflow-hidden rounded-2xl
            border border-gray-200 bg-white shadow-sm
            dark:border-gray-700 dark:bg-gray-900
        "
    >
        <div
            class="
                flex flex-col gap-2 border-b border-gray-200
                px-6 py-5
                sm:flex-row sm:items-center sm:justify-between
                dark:border-gray-700
            "
        >
            <div>
                <h3
                    class="
                        text-lg font-bold
                        text-gray-950 dark:text-white
                    "
                >
                    Children
                </h3>

                <p
                    class="
                        mt-1 text-sm
                        text-gray-500 dark:text-gray-400
                    "
                >
                    Showing
                    {{ number_format($children->firstItem() ?? 0) }}
                    –
                    {{ number_format($children->lastItem() ?? 0) }}
                    of
                    {{ number_format($children->total()) }}
                    matching records.
                </p>
            </div>
        </div>

        @if ($children->isEmpty())
            <div class="px-6 py-14 text-center">
                <x-heroicon-o-user-group
                    class="
                        mx-auto h-10 w-10
                        text-gray-400
                    "
                />

                <h3
                    class="
                        mt-3 font-semibold
                        text-gray-900 dark:text-white
                    "
                >
                    No children found
                </h3>

                <p
                    class="
                        mt-1 text-sm
                        text-gray-500 dark:text-gray-400
                    "
                >
                    No Person record currently matches these filters
                    with an automatic category of Children.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table
                    class="
                        min-w-full divide-y divide-gray-200
                        dark:divide-gray-700
                    "
                >
                    <thead
                        class="
                            bg-gray-50
                            dark:bg-gray-950
                        "
                    >
                        <tr>
                            <th
                                class="
                                    px-6 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Name
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Age
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Sex
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Birthdate
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Person Locality
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Children's Work Locality
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Group / Class
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Serving One
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                CW Status
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Household
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                School
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Grade Level
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Father
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Mother
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Guardian
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Contact
                            </th>

                            <th
                                class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Category
                            </th>

                            <th
                                class="
                                    px-6 py-3 text-right
                                    text-xs font-semibold uppercase
                                    tracking-wide text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="
                            divide-y divide-gray-100
                            bg-white dark:divide-gray-800
                            dark:bg-gray-900
                        "
                    >
                        @foreach ($children as $person)
                            <tr
                                class="
                                    transition
                                    hover:bg-gray-50
                                    dark:hover:bg-gray-800/50
                                "
                            >
                                <td class="px-6 py-4">
                                    <a
                                        href="{{
                                            \App\Filament\Resources\People\PersonResource::getUrl(
                                                'view',
                                                ['record' => $person]
                                            )
                                        }}"
                                        class="
                                            font-semibold
                                            text-primary-600
                                            hover:underline
                                            dark:text-primary-400
                                        "
                                    >
                                        {{ $person->display_name }}
                                    </a>

                                    @if (filled($person->nickname))
                                        <div
                                            class="
                                                mt-0.5 text-xs
                                                text-gray-500
                                                dark:text-gray-400
                                            "
                                        >
                                            "{{ $person->nickname }}"
                                        </div>
                                    @endif
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    @if ($person->birthdate)
                                        {{ $person->birthdate->age }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{ $person->sex ?: '—' }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $person->birthdate
                                            ? $person->birthdate
                                                ->format('M j, Y')
                                            : '—'
                                    }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $person->localityRecord?->name
                                            ?? $person->locality
                                            ?? '—'
                                    }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $person
                                            ->childrenWorkProfile
                                            ?->locality
                                            ?->name
                                            ?? '—'
                                    }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $person
                                            ->childrenWorkProfile
                                            ?->group_name
                                            ?? '—'
                                    }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $person
                                            ->childrenWorkProfile
                                            ?->servingOne
                                            ?->display_name
                                            ?? '—'
                                    }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                    "
                                >
                                    @if (
                                        $person->childrenWorkProfile
                                    )
                                        @if (
                                            $person
                                                ->childrenWorkProfile
                                                ->is_active
                                        )
                                            <span
                                                class="
                                                    inline-flex
                                                    rounded-full
                                                    bg-emerald-50
                                                    px-2.5 py-1
                                                    text-xs font-semibold
                                                    text-emerald-700
                                                    ring-1
                                                    ring-emerald-200
                                                    dark:bg-emerald-950
                                                    dark:text-emerald-300
                                                    dark:ring-emerald-900
                                                "
                                            >
                                                Active
                                            </span>
                                        @else
                                            <span
                                                class="
                                                    inline-flex
                                                    rounded-full
                                                    bg-gray-100
                                                    px-2.5 py-1
                                                    text-xs font-semibold
                                                    text-gray-600
                                                    ring-1
                                                    ring-gray-200
                                                    dark:bg-gray-800
                                                    dark:text-gray-300
                                                    dark:ring-gray-700
                                                "
                                            >
                                                Inactive
                                            </span>
                                        @endif
                                    @else
                                        <span
                                            class="
                                                text-sm text-gray-400
                                            "
                                        >
                                            Not set
                                        </span>
                                    @endif
                                </td>

                                <td
                                    class="
                                        px-4 py-4 text-sm
                                        text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $person->household?->household_name
                                            ?? '—'
                                    }}
                                </td>

                                <td
                                    class="
                                        px-4 py-4 text-sm
                                        text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $person
                                            ->educationProfile
                                            ?->school
                                            ?->name
                                            ?? '—'
                                    }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $person
                                            ->educationProfile
                                            ?->grade_level
                                            ?? '—'
                                    }}
                                </td>

                                <td
                                    class="
                                        px-4 py-4 text-sm
                                        text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $parentLabel(
                                            $person,
                                            'Father'
                                        )
                                    }}
                                </td>

                                <td
                                    class="
                                        px-4 py-4 text-sm
                                        text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $parentLabel(
                                            $person,
                                            'Mother'
                                        )
                                    }}
                                </td>

                                <td
                                    class="
                                        px-4 py-4 text-sm
                                        text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{
                                        $parentLabel(
                                            $person,
                                            'Guardian'
                                        )
                                    }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                        text-sm text-gray-700
                                        dark:text-gray-200
                                    "
                                >
                                    {{ $person->contact_number ?: '—' }}
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-4 py-4
                                    "
                                >
                                    <span
                                        class="
                                            inline-flex rounded-full
                                            bg-sky-50 px-2.5 py-1
                                            text-xs font-semibold
                                            text-sky-700
                                            ring-1 ring-sky-200
                                            dark:bg-sky-950
                                            dark:text-sky-300
                                            dark:ring-sky-900
                                        "
                                    >
                                        {{
                                            $person
                                                ->churchProfile
                                                ?->category
                                                ?? 'Children'
                                        }}
                                    </span>
                                </td>

                                <td
                                    class="
                                        whitespace-nowrap px-6 py-4
                                        text-right text-sm
                                    "
                                >
                                    <div
                                        class="
                                            flex justify-end gap-2
                                        "
                                    >
                                        <button
                                            type="button"
                                            wire:click="editChildrenWorkProfile({{ $person->id }})"
                                            class="
                                                font-semibold
                                                text-pink-600
                                                hover:underline
                                                dark:text-pink-400
                                            "
                                        >
                                            Profile
                                        </button>

                                        <a
                                            href="{{
                                                \App\Filament\Resources\People\PersonResource::getUrl(
                                                    'view',
                                                    [
                                                        'record' =>
                                                            $person
                                                    ]
                                                )
                                            }}"
                                            class="
                                                font-semibold
                                                text-primary-600
                                                hover:underline
                                                dark:text-primary-400
                                            "
                                        >
                                            View
                                        </a>

                                        <a
                                            href="{{
                                                \App\Filament\Resources\People\PersonResource::getUrl(
                                                    'edit',
                                                    [
                                                        'record' =>
                                                            $person
                                                    ]
                                                )
                                            }}"
                                            class="
                                                font-semibold
                                                text-gray-600
                                                hover:underline
                                                dark:text-gray-300
                                            "
                                        >
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($children->hasPages())
                <div
                    class="
                        border-t border-gray-200 px-6 py-4
                        dark:border-gray-700
                    "
                >
                    {{ $children->links() }}
                </div>
            @endif
        @endif
    </div>

    @if ($withoutBirthdateCount > 0)
        <div
            class="
                rounded-xl border border-amber-200
                bg-amber-50 px-4 py-3
                text-sm text-amber-800
                dark:border-amber-900 dark:bg-amber-950
                dark:text-amber-200
            "
        >
            {{ $withoutBirthdateCount }}
            child record(s) currently have no birthdate.
        </div>
    @endif
</div>
</x-filament-panels::page>
