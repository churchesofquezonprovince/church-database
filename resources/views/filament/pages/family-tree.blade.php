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
                    <option value="{{ $id }}">
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
                    Selected Person
                </p>

                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $root['name'] }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Sex: {{ $root['sex'] ?? 'Not specified' }}
                </p>
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
