<x-filament-panels::page>
    @php
        $summary =
            $this->summary();

        $linkedExternalSources =
            $this->linkedExternalSources();
    @endphp

    <div class="space-y-6">
        <div
            class="rounded-2xl border
                   border-primary-200
                   bg-primary-50 p-6
                   shadow-sm
                   dark:border-primary-900
                   dark:bg-primary-950"
        >
            <div
                class="flex flex-col gap-4
                       lg:flex-row
                       lg:items-start
                       lg:justify-between"
            >
                <div>
                    <p
                        class="text-sm font-semibold
                               uppercase tracking-wide
                               text-primary-600
                               dark:text-primary-300"
                    >
                        External Hymn Sources
                    </p>

                    <h2
                        class="mt-2 text-3xl
                               font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        External Hymns Setup
                    </h2>

                    <p
                        class="mt-2 max-w-3xl
                               text-sm
                               text-gray-600
                               dark:text-gray-300"
                    >
                        Inspect YouTube, SoundCloud,
                        and other external sources
                        linked to canonical Hymns.
                    </p>

                    <p
                        class="mt-2 max-w-3xl
                               text-xs
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Review decisions remain in
                        Hymns Setup. External submissions
                        do not become canonical Hymns
                        until they are approved there.
                    </p>
                </div>

                <a
                    href="{{
                        \App\Filament\Pages\HymnsSetup
                            ::getUrl()
                    }}"
                    class="inline-flex items-center
                           justify-center rounded-lg
                           bg-primary-600 px-4 py-2.5
                           text-sm font-bold text-white
                           shadow-sm
                           hover:bg-primary-500"
                >
                    Open Hymns Setup
                </a>
            </div>
        </div>

        <div
            class="grid gap-4
                   sm:grid-cols-2
                   xl:grid-cols-4"
        >
            @foreach ([
                'Pending External Requests' =>
                    $summary['pending_external'],

                'YouTube Sources' =>
                    $summary['youtube'],

                'SoundCloud Sources' =>
                    $summary['soundcloud'],

                'Other External Sources' =>
                    $summary['other'],
            ] as $label => $value)
                <div
                    class="rounded-2xl border
                           border-gray-200
                           bg-white p-5
                           shadow-sm
                           dark:border-gray-700
                           dark:bg-gray-900"
                >
                    <div
                        class="text-xs font-bold
                               uppercase tracking-wide
                               text-gray-500
                               dark:text-gray-400"
                    >
                        {{ $label }}
                    </div>

                    <div
                        class="mt-2 text-2xl
                               font-bold
                               text-gray-950
                               dark:text-white"
                    >
                        {{ number_format($value) }}
                    </div>
                </div>
            @endforeach
        </div>

        <div
            class="rounded-2xl border
                   border-gray-200
                   bg-white p-6
                   shadow-sm
                   dark:border-gray-700
                   dark:bg-gray-900"
        >
            <div>
                <h3
                    class="text-lg font-bold
                           text-gray-950
                           dark:text-white"
                >
                    Linked External Sources
                </h3>

                <p
                    class="mt-1 text-sm
                           text-gray-500
                           dark:text-gray-400"
                >
                    YouTube, SoundCloud, and other
                    external sources already attached
                    to canonical Hymns.
                </p>
            </div>

            @if ($linkedExternalSources->isEmpty())
                <div
                    class="mt-5 rounded-xl
                           border border-dashed
                           border-gray-300
                           px-5 py-8
                           text-center text-sm
                           text-gray-500
                           dark:border-gray-700
                           dark:text-gray-400"
                >
                    No linked external hymn sources yet.
                </div>
            @else
                <div class="mt-5 space-y-3">
                    @foreach (
                        $linkedExternalSources
                        as $source
                    )
                        <div
                            wire:key="external-source-{{ $source->id }}"
                            class="rounded-xl border
                                   border-gray-200
                                   p-4
                                   dark:border-gray-700
                                   dark:bg-gray-950/40"
                        >
                            <div
                                class="flex flex-col
                                       gap-2"
                            >
                                <div
                                    class="flex flex-wrap
                                           items-center
                                           gap-2"
                                >
                                    <div
                                        class="font-bold
                                               text-gray-950
                                               dark:text-white"
                                    >
                                        {{
                                            $source->hymn?->title
                                            ?? 'Missing canonical Hymn'
                                        }}
                                    </div>

                                    <span
                                        class="rounded-full
                                               bg-sky-100
                                               px-2 py-0.5
                                               text-[10px]
                                               font-bold
                                               text-sky-950
                                               ring-1
                                               ring-sky-200"
                                    >
                                        {{
                                            $this
                                                ->sourceLabel(
                                                    $source
                                                )
                                        }}
                                    </span>
                                </div>

                                <a
                                    href="{{ $source->source_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="break-all
                                           text-sm
                                           font-semibold
                                           text-sky-700
                                           hover:underline
                                           dark:text-sky-400"
                                >
                                    {{ $source->source_url }}
                                </a>

                                <div
                                    class="text-xs
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Source #{{ $source->id }}
                                    ·
                                    {{ $source->source_type }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
