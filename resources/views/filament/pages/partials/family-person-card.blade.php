@php
    $isSelected = isset($selectedId, $person['id']) && (int) $selectedId === (int) $person['id'];
    $relationshipLabel = $relationshipLabel ?? null;

    $profileUrl = \App\Filament\Resources\People\PersonResource::getUrl('view', [
        'record' => $person['id'],
    ]);
@endphp

<div
    @class([
        'min-w-52 rounded-xl border bg-white p-4 text-center shadow-sm transition dark:bg-gray-900',
        'border-primary-500 ring-2 ring-primary-500' => $isSelected,
        'border-gray-200 hover:border-primary-400 dark:border-gray-700' => ! $isSelected,
    ])
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


@if (! empty($person['household']))
    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        Household: {{ $person['household'] }}
    </p>
@endif

@if (! empty($person['locality']))
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        Locality: {{ $person['locality'] }}
    </p>
@endif


    <div class="mt-4 flex items-center justify-center gap-2">
        <button
            type="button"
            wire:click="selectPerson({{ $person['id'] }})"
            class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-500"
        >
            Focus Tree
        </button>

        <a
            href="{{ $profileUrl }}"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
        >
            View Profile
        </a>
    </div>
</div>
