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

    $totalChildren = (clone $childrenBaseQuery)->count();

    $boysCount = (clone $childrenBaseQuery)
        ->where('sex', 'Male')
        ->count();

    $girlsCount = (clone $childrenBaseQuery)
        ->where('sex', 'Female')
        ->count();

    $withoutBirthdateCount = (clone $childrenBaseQuery)
        ->whereNull('birthdate')
        ->count();

    $search = trim((string) request('search', ''));
    $sex = trim((string) request('sex', ''));
    $localityId = request()->integer('locality_id');

    $childrenQuery = \App\Models\Person::query()
        ->with([
            'churchProfile',
            'localityRecord',
            'household',
        ])
        ->whereHas(
            'churchProfile',
            fn ($query) => $query->where('category', 'Children')
        );

    if ($search !== '') {
        $childrenQuery->where(function ($query) use ($search): void {
            $like = '%' . $search . '%';

            $query
                ->where('firstname', 'like', $like)
                ->orWhere('middlename', 'like', $like)
                ->orWhere('lastname', 'like', $like)
                ->orWhere('suffix', 'like', $like)
                ->orWhere('nickname', 'like', $like)
                ->orWhere('contact_number', 'like', $like)
                ->orWhere('locality', 'like', $like)
                ->orWhereHas(
                    'localityRecord',
                    fn ($localityQuery) =>
                        $localityQuery->where('name', 'like', $like)
                );
        });
    }

    if (in_array($sex, ['Male', 'Female'], true)) {
        $childrenQuery->where('sex', $sex);
    }

    if ($localityId > 0) {
        $childrenQuery->where('locality_id', $localityId);
    }

    $children = $childrenQuery
        ->orderBy('lastname')
        ->orderBy('firstname')
        ->paginate(30)
        ->withQueryString();

    $localities = \App\Models\Locality::query()
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

    {{-- Summary cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
            class="
                rounded-2xl border border-gray-200 bg-white p-5 shadow-sm
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
                rounded-2xl border border-gray-200 bg-white p-5 shadow-sm
                dark:border-gray-700 dark:bg-gray-900
            "
        >
            <p
                class="
                    text-sm font-medium
                    text-gray-500 dark:text-gray-400
                "
            >
                Boys
            </p>

            <p
                class="
                    mt-2 text-3xl font-bold
                    text-gray-950 dark:text-white
                "
            >
                {{ number_format($boysCount) }}
            </p>
        </div>

        <div
            class="
                rounded-2xl border border-gray-200 bg-white p-5 shadow-sm
                dark:border-gray-700 dark:bg-gray-900
            "
        >
            <p
                class="
                    text-sm font-medium
                    text-gray-500 dark:text-gray-400
                "
            >
                Girls
            </p>

            <p
                class="
                    mt-2 text-3xl font-bold
                    text-gray-950 dark:text-white
                "
            >
                {{ number_format($girlsCount) }}
            </p>
        </div>

        <div
            class="
                rounded-2xl border border-gray-200 bg-white p-5 shadow-sm
                dark:border-gray-700 dark:bg-gray-900
            "
        >
            <p
                class="
                    text-sm font-medium
                    text-gray-500 dark:text-gray-400
                "
            >
                Category
            </p>

            <div class="mt-3">
                <span
                    class="
                        inline-flex rounded-full
                        bg-sky-50 px-3 py-1
                        text-sm font-semibold text-sky-700
                        ring-1 ring-sky-200
                        dark:bg-sky-950 dark:text-sky-300
                        dark:ring-sky-900
                    "
                >
                    Children
                </span>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form
        method="GET"
        class="
            rounded-2xl border border-gray-200 bg-white p-5 shadow-sm
            dark:border-gray-700 dark:bg-gray-900
        "
    >
        <div
            class="
                grid gap-4
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
                    placeholder="Name, nickname, contact, locality..."
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500 focus:ring-primary-500
                        dark:border-gray-700 dark:bg-gray-950
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
                        focus:border-primary-500 focus:ring-primary-500
                        dark:border-gray-700 dark:bg-gray-950
                        dark:text-white
                    "
                >
                    <option value="">All</option>

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
                    Locality
                </label>

                <select
                    id="children-locality"
                    name="locality_id"
                    class="
                        block w-full rounded-xl border-gray-300
                        bg-white text-sm text-gray-950 shadow-sm
                        focus:border-primary-500 focus:ring-primary-500
                        dark:border-gray-700 dark:bg-gray-950
                        dark:text-white
                    "
                >
                    <option value="">All Localities</option>

                    @foreach ($localities as $locality)
                        <option
                            value="{{ $locality->id }}"
                            @selected(
                                $localityId === (int) $locality->id
                            )
                        >
                            {{ $locality->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button
                type="submit"
                class="
                    inline-flex items-center justify-center rounded-xl
                    bg-primary-600 px-4 py-2
                    text-sm font-semibold text-white shadow-sm
                    transition hover:bg-primary-500
                "
            >
                Apply Filters
            </button>

            @if (
                $search !== ''
                || $sex !== ''
                || $localityId > 0
            )
                <a
                    href="{{ \App\Filament\Pages\ChildrenWorkDatabase::getUrl() }}"
                    class="
                        inline-flex items-center justify-center rounded-xl
                        border border-gray-300 bg-white px-4 py-2
                        text-sm font-semibold text-gray-700 shadow-sm
                        transition hover:bg-gray-50
                        dark:border-gray-700 dark:bg-gray-950
                        dark:text-gray-200 dark:hover:bg-gray-800
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
                                Locality
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
