@php
    $workflowKey =
        data_get($workflow, 'key');

    $workflowLabel =
        data_get($workflow, 'label');

    $workflowDetail =
        data_get($workflow, 'detail');
@endphp

@if ($workflowLabel)
    <span
        @class([
            'rounded-full px-2 py-1 text-xs font-bold',

            'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100'
                => $workflowKey === 'needs_identity_review',

            'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-100'
                => $workflowKey === 'ready_for_participant',

            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100'
                => $workflowKey === 'participant_covered',

            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100'
                => $workflowKey === 'participant_review',

            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
                => $workflowKey === 'no_participant_needed',
        ])
        @if ($workflowDetail)
            title="{{ $workflowDetail }}"
        @endif
    >
        {{ $workflowLabel }}
    </span>
@endif
