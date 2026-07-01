@php
    $isSelected = isset($selectedId, $person['id']) && (int) $selectedId === (int) $person['id'];
    $relationshipLabel = $relationshipLabel ?? null;
@endphp

<div
    @class([
        'min-w-52 rounded-xl border bg-white p-4 text-center shadow-sm transition dark:bg-gray-900',
        'border-primary-500 ring-2 ring-primary-500' => $isSelected,
        'border-gray-200 hover:border-primary-400 dark:border-gray-700' => ! $isSelected,
    ])
>
    <button
        type="button"
        wire:click="selectPerson({{ $person['id'] }})"
        class="block w-full"
    >
        @if ($relationshipLabel)
            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-primary-600 dark:text-primary-400">
                {{ $relationshipLabel }}
            </p>
        @endif

        <p class="font-semibold text-gray-900 dark:text-white">
            {{ $person['name'] }}
        </p>

        @if (! empty($person['sex']))
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $person['sex'] }}
            </p>
        @endif
    </button>
</div>
