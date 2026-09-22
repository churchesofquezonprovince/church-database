<x-filament-panels::page>
@php
    $today = today();

    $lessons = \App\Models\ChildrenWorkLesson::query()
        ->whereNotNull('scheduled_on')
        ->orderBy('scheduled_on')
        ->orderBy('id')
        ->get();

    $editingLesson = null;

    $statusOptions = [
        'scheduled' => 'Scheduled',
        'draft' => 'Draft',
        'special_activity' => 'Special Activity',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    if (request()->integer('edit_lesson')) {
        $editingLesson = \App\Models\ChildrenWorkLesson::query()
            ->find(request()->integer('edit_lesson'));
    }

$nextLesson = $lessons
    ->filter(fn ($lesson) =>
        ! in_array($lesson->status, ['cancelled'], true)
        && $lesson->scheduled_on
        && $lesson->scheduled_on->gte($today)
    )
    ->sortBy([
        ['scheduled_on', 'asc'],
        ['id', 'asc'],
    ])
    ->first();

$currentMonthLessons = $lessons
    ->filter(fn ($lesson) =>
        $lesson->scheduled_on
        && $lesson->scheduled_on->year === $today->year
        && $lesson->scheduled_on->month === $today->month
        && (! $nextLesson || $lesson->id !== $nextLesson->id)
    )
    ->sortBy([
        ['scheduled_on', 'asc'],
        ['id', 'asc'],
    ]);

$futureLessons = $lessons
    ->filter(fn ($lesson) =>
        $lesson->scheduled_on
        && $lesson->scheduled_on->gt($today->copy()->endOfMonth())
        && (! $nextLesson || $lesson->id !== $nextLesson->id)
    )
    ->sortBy([
        ['scheduled_on', 'asc'],
        ['id', 'asc'],
    ]);
    $pastLessons = $lessons
    ->filter(fn ($lesson) =>
        $lesson->scheduled_on
        && $lesson->scheduled_on->lt($today->copy()->startOfMonth())
    )
    ->sortByDesc(fn ($lesson) => [
        $lesson->scheduled_on->timestamp,
        $lesson->id,
    ]);
@endphp

    <div class="space-y-6">
        @if (session('children_work_saved'))
            <div data-coqp-flash="success" data-coqp-flash-id="cf10342d4cc36641" data-coqp-keep="false" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                {{ session('children_work_saved') }}
            </div>
        @endif

        @if (session('children_work_error'))
            <div data-coqp-flash="danger" data-coqp-flash-id="70d445fd819e17f6" data-coqp-keep="false" class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                {{ session('children_work_error') }}
            </div>
        @endif

        @if ($editingLesson)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900 dark:bg-amber-950">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                            Edit Lesson
                        </p>

                        <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editingLesson->displayTitle() }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $editingLesson->displayDate() }}
                        </p>
                    </div>

                    <a
                        href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() }}"
                        class="inline-flex rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                    >
                        Cancel Edit
                    </a>
                </div>

                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.children-work.lessons.update', $editingLesson) }}"
                    class="mt-5 grid gap-4 lg:grid-cols-2"
                >
                    @csrf
                    @method('PATCH')

@include('filament.pages.partials.children-work-lesson-form-fields', [
    'lesson' => null,
    'statusOptions' => $statusOptions,
])

                    <div class="flex flex-wrap gap-2 lg:col-span-2">
                        <button type="submit" class="rounded-xl bg-pink-600 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500">
                            Save Changes
                        </button>

                        <a
                            href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() }}"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        @endif

<details
    class="group rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
    @if (
        ! $editingLesson
        && (
            $lessons->isEmpty()
            || $errors->any()
            || request()->boolean('add_lesson')
        )
    )
        open
    @endif
>
    <summary
        class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5
               [&::-webkit-details-marker]:hidden"
    >
        <div class="flex min-w-0 items-start gap-3">
            <x-heroicon-o-chevron-right
                class="mt-0.5 h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200 group-open:rotate-90"
            />

            <div class="min-w-0">
                <p class="font-bold text-gray-900 dark:text-white">
                    Add Lesson
                </p>

                <p class="mt-1 text-sm font-normal text-gray-500 dark:text-gray-400">
                    Add a scheduled meeting or special activity.
                </p>
            </div>
        </div>

        <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:ring-emerald-900">
            Google Sheet Auto-Sync
        </span>
    </summary>

    <form
        method="POST"
        action="{{ route('quezonprovinceactivities.children-work.lessons.store') }}"
        class="border-t border-gray-200 p-6 dark:border-gray-700"
    >
        @csrf

        @if ($errors->any() && ! $editingLesson)
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                <p class="font-bold">
                    Please correct the following:
                </p>

                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            @include('filament.pages.partials.children-work-lesson-form-fields', [
                'lesson' => null,
                'statusOptions' => $statusOptions,
            ])
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
            <button
                type="submit"
                class="rounded-xl bg-pink-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-pink-500"
            >
                Add Lesson
            </button>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Scheduled lessons use the two-row Google Sheet layout.
                Special Activities use the yellow merged one-row layout.
            </p>
        </div>
    </form>
</details>



{{-- =========================================================
     NEXT MEETING
========================================================== --}}

<div class="mt-8">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
        Next Meeting
    </h3>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        The next upcoming Children's Work meeting.
    </p>
</div>


@if ($nextLesson)

    <div class="mt-5 overflow-hidden rounded-xl border border-pink-300 bg-pink-50 dark:border-pink-900 dark:bg-pink-950">

        <table
            class="w-full divide-y divide-pink-200 text-sm dark:divide-pink-900"
            style="table-layout: fixed;"
        >

            <colgroup>
                <col style="width: 9%;">   {{-- Date --}}
                <col style="width: 13%;">  {{-- Lesson --}}
                <col style="width: 12%;">  {{-- Suggested Hymn --}}
                <col style="width: 18%;">  {{-- Memory Verse --}}
                <col style="width: 13%;">  {{-- Story --}}
                <col style="width: 16%;">  {{-- Presentation Slides --}}
                <col style="width: 19%;">  {{-- Activity --}}
            </colgroup>


            <thead class="bg-pink-100 dark:bg-pink-950">
                <tr>

                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Date
                    </th>

                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Lesson
                    </th>

                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Suggested Hymn
                    </th>

                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Memory Verse
                    </th>

                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Story
                    </th>

                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Presentation Slides
                    </th>

                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Activity
                    </th>

                </tr>
            </thead>


            <tbody class="bg-pink-50 dark:bg-pink-950">

                <tr>

                    {{-- DATE + STATUS + ACTIONS --}}
                    <td class="px-4 py-3 align-top font-semibold text-gray-900 dark:text-white">

                        {{ $nextLesson->displayDate() }}

                        <span class="block text-xs font-normal text-gray-600 dark:text-gray-300">
                            {{
                                $statusOptions[$nextLesson->status]
                                ?? ucfirst(str_replace('_', ' ', $nextLesson->status))
                            }}
                        </span>

                        <div class="mt-2 flex flex-wrap gap-2">

                            <a
                                href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() . '?edit_lesson=' . $nextLesson->id }}"
                                class="inline-flex rounded-lg bg-gray-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-600"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.children-work.lessons.destroy', $nextLesson) }}"
                                onsubmit="return confirm('Delete this lesson from BOTH the website and Google Sheet? This will physically remove its Google Sheet row(s).');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="inline-flex rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-900 dark:bg-gray-950 dark:text-red-400 dark:hover:bg-red-950/40"
                                >
                                    Delete
                                </button>
                            </form>

                        </div>

                    </td>


                    {{-- LESSON --}}
                    <td class="px-4 py-3 align-top">

                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $nextLesson->displayTitle() }}
                        </p>

                        @if ($nextLesson->lesson_url)
                            <a
                                href="{{ $nextLesson->lesson_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Lesson Link
                            </a>
                        @endif

                        @if ($nextLesson->sync_status)
                            <span class="mt-2 block w-fit rounded-full bg-white/70 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-900/70 dark:text-gray-300">
                                {{ $nextLesson->sync_status }}
                            </span>
                        @endif

                    </td>


                    {{-- SUGGESTED HYMN --}}
                    <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $nextLesson->suggested_hymn ?: '—',
                                    80
                                )
                            }}
                        </div>

                        @if ($nextLesson->suggested_hymn_url)
                            <a
                                href="{{ $nextLesson->suggested_hymn_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Hymn Link
                            </a>
                        @endif

                    </td>


                    {{-- MEMORY VERSE --}}
                    <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">

                        <div class="whitespace-normal break-words">
                            {{ $nextLesson->memory_verse ?: '—' }}
                        </div>

                    </td>


                    {{-- STORY --}}
                    <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $nextLesson->story ?: '—',
                                    100
                                )
                            }}
                        </div>

                        @if ($nextLesson->story_url)
                            <a
                                href="{{ $nextLesson->story_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Story Link
                            </a>
                        @endif

                    </td>


                    {{-- PRESENTATION SLIDES --}}
                    <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $nextLesson->presentation_slides ?: '—',
                                    120
                                )
                            }}
                        </div>

                        @if ($nextLesson->presentation_slides_url)
                            <a
                                href="{{ $nextLesson->presentation_slides_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Presentation Slides
                            </a>
                        @endif

                    </td>


                    {{-- ACTIVITY --}}
                    <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">

                        <div class="whitespace-normal break-words">
                            {{ $nextLesson->activity ?: '—' }}
                        </div>

                        @if ($nextLesson->activity_url)
                            <a
                                href="{{ $nextLesson->activity_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Activity
                            </a>
                        @endif

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

@else

    <div class="mt-5 rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
        No upcoming lesson scheduled.
    </div>

@endif


{{-- =========================================================
     CURRENT MONTH / PRESENT SCHEDULES
========================================================== --}}

<div class="mt-8">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
        {{ $today->format('F Y') }} Schedule
    </h3>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Remaining Children's Work meetings for the current month.
    </p>
</div>


<div class="mt-5 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">

    <table
        class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700"
        style="table-layout: fixed;"
    >

        <colgroup>
            <col style="width: 9%;">   {{-- Date --}}
            <col style="width: 13%;">  {{-- Lesson --}}
            <col style="width: 12%;">  {{-- Suggested Hymn --}}
            <col style="width: 18%;">  {{-- Memory Verse --}}
            <col style="width: 13%;">  {{-- Story --}}
            <col style="width: 16%;">  {{-- Presentation Slides --}}
            <col style="width: 19%;">  {{-- Activity --}}
        </colgroup>

        <thead class="bg-gray-50 dark:bg-gray-950">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                    Date
                </th>

                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                    Lesson
                </th>

                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                    Suggested Hymn
                </th>

                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                    Memory Verse
                </th>

                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                    Story
                </th>

                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                    Presentation Slides
                </th>

                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                    Activity
                </th>
            </tr>
        </thead>


        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">

            @forelse ($currentMonthLessons as $lesson)

                <tr @class([
                    'bg-amber-50 dark:bg-amber-950/40'
                        => $editingLesson?->id === $lesson->id,
                ])>

                    {{-- Date --}}
                    <td class="px-4 py-3 align-top font-semibold text-gray-900 dark:text-white">

                        {{ $lesson->displayDate() }}

                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                            {{
                                $statusOptions[$lesson->status]
                                ?? ucfirst(str_replace('_', ' ', $lesson->status))
                            }}
                        </span>

                        <div class="mt-2 flex flex-wrap gap-2">

                            <a
                                href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() . '?edit_lesson=' . $lesson->id }}"
                                class="inline-flex rounded-lg bg-gray-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-600"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.children-work.lessons.destroy', $lesson) }}"
                                onsubmit="return confirm('Delete this lesson from BOTH the website and Google Sheet? This will physically remove its Google Sheet row(s).');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="inline-flex rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-900 dark:bg-gray-950 dark:text-red-400 dark:hover:bg-red-950/40"
                                >
                                    Delete
                                </button>
                            </form>

                        </div>

                    </td>


                    {{-- Lesson --}}
                    <td class="px-4 py-3 align-top">

                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $lesson->displayTitle() }}
                        </p>

                        @if ($lesson->lesson_url)
                            <a
                                href="{{ $lesson->lesson_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Lesson Link
                            </a>
                        @endif

                        @if ($lesson->sync_status)
                            <span class="mt-2 block w-fit rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ $lesson->sync_status }}
                            </span>
                        @endif

                    </td>


                    {{-- Suggested Hymn --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->suggested_hymn ?: '—',
                                    80
                                )
                            }}
                        </div>

                        @if ($lesson->suggested_hymn_url)
                            <a
                                href="{{ $lesson->suggested_hymn_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Hymn Link
                            </a>
                        @endif

                    </td>


                    {{-- Memory Verse --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{ $lesson->memory_verse ?: '—' }}
                        </div>

                    </td>


                    {{-- Story --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->story ?: '—',
                                    100
                                )
                            }}
                        </div>

                        @if ($lesson->story_url)
                            <a
                                href="{{ $lesson->story_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Story Link
                            </a>
                        @endif

                    </td>


                    {{-- Presentation Slides --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->presentation_slides ?: '—',
                                    120
                                )
                            }}
                        </div>

                        @if ($lesson->presentation_slides_url)
                            <a
                                href="{{ $lesson->presentation_slides_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Presentation Slides
                            </a>
                        @endif

                    </td>


                    {{-- Activity --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{ $lesson->activity ?: '—' }}
                        </div>

                        @if ($lesson->activity_url)
                            <a
                                href="{{ $lesson->activity_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Activity
                            </a>
                        @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="7"
                        class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                    >
                        No additional lessons scheduled for {{ $today->format('F Y') }}.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>


{{-- =========================================================
     FUTURE SCHEDULES
========================================================== --}}
<hr class="my-8 border-gray-300 dark:border-gray-700">

<div class="mt-8">

    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
        Future Schedules
    </h3>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Meetings scheduled after {{ $today->format('F Y') }}.
    </p>

</div>



<div class="mt-5 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
    <table
        class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700"
        style="table-layout: fixed;"
    >

<colgroup>
    <col style="width: 9%;">   {{-- Date --}}
    <col style="width: 13%;">  {{-- Lesson --}}
    <col style="width: 12%;">  {{-- Suggested Hymn --}}
    <col style="width: 18%;">  {{-- Memory Verse --}}
    <col style="width: 13%;">  {{-- Story --}}
    <col style="width: 16%;">  {{-- Presentation Slides --}}
    <col style="width: 19%;">  {{-- Activity --}}
</colgroup>

        <thead class="bg-gray-50 dark:bg-gray-950">
            <tr>

                <th class="px-4 py-3 text-left">
                    Date
                </th>

                <th class="px-4 py-3 text-left">
                    Lesson
                </th>

                <th class="px-4 py-3 text-left">
                    Suggested Hymn
                </th>

                <th class="px-4 py-3 text-left">
                    Memory Verse
                </th>

                <th class="px-4 py-3 text-left">
                    Story
                </th>

                <th class="px-4 py-3 text-left">
                    Presentation Slides
                </th>

                <th class="px-4 py-3 text-left">
                    Activity
                </th>

            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">

            @forelse ($futureLessons as $lesson)

                <tr @class([
                    'bg-amber-50 dark:bg-amber-950/40'
                        => $editingLesson?->id === $lesson->id,
                ])>

                    {{-- Date --}}
                    <td class="px-4 py-3 align-top font-semibold text-gray-900 dark:text-white">

                        {{ $lesson->displayDate() }}

                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                            {{
                                $statusOptions[$lesson->status]
                                ?? ucfirst(str_replace('_', ' ', $lesson->status))
                            }}
                        </span>

                        <div class="mt-2 flex flex-wrap gap-2">

                            <a
                                href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() . '?edit_lesson=' . $lesson->id }}"
                                class="inline-flex rounded-lg bg-gray-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-600"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.children-work.lessons.destroy', $lesson) }}"
                                onsubmit="return confirm('Delete this lesson from BOTH the website and Google Sheet? This will physically remove its Google Sheet row(s).');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="inline-flex rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-900 dark:bg-gray-950 dark:text-red-400 dark:hover:bg-red-950/40"
                                >
                                    Delete
                                </button>
                            </form>

                        </div>

                    </td>


                    {{-- Lesson --}}
                    <td class="px-4 py-3 align-top">

                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $lesson->displayTitle() }}
                        </p>

                        @if ($lesson->lesson_url)
                            <a
                                href="{{ $lesson->lesson_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Lesson Link
                            </a>
                        @endif

                        @if ($lesson->sync_status)
                            <span class="mt-2 block w-fit rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ $lesson->sync_status }}
                            </span>
                        @endif

                    </td>


                    {{-- Suggested Hymn --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->suggested_hymn ?: '—',
                                    80
                                )
                            }}
                        </div>

                        @if ($lesson->suggested_hymn_url)
                            <a
                                href="{{ $lesson->suggested_hymn_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Hymn Link
                            </a>
                        @endif

                    </td>


                    {{-- Memory Verse - FULL --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{ $lesson->memory_verse ?: '—' }}
                        </div>

                    </td>


                    {{-- Story - COMPRESSED --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->story ?: '—',
                                    100
                                )
                            }}
                        </div>

                        @if ($lesson->story_url)
                            <a
                                href="{{ $lesson->story_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Story Link
                            </a>
                        @endif

                    </td>


                    {{-- Presentation --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->presentation_slides ?: '—',
                                    120
                                )
                            }}
                        </div>

                        @if ($lesson->presentation_slides_url)
                            <a
                                href="{{ $lesson->presentation_slides_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Presentation Slides
                            </a>
                        @endif

                    </td>


                    {{-- Activity - FULL --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{ $lesson->activity ?: '—' }}
                        </div>

                        @if ($lesson->activity_url)
                            <a
                                href="{{ $lesson->activity_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Activity
                            </a>
                        @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="7"
                        class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                    >
                        No future schedules.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>


{{-- =========================================================
     PAST SCHEDULES
========================================================== --}}
<hr class="my-8 border-gray-300 dark:border-gray-700">

<div class="mt-8">

    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
        Past Schedules
    </h3>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Children's Work meetings before {{ $today->format('F Y') }}.
        Most recent schedules are shown first.
    </p>

</div>


<div class="mt-5 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
    <table
        class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700"
        style="table-layout: fixed;"
    >

<colgroup>
    <col style="width: 9%;">   {{-- Date --}}
    <col style="width: 13%;">  {{-- Lesson --}}
    <col style="width: 12%;">  {{-- Suggested Hymn --}}
    <col style="width: 18%;">  {{-- Memory Verse --}}
    <col style="width: 13%;">  {{-- Story --}}
    <col style="width: 16%;">  {{-- Presentation Slides --}}
    <col style="width: 19%;">  {{-- Activity --}}
</colgroup>


        <thead class="bg-gray-50 dark:bg-gray-950">
            <tr>

                <th class="px-4 py-3 text-left">
                    Date
                </th>

                <th class="px-4 py-3 text-left">
                    Lesson
                </th>

                <th class="px-4 py-3 text-left">
                    Suggested Hymn
                </th>

                <th class="px-4 py-3 text-left">
                    Memory Verse
                </th>

                <th class="px-4 py-3 text-left">
                    Story
                </th>

                <th class="px-4 py-3 text-left">
                    Presentation Slides
                </th>

                <th class="px-4 py-3 text-left">
                    Activity
                </th>

            </tr>
        </thead>


        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">

            @forelse ($pastLessons as $lesson)

                <tr @class([
                    'bg-amber-50 dark:bg-amber-950/40'
                        => $editingLesson?->id === $lesson->id,
                ])>


                    {{-- Date --}}
                    <td class="px-4 py-3 align-top font-semibold text-gray-900 dark:text-white">

                        {{ $lesson->displayDate() }}

                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                            {{
                                $statusOptions[$lesson->status]
                                ?? ucfirst(str_replace('_', ' ', $lesson->status))
                            }}
                        </span>

                        <div class="mt-2 flex flex-wrap gap-2">

                            <a
                                href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() . '?edit_lesson=' . $lesson->id }}"
                                class="inline-flex rounded-lg bg-gray-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-600"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="{{ route('quezonprovinceactivities.children-work.lessons.destroy', $lesson) }}"
                                onsubmit="return confirm('Delete this lesson from BOTH the website and Google Sheet? This will physically remove its Google Sheet row(s).');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="inline-flex rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-900 dark:bg-gray-950 dark:text-red-400 dark:hover:bg-red-950/40"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    </td>


                    {{-- Lesson --}}
                    <td class="px-4 py-3 align-top">

                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $lesson->displayTitle() }}
                        </p>

                        @if ($lesson->lesson_url)
                            <a
                                href="{{ $lesson->lesson_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Lesson Link
                            </a>
                        @endif

                        @if ($lesson->sync_status)
                            <span class="mt-2 block w-fit rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ $lesson->sync_status }}
                            </span>
                        @endif

                    </td>


                    {{-- Suggested Hymn --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->suggested_hymn ?: '—',
                                    80
                                )
                            }}
                        </div>

                        @if ($lesson->suggested_hymn_url)
                            <a
                                href="{{ $lesson->suggested_hymn_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Hymn Link
                            </a>
                        @endif

                    </td>


                    {{-- Memory Verse - FULL --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{ $lesson->memory_verse ?: '—' }}
                        </div>

                    </td>


                    {{-- Story - COMPRESSED --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->story ?: '—',
                                    100
                                )
                            }}
                        </div>

                        @if ($lesson->story_url)
                            <a
                                href="{{ $lesson->story_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Story Link
                            </a>
                        @endif

                    </td>


                    {{-- Presentation --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{
                                \Illuminate\Support\Str::limit(
                                    $lesson->presentation_slides ?: '—',
                                    120
                                )
                            }}
                        </div>

                        @if ($lesson->presentation_slides_url)
                            <a
                                href="{{ $lesson->presentation_slides_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Presentation Slides
                            </a>
                        @endif

                    </td>


                    {{-- Activity - FULL --}}
                    <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">

                        <div class="whitespace-normal break-words">
                            {{ $lesson->activity ?: '—' }}
                        </div>

                        @if ($lesson->activity_url)
                            <a
                                href="{{ $lesson->activity_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300"
                            >
                                Open Activity
                            </a>
                        @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="7"
                        class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                    >
                        No past schedules.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>

    </div>
</x-filament-panels::page>
