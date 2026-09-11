@php
    $immichDetectionHistory =
        $this->immichDetectionHistory();

    $immichDetectionEvents =
        $immichDetectionHistory->sum('detection_events');

    $immichUniqueDetectedPeople =
        $immichDetectionHistory
            ->flatMap(fn ($row) => $row['people'])
            ->pluck('identity_key')
            ->unique()
            ->count();

    $immichRemovedTotal =
        $immichDetectionHistory->sum('removed');
@endphp

@if ($immichDetectionHistory->isNotEmpty())
    <details
        class="min-w-0 overflow-hidden rounded-2xl border border-violet-200 bg-violet-50 shadow-sm dark:border-violet-900 dark:bg-violet-950"
    >
        <summary
            class="cursor-pointer px-4 py-4 hover:bg-violet-100 dark:hover:bg-violet-900 sm:px-6"
        >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-lg font-bold text-violet-950 dark:text-violet-100">
                        Immich Detection History
                    </p>

                    <p class="mt-1 text-xs text-violet-700 dark:text-violet-300">
                        Whole Attendance Sheet · grouped by Session
                    </p>
                </div>

                <span class="text-xs font-semibold text-violet-700 dark:text-violet-300">
                    {{ $immichDetectionHistory->count() }} Session(s)
                </span>
            </div>
        </summary>

        <div class="space-y-4 border-t border-violet-200 p-4 dark:border-violet-900 sm:p-6">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-violet-200 bg-white p-3 dark:border-violet-800 dark:bg-gray-950">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Unique Detected People
                    </p>

                    <p class="mt-1 text-xl font-bold text-violet-700 dark:text-violet-300">
                        {{ $immichUniqueDetectedPeople }}
                    </p>
                </div>

                <div class="rounded-xl border border-violet-200 bg-white p-3 dark:border-violet-800 dark:bg-gray-950">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Detection Events
                    </p>

                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                        {{ $immichDetectionEvents }}
                    </p>
                </div>

                <div class="rounded-xl border border-violet-200 bg-white p-3 dark:border-violet-800 dark:bg-gray-950">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Sessions Detected
                    </p>

                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                        {{ $immichDetectionHistory->count() }}
                    </p>
                </div>

                <div class="rounded-xl border border-red-200 bg-white p-3 dark:border-red-900 dark:bg-gray-950">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Removed Detections
                    </p>

                    <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-300">
                        {{ $immichRemovedTotal }}
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                @foreach ($immichDetectionHistory as $history)
                    <details
                        class="overflow-hidden rounded-xl border border-violet-200 bg-white dark:border-violet-800 dark:bg-gray-950"
                    >
                        <summary
                            class="cursor-pointer px-4 py-3 hover:bg-violet-50 dark:hover:bg-violet-950"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">
                                        {{
                                            $history['session']
                                                ->dateTimeLabel('M d, Y')
                                        }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Detected:
                                        {{ $history['detected_people'] }}

                                        · Present:
                                        {{ $history['present'] }}

                                        · Removed:
                                        {{ $history['removed'] }}

                                        @if ($history['absent'] > 0)
                                            · Absent:
                                            {{ $history['absent'] }}
                                        @endif

                                        @if ($history['unmatched'] > 0)
                                            · Unmatched:
                                            {{ $history['unmatched'] }}
                                        @endif
                                    </p>
                                </div>

                                <a
                                    href="{{ $this->sessionUrl($history['session']) }}"
                                    onclick="event.stopPropagation()"
                                    class="text-xs font-bold text-violet-700 hover:underline dark:text-violet-300"
                                >
                                    Open Session
                                </a>
                            </div>
                        </summary>

                        <div class="border-t border-violet-100 dark:border-violet-900">
                            @foreach ($history['people'] as $detectedPerson)
                                @php
                                    $statusLabel =
                                        match ($detectedPerson['status']) {
                                            'present' => 'Present',
                                            'absent' => 'Absent',
                                            'removed' => 'Removed',
                                            'unmatched' => 'Unmatched',
                                            default => 'Detected',
                                        };

                                    $statusClasses =
                                        match ($detectedPerson['status']) {
                                            'present' =>
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100',

                                            'absent' =>
                                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',

                                            'removed' =>
                                                'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200',

                                            'unmatched' =>
                                                'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100',

                                            default =>
                                                'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-100',
                                        };
                                @endphp

                                <div
                                    class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 last:border-b-0 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $detectedPerson['name'] }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $detectedPerson['asset_count'] }}
                                            photo(s)

                                            ·
                                            {{ $detectedPerson['detection_events'] }}
                                            detection event(s)

                                            @if (! $detectedPerson['person_id'])
                                                · Immich ID
                                                {{
                                                    \Illuminate\Support\Str::limit(
                                                        $detectedPerson['immich_person_id'],
                                                        12
                                                    )
                                                }}
                                            @endif
                                        </p>
                                    </div>

                                    <span
                                        class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses }}"
                                    >
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </details>
@endif
