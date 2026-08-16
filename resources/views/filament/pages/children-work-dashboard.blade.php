<x-filament-panels::page>
    @php
        $nextLesson = \App\Models\ChildrenWorkLesson::query()->upcoming()->first()
            ?: \App\Models\ChildrenWorkLesson::query()->past()->first();

        $upcomingLessons = \App\Models\ChildrenWorkLesson::query()
            ->upcoming()
            ->limit(8)
            ->get();

        $recentLessons = \App\Models\ChildrenWorkLesson::query()
            ->past()
            ->limit(5)
            ->get();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-pink-200 bg-pink-50 p-6 shadow-sm dark:border-pink-900 dark:bg-pink-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-pink-700 dark:text-pink-300">
                        Children's Work
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        Children's Work Dashboard
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                        Latest children's meeting schedule and lesson preparation materials.
                    </p>
                </div>

                <a
                    href="{{ url('/children-work') }}"
                    target="_blank"
                    class="inline-flex rounded-xl bg-pink-600 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500"
                >
                    Open Public Dashboard
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900 dark:bg-amber-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                Latest Schedule
            </p>

            @if ($nextLesson)
                <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $nextLesson->displayTitle() }}
                </h3>

                <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                    {{ $nextLesson->displayDate() }}
                    @if ($nextLesson->assigned_to)
                        · {{ $nextLesson->assigned_to }}
                    @endif
                </p>

                @if ($nextLesson->memory_verse)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-white p-4 text-sm text-gray-700 dark:border-amber-900 dark:bg-gray-950 dark:text-gray-200">
                        <p class="font-bold">Memory Verse</p>
                        <p class="mt-1 whitespace-pre-line">{{ $nextLesson->memory_verse }}</p>
                    </div>
                @endif
            @else
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                    No Children's Work lesson schedule has been added yet.
                </p>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Upcoming Lessons
                </h3>

                <div class="mt-5 space-y-3">
                    @forelse ($upcomingLessons as $lesson)
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-bold uppercase tracking-wide text-pink-600 dark:text-pink-300">
                                {{ $lesson->displayDate() }}
                            </p>
                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                                {{ $lesson->displayTitle() }}
                            </p>
                            @if ($lesson->activity)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Activity: {{ \Illuminate\Support\Str::limit($lesson->activity, 120) }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming lessons yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Recent Lessons
                </h3>

                <div class="mt-5 space-y-3">
                    @forelse ($recentLessons as $lesson)
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $lesson->displayDate() }}
                            </p>
                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                                {{ $lesson->displayTitle() }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No recent lessons yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
