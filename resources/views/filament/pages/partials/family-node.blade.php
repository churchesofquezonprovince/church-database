@php
    $hasChildren = ! empty($node['children']);
@endphp

<div class="flex flex-col items-center">
    <div class="flex items-center gap-3">
        @include('filament.pages.partials.family-person-card', [
            'person' => [
                'id' => $node['id'],
                'name' => $node['name'],
                'sex' => $node['sex'] ?? null,
                'household_id' => $node['household_id'] ?? null,
                'household' => $node['household'] ?? null,
                'locality' => $node['locality'] ?? null,
            ],
            'selectedId' => $selectedId,
            'relationshipLabel' => $node['relationship_to_root'] ?? 'relative',
        ])

        @if (! empty($node['spouse']))
            <div class="h-px w-10 bg-gray-300 dark:bg-gray-700"></div>

            @include('filament.pages.partials.family-person-card', [
                'person' => $node['spouse'],
                'selectedId' => $selectedId,
                'relationshipLabel' => 'spouse',
            ])
        @endif
    </div>

    @if ($hasChildren)
        <div class="h-7 w-px bg-gray-300 dark:bg-gray-700"></div>

        <div class="relative flex max-w-full gap-8 overflow-x-auto rounded-2xl px-4 pb-3">
            @foreach ($node['children'] as $child)
                <div class="flex flex-col items-center">
                    <div class="h-7 w-px bg-gray-300 dark:bg-gray-700"></div>

                    @include('filament.pages.partials.family-node', [
                        'node' => $child,
                        'selectedId' => $selectedId,
                    ])
                </div>
            @endforeach
        </div>
    @endif
</div>
