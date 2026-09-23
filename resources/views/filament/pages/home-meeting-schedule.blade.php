<x-filament-panels::page>
    @php
        $locality = $this->locality();
        $scheduleGroups =
            $this->scheduleGroups();
    @endphp

    <div class="space-y-6">

        <div
            class="rounded-2xl border
                   border-emerald-200
                   bg-emerald-50 p-6 shadow-sm
                   dark:border-emerald-900
                   dark:bg-emerald-950"
        >
            <p
                class="text-sm font-semibold uppercase
                       tracking-wide text-emerald-600
                       dark:text-emerald-300"
            >
                Shepherding Roadmap
            </p>

            <div
                class="mt-2 flex flex-wrap
                       items-end justify-between gap-4"
            >
                <div>
                    <h2
                        class="text-3xl font-bold
                               text-gray-900
                               dark:text-white"
                    >
                        Home Meeting Schedule
                    </h2>

                    <p
                        class="mt-2 max-w-3xl text-sm
                               text-gray-600
                               dark:text-gray-300"
                    >
                        Weekly home meeting schedule for
                        {{ $locality?->name ?? 'Lucban' }}.
                        Household linking and Shepherding
                        Record shortcuts will be added next.
                    </p>
                </div>

                <span
                    class="rounded-full bg-white
                           px-3 py-1.5 text-sm
                           font-bold text-emerald-700
                           ring-1 ring-emerald-200
                           dark:bg-gray-900
                           dark:text-emerald-300
                           dark:ring-emerald-900"
                >
                    Locality:
                    {{ $locality?->name ?? 'Lucban' }}
                </span>
            </div>
        </div>


        @if ($scheduleGroups->isEmpty())

            <div
                class="rounded-2xl border border-dashed
                       border-gray-300 bg-white p-8
                       text-center shadow-sm
                       dark:border-gray-700
                       dark:bg-gray-900"
            >
                <p
                    class="font-semibold text-gray-700
                           dark:text-gray-200"
                >
                    No active home meetings configured.
                </p>
            </div>

        @else

            <div
                class="grid gap-5
                       xl:grid-cols-2"
            >
                @foreach ($scheduleGroups as $group)

                    <section
                        class="overflow-hidden rounded-2xl
                               border border-gray-200
                               bg-white shadow-sm
                               dark:border-gray-700
                               dark:bg-gray-900"
                    >
                        <div
                            class="border-b border-gray-200
                                   bg-gray-50 px-5 py-4
                                   dark:border-gray-700
                                   dark:bg-gray-800/60"
                        >
                            <h3
                                class="text-lg font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                {{ $group['day_label'] }}
                            </h3>

                            @if (
                                filled(
                                    $group['area_name']
                                )
                            )
                                <p
                                    class="mt-1 text-sm
                                           font-semibold
                                           text-emerald-700
                                           dark:text-emerald-300"
                                >
                                    {{ $group['area_name'] }}
                                </p>
                            @endif
                        </div>

                        <div class="divide-y divide-gray-100 dark:divide-gray-800">

                            @foreach (
                                $group['entries']
                                as $entry
                            )
                                <div
                                    class="flex flex-wrap
                                           items-center
                                           justify-between
                                           gap-3 px-5 py-4"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="font-semibold
                                                   text-gray-900
                                                   dark:text-white"
                                        >
                                            {{ $entry->display_name }}
                                        </p>

                                        @if ($entry->household)
                                            <p
                                                class="mt-1 text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Household:
                                                {{
                                                    $entry
                                                        ->household
                                                        ->display_name
                                                }}
                                            </p>
                                        @elseif (
                                            $entry->contactPerson
                                        )
                                            <p
                                                class="mt-1 text-xs
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                Person linked
                                            </p>
                                        @endif
                                    </div>

                                    <div
                                        class="flex shrink-0
                                               flex-wrap items-center
                                               justify-end gap-2"
                                    >
                                        <span
                                            class="rounded-full
                                                   bg-emerald-50
                                                   px-3 py-1.5
                                                   text-sm font-bold
                                                   text-emerald-700
                                                   ring-1
                                                   ring-emerald-200
                                                   dark:bg-emerald-950
                                                   dark:text-emerald-300
                                                   dark:ring-emerald-900"
                                        >
                                            {{
                                                \Carbon\CarbonImmutable::createFromFormat(
                                                    'H:i:s',
                                                    $entry
                                                        ->meeting_time
                                                )->format('g:i A')
                                            }}
                                        </span>

                                        @if ($entry->household_id)
                                            <a
                                                href="{{
                                                    $this
                                                        ->shepherdingRecordUrl(
                                                            $entry
                                                        )
                                                }}"
                                                class="inline-flex
                                                       items-center
                                                       rounded-lg
                                                       bg-primary-600
                                                       px-3 py-1.5
                                                       text-xs font-bold
                                                       text-white
                                                       transition
                                                       hover:bg-primary-500"
                                            >
                                                Add Shepherding Record
                                            </a>
                                        @else
                                            <span
                                                class="rounded-lg
                                                       bg-gray-100
                                                       px-3 py-1.5
                                                       text-xs font-semibold
                                                       text-gray-500
                                                       dark:bg-gray-800
                                                       dark:text-gray-400"
                                            >
                                                Household not linked
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </section>

                @endforeach
            </div>

        @endif

    </div>
</x-filament-panels::page>
