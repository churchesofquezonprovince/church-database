<x-filament-panels::page>

@php
        $locality = $this->locality();
        $scheduleGroups =
            $this->scheduleGroups();

        $householdOptions =
            $this->householdOptions();

        $localityOptions =
            $this->localityOptions();
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
                        {{ $locality?->name ?? 'the selected locality' }}.
                        Link Households and open Shepherding
                        Records directly from each schedule.
                    </p>
                </div>

                <label
                    class="flex items-center gap-2 rounded-xl
                           bg-white px-3 py-2 text-sm font-bold
                           text-emerald-700 ring-1
                           ring-emerald-200
                           dark:bg-gray-900
                           dark:text-emerald-300
                           dark:ring-emerald-900"
                >
                    <span>Locality:</span>

                    <select
                        wire:model.live="selectedLocality"
                        class="rounded-lg border-gray-300
                               bg-white py-1 pl-2 pr-8
                               text-sm font-semibold
                               text-gray-900 shadow-sm
                               focus:border-emerald-500
                               focus:ring-emerald-500
                               dark:border-gray-700
                               dark:bg-gray-950
                               dark:text-white"
                    >
                        @forelse (
                            $localityOptions
                            as $localityName => $label
                        )
                            <option
                                value="{{ $localityName }}"
                            >
                                {{ $label }}
                            </option>
                        @empty
                            <option value="">
                                No localities with People
                            </option>
                        @endforelse
                    </select>
                </label>
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
                    class="dark:text-emerald-300 text-emerald-600 font-semibold text-gray-700"
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
                            class="bg-white dark:bg-gray-800 border-b border-gray-200 px-5 py-4
                                   dark:border-gray-700"
                        >
                            <h3
                                class="text-lg font-bold
                                       text-gray-900 dark:text-white
                                      "
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
                                           text-emerald-700 dark:text-emerald-300
"
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

                                            <button
                                                type="button"
                                                wire:click="unlinkHousehold({{ $entry->id }})"
                                                wire:confirm="Unlink this Household from {{ $entry->display_name }}?"
                                                class="rounded-lg
                                                       bg-gray-100
                                                       px-3 py-1.5
                                                       text-xs font-semibold
                                                       text-gray-600
                                                       transition
                                                       hover:bg-gray-200
                                                       dark:bg-gray-800
                                                       dark:text-gray-300
                                                       dark:hover:bg-gray-700"
                                            >
                                                Unlink
                                            </button>

                                        @else

                                            <select
                                                wire:model="householdSelections.{{ $entry->id }}"
                                                class="min-w-44 rounded-lg
                                                       border-gray-300
                                                       bg-white
                                                       py-1.5 text-xs
                                                       text-gray-700
                                                       shadow-sm
                                                       focus:border-primary-500
                                                       focus:ring-primary-500
                                                       dark:border-gray-700
                                                       dark:bg-gray-900
                                                       dark:text-gray-200"
                                            >
                                                <option value="">
                                                    Select Household
                                                </option>

                                                @foreach (
                                                    $householdOptions
                                                    as $householdId
                                                    => $householdName
                                                )
                                                    <option
                                                        value="{{ $householdId }}"
                                                    >
                                                        {{ $householdName }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button
                                                type="button"
                                                wire:click="linkHousehold({{ $entry->id }})"
                                                class="rounded-lg
                                                       bg-emerald-600
                                                       px-3 py-1.5
                                                       text-xs font-bold
                                                       text-white
                                                       transition
                                                       hover:bg-emerald-500"
                                            >
                                                Link
                                            </button>

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
