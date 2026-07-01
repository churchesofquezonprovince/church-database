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
            ],
            'selectedId' => $selectedId,
            'relationshipLabel' => $node['relationship_to_root'] ?? 'relative',
        ])

        @if (! empty($node['spouse']))
            <div class="h-px w-8 bg-gray-300 dark:bg-gray-700"></div>

            @include('filament.pages.partials.family-person-card', [
                'person' => $node['spouse'],
                'selectedId' => $selectedId,
                'relationshipLabel' => 'spouse',
            ])
        @endif
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
