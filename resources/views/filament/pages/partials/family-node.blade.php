@php
    $hasChildren = ! empty($node['children']);
    $isSelected = isset($selectedId) && (int) $selectedId === (int) $node['id'];
@endphp

<div class="flex flex-col items-center">
    <div
        @class([
            'min-w-52 rounded-xl border bg-white p-4 text-center shadow-sm transition dark:bg-gray-900',
            'border-primary-500 ring-2 ring-primary-500' => $isSelected,
            'border-gray-200 hover:border-primary-400 dark:border-gray-700' => ! $isSelected,
        ])
    >
        <button
            type="button"
            wire:click="selectPerson({{ $node['id'] }})"
            class="block w-full"
        >
            <p class="font-semibold text-gray-900 dark:text-white">
                {{ $node['name'] }}
            </p>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $node['relationship_to_root'] ?? 'relative' }}
                @if (! empty($node['sex']))
                    · {{ $node['sex'] }}
                @endif
            </p>
        </button>

        @if (! empty($node['spouse']))
            <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

            <button
                type="button"
                wire:click="selectPerson({{ $node['spouse']['id'] }})"
                class="text-xs text-primary-600 hover:underline dark:text-primary-400"
            >
                Spouse: {{ $node['spouse']['name'] }}
            </button>
        @endif

        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
            @if (! empty($node['father']))
                <button
                    type="button"
                    wire:click="selectPerson({{ $node['father']['id'] }})"
                    class="rounded-lg bg-gray-50 px-2 py-1 text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Father:<br>
                    {{ $node['father']['name'] }}
                </button>
            @endif

            @if (! empty($node['mother']))
                <button
                    type="button"
                    wire:click="selectPerson({{ $node['mother']['id'] }})"
                    class="rounded-lg bg-gray-50 px-2 py-1 text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Mother:<br>
                    {{ $node['mother']['name'] }}
                </button>
            @endif
        </div>
    </div>

    @if ($hasChildren)
        <div class="h-6 w-px bg-gray-300 dark:bg-gray-700"></div>

        <div class="flex max-w-full gap-6 overflow-x-auto rounded-xl px-4 pb-2">
            @foreach ($node['children'] as $child)
                <div class="flex flex-col items-center">
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-700"></div>

                    @include('filament.pages.partials.family-node', [
                        'node' => $child,
                        'selectedId' => $selectedId,
                    ])
                </div>
            @endforeach
        </div>
    @endif
</div>
