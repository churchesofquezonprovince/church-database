<x-filament-panels::page>
    @php
$dashboardQuery = \App\Models\ChildrenWorkLesson::query()
    ->whereNotIn('status', ['draft', 'cancelled']);

/*
 * Phase 26F
 *
 * A lesson clicked from Upcoming / Recent / Future
 * becomes the lesson flashed at the top of the dashboard.
 *
 * If no valid lesson was requested, retain the normal
 * automatic current/next lesson behavior.
 */
$selectedLessonId = request()->integer('lesson');

$nextLesson = null;

if ($selectedLessonId > 0) {
    $nextLesson = (clone $dashboardQuery)
        ->whereKey($selectedLessonId)
        ->whereNotNull('scheduled_on')
        ->first();
}

if (! $nextLesson) {
    $nextLesson = (clone $dashboardQuery)
        ->whereNotNull('scheduled_on')
        ->whereDate('scheduled_on', '>=', today())
        ->orderBy('scheduled_on')
        ->orderBy('id')
        ->first();
}

if (! $nextLesson) {
            $nextLesson = (clone $dashboardQuery)
                ->whereNotNull('scheduled_on')
                ->whereDate('scheduled_on', '<', today())
                ->orderByDesc('scheduled_on')
                ->orderByDesc('id')
                ->first();
        }

        $upcomingLessons = (clone $dashboardQuery)
            ->whereNotNull('scheduled_on')
            ->whereDate('scheduled_on', '>=', today())
            ->whereDate(
                'scheduled_on',
                '<=',
                now()->endOfMonth()->toDateString()
            )
->when(
    $nextLesson,
    fn ($query) => $query->where(
        'id',
        '!=',
        $nextLesson->id
    )
)
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->limit(8)
            ->get();

        $showingNextMonthLessons = false;

        if ($upcomingLessons->isEmpty()) {
            $nextMonthStart =
                now()
                    ->addMonthNoOverflow()
                    ->startOfMonth()
                    ->toDateString();

            $nextMonthEnd =
                now()
                    ->addMonthNoOverflow()
                    ->endOfMonth()
                    ->toDateString();

            /*
             * When the current month's remaining list is empty,
             * preview the full next calendar month.
             *
             * Do not exclude $nextLesson here. If the next
             * scheduled lesson is already next month, it should
             * appear as the first item in "Next Month Lessons".
             */
            $upcomingLessons =
                (clone $dashboardQuery)
                    ->whereNotNull('scheduled_on')
                    ->whereDate(
                        'scheduled_on',
                        '>=',
                        $nextMonthStart
                    )
                    ->whereDate(
                        'scheduled_on',
                        '<=',
                        $nextMonthEnd
                    )
                    ->when(
                        $nextLesson,
                        fn ($query) => $query->where(
                            'id',
                            '!=',
                            $nextLesson->id
                        )
                    )
                    ->orderBy('scheduled_on')
                    ->orderBy('id')
                    ->limit(8)
                    ->get();

            $showingNextMonthLessons =
                $upcomingLessons->isNotEmpty();
        }

        $futureLessonsAfter =
            $showingNextMonthLessons
                ? $nextMonthEnd
                : now()
                    ->endOfMonth()
                    ->toDateString();

        $recentLessons = (clone $dashboardQuery)
            ->whereNotNull('scheduled_on')
            ->whereDate('scheduled_on', '<', today())
            ->when(
    $nextLesson,
    fn ($query) => $query->where(
        'id',
        '!=',
        $nextLesson->id
    )
)
            ->orderByDesc('scheduled_on')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
$pastLessons = (clone $dashboardQuery)
    ->whereNotNull('scheduled_on')
    ->whereDate('scheduled_on', '<', today())
    ->when(
        $nextLesson,
        fn ($query) => $query->where(
            'id',
            '!=',
            $nextLesson->id
        )
    )
    ->whereNotIn(
        'id',
        $recentLessons->pluck('id')
    )
    ->orderByDesc('scheduled_on')
    ->orderByDesc('id')
    ->get();

        $futureLessons = (clone $dashboardQuery)
            ->whereNotNull('scheduled_on')
            ->whereDate(
                'scheduled_on',
                '>',
                $futureLessonsAfter
            )
            ->when(
    $nextLesson,
    fn ($query) => $query->where(
        'id',
        '!=',
        $nextLesson->id
    )
)
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->limit(12)
            ->get();

        $storyEmbed = $nextLesson
            ? \App\Models\ChildrenWorkLesson::youtubeEmbedUrl(
                $nextLesson->story_url
            )
            : null;

        $hymnEmbed = $nextLesson
            ? \App\Models\ChildrenWorkLesson::youtubeEmbedUrl(
                $nextLesson->suggested_hymn_url
            )
            : null;
            $resourceLabel = function (?string $value, string $fallback): string {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $fallback;
        }

        return $value;
    };

    $storyTitle = $nextLesson
        ? \App\Models\ChildrenWorkLesson::resourceDisplayTitle(
            $nextLesson->story,
            $nextLesson->story_url,
            'Story Video'
        )
        : 'Story Video';

    $hymnTitle = $nextLesson
        ? \App\Models\ChildrenWorkLesson::resourceDisplayTitle(
            $nextLesson->suggested_hymn,
            $nextLesson->suggested_hymn_url,
            'Suggested Hymn'
        )
        : 'Suggested Hymn';

    $slidesTitle = $nextLesson
        ? \App\Models\ChildrenWorkLesson::resourceDisplayTitle(
            $nextLesson->presentation_slides,
            $nextLesson->presentation_slides_url,
            'Presentation Slides'
        )
        : 'Presentation Slides';

    $activityTitle = $nextLesson
        ? \App\Models\ChildrenWorkLesson::resourceDisplayTitle(
            $nextLesson->activity,
            $nextLesson->activity_url,
            'Activity Material'
        )
        : 'Activity Material';
    @endphp

    <div class="space-y-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Children's meeting preparation and lesson materials
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if (auth()->user()?->canManageRecords())
                    <a
                        href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() }}"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                    >
                        Manage Lessons
                    </a>
                @endif

                <a
                    href="{{ url('/children-work') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center rounded-xl bg-pink-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
                >
                    Open Public Dashboard
                </a>
            </div>
        </div>

        @if ($nextLesson)
            <section class="grid gap-6 lg:grid-cols-12">

                {{-- Lesson information --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:col-span-6">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($nextLesson->lesson_code)
                            <span class="rounded-full bg-pink-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-pink-700 dark:bg-pink-950 dark:text-pink-300">
                                {{ $nextLesson->lesson_code }}
                            </span>
                        @endif

                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ ucfirst(str_replace('_', ' ', $nextLesson->status)) }}
                        </span>
                    </div>

                    <h2 class="mt-4 text-2xl font-bold text-gray-950 dark:text-white">
                        {{ $nextLesson->displayTitle() }}
                    </h2>

                    <p class="mt-3 text-base font-semibold text-pink-600 dark:text-pink-300">
                        {{ $nextLesson->displayDate() }}
                    </p>

                    @if ($nextLesson->assigned_to)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            c/o {{ $nextLesson->assigned_to }}
                        </p>
                    @endif

                    @if ($nextLesson->lesson_url)
                        <a
                            href="{{ $nextLesson->lesson_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-5 inline-flex rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900"
                        >
                            Open Lesson
                        </a>
                    @endif
                </div>

                {{-- Memory Verse --}}
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900 dark:bg-amber-950/40 lg:col-span-6">
                    <p class="text-sm font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                        Memory Verse
                    </p>

                    @if ($nextLesson->memory_verse)
                        <blockquote class="mt-5 whitespace-pre-line text-lg font-medium leading-8 text-gray-900 dark:text-gray-100">
                            {{ $nextLesson->memory_verse }}
                        </blockquote>
                    @else
                        <p class="mt-5 text-sm text-gray-500 dark:text-gray-400">
                            No memory verse has been added.
                        </p>
                    @endif
                </div>

                {{-- Story --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:col-span-12">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                        <p class="text-sm font-bold uppercase tracking-wide text-pink-600 dark:text-pink-300">
                            Story
                        </p>

                        @if ($nextLesson->story)
                            <h3 class="mt-2 text-xl font-bold text-gray-950 dark:text-white">
                                {{ $storyTitle }}
                            </h3>
                        @endif
                    </div>

                    @if ($storyEmbed)
                        <div class="aspect-video w-full bg-black">
                            <iframe
                                src="{{ $storyEmbed }}"
                                title="{{ $storyTitle }}"
                                class="h-full w-full"
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                            ></iframe>
                        </div>
                    @elseif ($nextLesson->story_url)
                        <div class="flex min-h-64 items-center justify-center p-8">
                            <a
                                href="{{ $nextLesson->story_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex rounded-xl bg-pink-600 px-5 py-3 text-sm font-bold text-white hover:bg-pink-500"
                            >
                                Open Story
                            </a>
                        </div>
                    @else
                        <div class="flex min-h-48 items-center justify-center p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No story video or link has been added.
                        </div>
                    @endif
                </div>

                {{-- Hymn --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:col-span-6">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                        <p class="text-sm font-bold uppercase tracking-wide text-pink-600 dark:text-pink-300">
                            Suggested Hymn
                        </p>

                        <h3 class="mt-2 text-lg font-bold text-gray-950 dark:text-white">
                            {{ $hymnTitle }}
                        </h3>
                    </div>

                    @if ($hymnEmbed)
                        <div class="aspect-video bg-black">
                            <iframe
                                src="{{ $hymnEmbed }}"
                                title="{{ $hymnTitle }}"
                                class="h-full w-full"
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                            ></iframe>
                        </div>
                    @elseif ($nextLesson->suggested_hymn_url)
                        <div class="p-6">
                            <a
                                href="{{ $nextLesson->suggested_hymn_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex rounded-xl bg-pink-600 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500"
                            >
                                Open Hymn
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Presentation --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:col-span-3">
                    <p class="text-sm font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                        Presentation Slides
                    </p>

                    <p class="mt-4 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        {{ $slidesTitle }}
                    </p>

                    @if ($nextLesson->presentation_slides_url)
                        <a
                            href="{{ $nextLesson->presentation_slides_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-6 inline-flex rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"
                        >
                            Open Slides
                        </a>
                    @endif
                </div>

                {{-- Activity --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:col-span-3">
                    <p class="text-sm font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">
                        Activity
                    </p>

                    <p class="mt-4 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        {{ $activityTitle }}
                    </p>

                    @if ($nextLesson->activity_url)
                        <a
                            href="{{ $nextLesson->activity_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-6 inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                        >
                            Open Activity
                        </a>
                    @endif
                </div>
            </section>
        @else
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-gray-500 dark:text-gray-400">
                    No Children's Work schedule has been added yet.
                </p>
            </div>
        @endif

        {{-- Schedule lists --}}
        <section class="grid gap-6 xl:grid-cols-3">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                    {{
                        $showingNextMonthLessons
                            ? 'Next Month Lessons'
                            : 'Upcoming Lessons'
                    }}
                </h3>

                <div class="mt-5 space-y-3">
                    @forelse ($upcomingLessons as $lesson)
                        <a
    href="{{ \App\Filament\Pages\ChildrenWorkDashboard::getUrl() }}?lesson={{ $lesson->id }}"
    class="block rounded-xl border border-gray-200 p-4 transition hover:border-pink-300 hover:bg-pink-50 dark:border-gray-700 dark:hover:border-pink-800 dark:hover:bg-pink-950"
>
                            <p class="text-xs font-bold uppercase tracking-wide text-pink-600 dark:text-pink-300">
                                {{ $lesson->displayDate() }}
                            </p>

                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                                {{ $lesson->displayTitle() }}
                            </p>
</a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            No more lessons scheduled this month.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                    Recent Lessons
                </h3>

                <div class="mt-5 space-y-3">
                    @forelse ($recentLessons as $lesson)
                        <a
    href="{{ \App\Filament\Pages\ChildrenWorkDashboard::getUrl() }}?lesson={{ $lesson->id }}"
    class="block rounded-xl border border-gray-200 p-4 transition hover:border-gray-400 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-800"
>
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $lesson->displayDate() }}
                            </p>

                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                                {{ $lesson->displayTitle() }}
                            </p>
</a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            No recent lessons.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                    Future Lessons
                </h3>

                <div class="mt-5 space-y-3">
                    @forelse ($futureLessons as $lesson)
                        <a
    href="{{ \App\Filament\Pages\ChildrenWorkDashboard::getUrl() }}?lesson={{ $lesson->id }}"
    class="block rounded-xl border border-gray-200 p-4 transition hover:border-violet-300 hover:bg-violet-50 dark:border-gray-700 dark:hover:border-violet-800 dark:hover:bg-violet-950"
>
                            <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-300">
                                {{ $lesson->displayDate() }}
                            </p>

                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                                {{ $lesson->displayTitle() }}
                            </p>
</a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            No future lessons currently scheduled.
                        </p>
                    @endforelse
                </div>
            </div>
        </section>
{{-- All Past Lessons --}}
<section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div>
        <h3 class="text-lg font-bold text-gray-950 dark:text-white">
            All Past Lessons
        </h3>

        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Complete lesson history, newest first.
        </p>
    </div>

    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($pastLessons as $lesson)
            <a
                href="{{ \App\Filament\Pages\ChildrenWorkDashboard::getUrl() }}?lesson={{ $lesson->id }}"
                class="block rounded-xl border border-gray-200 p-4 transition hover:border-pink-300 hover:bg-pink-50 dark:border-gray-700 dark:hover:border-pink-800 dark:hover:bg-pink-950"
            >
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $lesson->displayDate() }}
                </p>

                <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                    {{ $lesson->displayTitle() }}
                </p>
            </a>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No past lessons.
            </p>
        @endforelse
    </div>
</section>

    </div>
</x-filament-panels::page>