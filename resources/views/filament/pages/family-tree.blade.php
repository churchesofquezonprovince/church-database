<x-filament-panels::page>
    <div class="space-y-6">

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <label for="personId" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">
                Select Person
            </label>

            <select
                id="personId"
                wire:model.live="personId"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
@foreach ($people as $id => $name)
    <option
        value="{{ $id }}"
        style="color: #111827; background-color: #ffffff;"
    >
        {{ $name }}
    </option>
@endforeach
            </select>
        </div>

        @if (empty($tree))
            <div class="rounded-xl border border-gray-200 bg-white p-6 text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                No family tree data available yet.
            </div>
        @else
            @php
                $root = $tree['tree'];
            @endphp

            <div class="rounded-xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">
                    Visual Family Tree
                </p>

                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $root['name'] }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Click any person in the tree to focus on that person.
                </p>
            </div>

@php
    $generations = $tree['generations'] ?? [];
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Generation Levels
    </h3>

    <div class="mt-6 space-y-6">

        <div>
            <p class="mb-3 text-sm font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Grandparents
            </p>

            <div class="flex flex-wrap gap-3">
                @forelse ($generations['grandparents'] ?? [] as $person)
                    @include('filament.pages.partials.family-person-card', [
                        'person' => $person,
                        'selectedId' => $personId,
                        'relationshipLabel' => 'grandparent',
                    ])
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-300">None recorded</p>
                @endforelse
            </div>
        </div>

        <div>
            <p class="mb-3 text-sm font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Parents
            </p>

            <div class="flex flex-wrap gap-3">
                @forelse ($generations['parents'] ?? [] as $person)
                    @include('filament.pages.partials.family-person-card', [
                        'person' => $person,
                        'selectedId' => $personId,
                        'relationshipLabel' => 'parent',
                    ])
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-300">None recorded</p>
                @endforelse
            </div>
        </div>

        <div>
            <p class="mb-3 text-sm font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Selected Person
            </p>

            <div class="flex flex-wrap gap-3">
                @foreach ($generations['self'] ?? [] as $person)
                    @include('filament.pages.partials.family-person-card', [
                        'person' => $person,
                        'selectedId' => $personId,
                        'relationshipLabel' => 'selected',
                    ])
                @endforeach
            </div>
        </div>

        <div>
            <p class="mb-3 text-sm font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Children
            </p>

            <div class="flex flex-wrap gap-3">
                @forelse ($generations['children'] ?? [] as $person)
                    @include('filament.pages.partials.family-person-card', [
                        'person' => $person,
                        'selectedId' => $personId,
                        'relationshipLabel' => 'child',
                    ])
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-300">None recorded</p>
                @endforelse
            </div>
        </div>

        <div>
            <p class="mb-3 text-sm font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Grandchildren
            </p>

            <div class="flex flex-wrap gap-3">
                @forelse ($generations['grandchildren'] ?? [] as $person)
                    @include('filament.pages.partials.family-person-card', [
                        'person' => $person,
                        'selectedId' => $personId,
                        'relationshipLabel' => 'grandchild',
                    ])
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-300">None recorded</p>
                @endforelse
            </div>
        </div>

    </div>
</div>

<div class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-50 p-8 shadow-sm dark:border-gray-700 dark:bg-gray-950">
    <div class="flex min-w-max flex-col items-center">

        @if (! empty($root['father']) || ! empty($root['mother']))
            <div class="flex items-center gap-3">
                @if (! empty($root['father']))
                    @include('filament.pages.partials.family-person-card', [
'person' => [
    'id' => $root['father']['id'],
    'name' => $root['father']['name'],
    'sex' => $root['father']['sex'] ?? 'Male',
    'household_id' => $root['father']['household_id'] ?? null,
    'household' => $root['father']['household'] ?? null,
    'locality' => $root['father']['locality'] ?? null,
],
                        'selectedId' => $personId,
                        'relationshipLabel' => 'father',
                    ])
                @endif

                @if (! empty($root['father']) && ! empty($root['mother']))
                    <div class="h-px w-8 bg-gray-300 dark:bg-gray-700"></div>
                @endif

                @if (! empty($root['mother']))
                    @include('filament.pages.partials.family-person-card', [
'person' => [
    'id' => $root['mother']['id'],
    'name' => $root['mother']['name'],
    'sex' => $root['mother']['sex'] ?? 'Female',
    'household_id' => $root['mother']['household_id'] ?? null,
    'household' => $root['mother']['household'] ?? null,
    'locality' => $root['mother']['locality'] ?? null,
],
                        'selectedId' => $personId,
                        'relationshipLabel' => 'mother',
                    ])
                @endif
            </div>

            <div class="h-8 w-px bg-gray-300 dark:bg-gray-700"></div>
        @endif

        @include('filament.pages.partials.family-node', [
            'node' => $root,
            'selectedId' => $personId,
        ])
    </div>
</div>

            <div class="grid gap-6 md:grid-cols-2">

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Immediate Family
                    </h3>

                    <div class="mt-4 space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Spouse</p>

                            @if ($root['spouse'])
                                <button
                                    type="button"
                                    wire:click="selectPerson({{ $root['spouse']['id'] }})"
                                    class="mt-1 text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    {{ $root['spouse']['name'] }}
                                </button>
                            @else
                                <p class="mt-1 text-gray-700 dark:text-gray-300">None recorded</p>
                            @endif
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Father</p>

                            @if ($root['father'])
                                <button
                                    type="button"
                                    wire:click="selectPerson({{ $root['father']['id'] }})"
                                    class="mt-1 text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    {{ $root['father']['name'] }}
                                </button>
                            @else
                                <p class="mt-1 text-gray-700 dark:text-gray-300">None recorded</p>
                            @endif
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mother</p>

                            @if ($root['mother'])
                                <button
                                    type="button"
                                    wire:click="selectPerson({{ $root['mother']['id'] }})"
                                    class="mt-1 text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    {{ $root['mother']['name'] }}
                                </button>
                            @else
                                <p class="mt-1 text-gray-700 dark:text-gray-300">None recorded</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Siblings
                    </h3>

                    <div class="mt-4 space-y-2">
                        @forelse ($root['siblings'] as $sibling)
                            <button
                                type="button"
                                wire:click="selectPerson({{ $sibling['id'] }})"
                                class="block text-primary-600 hover:underline dark:text-primary-400"
                            >
                                {{ $sibling['name'] }}
                            </button>
                        @empty
                            <p class="text-gray-700 dark:text-gray-300">No siblings recorded</p>
                        @endforelse
                    </div>
                </div>

            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Children
                </h3>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @forelse ($root['children'] as $child)
                        <button
                            type="button"
                            wire:click="selectPerson({{ $child['id'] }})"
                            class="rounded-lg border border-gray-200 p-4 text-left hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $child['name'] }}
                            </p>

                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $child['sex'] ?? 'Not specified' }}
                            </p>
                        </button>
                    @empty
                        <p class="text-gray-700 dark:text-gray-300">No children recorded</p>
                    @endforelse
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Ancestors
                    </h3>

                    <div class="mt-4 space-y-2">
                        @forelse ($tree['ancestors'] as $ancestor)
                            <button
                                type="button"
                                wire:click="selectPerson({{ $ancestor['id'] }})"
                                class="block text-primary-600 hover:underline dark:text-primary-400"
                            >
                                {{ $ancestor['name'] }}
                            </button>
                        @empty
                            <p class="text-gray-700 dark:text-gray-300">No ancestors recorded</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Descendants
                    </h3>

                    <div class="mt-4 space-y-2">
                        @forelse ($tree['descendants'] as $descendant)
                            <button
                                type="button"
                                wire:click="selectPerson({{ $descendant['id'] }})"
                                class="block text-primary-600 hover:underline dark:text-primary-400"
                            >
                                {{ $descendant['name'] }}
                            </button>
                        @empty
                            <p class="text-gray-700 dark:text-gray-300">No descendants recorded</p>
                        @endforelse
                    </div>
                </div>

            </div>
        @endif

    </div>
</x-filament-panels::page>
